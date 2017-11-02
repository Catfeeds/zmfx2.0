<?php
/**
 * 手动刷新
 */
namespace Admin\Controller;
use Think\Page;
class RefreshController extends AdminController {

  public function __construct() {
    parent::__construct();
    // isSuperAgent()
  }
  
  //手寸修改
  //Admin/Refresh/is_hand.html
  public function is_hand(){
    $where['ca_name'] = ['in',[
      '女戒','男戒','情侣戒','宝瓶戒指','蝙蝠戒指','元宝戒指',
      '铲币戒指','耳是福戒指','大象戒指','刀币戒指','福袋戒指',
      '净水瓶戒指','立体鼎戒指','立体葫芦戒指','毛笔戒指','平面鼎戒指',
      '平面葫芦戒指','如意戒指','色子戒指','算盘戒指','铜钱币戒指',
    ]];
    $where['pid'] = ['gt','0'];
    M('banfang_cate')->where($where)->save(['is_hand'=>1]);
  }

  //Admin/Refresh/chaxun_zlftp.html
  public function chaxun_zlftp(){
    $banfang_goods = M('banfang_goods')->where(['zlf_id'=>['gt',0]])->select();
    $i = 0;
    foreach ($banfang_goods as $k => $v) {
      if(M('goods_images')->where(['goods_id'=>$v['goods_id']])->find()){
        M('banfang_goods')->where(['goods_id'=>$v['goods_id']])->save(['g_status'=>1]);
        $i ++;
      }else{
        M('banfang_goods')->where(['goods_id'=>$v['goods_id']])->save(['g_status'=>0]);
      }
    }
    echo $i;
  }

