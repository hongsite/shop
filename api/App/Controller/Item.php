<?php
/*
* @author 牛头
* @function 帮助文档
* @date 2024/7/29
* @version v1.0.0
*/
namespace hst\Item;
use hst\Common;
class Item extends Common{
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;

		$this->pricearr = array('yprice','price');
	}	

	function _before_index(){
		$uid = getuid();
		$where = '`A1`.`deleted`=0';
		$q = I('q',0);
		$code = I('code',0);
		if($q!=''){
			if(strlen($q)==4 && is_numeric($q)){
				$where .= ' AND RIGHT(`A3`.`mobile`,4) = \''.$q.'\'';
			}else if(checkMobile($q)){
				$where .= ' AND `A3`.`mobile` = '.$q;
			}else{
				$where .= ' AND `A1`.`title` like \'%'.$q.'%\'';
			}
		}
		switch ($code){
			case 'wait':
				$where .= ' AND `A1`.`stat`=1';
				break;
			case 'ing':
				$where .= ' AND `A1`.`stat`=3';
				break;
			case 'success':
				$where .= ' AND `A1`.`stat`=5';
				break;
		}

		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`addtime`,`A1`.`gg`,`A1`.`price`,`A1`.`stock`,`A1`.`stockfz`,`A1`.`gysnums`';		
		$this->where = $where;
		$this->order = '`A1`.`id` DESC';
	}

	function _after_update($id=0){
		$gysarr = I('i',0,'post');			
		$kkk = 0;
		$jjj = 0;
		$dataall = null;
		$saveall = null;
		$nowt = time();
		if($gysarr){
			foreach($gysarr as $kk=>$vv){
				$company = I('company_'.$vv,0,'post');
				$mobile = I('mobile_'.$vv,0,'post');
				$price = I('price_'.$vv,1,'post');
				$gysid = I('id_'.$vv,1,'post');
				$price *= 100;
				if($gysid==0){
					$dataall[$kkk]['itemid'] = $id;
					$dataall[$kkk]['company'] = $company;
					$dataall[$kkk]['mobile'] = $mobile;
					$dataall[$kkk]['price'] = $price;					
					$dataall[$kkk]['sort'] = $kk+1;
					$dataall[$kkk]['addtime'] = $nowt;
					$kkk++;
				}else{
					$saveall[$jjj]['id'] = $gysid;
					$saveall[$jjj]['company'] = $company;
					$saveall[$jjj]['mobile'] = $mobile;
					$saveall[$jjj]['price'] = $price;
					$saveall[$jjj]['sort'] = $kk+1;
					$jjj++;
				}
			}
		}
		
		if($dataall) M('Gys')->addAll($dataall);
		if($saveall) M('')->saveAll($saveall,'Gys');

		$gysnums = M('Gys')->where('itemid='.$id.' AND deleted=0')->count();
		M('Item')->where('id='.$id)->setField('gysnums',$gysnums);
	}
	function gys_json(){		
		$uid = getuid();
		$id = I('id',1,'post');	
		$itemid = I('itemid',1,'post');	
		$js['status'] = 0;
		$js['msg'] = '';		
		$m = M('Gys');
		$rt = $m->field('id,company')->where('id='.$id)->find();
		if(!$rt){
			$js['msg'] = '数据不存在';
			$this->json($js);
		}		


		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}		

		$rtcmd = $m->where('id='.$id)->setField('deleted',1);		

		if($rtcmd!==false){
			$js['status'] = 1;
			$js['data'] = $id;
			$js['msg'] = '删除成功';			

		}
		$this->json($js);
	}

	public function _after_edit($rt=null){
		$mm = M('Gys');
		$id = $rt['id'] ?? 0;
		$detail = $mm->field('id,company,mobile,sort,addtime,price')->where('itemid='.$id.' AND deleted=0')->limit(100)->select();
		$rt['detail'] = $detail;
		return $rt;
	}

}