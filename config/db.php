<?php
/**
 * 数据库配置文件
 * Author:shengsheng
 */
return [
    'mysql' => [
        'master' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'admin',
            'username' => 'root',
            'password' => 'root',
        ],
    ],
   'postgresql' => [
       'host' => '127.0.0.1',
       'port' => '5432',
       'dbname' => 'data',
       'username' => 'root',
       'password' => 'root',
   ],
    'redis' => [
        'host' => '127.0.0.1',
        'port' => '6379',
        'auth' => '',
    ],
    'ssdb' => [
        'host' => '127.0.0.1',
        'port' => '8888',
    ],
];