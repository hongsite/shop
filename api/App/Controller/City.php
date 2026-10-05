<?php
/*
* @author 牛头
* @function 帮助文档
* @date 2024/7/29
* @version v1.0.0
*/
namespace hst\City;
use hst\Common;
class City extends Common{
	function _init(){
		parent::_init();		
	}	
	function province_json(){		
			
		$js['status'] = 0;
		$js['msg'] = '';	
		$js['i'] = I('i',1);
		$value = I('value',1);
		$list = M('Area')->field('id,title,'.$value.' AS `value`')->where('pid=0')->order('sort ASC')->select();
		if($list) {
			$js['status'] = 1;
			$js['data'] = $list;
			$js['msg'] = '加载成功';
		}else {
			$js['msg'] = '加载失败';
			
		}
		$this->json($js);
	}
	function city_json(){
		
		$pid = I('pid',1);
		$value = I('value',1);
		$city = I('city',0);
		$county = I('county',0);
			
		$js['status'] = 0;
		$js['msg'] = '';	
		$js['i'] = I('i',1);
		$js['city'] = $city;
		$js['county'] = $county;
		$list = M('Area')->field('id,title,'.$value.' AS `value`')->where('pid='.$pid)->order('sort ASC')->select();
		if($list) {
			$js['status'] = 1;
			$js['data'] = $list;
			$js['msg'] = '加载成功';
		}else {
			$js['msg'] = '加载失败';
			
		}
		$this->json($js);
	}
}