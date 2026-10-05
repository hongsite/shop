<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 产品管理
*@date 2022/10/13
*@version hst-v1.0.0
*/
namespace hst\Catelist;
use hst\Common;
class Catelist extends Common{		
	function _init(){
		parent::_init();		
	}
	function index(){
		$js = null;
		$js['msg'] = '';
		$js['status'] = 0;
		$list = M(MODULE_NAME)->order('sort ASC')->select();
		if($list){
			$js['status'] = 1;
			$js['data'] = $list;
		}
		$this->json($js);
	}
	function update_json(){
		$js = null;
		$js['msg'] = '';
		$js['status'] = 0;

		$batch = I('batch',0,'post');
		if(!$batch){
			$js['msg'] = '无提交数据';
			$this->json($js);
		}

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}

		$data = null;

		$dataup = null;
		$dataadd = null;
		$i = 0;
		$j = 0;

		foreach($batch as $k=>$v){
			$id = I('id_'.$v,1,'post');
			$picurl = I('picurl_'.$v,0,'post');
			$url = I('url_'.$v,0,'post');
			$title = I('title_'.$v,0,'post');
			$stat = I('stat_'.$v,1,'post');
			$tid = I('tid_'.$v,1,'post');
			$width = I('width_'.$v,1,'post');
			$height = I('height_'.$v,1,'post');
			if($id>0){
				$dataup[$j]['id'] = $id;
				$dataup[$j]['picurl'] = $picurl;
				$dataup[$j]['title'] = $title;
				$dataup[$j]['stat'] = $stat;
				$dataup[$j]['tid'] = $tid;
				$dataup[$j]['width'] = $width;
				$dataup[$j]['height'] = $height;
				$dataup[$j]['sort'] = $k;
				$dataup[$j]['url'] = $url;
				$j++;
			}else{				
				$dataadd[$i]['picurl'] = $picurl;
				$dataadd[$i]['title'] = $title;
				$dataadd[$i]['stat'] = $stat;
				$dataadd[$i]['tid'] = $tid;
				$dataadd[$i]['width'] = $width;
				$dataadd[$i]['height'] = $height;
				$dataadd[$i]['sort'] = $k;
				$dataadd[$i]['url'] = $url;
				$i++;
			}
		}

		$js['msg'] = '保存成功';

		if($dataup) M('')->saveAll($dataup,MODULE_NAME);
		if($dataadd) M(MODULE_NAME)->addAll($dataadd);

		$js['status'] = 1;

		$this->json($js);
	}
	function delete_json(){
		$js = null;
		$js['msg'] = '';
		$js['status'] = 0;

		$id = I('id',1,'post');
		if($id<1){
			$js['msg'] = '数据不存在';
			$this->json($js);
		}

		$where = 'id='.$id;

		$m = M(MODULE_NAME);

		$rt = $m->where($where)->find();
		if(!$rt){
			$js['msg'] = '数据不存在';
			$this->json($js);
		}

		$rtcmd = $m->where($where)->delete();

		if($rtcmd!==false){
			$js['status'] = 1;
			$js['msg'] = '删除成功';
		}else{
			$js['msg'] = '删除失败';
		}		

		$this->json($js);
	}
}