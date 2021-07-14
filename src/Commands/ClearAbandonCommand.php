<?php

namespace ClearAbandon\Commands;

use ClearAbandon\ConfigHelper;
use ClearAbandon\DBHelper;
use ClearAbandon\FileManager;
use ClearAbandon\ScanFile;
use ClearAbandon\UsedFile\UsedFile;
use Illuminate\Console\Command;
use Symfony\Component\Finder\SplFileInfo;

class ClearAbandonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qscmf:clear-abandon
                            {--D|delete : yes will clear abandon;no will move and backup abandon}
                            {--T|type=soft :  soft(default):remove file to other directory and backup db data to other table;delete:delete tmp dir and drop tmp table;recover:recover file and db data like before soft delete}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove or delete unused upload file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->canMkDir();
        ConfigHelper::validDbConfig();

        $is_delete = $this->option('delete');
        if ($is_delete){
            if ($this->confirm("确定要直接删除吗？")){
                $this->directDel();
            }
        }else{
            $this->softDel();
        }
    }

    protected function canMkDir(){
        $path = LARA_DIR.'/test_mkdir';
        mkdir($path);
        rmdir($path);
    }

    // 直接删除
    protected function directDel(){
        $this->checkDelConfig();

        $files = ScanFile::scan();
        if (!$files){
            $this->info('not found file in upload dir');
            return ;
        }
        $this->info('found '.count($files).' files');
        $this->genFileClass($files);
        $files = null;

        UsedFile::extractUsedFile();

        $unused_files = FileManager::getUnusedFile();
        if (!$unused_files){
            $this->info('no unused file');
            return ;
        }

        $unused_file_keys = FileManager::getFilesKey($unused_files);
        $unused_file_ids = DBHelper::fetchIdsWithFile($unused_file_keys);
        if ($unused_file_ids){
            $this->info('deleting '.count($unused_file_ids).' da data');
            DBHelper::deleteFileIds($unused_file_ids);

            $unused_file_keys = null;
            $unused_file_ids = null;
        }

        $this->info('deleting '.count($unused_files).' files');
        FileManager::deleteFile($unused_files);

        $this->deleteEmptyDirectory(ConfigHelper::getStorageFileDirName(), true);

        $this->info('delete success');
    }

    // 软删除
    // move 移动需要删除的图片至临时文件夹、将数据表的数据移动到临时数据表，并删除
    // recover 从临时文件夹、数据表中恢复文件以及数据
    // delete 删除临时文件夹、删除临时数据表
    protected function softDel(){
        $type = $this->option('type');

        $this->checkSoftConfig();

        switch ($type){
            case 'soft':
                $this->move();
                break;
            case 'recover':
                $this->recover();
                break;
            case 'delete':
                if ($this->confirm("确定要删除吗？")){
                    $this->delete();
                }
                break;
            default:
                throw new \Exception('invalid type');
                break;
        }
    }

    protected function delete(){
        DBHelper::dropBakTable();
        FileManager::delStorageFileTmpDir();

        $this->info('delete success');
    }

    protected function move(){
        $files = ScanFile::scan();
        if (!$files){
            $this->info('not found file in upload dir');
            return ;
        }
        $this->info('found '.count($files).' files');
        $this->genFileClass($files);
        $files = null;

        UsedFile::extractUsedFile();

        $unused_files = FileManager::getUnusedFile();
        if (!$unused_files){
            $this->info('no unused file');
            return ;
        }

        $unused_file_keys = FileManager::getFilesKey($unused_files);
        $unused_file_ids = DBHelper::fetchIdsWithFile($unused_file_keys);
        if ($unused_file_ids){
            $this->info('backing '.count($unused_file_ids).' db data');
            DBHelper::backFileIds($unused_file_ids);

            $unused_file_keys = null;
            $unused_file_ids = null;
        }

        $this->info('moving '.count($unused_files).' files');
        FileManager::moveFile($unused_files);

        $this->deleteEmptyDirectory(ConfigHelper::getStorageFileDirName(), true);

        $this->info('soft delete success');
    }

    protected function recover(){
        $files = ScanFile::scanBakDir();
        if (!$files){
            $this->info('not found in tmp dir');
            return ;
        }
        $this->info('found '.count($files).' files');
        $this->genFileClass($files);

        $this->info('recovering db data');
        DBHelper::recoverFileData();

        $this->info('recovering '.count($files).' files');
        FileManager::recoverFile();
        $files = null;

        $this->info('recover file success');
    }

    protected function genFileClass($files){
        collect($files)->each(function (SplFileInfo $file){
            FileManager::create($file->getRelativePathname(), $file->getPathname(), $file->getRelativePath());
        });
    }

    protected function checkDelConfig(){
        list($key_name, $without_empty_config) = ConfigHelper::checkDelConfig();
        if (!$without_empty_config){
            throw new \Exception('invalid config:'. $key_name);
        }
    }

    protected function checkSoftConfig(){
        list($key_name, $without_empty_config) = ConfigHelper::checkSoftConfig();
        if (!$without_empty_config){
            throw new \Exception('invalid config:'. $key_name);
        }
    }

    public function deleteEmptyDirectory($directory, $preserve = false)
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->deleteEmptyDirectory($item->getPathname());
            }
        }

        if (!$preserve && count(scandir($directory)) === 2) {
            rmdir($directory);
        }

        return true;
    }

}
