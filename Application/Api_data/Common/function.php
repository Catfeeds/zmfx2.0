<?php 
/**
 * ��ȡ��Ʒ�ӵ�
 * @param array $domain
 * @param int   $goods_type ��Ʒ����
 * @param int $type �ӵ�����1.�ɹ��ӵ�2�����ۼӵ�
 */
function getAdvantage($domain,$goods_type=1,$type=2){
    if($goods_type == 1){
        $config_key = 'luozuan_advantage';
    }elseif ($goods_type == 2){
        $config_key = 'sanhuo_advantage';
    }else{
        $config_key = 'consignment_advantage';
    }
    $advantage = C($config_key);
    if($type == 2){//���ۼӵ�
        if($domain['trader_type'] == 2){
            return $advantage;
        }elseif ($domain['trader_type'] == 3){
            //��ȡ�ϼ��ӵ�
            $T = M('trader');
            $pid = $T->where('tid = '.$domain['trader_id'])->getField('parent_id');
            $C = M('config_value');
            $where = 'parent_type = 1 and parent_id= '.$pid." and config_key = '$config_key' and agent_id = ".C('agent_id');
            $consignment_advantage = $C->where($where)->getField('config_value');
            $advantage             = $advantage + $consignment_advantage;
            return $advantage;
        }else{
            return 0;
        }
    }else{//�ɹ��ӵ�
        if ($domain['trader_type'] == 3){
            //��ȡ�ϼ��ӵ�
            $T = M('trader');
            $pid = $T->where('tid = '.$domain['trader_id'])->getField('pid');
            $C = M('config_value');
            $advantage = $C->where('parent_type = 1 and parent_id= '.$pid.' and agent_id = '.C('agent_id'))->getField($config_key);
            return $advantage;
        }else{
            return 0;
        }
    }
}









?>