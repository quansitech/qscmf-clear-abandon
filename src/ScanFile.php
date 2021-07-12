<?php

namespace ClearAbandon;
use Illuminate\Filesystem\Filesystem;

class ScanFile{

    static public function scan($dir = ''){
        $scan_dir = $dir ? : ConfigHelper::getStorageFileDirName();
        $file_obj = new Filesystem();
        $files = [];

//        collect(self::$scan_dir)->each(function ($dir) use(&$files, $file_obj){
//            $one_dir_files = $file_obj->allFiles($dir);
//            $files = array_merge($files, (array)$one_dir_files);
//        });

        $files =  $file_obj->allFiles($scan_dir);

        return $files;
    }

    static public function scanBakDir(){
        return self::scan(ConfigHelper::getStorageTmpDirName());
    }


}