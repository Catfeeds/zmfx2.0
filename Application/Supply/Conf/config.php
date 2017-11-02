<?php
return array(
    'SITE_NAME'    => '钻明供应宝',
    
    //供应宝上传的路径
    'SUPPLY_FILE_SAVE_PATH'        =>__ROOT__.'/Uploads/Supply/',
    'SUPPLY_LUOZUAN_FILE_SAVE_PATH'=>__ROOT__.'/Uploads/Supply/Luozuan/',

    //开启模板
    'LAYOUT_ON'         => false,
	'TMPL_DETECT_THEME' => false,
    'TMPL_PARSE_STRING' => array (
        '__CSS__'=>__ROOT__.'/Application/Supply/View/Public/css',
        '__IMG__'=>__ROOT__.'/Application/Supply/View/Public/images',
        '__JS__'=>__ROOT__.'/Application/Supply/View/Public/js',
        '__PUBLIC__'=>__ROOT__.'/Application/Supply/View/Public'
    ),
    //开启多语言
    'LANG_SWITCH_ON'    => true,
    'LANG_AUTO_DETECT'  => true,
	'LANG_LIST'         => 'zh-cn,zh-tw,en-us', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'      => 'lang',
    'DEFAULT_LANG'      => 'en-us',
    'DEFAULT_TEMPLATE'  => 'Default',
    //开启注册验证
    'VERIFY_EMAIL' => true,
);