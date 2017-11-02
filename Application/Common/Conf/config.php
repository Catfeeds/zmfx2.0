<?php
return array(
    /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '192.168.16.63', // 服务器地址
    'DB_NAME'               =>  'zmfx_db',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'B^oawri1sty168',          // 密码
    /*'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'zmfx_db',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码*/
    'DB_PORT'               =>  '3307',        // 端口
    'DB_PREFIX'             =>  'zm_',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
    'SALT'    =>   's@z#z$m%z^b',//密码加密串
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => true,
    'LANG_LIST'        => 'zh-cn,en-us',
    'VAR_LANGUAGE'     => 'l',
   
    'APP_SUB_DOMAIN_DEPLOY'=>true, // 开启子域名配置
     /*子域名配置 *格式如: '子域名'=>array('分组名/[模块名]','var1=a&var2=b'); */ 
    'APP_SUB_DOMAIN_RULES'=>array(    
        'm'=>array('Mobile/'),
        'app'=>array('App/'),
        'demom'=>array('Mobile/'),
        'supply'=>array('Supply/'),
        'app'=>array('App/'),
        'api-data'=>array('Api_data/'),
        'api-view'=>array('Api_view/'),
        'apidata'=>array('Api_data/'),
        'apiview'=>array('Api_view/'),
     ),
     'ZMALL_DB'=>array(
        'DB_TYPE'               =>  'mysql',       // 数据库类型
        'DB_HOST'               =>  '192.168.16.63',   // 服务器地址
        'DB_NAME'               =>  'zuanming', // 数据库名
        'DB_USER'               =>  'root',        // 用户名
        'DB_PWD'                =>  'B^oawri1sty168',
        /*'DB_HOST'               =>  '127.0.0.1', // 服务器地址
        'DB_NAME'               =>  'zmfx_db',          // 数据库名
        'DB_USER'               =>  'root',      // 用户名
        'DB_PWD'                =>  'root',          // 密码*/
        'DB_PORT'               =>  '3307',        // 端口
        'DB_PREFIX'             =>  'zm_',         // 数据库表前缀
        'DB_CHARSET'            =>  'utf8',        // 数据库编码默认采用utf8
    ),
    'ZLF_DB'=>array(
            'DB_TYPE'               =>  'sqlsrv',       // 数据库类型
            'DB_HOST'               =>  '59.37.13.52',   // 服务器地址
            //'DB_NAME'               =>  'ZLFMiddle', // 正式
            'DB_NAME'               =>  'zlf_test', // 测试
            'DB_USER'               =>  'sa',        // 用户名
            'DB_PWD'                =>  'Zlf2015.',
            //'DB_DSN'       =>  'dblib:host=59.37.13.52:33112;dbname=ZLFmiddle',
        /*'DB_HOST'               =>  '127.0.0.1', // 服务器地址
        'DB_NAME'               =>  'zmfx_db',          // 数据库名
        'DB_USER'               =>  'root',      // 用户名
        'DB_PWD'                =>  'root',          // 密码*/
            'DB_PORT'               =>  '33112',        // 端口
            'DB_PREFIX'             =>  'zlf',         // 数据库表前缀
            'DB_CHARSET'            =>  'utf8',        // 数据库编码默认采用utf8
    ),
    /*'DEV_URL_ST'=> array(
        'replace_url_arr'=>array(
            "/api-data.zmfx.com/",//测试
            "/api-data.btbzm.com/",//正式
        ),
        'replace_to_url_arr'=>array(
            'dingzhi_zlf.zmfx.com/Api_data',
            'www.zlfyun.cn/Api_data'
        ),
        'replace_agent_id'=>array(1234567892)
    ),*/
    


    'ZMALL_URL'=>'http://zmall.com',
    'ZMFX_IMG_URL'=>'http://zmfx.com',
);