<?php

namespace ClearAbandon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DBHelper
{

    static public function fetchIdsWithFile($file){
        $table_with_column = ConfigHelper::getStorageFileTableWithColumn();
        $table = $table_with_column['table_name'];
        $field_name = $table_with_column['column_name'];
        $uq_key = $table_with_column['uq_key'];
        $file = array_values($file);

        DB::table($table)
            ->select($uq_key)
            ->whereIn($field_name, $file)
            ->chunkById(1000, function ($list) use(&$ids, $uq_key){
                foreach ($list as $one_data){
                    $ids[] = $one_data->$uq_key;
                }
            }, $uq_key);

        return $ids;
    }

    static public function backFileIds($ids){
        $from_table = ConfigHelper::getStorageFileTable();
        $target_table = ConfigHelper::getStorageFileBakTable();

        DB::beginTransaction();
        try {
            $create_r = self::createTableLikeSource($target_table, $from_table);
            if ($create_r === false){
                E('创建数据表失败');
            }
            $bak_r = self::bakSourceFileData($ids, $target_table, $from_table);
            if ($bak_r === false){
                E('备份数据表失败');
            }
            $del_data_r = self::deleteSourceFileData($ids, $from_table);
            if ($del_data_r === false){
                E('删除源数据表失败');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    static protected function createTableLikeSource($target, $source){
        $r = DB::select("SHOW TABLES LIKE '{$target}'");
        if (!$r){
            $source_str = DB::select("SHOW CREATE TABLE {$source}");
            $source_str = array_values(get_object_vars($source_str[0]));
            $rule = '/('.$source.')/';
            $sql = preg_replace($rule,  $target, $source_str[1], 1);
            return DB::unprepared($sql);
        }
        return true;
    }

    static protected function bakSourceFileData($ids, $target, $source){
        $ids_str = "'".implode("','", $ids)."'";
        $sql = <<<sql
insert into `{$target}` select * from `{$source}` where  cast(id as char) in ({$ids_str});
sql;

        return DB::unprepared($sql);
    }

    static protected function deleteSourceFileData($ids, $table){
        return DB::table($table)->delete($ids);
//        return 1;
    }

    static public function dropBakTable(){
        $table = ConfigHelper::getStorageFileBakTable();
        Schema::dropIfExists($table);
    }

    static public function recoverFileData(){
        $from_table = ConfigHelper::getStorageFileBakTable();
        $r = DB::select("SHOW TABLES LIKE '{$from_table}'");
        if (!$r){
            return true;
        }

        $table_with_column = ConfigHelper::getStorageFileTableWithColumn();
        $target_table = $table_with_column['table_name'];
        $uq_key = $table_with_column['uq_key'];

        DB::beginTransaction();
        try {
            DB::table($from_table)->select($uq_key)->chunkById(1000, function ($list) use(&$ids, $uq_key, $target_table, $from_table){
                $ids = [];
                foreach ($list as $one_data){
                    $ids[] = $one_data->$uq_key;
                }
                $bak_r = self::bakSourceFileData($ids, $target_table, $from_table);
                if ($bak_r === false){
                    E('恢复数据失败');
                }
            }, $uq_key);

            self::dropBakTable();

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    static public function deleteFileIds($ids){
        $from_table = ConfigHelper::getStorageFileTable();

        DB::beginTransaction();
        try {
            $del_data_r = self::deleteSourceFileData($ids, $from_table);
            if ($del_data_r === false){
                E('删除数据表失败');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    static public function defaultEditorField($editor_data_type){
        $table_schema = ConfigHelper::getDatabase();
        $information_schema_sql = <<<sql
SELECT tmp.*,c.COLUMN_NAME as uq_key from (
SELECT TABLE_NAME as table_name,GROUP_CONCAT(COLUMN_NAME) as column_name FROM `information_schema`.`COLUMNS` where TABLE_SCHEMA='{$table_schema}' and DATA_TYPE in ({$editor_data_type}) GROUP BY TABLE_NAME) tmp
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

    static public function checkExistsTableWithColumn($data){
        $table_schema = ConfigHelper::getDatabase();
        self::parseTableNameStr($data, $all_table_name_str);

        $sql = <<<sql
SELECT tmp.*,c.COLUMN_NAME as uq_key from (
SELECT TABLE_NAME as table_name,GROUP_CONCAT(COLUMN_NAME) as column_name FROM `information_schema`.`COLUMNS` where TABLE_NAME in ({$all_table_name_str}) and TABLE_SCHEMA='{$table_schema}' GROUP BY TABLE_NAME) tmp
left join (SELECT TABLE_NAME,COLUMN_NAME,COLUMN_KEY from `information_schema`.`COLUMNS` where COLUMN_KEY = 'PRI' GROUP by TABLE_NAME,COLUMN_NAME,COLUMN_KEY) c ON c.TABLE_NAME = tmp.TABLE_NAME;
sql;

        $table_with_column = DB::select($sql);
        return self::checkTableWithColumn($data, $table_with_column);
    }

    static protected function parseTableNameStr($data, &$all_table_name_str){
        $all_table_name = array_column($data, 'table_name');
        $all_table_name_str = "'".implode("','", $all_table_name)."'";
    }

    static protected function checkTableWithColumn($data, $db_data_arr_cls){
        $error_tip = [];
        $db_table_name = [];

        collect($db_data_arr_cls)->each(function ($ent) use($data, &$error_tip, &$db_table_name){
            $table_name = $ent->table_name;
            $db_table_name[] = $table_name;
            $uq_key = $ent->uq_key;
            $column_name = explode(',', $ent->column_name);

            $diff_column = self::hasDiffColumn($column_name, $data[$table_name]['column_name']);
            if (!empty($diff_column)){
                $error_tip[$table_name] = $table_name.' 数据表配置不正确，不存在字段'. implode(",", $diff_column);
            }
            $is_same = self::isSameUqKey($uq_key, $data[$table_name]['uq_key']);
            if (!$is_same){
                $error_tip[$table_name] = isset($error_tip[$table_name]) ?
                    $error_tip[$table_name].'，uq_key不正确':
                    $table_name.' 数据表配置不正确，uq_key不正确';
            }
        });

        $diff_table = self::hasDiffTable($db_table_name, array_keys($data));
        if (!empty($diff_table)){
            $error_tip[] = implode(",", $diff_table). " 数据表不存在";
        }

        if (!empty($error_tip)){
            throw new \Exception(implode(PHP_EOL, $error_tip).PHP_EOL.'请检查并修改配置');
        }else{
            return true;
        }
    }

    static protected function hasDiffTable($db_table, $cus_table){
        return array_diff($cus_table, $db_table);
    }

    static protected function hasDiffColumn($db_column, $cus_column){
        return array_diff($cus_column, $db_column);
    }

    static protected function isSameUqKey($db_column, $cus_column){
        return $db_column === $cus_column;
    }

    static public function getUqKey($config, $key = 'uq_key'){
        return isset($config[$key]) && $config[$key] ? $config[$key] : 'id';
    }
}