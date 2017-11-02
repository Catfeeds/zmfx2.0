<?php
return array(
	/* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'zmfx_develop',          // 数据库名
    'DB_USER'               =>  'zmfx_develop',      // 用户名
    'DB_PWD'                =>  'zmfx_develop123',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'zm_',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
    'SALT'    =>   's@z#z$m%z^b',//密码加密串
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => true,
    'LANG_LIST'        => 'zh-cn,en-us',
    'VAR_LANGUAGE'     => 'l',
    
    'APP_SUB_DOMAIN_DEPLOY'=>1, // 开启子域名配置
     /*子域名配置 *格式如: '子域名'=>array('分组名/[模块名]','var1=a&var2=b'); */ 
    'APP_SUB_DOMAIN_RULES'=>array(    
        'm'=>array('Mobile/'),
        '192.168.16.63'=>array('Mobile/'),
        '192.168.1.79'=>array('Mobile/'),
        'api'=>array('Api/'),
     ),
);