<?php

namespace ClearAbandon;

class ConfigHelper{

    const PER_PAGE_NMU = 1000;

    static public function getConfigPrefix($prefix = null){
        return $prefix ?: 'clearAbandon';
    }

    static public function getStorageFileTableWithColumn(){
        return self::getConfigWithKey('storage_file_table');
    }

    static public function getStorageFileDir(){
        $dir_name = self::getConfigWithKey('storage_file_dir');
        if ($dir_name){
            $dir_name_arr = explode('/',trim($dir_name, '/'));
            $dir = '/'.end($dir_name_arr).'/';
        }else{
            $dir = '/Uploads/';
        }
        return $dir;
    }

    static public function getConfigWithKey($key, $prefix = null){
        return config(self::getConfigPrefix($prefix).'.'.$key);
    }

    static public function getStorageFileDirName(){
        return self::getConfigWithKey('storage_file_dir') ?: LARA_DIR.'/../www'.'/Uploads/';
    }

    static public function getFileDomain(){
        return (array)self::getConfigWithKey('file_domain');
    }

    static public function getStorageTmpDirName(){
        $dir = self::getConfigWithKey('storage_unused_file_tmp_dir');
        return rtrim($dir, '/').'/';
    }

    static public function getStorageFileBakTable(){
        return self::getConfigWithKey('storage_file_bak_table') ?: self::getStorageFileTable().'_bak';
    }

    static public function getStorageFileTable(){
        return self::getStorageFileTableWithColumn()['table_name'];
    }

    static public function getStorageFileColumn(){
        return self::getStorageFileTableWithColumn()['column_name'];
    }

    static public function getStorageFileUqKey(){
        return DBHelper::getUqKey(self::getStorageFileTableWithColumn());
    }

    static public function getDatabase(){
        return config('database.connections.mysql.database');
    }

    static public function checkSoftConfig(){
        $not_empty_config_key = [
            'storage_file_table',
            'storage_unused_file_tmp_dir',
            'table_field_mapping',
        ];

        return self::checkConfig($not_empty_config_key);
    }

    static protected function checkConfig($not_empty_config_key){
        $valid_config = true;
        collect($not_empty_config_key)->each(function ($key) use(&$valid_config, &$empty_key){
            $valid_config = !empty(self::getConfigWithKey($key));
            $empty_key = $key;
            return $valid_config;
        });

        return [$empty_key, $valid_config];
    }

    static public function checkDelConfig(){
        $not_empty_config_key = [
            'storage_file_table',
            'table_field_mapping',
        ];

        return self::checkConfig($not_empty_config_key);
    }

    static public function existsSecurity(){
        $config = self::getStorageFileTableWithColumn();
        return isset($config['exits_security']) ? $config['exits_security'] : true;
    }

    static public function getSecurityColumnName(){
        return 'security';
    }

    static public function combineStorageFileTable(&$table_arr){
        $table_name = ConfigHelper::getStorageFileTable();
        $column_name = ConfigHelper::getStorageFileColumn();
        $uq_key = ConfigHelper::getStorageFileUqKey();

        $table_arr[$table_name]['table_name'] = $table_name;
        $table_arr[$table_name]['column_name'] = is_array($column_name)?$column_name:[$column_name];
        $table_arr[$table_name]['uq_key'] = $uq_key;
        self::existsSecurity()  && $table_arr[$table_name]['column_name'][] = self::getSecurityColumnName();
    }

    static public function combineTableWithColumn(&$table_arr){
        self::combineStorageFileTable($table_arr);

        $key = 'table_field_mapping';

        $config_value = ConfigHelper::getConfigWithKey($key);
        collect($config_value)->each(function ($ent) use(&$table_arr){
            $table_name = $ent['table_name'];
            $column_name = array_column($ent['column_name'], 'name');

            if (isset($table_arr[$table_name])){
                $table_arr[$table_name]['column_name'] = array_merge($table_arr[$table_name]['column_name'] , (array)$column_name);
            }else{
                $table_arr[$table_name]['table_name'] = $table_name;
                $table_arr[$table_name]['column_name'] = (array)$column_name;
            }
            $table_arr[$table_name]['uq_key'] = DBHelper::getUqKey($ent);
        });

//        $table_arr = collect($table_arr)->map(function ($table_column){
//            $table_column['column_name'] = array_unique($table_column['column_name']);
//            return $table_column;
//        })->all();
    }

    static public function validDbConfig(){
        self::combineTableWithColumn($table_arr);
        return DBHelper::checkExistsTableWithColumn($table_arr);
    }

}
