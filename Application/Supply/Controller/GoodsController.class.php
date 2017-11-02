<?php
namespace Supply\Controller;
class GoodsController extends SupplyController{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 生成归属条件
     * @param string $as 不同的表AS名称
     * @return string
     */
    protected function buildWhere($as=''){
        $where = $as; ///.'agent_id = '.C('agent_id');//超级管理员和其他
        return $where;
    }

    protected function buildUuid($key){
        //获取已有的Uuid
        $sess = session('admin.uuid');
        $arr = $sess[$key];
        for ($i=0;$i<999;$i++){
            $chars = md5(uniqid(mt_rand(), true));
            $uuid  = substr($chars,0,8) . '-';
            $uuid .= substr($chars,8,4) . '-';
            $uuid .= substr($chars,12,4) . '-';
            $uuid .= substr($chars,16,4) . '-';
            $uuid .= substr($chars,20,12);
            if(!in_array($i, $arr)){
                $newID = $uuid;
                break;
            }
        }
        $newArray = array_merge($arr,array($newID));
        session('admin.uuid',array($key=>$newArray));
        return $newID;
    }

    /**
     * 生成IN参数
     * @param array $array
     * @param int $id
     * @return Ambigous <string, unknown>
     */
    protected function parIn($array,$id){
        $ids ='';
        foreach ($array as $key => $value) {
            if($key) $ids .= ','.$value[$id];
            else $ids .= $value[$id];
        }
        return $ids;
    }

    /**
     * 把数组里的字段值作为KEY存放
     * @param array $array
     * @param string $id 没有键名，第一个就是键名
     */
    protected function _arrayIdToKey($array,$id=''){
        if(!$id){$ids = array_keys($array[0]);$id = $ids[0];}
        foreach ($array as $key => $value) {
            $arr[$value[$id]] = $value;
        }
        return $arr;
    }

    /**
     * 递归数组
     * @param array $data 数组对象
     * @param int $id 一条记录的id
     * @param int $pid 上级id
     * @param int $objId 开始的id
     * @return multitype:multitype:unknown
     */
    protected function _arrayRecursive($data,$id,$pid,$objId=0,$isKey='0'){
        $list = array();
        foreach ($data AS $key => $val){
            if($val[$pid] == $objId){
                $val['sub'] = self::_arrayRecursive($data, $id, $pid, $val[$id],$isKey);
                if(empty($val['sub'])){unset($val['sub']);}
                if($isKey) $list[] = $val;
                else $list[$val[$id]] = $val;
            }
        }
        return $list;
    }

    /**
     * 添加成品成品的SKU
     * @param int $goods_id
     * @param array $sku
     */
    protected function goodsSku($goods_id,$sku){
        $GS = M('goods_sku');
        if(empty($sku)){
            $this->error(L('error_msg_060'));
        }
        foreach ($sku as $key => $value) {
            $arr['sku_sn']       = $this->generateSkuSn($value['sku_sn'],$goods_id,$key);
            $arr['goods_id']     = $goods_id;
            $arr['agent_id']     = $this->agent_id;
            $arr['goods_number'] = $value['goods_number']?$value['goods_number']:1;
            $arr['goods_price']  = $value['goods_price'];
            $arr['attributes']   = $key;
            $skuS[] = $arr;
        }
        $GS->where('goods_id = '.$goods_id)->delete();
        return $GS->addAll($skuS);
    }

    /**
     * 属性关联，主要做页面发过来的属性处理
     * @param array $attribute
     * @param int $category_id
     * @param int $goods_id
     */
    protected function attributeAssociate($attribute,$category_id,$goods_id){
        if(!$attribute) return true;//没有属性直接返回
        $GA  = M('goods_attributes');
        $GAV = M('goods_attributes_value');
        $GAA = M('goods_associate_attributes');
        //根据属性名称和属性值生成实际插入数组
        foreach ($attribute as $key => $value) {
            $ids .= ','.$key;
        }
        $ids = substr($ids, 1);
        $list = $GA->where('attr_id in ('.$ids.')')->select();
        $list = $this->_arrayIdToKey($list,'attr_id');
        $listSub = $GAV->where('attr_id in ('.$ids.')')->select();
        foreach ($listSub as $key => $value) {
            $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
        }
        foreach ($attribute as $key => $value) {
            $arr['category_id'] = $category_id;
            $arr['goods_id']    = $goods_id;
            $arr['agent_id']    = $this->agent_id;
            $arr['attr_id']     = $key;
            $arr['attr_value']  = '0';
            $arr['attr_code']   = '0';
            if($list[$key]['input_type'] == 1){
                $arr['attr_code'] = $list[$key]['sub'][$value]['attr_code'];
            }elseif($list[$key]['input_type'] == 2){
                foreach ($value as $k => $v) {
                    $arr['attr_code'] += intval($list[$key]['sub'][$v]['attr_code']);
                }
            }elseif($list[$key]['input_type'] == 3){
                foreach ($value as $k => $v) {
                    $attr_value .= ','.$v;
                }
                $attr_value = substr($attr_value, 1);
                $arr['attr_value'] = $attr_value;
                unset($attr_value);
            }
            $attributeAssociate[] = $arr;
            unset($arr);
        }
        $GAA->where('goods_id = '.$goods_id)->delete();
        return $GAA->addAll($attributeAssociate);
    }

