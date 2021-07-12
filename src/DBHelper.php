<?php

namespace ClearAbandon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Collection;
use function foo\func;

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

}