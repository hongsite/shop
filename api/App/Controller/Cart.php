<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 购物车
* @date 2020/10/29
* @version hst-v1.0.0
*/
namespace Hst\Cart;
use Hst\Common;
class Cart extends Common{
	function _init(){
		parent::_init();	
		$this->allowindex = true;
		$this->tb = getcarttb($this->uid);
	}
	function _before_index(){		
		$where = '`A1`.`uid`='.$this->uid.' AND `A1`.`quantity`>0';
		$this->field = '`A1`.`id`,`A1`.`proid`,`A1`.`quantity`,`A1`.`skuid`,`A2`.`title`,IF(`A1`.`skuid`=0,`A2`.`price`,`A3`.`price`) AS `price`,IF(`A1`.`skuid`=0,`A2`.`stock`,`A3`.`stock`) AS `stock`,IF(`A1`.`skuid`=0,\'\',`A3`.`title`) AS `sku`,`A4`.`picurl_0` AS `picurl`';
		$this->detail = array('Pro','ProSku','ProPicurl');
		$this->tbon = array('`A1`.`proid`=`A2`.`id`','`A1`.`skuid`=`A3`.`id`','`A2`.`id`=`A4`.`id`');
		$this->where = $where;
		$this->order = '`A1`.`id` DESC';
		$this->perpage = 20;
	}
	public function getcart_json(){

		
		$js['status'] = 0;
		$js['msg'] = '';

		$isxcx = I('isxcx',1,'');	


		$uid = $this->getuid;

		$list = D('Cart')->getValue($uid,null);		
		
		$shop_arr = null;
		if($list){
			foreach ($list AS $k => $v){			
				$shop_arr[$v['shopid']]['item'][] = $v;
			}
		}
		$objpro = D('Pro');

		if($shop_arr){			
			$m = M('Shop');			
			$i = 0;
			foreach($shop_arr as $k => $v) {
				// 取得商家信息
				$shop =  $m->field('`id`,`shopname`')->where(array('id' => $k))->find();
				$shop['price'] = $objpro->getshopprice($v['item']);
				$shop_arrs[$i]['shop'] = $shop;
				$shop_arrs[$i]['item'] = $v['item'];
				$i++;
			}
			$js['data'] = $shop_arrs;
			$js['status'] = 1;
			$js['msg'] = '获取成功';			
		}else{
			$js['msg'] = '购物车是空的';
		}		
		
		$this->json($js);
		
	}
	public function getcart_nums_json(){

		
		$js['status'] = 0;
		$js['msg'] = '';	

		$uid = $this->huid;

		$nums = D('Cart')->getNums($uid);
		
		if($nums>0){			
			$js['data'] = $nums;
			$js['status'] = 1;			
		}else{
			$js['msg'] = '';
		}		
		$this->json($js);
	}
	//更新数据
	

	//删除数据
	function del_json(){		
		$js['status'] = 0;
		$js['msg'] = '';
		$items = I('items',0,'post');		

		
		if(!$items){
			$js['msg'] = '请选择一个购物车商品删除';
			$this->json($js);
		}

		parent::check_is_login(1);
		$uid = getuid();		
		$tb = getcarttb($uid);
		$map = 'id in('.implode(',',$items).')';		
		$map .= ' AND uid = '.$uid;		

		$mm = M($tb);
		$rts = $mm->where($map)->find();

		$tit = '删除购物车商品';

		if(!$rts){
			$js['msg'] = '可能已经删除过了';
			$this->json($js);
		}		

		$rtcmd = $mm->where($map)->delete();		

		if($rtcmd!==false){			
			$js['msg'] = $tit.'成功';
			$js['data'] = $items;
			$js['status'] = 1;
		}else{
			$js['msg'] = $tit.'失败，请重试';	
		}
		$this->json($js);
	}
}