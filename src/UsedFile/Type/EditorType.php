<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\DBHelper;
use ClearAbandon\FileManager;
use Illuminate\Support\Facades\DB;

class EditorType extends AType
{
    protected $table_with_column_mapping_key = 'table_editor_field_mapping';
    protected $storage_file_dir;
   // protected $editor_data_type = "'varchar','text','longtext'";
    protected $editor_data_type = "'text','longtext'";

    public function __construct()
    {
        $this->storage_file_dir = ConfigHelper::getStorageFileDir();
        parent::__construct();
    }

    protected function getTableWithColumnMapping()
    {
        return parent::getTableWithColumnMapping() ?: DBHelper::defaultEditorField($this->editor_data_type);
    }

    public function extractUsedFile()
    {
        $editor_info = $this->table_with_column_mapping;
        collect($editor_info)->each(function ($ent){
            $table_name = $ent['table_name'];
            $uq_key = $this->getUqKey($ent);
            $column_name = $ent['column_name'];
            $where_arr = $this->combineColumnLikeQuery($column_name);
            DB::table($table_name)
                ->where($where_arr, null, null, 'or')
                ->select($column_name)
                ->orderBy($uq_key)
                ->chunk($this->per_page, function ($list) use($column_name){
                    foreach ($list as $row_data){
                        $this->parseEditorRow($column_name, $row_data);
                    }
                });
        });
    }

    protected function combineColumnLikeQuery($column){
        return collect($column)->map(function ($field){
            return [$field, 'like', "%{$this->storage_file_dir}%"];
        })->all();
    }

    protected function parseEditorRow($fields, $db_data){
        collect($fields)->each(function ($field) use($db_data){
            $value = $db_data->$field;
            $this->extractFileFromEditorStr($value);
        });
    }

    protected function extractFileFromEditorStr($string){
        $decode_string = htmlspecialchars_decode($string);
        $path = trim($this->storage_file_dir, '/');
        $file_rule = '/(?:&quot;|\"|\')((?:[\w|\:|\/|\d|\.|\-]+)?\/'.$path.'\/(.*?))(?:&quot;|\"|\')/i';
        $r = preg_match_all($file_rule, $decode_string, $matches);
        if ($r > 0){
            array_map(function ($used, $key){
                FileManager::isUsed($key, $used);
            }, $matches[1], $matches[2]);
        }
    }

}