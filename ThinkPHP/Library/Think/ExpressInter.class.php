<?php
/**
 * 快递查询接口
 * 业务判断层在控制器，数据处理层在此
 * zhy	find404@foxmail.com
 * 2016年12月29日 15:56:22
 */
namespace Think;
class ExpressInter{


	const EXP_INTER_URL 	= 'https://m.kuaidi100.com/query'; 
	const EXP_INTER_TYPE 	= '?type=';
	const EXP_INTER_POSTID  = '&postid=';	
	 
    /**
     * 获取快递别名
     * zhy	find404@foxmail.com
     * 2016年12月29日 15:56:22
     */	 
  	static public function get_name($name){
		$type		= '';
		$name_data	= self::get_expert_data();
        $callback   = function ($closure_data) use ($name, &$type){
						if($closure_data['name']==$name){
							$type=$closure_data['as_name'];
					    }
        };
		array_walk($name_data, $callback);
		return $type;
    }

    /**
     * 获取快递数据
     * zhy	find404@foxmail.com
     * 2016年12月29日 15:56:22
     */	
    static public function get_data($order_express){
		$data=array();
		$type=self::get_name($order_express['company']);
		if($type){
			$now_time		=	time ();
			//如果为状态为0，是第三方的时候，进来此处查询，6小时以内用，缓存，6小时之后用新数据。
			if($order_express['qtime']){
				$endtime = $now_time - $order_express['qtime'];
				if($endtime < 21600) {
					$data=unserialize($order_express['data']);
				}
			}
			if(empty($data)){
				$url=ExpressInter::EXP_INTER_URL.ExpressInter::EXP_INTER_TYPE.$type.ExpressInter::EXP_INTER_POSTID.$order_express['number'];
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.112.143', 'CLIENT-IP:220.181.112.143'));
				curl_setopt($curl, CURLOPT_REFERER, "http://www.baidu.com/");
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 0);
				curl_setopt($curl, 156, 99999); // problem solved
				$data = curl_exec($curl);
				curl_close($curl);			 
				if($data){
					$json_data = json_decode($data);
					unset($data);
					if($json_data->data){
						$callback =function ($closure_data) use (&$data){
										$data[]=(array)$closure_data;
						};
						array_walk($json_data->data,$callback);
						$save_data = array('qtime'=>$now_time,'data'=>serialize($data));
						$oe				= M('order_express');
						$oe-> where('id='.$order_express['id'])->setField($save_data);						
					}
				}else{
					$data=array();
				}
			}
		}
		return $data;
    }	 	 

	
    /**
     * 快递名称数据源头
     * zhy	find404@foxmail.com
     * 2016年12月29日 15:56:22
     */
	static public function get_expert_data() {
        $name_data	= array(
						array(
							'name' 			=> '申通快递',
							'as_name' 		=> 'shentong',
						),
						array(
							'name' 			=> 'EMS',
							'as_name' 		=> 'ems',
						),
						array(
							'name' 			=> '顺丰速运',
							'as_name' 		=> 'shunfeng',
						),
						array(
							'name' 			=> '圆通速递',
							'as_name' 		=> 'yuantong',
						),
						array(
							'name' 			=> '韵达快运',
							'as_name' 		=> 'yunda',
						),
						array(
							'name' 			=> '中通快递',
							'as_name' 		=> 'zhongtong',
						),
						array(
							'name' 			=> '百世快递',
							'as_name' 		=> 'huitongkuaidi',
						),
						array(
							'name' 			=> '天天快递',
							'as_name' 		=> 'tiantian',
						),
						array(
							'name' 			=> '宅急送',
							'as_name' 		=> 'zhaijisong',
						),
						array(
							'name' 			=> '邮政国内包裹',
							'as_name' 		=> 'youzhengguonei',
						),
						array(
							'name' 			=> '邮政国际包裹',
							'as_name' 		=> 'youzhengguoji',
						),
						array(
							'name' 			=> 'EMS国际快递',
							'as_name' 		=> 'emsguoji',
						),
						array(
							'name' 			=> 'AAE-中国',
							'as_name' 		=> 'aae',
						),
						array(
							'name' 			=> '安捷快递',
							'as_name' 		=> 'anjiekuaidi',
						),
						array(
							'name' 			=> '安信达',
							'as_name' 		=> 'anxindakuaixi',
						),
						array(
							'name' 			=> '包裹/平邮/挂号信',
							'as_name' 		=> 'youzhengguonei',
						),		
						array(
							'name' 			=> 'BHT国际快递',
							'as_name' 		=> 'bht',
						),		
						array(
							'name' 			=> '百福东方',
							'as_name' 		=> 'baifudongfang',
						),		
						array(
							'name' 			=> 'CCES（希伊艾斯）',
							'as_name' 		=> 'cces',
						),		
						array(
							'name' 			=> '成都立即送',
							'as_name' 		=> 'lijisong',
						),		
						array(
							'name' 			=> 'DHL',
							'as_name' 		=> 'dhl',
						),		
						array(
							'name' 			=> 'DHL德国',
							'as_name' 		=> 'dhlde',
						),		
						array(
							'name' 			=> 'D速物流',
							'as_name' 		=> 'dsukuaidi',
						),		
						array(
							'name' 			=> '德邦物流',
							'as_name' 		=> 'debangwuliu',
						),
						array(
							'name' 			=> '大田物流',
							'as_name' 		=> 'datianwuliu',
						),
						array(
							'name' 			=> 'DPEX',
							'as_name' 		=> 'dpex',
						),
						array(
							'name' 			=> 'EMS - 国内',
							'as_name' 		=> 'ems',
						),
						array(
							'name' 			=> 'EMS - 国际',
							'as_name' 		=> 'emsguoji',
						),	
						array(
							'name' 			=> 'EMS - 国际',
							'as_name' 		=> 'emsguoji',
						),	
						array(
							'name' 			=> 'E邮宝',
							'as_name' 		=> 'ems',
						),	
						array(
							'name' 			=> '凡客',
							'as_name' 		=> 'rufengda',
						),	
						array(
							'name' 			=> 'FedEx - 美国',
							'as_name' 		=> 'fedexus',
						),	
						array(
							'name' 			=> 'FedEx - 国际',
							'as_name' 		=> 'fedex',
						),	
						array(
							'name' 			=> 'FedEx - 国内',
							'as_name' 		=> 'lianbangkuaidi',
						),	
						array(
							'name' 			=> '飞康达',
							'as_name' 		=> 'feikangda',
						),		
						array(
							'name' 			=> '挂号信',
							'as_name' 		=> 'youzhengguonei',
						),	

						array(
							'name' 			=> '能达速递',
							'as_name' 		=> 'ganzhongnengda',
						),	
						array(
							'name' 			=> '共速达',
							'as_name' 		=> 'gongsuda',
						),	
						array(
							'name' 			=> 'GLS',
							'as_name' 		=> 'gls',
						),	
						array(
							'name' 			=> '海航天天',
							'as_name' 		=> 'tiantian',
						),	
						array(
							'name' 			=> '百世快递',
							'as_name' 		=> 'huitongkuaidi',
						),	
						array(
							'name' 			=> '华宇物流',
							'as_name' 		=> 'tiandihuayu',
						),	
						array(
							'name' 			=> '恒路物流',
							'as_name' 		=> 'hengluwuliu',
						),	
						array(
							'name' 			=> '华夏龙',
							'as_name' 		=> 'huaxialongwuliu',
						),		
						array(
							'name' 			=> '佳吉快运',
							'as_name' 		=> 'jiajiwuliu',
						),		
						array(
							'name' 			=> '嘉里大通',
							'as_name' 		=> 'jialidatong',
						),		
						array(
							'name' 			=> '佳怡物流',
							'as_name' 		=> 'jiayiwuliu',
						),		
						array(
							'name' 			=> '京广速递',
							'as_name' 		=> 'jinguangsudikuaijian',
						),							
						array(
							'name' 			=> '金大物流',
							'as_name' 		=> 'jindawuliu',
						),		
						array(
							'name' 			=> '晋越快递',
							'as_name' 		=> 'jinyuekuaidi',
						),		
						array(
							'name' 			=> '急先达',
							'as_name' 		=> 'jixianda',
						),		
						array(
							'name' 			=> '加运美',
							'as_name' 		=> 'jiayunmeiwuliu',
						),		
						array(
							'name' 			=> '快捷速递',
							'as_name' 		=> 'kuaijiesudi',
						),
						array(
							'name' 			=> '跨越速运',
							'as_name' 		=> 'kuayue',
						),
						array(
							'name' 			=> '联邦快递',
							'as_name' 		=> 'lianbangkuaidi',
						),
						array(
							'name' 			=> '龙邦速递',
							'as_name' 		=> 'longbanwuliu',
						),
						array(
							'name' 			=> '蓝镖快递',
							'as_name' 		=> 'lanbiaokuaidi',
						),
						array(
							'name' 			=> '立即送',
							'as_name' 		=> 'lijisong',
						),
						array(
							'name' 			=> '乐捷递',
							'as_name' 		=> 'lejiedi',
						),
						array(
							'name' 			=> '联昊通',
							'as_name' 		=> 'lianhaowuliu',
						),
						array(
							'name' 			=> '民航快递',
							'as_name' 		=> 'minghangkuaidi',
						),
						array(
							'name' 			=> '美国快递',
							'as_name' 		=> 'meiguokuaidi',
						),
						array(
							'name' 			=> '门对门',
							'as_name' 		=> 'menduimen',
						),
						array(
							'name' 			=> 'OCS',
							'as_name' 		=> 'ocs',
						),
						array(
							'name' 			=> '全峰快递',
							'as_name' 		=> 'quanfengkuaidi',
						),
						array(
							'name' 			=> '全一快递',
							'as_name' 		=> 'quanyikuaidi',
						),
						array(
							'name' 			=> '全晨快递',
							'as_name' 		=> 'quanchenkuaidi',
						),
						array(
							'name' 			=> '全际通',
							'as_name' 		=> 'quanjitong',
						),
						array(
							'name' 			=> '全日通',
							'as_name' 		=> 'quanritongkuaidi',
						),
						array(
							'name' 			=> '申通E物流',
							'as_name' 		=> 'shentong',
						),
						array(
							'name' 			=> '申通快递',
							'as_name' 		=> 'shentong',
						),
						array(
							'name' 			=> '速尔快递',
							'as_name' 		=> 'suer',
						),
						array(
							'name' 			=> '盛辉物流',
							'as_name' 		=> 'shenghuiwuliu',
						),
						array(
							'name' 			=> '盛丰物流',
							'as_name' 		=> 'shengfengwuliu',
						),
						array(
							'name' 			=> '上大国际',
							'as_name' 		=> 'shangda',
						),
						array(
							'name' 			=> '三态速递',
							'as_name' 		=> 'santaisudi',
						),
						array(
							'name' 			=> '山东海红',
							'as_name' 		=> 'haihongwangsong',
						),
						array(
							'name' 			=> '赛澳递',
							'as_name' 		=> 'saiaodi',
						),
						array(
							'name' 			=> 'TNT',
							'as_name' 		=> 'tnt',
						),
						array(
							'name' 			=> '天地华宇',
							'as_name' 		=> 'tiandihuayu',
						),
						array(
							'name' 			=> '通和天下',
							'as_name' 		=> 'tonghetianxia',
						),
						array(
							'name' 			=> 'UPS',
							'as_name' 		=> 'ups',
						),
						array(
							'name' 			=> 'USPS（美国邮政）',
							'as_name' 		=> 'usps',
						),
						array(
							'name' 			=> '万家物流',
							'as_name' 		=> 'wanjiawuliu',
						),
						array(
							'name' 			=> '万象物流',
							'as_name' 		=> 'wanxiangwuliu',
						),
						array(
							'name' 			=> '微特派',
							'as_name' 		=> 'weitepai',
						),
						array(
							'name' 			=> '鑫飞鸿',
							'as_name' 		=> 'saiaodi',
						),
						array(
							'name' 			=> '新邦物流',
							'as_name' 		=> 'xinbangwuliu',
						),
						array(
							'name' 			=> '信丰物流',
							'as_name' 		=> 'xinfengwuliu',
						),
						array(
							'name' 			=> '希伊艾斯（CCES）',
							'as_name' 		=> 'cces',
						),
						array(
							'name' 			=> '远成物流',
							'as_name' 		=> 'yuanchengwuliu',
						),
						array(
							'name' 			=> '亚风速递',
							'as_name' 		=> 'yafengsudi',
						),
						array(
							'name' 			=> '源伟丰',
							'as_name' 		=> 'yuanweifeng',
						),
						array(
							'name' 			=> '优速快递',
							'as_name' 		=> 'youshuwuliu',
						),
						array(
							'name' 			=> '元智捷诚',
							'as_name' 		=> 'yuanzhijiecheng',
						),
						array(
							'name' 			=> '越丰物流',
							'as_name' 		=> 'yuefengwuliu',
						),
						array(
							'name' 			=> '源安达',
							'as_name' 		=> 'yuananda',
						),
						array(
							'name' 			=> '原飞航',
							'as_name' 		=> 'yuanfeihangwuliu',
						),
						array(
							'name' 			=> '银捷速递',
							'as_name' 		=> 'yinjiesudi',
						),
						array(
							'name' 			=> '运通中港',
							'as_name' 		=> 'yuntongkuaidi',
						),
						array(
							'name' 			=> '中铁快运',
							'as_name' 		=> 'zhongtiewuliu',
						),
						array(
							'name' 			=> '中铁物流',
							'as_name' 		=> 'ztky',
						),
						array(
							'name' 			=> '中邮物流',
							'as_name' 		=> 'zhongyouwuliu',
						),
						array(
							'name' 			=> '芝麻开门',
							'as_name' 		=> 'zhimakaimen',
						),
						array(
							'name' 			=> '忠信达',
							'as_name' 		=> 'zhongxinda',
						),
						array(
							'name' 			=> '郑州建华',
							'as_name' 		=> 'zhengzhoujianhua',
						),					
				);
		return $name_data;
    }

}

?>