    /**
     * 生成新的sku编码和检查编码是否可用
     */
    protected function generateSkuSn($sku_sn,$goods_id,$attributes){
        $GS = M('goods_sku');//实例化SKU表
        $sn = date('Ymdhis',time()).rand(10000, 99999);
        $sku_sn = empty($sku_sn)?$sn:$sku_sn;
        $where  = "sku_sn = '$sku_sn' AND agent_id=".$this->agent_id;
        $list   = $GS->where($where)->select();
        if($list and count($list) > 1){
            $this->error(L('error_msg_079'));
        }else if($list){
            if($list[0]['goods_id'] == $goods_id and $list[0]['attributes'] == $attributes){
                return $sku_sn;
            }else{
                $this->error(L('error_msg_080'));
            }
        }else{
            return $sku_sn;
        }
    }

    /**
     * 后台记录操作日志,有区分中英文语言
     * @param string $type 日志类型
     * @param string $message 语言包KEY
     * @param string $key 操作对象(比如ID:56)
     * @param string $jumpUrl 跳转地址
     * @param string $ajax 是否ajax返回
     */
    protected function log($type,$message,$key='',$jumpUrl='',$ajax=false){
        $messageS = L($message);
        $this->$type($messageS,$jumpUrl,$ajax);
    }

    /**
     * 计算产品价格<br>
     * 产品总价格=(金重*(1+损耗)*金价+基本工费+钻石工费+副石价格)*加点<br>
     * 实际计算过程
     */
    function recalculatingPriceDo($weights_name,$loss_name,$gold_price,$basic_cost,$luozuan_price,$deputystone_price,$advantage=0){
        $loss_name = $loss_name/100;
        return formatPrice(($gold_price*$weights_name*(1+$loss_name)+$basic_cost+$luozuan_price+$deputystone_price)*(1+$advantage/100), 2);
    }

    /**
     * 产品生成小图
     * @param string $imgFile
     * @return multitype:boolean string
     */
    private function productThumb($imgFile) {
        $big_path = $imgFile['savepath'].$imgFile['savename'];
        $big_path = './Public'.substr($big_path, 1);
        $image = new \Think\Image();
        $image->open($big_path);
        // 检查图片宽高是否在指定的等比中
        $ratio = number_format($image->width() / $image->height(), 1);
        if (($ratio >= 1) && ($ratio <= 1.3)) {
            $pi = pathinfo($big_path);
            $small_path = $pi['dirname'].'/'.$pi['filename'].'_2.'.$pi['extension'];
            //默认采用原图等比例缩放的方式生成缩略图
            $image->thumb(230, 230, 1)->save($small_path);
            $result = array('success'=>true, 'data'=>$small_path);
        } else {
            unlink($big_path); // 删除原文件
            $result = array('success'=>false, 'msg'=>L('text_msg_004'));
        }
        return $result;
    }
    /**
     * 产品图片文件上传的处理
     * @param number $goods_id
     */
    private function productImg($goods_id=0) {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728 ;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath = './Uploads/product/'; // 设置附件上传目录    // 上传文件
        $info = $upload->uploadOne($_FILES['product_img']);
        if(!$info) {// 上传错误提示错误信息
            $result = $upload->getError();
        }else{// 上传成功
            // 生成缩略图
            $thumbRes = $this->productThumb($info);
            if ($thumbRes['success']) {
                $imgData['small_path']  = '.'.substr($thumbRes['data'], 8);
                $I                      = M('goods_images');
                // 保存图片数据
                $imgData['goods_id']    = $goods_id;
                $imgData['big_path']    = $info['savepath'].$info['savename'];
                $imgData['agent_id']    = $this->agent_id;
                $imgData['create_time'] = time();
                $imgID = $I->add($imgData);
                if ($imgID) {
                    $result = array('success'=>true, 'msg'=>L('success'), 'data'=>$I->find($imgID));
                } else {
                    $result = array('success'=>false, 'msg'=>L('failed'));
                }
            } else {
                $result = $thumbRes;
            }
        }
        return $result;
    }
    /**
     * 产品图片一次上传与删除
     */
    public function productByOne() {
        $images_id = I('post.images_id');
        if (empty($images_id)) {
            echo json_encode($this->productImg());
        } else {
            if (M('goods_images')->where('images_id = '.$images_id)->delete()) {
                echo json_encode(array("success"=>true, "msg"=>L('success')));
            } else {
                echo json_encode(array("success"=>false, "msg"=>L('operation_error')));
            }
        }
    }

