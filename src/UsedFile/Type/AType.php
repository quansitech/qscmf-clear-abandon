<?php


namespace ClearAbandon\UsedFile\Type;


use ClearAbandon\ConfigHelper;
use ClearAbandon\DBHelper;

abstract class AType implements IType
{
    protected $table_with_column_mapping_key;
    protected $table_with_column_mapping;
    protected $per_page = 1000;

    public function __construct()
    {
        $this->table_with_column_mapping = $this->getTableWithColumnMapping();
    }

    protected function getTableWithColumnMapping(){
        return ConfigHelper::getConfigWithKey($this->table_with_column_mapping_key);
    }

    protected function getUqKey($config, $key = 'uq_key'){
        return DBHelper::getUqKey($config, $key);
    }

    abstract public function extractUsedFile();

}