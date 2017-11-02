<?php
return array(

     // 布局设置
	'TMPL_DETECT_THEME'     =>  false,
    'DEFAULT_THEME'    =>    'default',   
    'TMPL_PARSE_STRING'  =>array(
        '__CSS__'=>__ROOT__.'/Application/App/View/default/css',
        '__IMG__'=>__ROOT__.'/Application/App/View/default/img',
        '__JS__'=>__ROOT__.'/Application/App/View/default/js',
        '__PUBLIC__'=>__ROOT__.'/Application/App/View/default/public',
		'__Upload__'=>__ROOT__.'/Public/Uploads'
    ),
);