<?php

return [
    // // mysql 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => '120.78.177.144',
    // 数据库名
    'database'        => 'wxapp',
    // 用户名
    'username'        => 'wxapp',
    // 密码
    'password'        => 'wxapp',
    // 端口
    'hostport'        => '3306',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8mb4',
    // 数据库表前缀
    'prefix'          => '',
    // 数据库调试模式
    'debug'           => true,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => false,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,

     // oracle数据库类型
        // 'type' => 'oracle',
        // // 服务器地址
        // 'hostname' => 'devserver',
        // // 数据库名
        // 'database' => 'ora10g',
        // // 用户名
        // 'username' => 'tpora',
        // // 密码
        // 'password' => 'tpora',
        // // 端口
        // 'hostport' => '1521',
        // // 连接dsn
        // 'dsn' => '',
        // // 数据库连接参数
        // 'params' => [
        //     PDO::ATTR_PERSISTENT => true,
        //     // 表查出来的字段大小写输出。PDO::CASE_LOWER：强制列名小写,PDO::CASE_NATURAL：列明按照原始的方式,PDO::CASE_UPPER：强制列名大写
        //     PDO::ATTR_CASE => PDO::CASE_LOWER,
        //     // PDO::ERRMODE_SILENT：不显示错误信息，只显示错误码,PDO::ERRMODE_WARNING：显示警告错误,PDO::ERRMODE_EXCEPTION：抛出异常
        //     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        //     // 字段为空，则返回啥，包括PDO::NULL_NATURAL,PDO::NULL_EmpTY_STRING,PDO::NULL_TO_STRING
        //     PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
        //     // 从表查出来的都是字符串格式
        //     PDO::ATTR_STRINGIFY_FETCHES => false,
        //     // 防驻入。建议设成false
        //     PDO::ATTR_EMULATE_PREPARES => false,
        // ],
        // // 数据库编码默认采用utf8
        // 'charset' => 'utf8',
        // // 数据库表前缀
        // 'prefix' => '',
        // // 数据库调试模式
        // 'debug' => true,
        // // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        // 'deploy' => 0,
        // // 数据库读写是否分离 主从式有效
        // 'rw_separate' => false,
        // // 读写分离后 主服务器数量
        // 'master_num' => 1,
        // // 指定从服务器序号
        // 'slave_no' => '',
        // // 是否严格检查字段是否存在
        // 'fields_strict' => false,
        // // 数据集返回类型
        // 'resultset_type' => 'array',
        // // 自动写入时间戳字段
        // 'auto_timestamp' => true,
        // // 时间字段取出后的默认时间格式
        // 'datetime_format' => 'Y-m-d H:i:s',
        // // 是否需要进行SQL性能分析
        // 'sql_explain' => false,

];
