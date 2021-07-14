<?php

namespace ClearAbandon\UsedFile\Type;

use ClearAbandon\ConfigHelper;
use ClearAbandon\DBHelper;
use ClearAbandon\FileManager;
use Illuminate\Support\Facades\DB;

class IdType extends AType
{
    protected $storage_file_table;

    public function __construct()
    {
        $this->storage_file_table = ConfigHelper::getStorageFileTableWithColumn();
        parent::__construct();
    }

    public function extractUsedFile(&$values = '')
    {
        $ids = $this->parseValue($values);
        $table = $this->storage_file_table['table_name'];
        $field = $this->storage_file_table['column_name'];
        $uq_key = DBHelper::getUqKey($this->storage_file_table);

        DB::table($table)
            ->whereIn($uq_key,$ids)
            ->select([$uq_key,$field])
            ->chunkById(ConfigHelper::PER_PAGE_NMU, function ($list) use($uq_key, $field){
                foreach ($list as $one_data) {
                    $value = $one_data->$field;

                    FileManager::isUsed($value, $value);
                }
            }, $uq_key);

    }
}