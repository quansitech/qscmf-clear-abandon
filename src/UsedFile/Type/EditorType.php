<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\FileManager;

class EditorType extends AType
{
    protected $storage_file_dir;

    public function __construct()
    {
        $this->storage_file_dir = ConfigHelper::getStorageFileDir();
        parent::__construct();
    }

    public function extractUsedFile(&$values = '')
    {
        $values = $this->parseValue($values);
        collect($values)->each(function ($value){
            $this->extractFileFromEditorStr($value);
        });
    }

    protected function parseValue(&$values)
    {
        return collect($values)->filter(function ($ent){
            $path = trim($this->storage_file_dir, '/');
            return strpos($ent, '/'.$path.'/') !== false;
        })->all();
    }

    protected function extractFileFromEditorStr($string){
        $decode_string = htmlspecialchars_decode($string);
        $path = trim($this->storage_file_dir, '/');
        $file_rule = '/((?:[\w\:\/\d\.-]+)?\/'.$path.'\/(.*?\.[\w]+))(?:&quot;|\"|\'|\s)?/i';
        $r = preg_match_all($file_rule, $decode_string, $matches);
        if ($r > 0){
            array_map(function ($used, $key){
                FileManager::isUsed($key, $used);
            }, $matches[1], $matches[2]);
        }
    }

}