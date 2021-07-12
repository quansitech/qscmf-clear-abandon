<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\FileManager;
use Illuminate\Support\Facades\DB;
use function foo\func;

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
        return parent::getTableWithColumnMapping() ?: $this->defaultEditorField();
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
        $file_rule = '/<(?:img|a).*?(?:src|href)=[\"|\']((?:[\w|\:|\/|\d|\.]+)?\/'.$path.'\/(.*?))[\"|\']/i';
        $r = preg_match_all($file_rule, $decode_string, $matches);
        if ($r > 0){
            array_map(function ($used, $key) use(&$file_matches){
                FileManager::isUsed($key, $used);
            }, $matches[1], $matches[2]);
        }
    }

    protected function defaultEditorField(){
        $table_schema = ConfigHelper::getDatabase();
        $information_schema_sql = <<<sql
SELECT tmp.*,c.COLUMN_NAME as uq_key from (
SELECT TABLE_NAME as table_name,GROUP_CONCAT(COLUMN_NAME) as column_name FROM `information_schema`.`COLUMNS` where table_name ='qs_user_area' and TABLE_SCHEMA='{$table_schema}' and DATA_TYPE in ({$this->editor_data_type}) GROUP BY TABLE_NAME) tmp
left join (SELECT TABLE_NAME,COLUMN_NAME,COLUMN_KEY from `information_schema`.`COLUMNS` where COLUMN_KEY = 'PRI' GROUP by TABLE_NAME,COLUMN_NAME,COLUMN_KEY) c ON c.TABLE_NAME = tmp.TABLE_NAME;
sql;
        $table_with_column = DB::select($information_schema_sql);
        collect($table_with_column)->each(function ($ent) use(&$data){
            $table_name = $ent->table_name;
            $uq_key = $ent->uq_key;
            $column_name = explode(',', $ent->column_name);
            $data[] = [
                'table_name' => $table_name,
                'column_name' => $column_name,
                'uq_key' => $uq_key,
            ];
        });

        return $data;
    }
}