    /**
     * 产品材质的裸钻主石，副石，金重/损耗关联
     * @param int $goods_id
     * @return double
     */
    protected function materialAssociate($goods_id){
        $GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
        $GAI = M('goods_associate_info');//产品材质损耗金重金价关联表
        $GAD = M('goods_associate_deputystone');//产品副石关联表
        //页面post数据
        $material = I('post.material');
        //处理副石相关数据
        $deputystone = $material['deputystone'];
        foreach ($deputystone AS $key => $value) {
            $value['goods_id'] = $goods_id;
            $value['agent_id'] = $this->agent_id;
            $deputystoneS[] = $value;
        }
        $GAD->where('goods_id = '.$goods_id)->delete();
        $GAD->addAll($deputystoneS);
        //处理材质损耗金重
        $info = $material['info'];
        if(empty($info)){
            $this->error(L('text_msg_005'));
        }
        foreach ($info as $key => $value) {
            $value['goods_id'] = $goods_id;
            $value['agent_id'] = $this->agent_id;
            $value['material_id'] = $key;
            $infoS[] = $value;
            //必须填写金重，并为数字
            if(!is_numeric($value['weights_name'])){
                $this->error(L('error_msg_062'));
            }
            //必须填写损耗，并为数字
            if(!is_numeric($value['loss_name'])){
                $this->error(L('error_msg_063'));
            }
            //必须填写损耗，并为数字
            if(!is_numeric($value['basic_cost'])){
                $this->error(L('error_msg_064'));
            }
        }
        $GAI->where('goods_id = '.$goods_id)->delete();
        $GAI->addAll($infoS);
        //处理材质裸钻
        $luozuan = $material['luozuan'];
        if(empty($luozuan)){
            $this->error(L('error_msg_065'));
        }
        foreach ($luozuan as $key => $value) {
            foreach ($value as $k => $v) {
                $v['goods_id']    = $goods_id;
                $v['agent_id']    = $this->agent_id;
                $v['material_id'] = $key;
                $luozuanS[] = $v;
                //形状必须填写
                if(!$v['shape_id']){
                    $this->error(L('error_msg_066'));
                }
                //分数必须填写
                if(!$v['weights_name'] or !is_numeric($v['weights_name'])){
                    $this->error(L('error_msg_067'));
                }
                //价格必须填写
                if(!$v['price'] or !is_numeric($v['price'])){
                    $this->error(L('error_msg_068'));
                }
            }
        }
        $GAL->where('goods_id = '.$goods_id)->delete();
        $GAL->addAll($luozuanS);
        //获取材质金价
        $GM = M('goods_material');
        $gp = $GM->where('material_id = '.$infoS[0]['material_id'])->getField('gold_price');
        if(!$gp){
            $this->error(L('error_msg_069'));
        }
        $wn = $infoS[0]['weights_name'];//默认第一个金重
        $ln = $infoS[0]['loss_name'];//默认第一个损耗
        $bc = $infoS[0]['basic_cost'];//默认第一个为基础工费
        $lp = $luozuanS[0]['price'];//默认第一个主石工费为镶嵌工费
        $dp = $deputystoneS[0]['deputystone_price'];//默认第一个副石工费
        $price = $this -> recalculatingPriceDo($wn,$ln,$gp,$bc,$lp,$dp,0);
        return $price;
    }

