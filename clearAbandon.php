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
    | exits_security 该数据表是否存在security字段，true 存在，false 不存在，默认为true
    |
    | 'storage_file_table' => ['table_name' => 'qs_file_pic', 'column_name' => 'file', 'uq_key' => 'id', 'exits_security' => true]
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
    | 存放文件的文件夹目录，为绝对路径，默认为 LARA_DIR.'/../www'.'/Uploads/'
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
    | table_name string 数据表名称
    |
    | column_name array 字段相关信息
    |   name 字段名称
    |   type 数据存储类型，目前的基本类型有 id、url、editor
    |        id 指使用了文件信息主键的数据，包括单个、多个（使用,隔开），如 1、5,6
    |        url 指使用了文件路径（包括相对路径、可远程访问的url）的数据
    |            ！！！需要有/Uploads/
    |            如 /sub_root/Uploads/image/20210716/1626160809666786.jpg， /Uploads/image/20210716/1626160809666786.jpg，http://quansitech.com/Uploads/image/20210716/1626160809666786.jpg
    |        editor 指存储了富文本的数据
    |               ！！！目前只支持显性使用了文件路径的情况
    |
    |        如果同一个字段的数据使用了多种类型来存储，则可以使用回调函数返回具体的类型，回调函数接收的参数为 该行所有字段数据的对象 。此时的配置： type 为 callback ， type_callback 为回调函数。
    |        例如系统配置数据表qs_config，该数据表有配置类型字段如type，有配置值字段如value。
    |        当type为 picture、file 等值时，value值存储的是 id类型；当type为 ueditor 时，value值存储的是 editor类型；非以上情况返回 false。
    |
    | uq_key string 该数据表的主键，默认为id
    |
    |
    |    'table_field_mapping' => [
    |        [
    |            'table_name' => 'qs_user_area',
    |            'column_name' => [
    |                ['name' => 'file_id', 'type' => 'id'],
    |                ['name' => 'file', 'type' => 'url'],
    |                ['name' => 'context', 'type' => 'editor'],
    |            ],
    |            'uq_key' => 'id'
    |        ],
    |        [
    |            'table_name' => 'qs_config',
    |            'column_name' => [
    |                ['name' => 'value', 'type' => 'callback', 'type_callback' => function($db_data){
    |                    $type_value = $db_data->type;
    |                    if (in_array($type_value, ['picture','file','pictures','files'])){
    |                        return 'id';
    |                    }elseif($type_value == 'ueditor'){
    |                        return 'editor';
    |                    }
    |                    return false;
    |                }],
    |            ],
    |        ],
    |        [
    |            'table_name' => 'qs_person',
    |            'column_name' => [
    |                ['name' => 'avatar', 'type' => 'callback', 'type_callback' => function($db_data){
    |                    $avatar = $db_data->avatar;
    |                    if (is_numeric($avatar)){
    |                        return 'id';
    |                    }elseif($avatar != ''){
    |                        return 'url';
    |                    }
    |                    return false;
    |                }],
    |                ['name' => 'id_pic', 'type' => 'id'],
    |            ],
    |        ]
    |    ],
    |
    */

    'table_field_mapping' => [],

    /*
    |--------------------------------------------------------------------------
    | 文件访问域名
    |--------------------------------------------------------------------------
    |
    | 如访问某个文件的url为 http://quansitech.com/Uploads/image/20210716/1626160809666786.jpg
    | 或者为 http://www.quansitech.com/Uploads/image/20210716/1626160809666786.jpg
    |
    | 'file_domain' => ['www.quansitech.com','quansitech.com']
    |
    */

    'file_domain' => []
];