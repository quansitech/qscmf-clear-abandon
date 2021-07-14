<?php


namespace ClearAbandon\UsedFile\Type;

abstract class AType implements IType
{
    public function __construct()
    {
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

        return array_unique($files);
    }

    abstract public function extractUsedFile(&$values = '');

}