    /**
     * 检查页面发送过来的产品编号，产品表是否有产品编号
     * @param string $goods_sn
     * @param int $goods_id
     * @return boolean
     */
    protected function checkGoodsSn($goods_sn,$goods_id=''){
        $G = M('goods');//实例化产品表
        //页面没有填写编号，自动生成编号
        $sn = date('Ymdhis',time()).rand(10000, 99999);
        $goods_sn = empty($goods_sn)?$sn:$goods_sn;
        //组装条件查询
        if($goods_id) $where = "goods_sn = '$goods_sn' and goods_id <> '$goods_id'";
        else $where = "goods_sn = '$goods_sn'";
        $res = $G->where($where)->getField('goods_id');
        //不能用返回false
        if($res) return false;
        else return $goods_sn;
    }

    /**
     * 获取产品金工石数据
     * @param int $goods_id
     */
    protected function getGoodsMaterialData($goods_id){
        $GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
        $GAI = M('goods_associate_info');//产品材质损耗金重关联表
        $GAD = M('goods_associate_deputystone');//产品副石关联表
        $material['deputystone'] = $GAD->where('goods_id = '.$goods_id.' and agent_id = '.$this->agent_id)->select();
        $join = 'LEFT JOIN zm_goods_material AS gm ON gm.material_id = gai.material_id';
        $goodsMaterialInfo = $GAI->alias('gai')->where('goods_id = '.$goods_id)->join($join)->select();
        $list = $GAL->where('goods_id = '.$goods_id)->select();
        foreach ($goodsMaterialInfo as $key => $value) {
            foreach ($list as $k => $v) {
                if($v['material_id'] == $value['material_id']){
                    $goodsMaterialInfo[$key]['sub'][] = $v;
                }
            }
        }
        $material['list'] = $goodsMaterialInfo;
        return $material;
    }

    /**
     * 添加编辑信息（页面显示）
     * @param number $goods_id
     */
    public function productInfo($goods_id=0) {

        $gsM = M('goods_series');
        $res = $gsM->where($this->buildWhere(''))->select();
        if(!empty($res)){
            $this -> goods_series = $res;
        }else{
            $this -> goods_series = array();
        }
        if ($goods_id) {
            //获取产品信息
            $G = M('goods');//实例化产品表
            $info = $G->where('goods_id = '.$goods_id)->find();
            //获取分类名称
            $GC = M('goods_category');//实例化分类配置表
            $category_info = $GC->alias('gc')->where('gc.category_id = '.$info['category_id'])->find();
            $info['category_name'] = $category_info['category_name'].'('.$category_info['name_alias'].')';
            $this->info = $info;
            //获取图片列表
            $I = M('goods_images');//实例化产品图片表
            $this->imgsList = $I->where('goods_id='.$goods_id)->select();
            $data = M('goods_category')->alias('gc')->field('gc.*,gc.category_name as name_alias')->select();
            $this->categoryList = $this->_arrayRecursive($data, 'category_id', 'pid');
        }else{
            //取到所有分类
            $data   = M('goods_category')->alias('gc')->field('gc.*,gc.category_name as name_alias')->select();
            $this->categoryList = $this->_arrayRecursive($data, 'category_id', 'pid');

        }
        $this->display($this->Template_Path."Goods:productInfo");
    }