  //周六福分类 Admin/Refresh/zlf_fl.html
  public function zlf_fl(){
    $model = M('banfang_cate');
    $banfang_cates = $model->select();
    $cate_arr = array();
    foreach($banfang_cates as $cates){
      if(!empty($cates['zlf_field']) && !empty($cates['zlf_val'])){
        $cate_arr[$cates['zlf_field']][$cates['zlf_val']] = $cates;
      }

    }
    $feild = 'xlmc';
    //bx
    $data =  M('gskhh','zlf_','ZLF_DB')->where([$feild=>['neq','']])->field($feild)->group($feild)->select();
    $save = ['agent_id'=>158,'pid'=>1,'ca_rank'=>1,'top_id'=>1];
    //$save['ca_status'] = 1;
    foreach ($data as $k => $v){
      /*if(strstr($v[$feild],'/')){
        continue;
      }*/
      if(empty($v[$feild])){
        //过滤Null
        continue;
      }
      $save['ca_code'] = $v[$feild];
      $save['ca_name'] = $v[$feild];
      $save['zlf_field'] = $feild;
      $save['zlf_val'] = $v[$feild];
      if(!empty($cate_arr[$feild][$v[$feild]])){
          $model->where(['id'=>$cate_arr[$feild][$v[$feild]]['id']])->save($save);
      }else{
          $model->add($save);
      }
    }
    return;

    //kzsg
    $data =  M('gskhh','zlf_','ZLF_DB')->where(['kzsg'=>['neq','']])->field('kzsg')->group('kzsg')->select();
    $save['agent_id'] = 7;
    $save['ca_status'] = 1;
    $save['pid'] = 6;
    $save['ca_rank'] = 5;
    $save['top_id'] = 6;
    foreach ($data as $k => $v){
      if(strstr($v['kzsg'],'/')){
        continue;
      }
      if(strstr($v['kzsg'],'、')){
        continue;
      }
      if(strstr($v['kzsg'],'，')){
        continue;
      }
      if(strstr($v['kzsg'],' ')){
        continue;
      }
      $save['ca_code'] = $v['kzsg'];
      $save['ca_name'] = $v['kzsg'];
      $save['zlf_field'] = 'kzsg';
      $save['zlf_val'] = $v['kzsg'];

      if($banfang_cate[$v['kzsg']]){
          $model->where(['id'=>$banfang_cate[$v['kzsg']]['id']])->save($save);
      }else{
          $model->add($save);
      }
    }

    //ksxf
    $data =  M('gskhh','zlf_','ZLF_DB')->where(['ksxf'=>['neq','']])->field('ksxf')->group('ksxf')->select();
    $save['agent_id'] = 7;
    $save['ca_status'] = 1;
    $save['pid'] = 6;
    $save['ca_rank'] = 5;
    $save['top_id'] = 6;
    foreach ($data as $k => $v){
      if(strstr($v['ksxf'],'/')){
        continue;
      }
      $save['ca_code'] = $v['ksxf'];
      $save['ca_name'] = $v['ksxf'];
      $save['zlf_field'] = 'ksxf';
      $save['zlf_val'] = $v['ksxf'];

      if($banfang_cate[$v['ksxf']]){
          $model->where(['id'=>$banfang_cate[$v['ksxf']]['id']])->save($save);
      }else{
          $model->add($save);
      }
      // if(strstr($v['ksxf'],'/')){
      //   $arr = explode('/', $v['ksxf']);
      //   // dump($arr);
      //   // exit;
      //   foreach ($arr as $k1 => $v1) {
      //     $save['ca_code'] = $v1;
      //     $save['ca_name'] = $v1;
      //     $save['zlf_val'] = $v1;
      //     if($banfang_cate[$v1]){
      //       dump($save);
      //       $model->where(['id'=>$banfang_cate[$v1]['id']])->save($save);
      //     }else{
      //       dump($save);
      //       $model->add($save);
      //     }
      //   }
      // }else{
        
      // }
    }

    //款式 plmc plbm
    $data =  M('gskhh','zlf_','ZLF_DB')->field('plmc,plbm')->group('plmc,plbm')->select();
    
    $save['agent_id'] = 7;
    $save['ca_status'] = 1;
    $save['pid'] = 4;
    $save['ca_rank'] = 4;
    $save['top_id'] = 4;
    foreach ($data as $k => $v){
      $save['ca_code'] = $v['plbm'];
      $save['ca_name'] = $v['plmc'];
      $save['zlf_field'] = 'plmc';
      $save['zlf_val'] = $v['plmc'];
      if($banfang_cate[$v['plmc']]){
        $model->where(['id'=>$banfang_cate[$v['plmc']]['id']])->save($save);
      }else{
        $model->add($save);
      }
    }
  }

