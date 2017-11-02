<?php
return array(


    'TMPL_PARSE_STRING'  =>array(
        '__CSS__'=>__ROOT__.'/Application/Admin/View/css',
        '__IMG__'=>__ROOT__.'/Application/Admin/View/img',
        '__JS__'=>__ROOT__.'/Application/Admin/View/js',
        '__Upload__'=>__ROOT__.'/Public/Uploads'
    ),

    //Auth权限设置
    'AUTH_CONFIG' => array(
        'AUTH_ON' => true,  // 认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP' => 'zm_auth_group', // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'zm_auth_group_access', // 用户-用户组关系表
        'AUTH_RULE' => 'zm_auth_rule', // 权限规则表
        'AUTH_USER' => 'zm_admin_user', // 用户信息表
    ),

//     'SHOW_PAGE_TRACE' =>true,

    //模板部署
    'LAYOUT_ON'=>true,
    'LAYOUT_NAME'=>'layout',
);