    /**
     * 添加编辑产品（实现过程）
     */
    public function productInfoDo($goods_id=0){
        $G = M('goods');//实例化产品表
        $I = M('goods_images');//实例化产品图片表
        $G->startTrans();//开启事务
        //动态验证数组
        $rules = array(
            array('category_id',0,L('text_id_must'),2,'notin'),
            array('goods_name','require',L('text_product_name_must')),
            array('goods_type','require',L('error_product_type_must')),
            ///array('goods_price','require','必须填写产品价格！'),
        );
        //验证公共数据
        $_POST['update_time']  = time();
        if(!$goods_id){
            $_POST['goods_sn'] = $this->checkGoodsSn($_POST['goods_sn'],$goods_id);
            if(!$_POST['goods_sn']) $this->error(L('A72'));
        }
        $_POST['parent_type']  = 1;
        $_POST['parent_id']    = 0;
        $_POST['agent_id']     = $this->agent_id;
        //创建数据，验证字段
        if($G->validate($rules)->create()){
            if($goods_id){
                $res = $G->where('goods_id = '.$goods_id.' and agent_id = '.$this->agent_id)->save();//修改产品
            }else{
                $res = $goods_id = $G->add();//添加产品
            }
        }else{
            $G->rollback();
            $this->error($G->getError());
        }
        if($res){
            //更新产品的图片数据
            foreach (I('post.images') as $key => $image) {
                $I->where('images_id='.$image)->setField('goods_id',$goods_id);
                if(!$key){
                    $thumb = $I->where('goods_id='.$goods_id)->order('images_id ASC')->getField("small_path");
                    $G->where('goods_id='.$goods_id)->setField('thumb',$thumb);
                }
            }
            //修改产品属性数据
            $res = $this->attributeAssociate(I('post.attribute'),I('post.category_id'),$goods_id);
            if(!$res){$G->rollback();$this->log('error', 'A63');}
            if($_POST['goods_type'] == 3){
                //珠宝成品添加Sku
                $res    = $this->goodsSku($goods_id,I('post.sku'));
                $number = 0;
                foreach (I('post.sku') as $key => $value) {
                    $value['goods_number'] = !empty($value['goods_number'])?$value['goods_number']:1;
                    $number += intval($value['goods_number']);
                }
                $G->where('goods_id = '.$goods_id)->setField('goods_number',$number);
                if(!$res){$G->rollback();$this->log('error', 'A63');}
            }elseif ($_POST['goods_type'] == 4){
                //定制货品添加金工石数据
                $res = $this->materialAssociate($goods_id);
                if($res > 0){
                    //有金工石数据，根据金工石数据自动计算价格
                    $G->where('goods_id = '.$goods_id)->setField('goods_price',$res);
                }
            }
            //全部成功提交事务
            $G    -> commit();
            $this -> log('success', 'A65', 'ID:'.$goods_id,U('Goods/productInfo','goods_id='.$goods_id));
        }else{
            $G    -> rollback();
            $this -> log('error', 'A63');
        }
    }

    /**
     * 添加材质数据
     * @param int $material_id
     */
    public function addMaterial($material_id){
        layout(false);
        $GAM = M('goods_material');
        $this->info = $GAM->find($material_id);
        $data = $this->fetch($this->Template_Path.'Goods:addMaterial');
        $this->ajaxReturn($data);
    }

    /**
     * 添加匹配副石
     */
    public function addLuozuanMatch2(){
        layout(false);
        $this->uuid = $this->buildUuid('luozuanMatch2');
        $data = $this->fetch($this->Template_Path.'Goods:addLuozuanMatch2');
        $this->ajaxReturn($data);
    }

    /**
     * 添加裸钻匹配记录
     */
    public function addLuozuanMatch($material_id){
        //裸钻匹配形状
        $GLS = M('goods_luozuan_shape');
        $this->goodsLuozuanShape = $GLS->select();
        layout(false);
        $this->material_id = $material_id;
        $this->uuid = $this->buildUuid('luozuanMatch');
        $data = $this->fetch($this->Template_Path.'Goods:addLuozuanMatch');
        $this->ajaxReturn($data);
    }
    /**
     * 根据父级ID获取属性列表
     */
    public function getAttributeList($pid=0){
        $GA = M('goods_attribute');
        $where = $this->buildWhere().' and pid = '.$pid;
        $data = $GA->where($where)->select();
        $this->ajaxReturn($data);
    }

