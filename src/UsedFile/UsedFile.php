<?php

namespace ClearAbandon\UsedFile;

use ClearAbandon\ConfigHelper;
use ClearAbandon\DBHelper;
use ClearAbandon\UsedFile\Type\IdType;
use ClearAbandon\UsedFile\Type\UrlType;
use ClearAbandon\UsedFile\Type\EditorType;
use Illuminate\Support\Facades\DB;

class UsedFile{

    static protected $type_mapping = [
        'id' => IdType::class,
        'url' => UrlType::class,
        'editor' => EditorType::class
    ];

    static public function extractUsedFile(){
        $table_with_column_mapping = ConfigHelper::getConfigWithKey('table_field_mapping');

        collect($table_with_column_mapping)->each(function ($ent){
            $column_name = $ent['column_name'];
            $db = DB::table($ent['table_name']);
            self::parseColumnName($column_name, $db, $field_name);

            $db ->orderBy(DBHelper::getUqKey($ent))
                ->chunk(ConfigHelper::PER_PAGE_NMU, function ($list) use($column_name){
                    $type_data = [];
                    foreach ($list as $col_data) {
                        self::parsePerColData($column_name, $col_data, $type_data);
                    }
                    collect($type_data)->each(function ($value_arr, $type){
                        $value_arr = array_filter($value_arr);
                        $type_cls = new self::$type_mapping[$type]();
                        $type_cls->extractUsedFile($value_arr);
                    });
                });
        });
    }

    static protected function parseColumnName($cus_column_config, &$db, &$field_name){
        $field_name = [];
        $need_all_column = false;

        collect($cus_column_config)->each(function ($ent) use(&$field_name, &$db, &$need_all_column){
            $field_name[] = $ent['name'];
            $need_all_column = isset($ent['type_callback']);
            $db = $db->orWhere($ent['name'], '!=', '');
        });

        if ($need_all_column){
            $db = $db->select();
        }else{
            $db = $db->select($field_name);
        }
    }

    static protected function parsePerColData($cus_column_config, $db_data, &$type_batch_data){
        collect($cus_column_config)->each(function ($ent) use($db_data, &$type_batch_data){
            $name = $ent['name'];
            $value = (string)$db_data->$name;
            if ($ent['type'] === 'callback' && isset($ent['type_callback'])){
                $type = call_user_func($ent['type_callback'], $db_data);
            }else{
                $type = $ent['type'];
            }
            $type && $type_batch_data[$type][] = $value;
        });
    }

}