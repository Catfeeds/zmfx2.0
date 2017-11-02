<?php
/**
	定义通用挂件
**/
namespace App\Widget;
use Think\Controller;

class FrameWidget extends Controller {
	public function footer($active){
		$this->active = $active;
		$this->display('Public:footer');
	}
}