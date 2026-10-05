<?php
/*
* @function 管理员管理
* @date 2018-04-14
* @version v1.0.0
*/
namespace hst\Cate;
use hst\Common;
class Cate extends Common{
    public function _init() {
        parent::_init();
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowindex = true;
		$this->perpage = 100;
		$field = I('field',0,'');
		$this->tb = $field.'Class';
		
    }
	public function _before_index(){
		$where = '`A1`.`deleted`=0';
		$q = I('q',0);
		if(!$q!=''){
			$where .= ' AND `A1`.`title` like \'%'.$q.'%\'';
		}
		$this->where = $where;
		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`sort`,`A1`.`stat`,`A1`.`icon`,`A1`.`url`,`A1`.`picurl`';		
		$field = I('field',0);
		if($field=='Pro') $this->field .= ',`A1`.`pronums`';
		$this->tb = $field.'Class';
		$this->order = '`A1`.`sort` ASC';
    }	
	function update_json(){
		$js = null;
		$js['msg'] = '';
		$js['status'] = 0;
		$field = I('field',0);

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
				$dataup[$j]['width'] = $width;
				$dataup[$j]['height'] = $height;
				$dataup[$j]['sort'] = $k;
				$dataup[$j]['url'] = $url;
				$dataup[$j]['stat'] = $stat;
				$j++;
			}else{				
				$dataadd[$i]['picurl'] = $picurl;
				$dataadd[$i]['title'] = $title;				
				$dataadd[$i]['width'] = $width;
				$dataadd[$i]['height'] = $height;
				$dataadd[$i]['sort'] = $k;
				$dataadd[$i]['url'] = $url;
				$dataadd[$i]['stat'] = $stat;
				$i++;
			}
		}

		$js['msg'] = '保存成功';

		if($dataup) M('')->saveAll($dataup,$field.'Class');
		if($dataadd) M($field.'Class')->addAll($dataadd);

		$js['status'] = 1;

		$this->json($js);
	}	
}