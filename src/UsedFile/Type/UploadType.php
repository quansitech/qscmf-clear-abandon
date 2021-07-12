<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\FileManager;
use Illuminate\Support\Facades\DB;

class UploadType extends AType
{
    protected $storage_file_table;
    protected $table_with_column_mapping_key = 'table_field_mapping';

    public function __construct()
    {
        $this->storage_file_table = ConfigHelper::getStorageFileTableWithColumn();
        parent::__construct();
    }

    protected function combineFileIdQuery($name){
        return collect($name)->map(function ($field){
            return [$field, '!=', ''];
        })->all();
    }

    protected function parseRow($fields, $db_data){
        $file_ids = [];
        collect($fields)->each(function ($field) use($db_data, &$file_ids){
            $value = (string)$db_data->$field;
            if ($value){
                strpos($value, ',') !== false && $value = explode(',', trim($value, ','));
                $value = is_array($value) ? $value : (array)$value;
                $file_ids = array_merge($file_ids, $value);
            }

        });

        return $file_ids;
    }

    public function fetchWithTableColumn()
    {
        $file_ids = [];
        collect($this->table_with_column_mapping)->each(function ($ent) use(&$file_ids){
            $field_name = $ent['column_name'];
            $where_arr = $this->combineFileIdQuery($field_name);

            DB::table($ent['table_name'])
                ->where($where_arr, null, null, 'or')
                ->select($ent['column_name'])
                ->orderBy($this->getUqKey($ent))
                ->chunk($this->per_page, function ($list) use(&$file_ids, $field_name){
                    foreach ($list as $row_data) {
                        $row_ids = $this->parseRow($field_name, $row_data);
                        $file_ids = array_merge($file_ids, $row_ids);
                    }
                });

        });

        return array_unique($file_ids);
    }

    public function extractUsedFile()
    {
        $ids = $this->fetchWithTableColumn();
        $files = [];
        $table = $this->storage_file_table['table_name'];
        $field = $this->storage_file_table['column_name'];
        $uq_key = $this->getUqKey($this->storage_file_table);

        DB::table($table)
            ->whereIn($uq_key,$ids)
            ->select([$uq_key,$field])
            ->chunkById($this->per_page, function ($list) use(&$files, $uq_key, $field){
                $row_files = [];
                foreach ($list as $one_data) {
                    $id = $one_data->$uq_key;
                    $value = $one_data->$field;

                    FileManager::isUsed($value, $value);

//                    $value && $row_files[$value] = ['file' => $value, 'id' => $id, 'used' => [$value => $value]];
                }
//                $files = array_merge($files, $row_files);
            }, $uq_key);

//        return $files;
    }
}