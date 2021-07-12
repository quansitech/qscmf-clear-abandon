<?php


namespace ClearAbandon\UsedFile;

use ClearAbandon\UsedFile\Type\EditorType;
use ClearAbandon\UsedFile\Type\IType;
use ClearAbandon\UsedFile\Type\UploadType;

class UsedFile{

    static protected $type_mapping = [
        'upload' => UploadType::class,
        'editor' => EditorType::class
    ];

    static public function extractUsedFile(){
        collect(self::$type_mapping)->each(function ($type){
            $type_cls = new $type();
            $type_cls->extractUsedFile();
        });
    }

}