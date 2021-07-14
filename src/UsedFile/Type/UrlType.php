<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\FileManager;

class UrlType extends AType
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
            $this->extractFileFromUrl($value);
        });
    }

    protected function parseValue(&$values){
        $files = [];
        collect($values)->each(function ($value) use(&$files){
            if ($value){
                strpos($value, ',') !== false && $value = explode(',', trim($value, ','));
                $value = is_array($value) ? $value : (array)$value;
                $files = array_merge($files, $value);
            }

        });

        $files = array_unique($files);

        return collect($files)->filter(function ($url){
            return strpos($url, $this->storage_file_dir) !== false;
        })->all();
    }

    protected function extractFileFromUrl($url){
        $file = explode($this->storage_file_dir, $url);
        $file = end($file);

        FileManager::isUsed($file, $url);
    }
}