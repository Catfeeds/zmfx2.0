<?php
namespace Supply\Model;
use Think\Model;
class GoodsSanhuoTypeModel extends Model{
    public function getList()
    {
       return $this->order('sort asc')->getField('tid,type_name');
    }
     public function getList_en()
    {
       return $this->order('sort asc')->getField('tid,type_name_en');
    }
}