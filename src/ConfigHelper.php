<?php

namespace ClearAbandon;

class ConfigHelper{

    static public function getConfigPrefix($prefix = null){
        return $prefix ?: 'clearAbandon';
    }

    static public function getStorageFileTableWithColumn(){
        return self::getConfigWithKey('storage_file_table');
    }

    static public function getStorageFileDir(){
        $dir = self::getConfigWithKey('storage_file_dir') ?: '/Uploads/';
        return rtrim($dir, '/').'/';
    }

    static public function getConfigWithKey($key, $prefix = null){
        return config(self::getConfigPrefix($prefix).'.'.$key);
    }

    static public function getStorageFileDirName(){
        return LARA_DIR.'/../www'.self::getStorageFileDir();
    }

    static public function getFileDomain(){
        return self::getConfigWithKey('file_domain');
    }

    static public function getStorageTmpDir(){
        $dir = self::getConfigWithKey('storage_unused_file_tmp_dir');
        return rtrim($dir, '/').'/';
    }

    static public function getStorageTmpDirName(){
//        return LARA_DIR.'/../www'.self::getStorageTmpDir();
        return self::getStorageTmpDir();
    }

    static public function getStorageFileBakTable(){
        return self::getConfigWithKey('storage_file_bak_table') ?: self::getStorageFileTable().'_bak';
    }

    static public function getStorageFileTable(){
        return self::getStorageFileTableWithColumn()['table_name'];
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

}
