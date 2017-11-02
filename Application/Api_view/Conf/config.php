<?php
return array(

     // 布局设置
	'TMPL_DETECT_THEME'     =>  true,
    'DEFAULT_THEME'    =>    'default',   
    'TMPL_PARSE_STRING'  =>array(
        '__CSS__'=>__ROOT__.'/Application/Api_view/View/default/css',
        '__IMG__'=>__ROOT__.'/Application/Api_view/View/default/img',
        '__JS__'=>__ROOT__.'/Application/Api_view/View/default/js',
        '__PUBLIC__'=>__ROOT__.'/Application/Api_view/View/default'
    ),
);