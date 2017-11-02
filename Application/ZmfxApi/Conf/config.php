<?php
return array(

     // 布局设置
	'TMPL_DETECT_THEME'     =>  true,
    'DEFAULT_THEME'    =>    'default',   
    'TMPL_PARSE_STRING'  =>array(
        '__CSS__'=>__ROOT__.'/Application/Mobile/View/default/Css',
        '__IMG__'=>__ROOT__.'/Application/Mobile/View/default/Img',
        '__JS__'=>__ROOT__.'/Application/Mobile/View/default/Js',
        '__PUBLIC__'=>__ROOT__.'/Application/Mobile/View/default'
    ),
);