    /**
     * 产品信息页根据分类ID获取属性分类
     * @param int $id
     * @param int $type 1成品属性2成品规格3定制属性
     * @param int
     */
    public function getGoodsAttribute($category_id,$goods_type,$goods_id=''){
        $GA    = M('goods_attributes');
        $GCA   = M('goods_category_attributes');
        $GAV   = M('goods_attributes_value');
        //按分类和类型获取属性
        if( $goods_type == 3 ){
            $where = 'type = 1';
        } elseif ( $goods_type == 4 ) {
            $where = 'type = 3';
        }
        $gacList = $GCA->where($where.' and category_id = '.$category_id)->select();
        $ids     = $this->parIn($gacList, 'attr_id');
        $list    = $GA->where('attr_id in('.$ids.')')->select();
        $list    = $this->_arrayIdToKey($list);
        $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
        foreach ($attrValueList as $key => $value) {
            $list[$value['attr_id']]['sub'][] = $value;
        }
        //有产品ID获取产品的属性数据
        if($goods_id){
            $GAA = M('goods_associate_attributes');
            $attr = $GAA->where('goods_id = '.$goods_id)->select();
            foreach ($attr as $key => $value) {
                if($list[$value['attr_id']]['input_type'] == 2 or $list[$value['attr_id']]['input_type'] == 1){
                    foreach ($list[$value['attr_id']]['sub'] as $kk => $vv) {
                        if(intval($value['attr_code'])&intval($vv['attr_code'])){
                            $list[$value['attr_id']]['sub'][$kk]['active'] = 1;
                        }
                    }
                }elseif ($list[$value['attr_id']]['input_type'] == 3){
                    if($value['attr_value']){
                        $sub = explode(',', $value['attr_value']);
                        foreach ($sub as $kk => $vv) {
                            $list[$value['attr_id']]['sub'][]['attr_value_name'] = $vv;
                        }
                    }
                }
            }
        }
        $this->list = $list;
        layout(false);
        $data['attrHtml'] = $this->fetch($this->Template_Path.'Goods:GoodsAttr');
        if($goods_type == 3){
            //获取分类成品产品的规格
            $gacList = $GCA->where('category_id = '.$category_id.' and type = 2')->select();
            $ids = $this->parIn($gacList, 'attr_id');
            $list = $GA->where('attr_id in('.$ids.')')->select();
            $list = $this->_arrayIdToKey($list);
            $attrValueList = $GAV->where('attr_id in('.$ids.')')->select();
            foreach ($attrValueList as $key => $value) {
                $list[$value['attr_id']]['sub'][$value['attr_value_id']] = $value;
            }
            //有产品ID获取产品的SKU数据
            if($goods_id){
                $GS = M('goods_sku');
                $sku = $GS->where('goods_id = '.$goods_id)->select();
            }
            //根据SKU给规格做选中
            if($sku){
                foreach ($sku as $key => $value) {
                    $attr = explode('^', $sku[$key]['attributes']);
                    unset($skuTop);
                    foreach ($attr as $kk => $vv) {
                        $active = explode(':', $vv);
                        $skuTop[]['attr_name'] = $list[$active[0]]['attr_name'];
                        if($list[$active[0]]['input_type'] == 2){
                            $list[$active[0]]['sub'][$active[1]]['active'] = 1;
                            $sku[$key]['attr_name'][]['attr_name'] = $list[$active[0]]['sub'][$active[1]]['attr_value_name'];
                        }elseif ($list[$active[0]]['input_type'] == 3){
                            $arr['attr_value_name'] = $active[1];
                            $arr['active'] = 1;
                            $list[$active[0]]['sub'][$active[1]] = $arr;
                            $sku[$key]['attr_name'][]['attr_name'] = $active[1];
                        }
                    }
                }
                $this->sku    = $sku;
                $this->skuTop = $skuTop;
            }
            $this->list = $list;
            layout(false);
            $data['spacHtml'] = $this->fetch($this->Template_Path.'Goods:GoodsSpec');
        }elseif ($goods_type == 4){
            if($goods_id){
                //获取分类定制产品的金工石数据
                $GAL = M('goods_associate_luozuan');//产品材质裸钻关联表
                $GAI = M('goods_associate_info');//产品材质损耗金重关联表
                $GAD = M('goods_associate_deputystone');//产品副石关联表
                $material['deputystone'] = $GAD->where('goods_id = '.$goods_id.' and agent_id = '.$this->agent_id)->select();
                $join = 'LEFT JOIN zm_goods_material AS gm ON gm.material_id = gai.material_id';
                $goodsMaterialInfo = $GAI->alias('gai')->where('goods_id = '.$goods_id)->join($join)->select();
                $list = $GAL->where('goods_id = '.$goods_id)->select();
                foreach ($goodsMaterialInfo as $key => $value) {
                    foreach ($list as $k => $v) {
                        if($v['material_id'] == $value['material_id']){
                            $goodsMaterialInfo[$key]['sub'][] = $v;
                        }
                    }
                }
                $material['list'] = $goodsMaterialInfo;
                $this->material = $material;
                //获取裸钻的形状
                $GLS = M('goods_luozuan_shape');
                $this->goodsLuozuanShape = $GLS->select();
            }
            //获取产品材质数据
            $this->materialList = getGoodsMaterial($this->buildWhere());
            $data['matchHtml'] = $this->fetch($this->Template_Path.'Goods:GoodsMatch');
        }
        $this->ajaxReturn($data);
    }
}