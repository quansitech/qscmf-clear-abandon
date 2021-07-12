<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\FileManager;
use Illuminate\Support\Facades\DB;
use function foo\func;

class EditorType extends AType
{
// 使用正则匹配字段是否使用了图片
// 可能有的情况：
// 相对路径 /Uploads/editor/20210706/1625562452316680.jpg
// 有子目录的相对路径 /reading/Uploads/editor/20200313/1584066556133155.png
// 没有使用代理且全域名 http://tslj1.t4tstudio.com/Uploads/editor/20180323/1521794771620269.jpg
// 使用代理且全域名 https://tslj.t4tstudio.com/booksharing/ip/q90/https://tslj.t4tstudio.com/booksharing/Uploads/editor/20210518/1621322855614850.png

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
        $files = [];
        $editor_info = $this->table_with_column_mapping;
        collect($editor_info)->each(function ($ent) use(&$files){
            $table_name = $ent['table_name'];
            $uq_key = $this->getUqKey($ent);
            $column_name = $ent['column_name'];
            $where_arr = $this->combineColumnLikeQuery($column_name);
            DB::table($table_name)
                ->where($where_arr, null, null, 'or')
                ->select($column_name)
                ->orderBy($uq_key)
                ->chunk($this->per_page, function ($list) use($column_name, &$files){
                    $row_files = [];
                    foreach ($list as $row_data){
//                        $row_files = $this->parseEditorRow($column_name, $row_data);
                        $this->parseEditorRow($column_name, $row_data);
                    }
//                    $files = array_merge($files, $row_files);
                });
        });

//        return $files;
    }

    protected function combineColumnLikeQuery($column){
        return collect($column)->map(function ($field){
            return [$field, 'like', "%{$this->storage_file_dir}%"];
        })->all();
    }

    protected function parseEditorRow($fields, $db_data){
        $files = [];
        collect($fields)->each(function ($field) use($db_data, &$files){
            $value = $db_data->$field;
//            list($r, $matches) = $this->extractFileFromEditorStr($value);
            $this->extractFileFromEditorStr($value);
//            if ($r > 0){
//                $files = array_merge($files, $matches);
//            }
        });
//        return $files;
    }

    protected function extractFileFromEditorStr($string){
        $decode_string = htmlspecialchars_decode($string);
        $path = trim($this->storage_file_dir, '/');
        $file_rule = '/<(?:img|a).*?(?:src|href)=[\"|\']((?:[\w|\:|\/|\d|\.]+)?\/'.$path.'\/(.*?))[\"|\']/i';
        $r = preg_match_all($file_rule, $decode_string, $matches);
        $file_matches = [];
        if ($r > 0){
            array_map(function ($used, $key) use(&$file_matches){
                FileManager::isUsed($key, $used);

//                if (isset($file_matches[$key])){
//                    $file_matches[$key]['used'][$used] = $used;
//                }else{
//                    $file_matches[$key] = ['file' => $key, 'used' => [$used => $used]];
//                }
            }, $matches[1], $matches[2]);
            $r = count($file_matches);
        }
//        return [$r, $file_matches];
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