  //Admin/Refresh/goods_category_banfang_mp.html
  public function goods_category_banfang_mp(){
    // $banfang_category = M('banfang_category','zm_','ZMALL_DB')->select();
    // $banfang_jewelry = M('banfang_jewelry','zm_','ZMALL_DB')->select();
    $goods_category = M('goods_category')->select();

    // foreach ($banfang_category as $k => $v) {
    //   $banfang_category_new[$v['category_name_1']]=$v;
    // }

    // foreach ($banfang_jewelry as $k => $v) {
    //   $banfang_jewelry_new[$v['jewelry_name_1']]=$v['jewelry_id'];
    // }

    //键表示banfang 值表示速易宝
    $banfang_category_name = [
      '1'=>'彩宝',
      '2'=>'翡翠',
      '4'=>'配件',
      '5'=>'玉器',
      '6'=>'珍珠',
      '7'=>'钻石',
    ];

    //键表示速易宝 值表示banfang
    $banfang_category_new = [
      '彩宝'=>1,
      '翡翠'=>2,
      '配件'=>4,
      '玉器'=>5,
      '珍珠'=>6,
      '钻石'=>7,
    ];

    //键表示速易宝 值表示banfang
    $banfang_jewelry_new = [
      '吊坠'=>'1,16',
      '耳饰'=>'2,3,4,17,18,19',
      '男戒'=>'6,20',
      '女戒'=>'7,15',
      '情侣对戒'=>'8,21',
      '手链'=>'9,23',
      '手镯'=>'10,24',
      '项链'=>'5,11,22,28',
      '扣头'=>'12',
      '链身'=>'13',
      '胸针'=>'14,25',
    ];

    foreach ($goods_category as $k => $v) {
      if($v['pid'] == 0){
        if($banfang_category_new[$v['category_name']]){
          $bc[$v['category_id']] = $banfang_category_new[$v['category_name']];
        }
      }
    }
    // dump($bc);
    // dump($goods_category);
    // dump($banfang_category_new);
    // dump($banfang_jewelry_new);
    $i = 0;
    foreach ($goods_category as $k => $v) {
      if($v['pid'] > 0){
        if($bc[$v['pid']]&&$banfang_jewelry_new[$v['category_name']]){
          if(!is_numeric($banfang_jewelry_new[$v['category_name']])){
            $arr = explode(',', $banfang_jewelry_new[$v['category_name']]);
            foreach ($arr as $k1 => $v1) {
              $data[$i]['category_id'] = $v['category_id'];
              $data[$i]['banfang_category_id'] = $bc[$v['pid']];
              $data[$i]['banfang_jewely_id'] = $v1;
              $data[$i]['banfang_jewely_name'] = $v['category_name'];
              $data[$i]['banfang_category_name'] = $banfang_category_name[$bc[$v['pid']]];
              $data[$i]['category_name'] = $data[$i]['banfang_category_name'].$data[$i]['banfang_jewely_name'];
              $i++;
            }
            continue;
          }

          $data[$i]['category_id'] = $v['category_id'];
          $data[$i]['banfang_category_id'] = $bc[$v['pid']];
          $data[$i]['banfang_jewely_id'] = $banfang_jewelry_new[$v['category_name']];
          $data[$i]['banfang_jewely_name'] = $v['category_name'];
          $data[$i]['banfang_category_name'] = $banfang_category_name[$bc[$v['pid']]];
          $data[$i]['category_name'] = $data[$i]['banfang_category_name'].$data[$i]['banfang_jewely_name'];
          $i++;
        }
      }
    }

    dump($data);
    $model = M('goods_category_banfang_mp');
    foreach ($data as $k => $v) {
      $where = [];
      $save = [];
      $where['banfang_category_id'] = $v['banfang_category_id'];
      $where['banfang_jewely_id'] = $v['banfang_jewely_id'];
      $find = $model->where($where)->find();

      $save ['banfang_category_id'] = $v['banfang_category_id'];
      $save ['banfang_jewely_id'] = $v['banfang_jewely_id'];
      $save ['category_id'] = $v['category_id'];
      $save ['banfang_jewely_name'] = $v['banfang_jewely_name'];
      $save ['banfang_category_name'] = $v['banfang_category_name'];
      $save ['category_name'] = $v['category_name'];
      if(!$find){
        $model->add($save);
      }else if($find['category_id']!=$v['category_id']){
        $model->where($where)->save($save);
      }else{
        $model->where($where)->save($save);
      }
    }
  }
  //Admin/Refresh/edit_banfang_carat.html
	public function edit_banfang_carat(){
	    $banfang_carat = M('banfang_carat')->where(['zlf_code'=>['gt',0]])->select();
	    foreach ($banfang_carat as $k => $v) {
	      dump($v);
	      $name = explode('-',$v['name']);
	      $name_1 = $name[0];
	      $name_2 = substr($name[1], 0, -2);
	      $save ['carat_section'] = $name_1*100;
	      $save ['carat_from'] = $name_1;
	      $save ['carat_to'] = $name_2;
	      M('banfang_carat')->where(['id'=>$v['id']])->save($save);
	    }
  	}

