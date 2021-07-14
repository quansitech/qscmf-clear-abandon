<?php

namespace ClearAbandon;

use Illuminate\Filesystem\Filesystem;
class FileManager
{
    static private $_files_class = [];

    static public function create($file,$path_name = null, $relative_path = null){
        if (in_array($file, array_keys(self::$_files_class))){
            return self::$_files_class[$file];
        }else{
            $new_file = new File($file, $path_name, $relative_path);
            self::$_files_class[$file] = $new_file;
            return $new_file;
        }
    }

    static public function unsetFile($file){
        if (self::$_files_class[$file]) unset(self::$_files_class[$file]);
    }

    static public function isUsed($file, $used_path = null){
        $file_cls = self::create($file);
        if ($file_cls->getUsed()){
            return true;
        }else{
            $r = self::checkPath($used_path);
            $file_cls->setUsed($r);
            return $r;
        }
    }

    static protected function checkPath($path){
        $rule = '/http[s]?\:\/\/([\w|\.|\d]+)/i';
        if (preg_match_all($rule, $path, $file_c_matches)){
            $latest_domain = end($file_c_matches[1]);
            return in_array($latest_domain, ConfigHelper::getFileDomain());
        }else{
            return true;
        }
    }

    static public function getUnusedFile(){
        return collect(self::$_files_class)->filter(function (File $file){
            return $file->getPathName() && !$file->getUsed();
        })->all();
    }

    static public function getFilesKey($file){
        return collect($file)->map(function (File $file){
            return $file->getRelativePathName();
        })->all();
    }

    static public function moveFile($need_move_file, $target_dir_name = null){
        $target_dir_name = $target_dir_name ?: ConfigHelper::getStorageTmpDirName();
        $file_system = new Filesystem();

        collect($need_move_file)->each(function (File $file) use($file_system, $target_dir_name){
            $file_path_name = $file->getPathName();
            if ($file_path_name){
                $target_path = $target_dir_name.$file->getRelativePath();
                $target_path_name = $target_dir_name.$file->getRelativePathName();
                self::createStorageTmpDir($target_path);
                $move_r = $file_system->move($file->getPathName(), $target_path_name);
                return $move_r;
            }
        });
    }

    static protected function createStorageTmpDir(&$storage_tmp_dir_name){
        $file_system = new Filesystem();
        if (!$file_system->exists($storage_tmp_dir_name)){
            return $file_system->makeDirectory($storage_tmp_dir_name, 0755, true);
        }

        return true;
    }

    static protected function clearFileCls(){
        self::$_files_class = [];
    }

    static public function delStorageFileTmpDir(){
        $path = ConfigHelper::getStorageTmpDirName();
        $file_system = new Filesystem();
        $file_system->deleteDirectory($path);
    }

    static public function recoverFile(){
        self::moveFile(self::$_files_class, ConfigHelper::getStorageFileDirName());
        self::delStorageFileTmpDir();
    }


    static public function deleteFile($need_delete_file){
        $file_system = new Filesystem();

        collect($need_delete_file)->each(function (File $file) use($file_system){
            $delete_r = $file_system->delete($file->getPathName());
            return $delete_r;
        });
    }
}