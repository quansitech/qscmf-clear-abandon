<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 存放文件信息的数据表名称及其字段
    |--------------------------------------------------------------------------
    |
    | table_name 数据表名称
    | column_name 存放文件相对路径的字段名称
    | uq_key 该数据表的主键，默认为id
    |
    | 'storage_file_table' => ['table_name' => 'qs_file_pic', 'column_name' => 'file', 'uq_key' => 'id']
    |
    */

    'storage_file_table' => [],

    /*
    |--------------------------------------------------------------------------
    | 存放被处理文件信息的数据表，用于备份storage_file_table的数据，默认为该数据表加'_bak'
    |--------------------------------------------------------------------------
    |
    | 该数据表的结构与storage_file_table中的数据表一致
    |
    */

    'storage_file_bak_table' => '',

    /*
    |--------------------------------------------------------------------------
    | 存放文件的文件夹目录，为项目www的相对路径，默认为 LARA_DIR.'/../www'.'/Uploads/'
    |--------------------------------------------------------------------------
    |
    */

    'storage_file_dir' => LARA_DIR.'/../www'.'/Uploads/',

    /*
    |--------------------------------------------------------------------------
    | 存放被处理文件的临时文件夹目录，为绝对路径
    |--------------------------------------------------------------------------
    |
    */

    'storage_unused_file_tmp_dir' => '',

    /*
    |--------------------------------------------------------------------------
    | 所有使用了文件信息的数据表及其字段信息
    |--------------------------------------------------------------------------
    |
    | table_name 数据表名称
    | column_name 字段名称，多个使用,隔开
    | uq_key 该数据表的主键，默认为id
    |
    | 'table_field_mapping' => [
    |     ['table_name' => 'qs_user_area','column_name' => ['file_id','img_ids'], 'uq_key' => 'id'],
    |     ['table_name' => 'qs_file_test','column_name' => ['file_id','img_ids']]
    | ]
    |
    */

    'table_field_mapping' => [],

    /*
    |--------------------------------------------------------------------------
    | 数据表及其富文本字段信息，若不配置则默认为所有数据表的text、longtext数据类型的字段
    |--------------------------------------------------------------------------
    |
    | table_name 数据表名称
    | column_name 字段名称，多个使用,隔开
    | uq_key 该数据表的主键，默认为id
    |
    | 'table_editor_field_mapping' => [
    |     ['table_name' => 'qs_user_area','column_name' => ['context','short_content'], 'uq_key' => 'id'],
    |     ['table_name' => 'qs_file_test','column_name' => ['summary']]
    | ]
    |
    */

    'table_editor_field_mapping' => [],

    /*
    |--------------------------------------------------------------------------
    | 文件访问域名
    |--------------------------------------------------------------------------
    |
    */

    'file_domain' => []
];