  //图片重复上传
  //Admin/Refresh/tupianchongfudel.html
  public function tupianchongfudel(){
    $banfang_goods = M('banfang_goods')->where(['zlf_id'=>['gt',0]])->select();
    foreach ($banfang_goods as $k => $v) {
      $goods_images = M('goods_images')->where(['goods_id'=>$v['goods_id']])->select();
      if($goods_images){
        $img_arr = [];
        foreach ($goods_images as $k1 => $v1) {
          $img = substr(strrchr($v1['small_path'],'/'),1);
          if(!in_array($img,$img_arr)){
            $img_arr[] = $img;
          }else{
            M('goods_images')->where(['images_id'=>$v1['images_id']])->delete();
          }
        }
      }
    }
  }
    //图片上传测试
    //Admin/Refresh/tupianceshi.html
	public function tupianceshi(){
        //内网
        //$this->readFileFromDir('/data/htdocs/www/zmfx/Public/zlf');
        //线上
		// $this->readFileFromDir('D:/phpstudy/PHPTutorial/WWW/zlf/');
		//$this->readFileFromDir('/home/www/html/zmfx/Public/customer/zlf/zlfnum2');
		// $this->readFileFromDir('/home/www/html/zmfx/Public/customer/zlf/2017-9-26');
    // $this->readFileFromDir('/home/www/html/zmfx/Public/customer/zlf/2017-9-27');
    if(I('riqi')){
      $this->readFileFromDir('/home/www/html/zmfx/Public/customer/zlf/'.I('riqi'));
    }
    
	}

	function readFileFromDir($dir,$data){
       if(!is_dir($dir))
         return false;
       $handle=opendir($dir);          //打开目录
       while(($file=readdir($handle))!==false)
       {
           if($file=='.'||$file=='..')
           {
              continue;
           }
           $file=$dir.DIRECTORY_SEPARATOR.$file;
           if(is_file($file))                 //是文件就输出
           {
              $goods_sn = substr(strrchr($dir,'/'),1);//linux
           	  // $goods_sn = substr(strrchr($dir,'\/'),1);//windows
           	  $img_src = $file;
           	  $banfang_goods = M('banfang_goods')->where(['goods_sn'=>$goods_sn])->find();
              if($banfang_goods){
                $new_body = substr(strrchr($dir,'/'),1);//linux
                // $new_body = substr(strrchr($img_src,'\/'),1);//windows
                $where['small_path'] = ['like','%'.$new_body];
                $where['goods_id'] = $banfang_goods['goods_id'];
                $b = M('goods_images')->where($where)->find();
           	  	if(!$b){
                  $sjk_img = $this->auto_save_image($img_src);
           	  		$imgData['small_path']  = $sjk_img;
	                // 保存图片数据
	                $imgData['goods_id']    = $banfang_goods['goods_id'];
	                $imgData['big_path']    = $sjk_img;
					        $imgData['agent_id']    = C("agent_id");
	                $imgData['create_time'] = time();
	                $imgID = M('goods_images')->add($imgData);
           	  	}
           	  	// exit;
           	  }
           	  
           	  print $goods_sn.'<br/>';
              print $file.'<br/>';
           }
           elseif(is_dir($file))
           {
            	print $file.'<br/>';
            	print substr(strrchr($file,'/'),1).'<br/>';
             	$this->readFileFromDir($file,$data);          //递归查询
           }
       }
       closedir($dir);                 //关闭目录
    }

    function auto_save_image($img_src){
        $imgPath = "Public/Uploads/zlf/".date("Y-m-d")."/";
        if(!is_dir($imgPath)) @mkdir($imgPath,0777);
        $milliSecond = strftime("%H%M%S",time());
        $new_body = substr(strrchr($img_src,'/'),1);//linux
        // $new_body = substr(strrchr($img_src,'\/'),1);//windows
        $bool = copy($img_src,$imgPath.$new_body);
        if($bool){
        	return "./Uploads/zlf/".date("Y-m-d")."/".$new_body;
        }
    }
}