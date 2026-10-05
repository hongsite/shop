<?php
/*
* @author 牛头
* @function 首页
* @date 2022/03/14
* @version v1.0.0
*/
namespace Hst\Api;
use Hst\Common;
class Api extends Common{
	private $sessionKey = '';
	private $grant_type = 'authorization_code';
    public function _init() {
        parent::_init();
		$this->tb = 'Pro';
		$this->allowindex = true;
    }
	function _before_index(){
		$sort = I('sort',0);
		$q = I('q',0);
		$cid = I('cid',1);
		if($sort=='') $sort = '`A1`.`id` DESC';
		switch ($sort){
			case '':
				$sort = '`A1`.`id` DESC';
				break;
			case 'sale-desc':
				$sort = '`A1`.`saled` DESC';
				break;
			case 'price-asc':
				$sort = '`A1`.`price` ASC';
				break;
			case 'price-desc':
				$sort = '`A1`.`price` DESC';
				break;
		}
		$where = '`A1`.`stat`=0 AND `A1`.`deleted`=0';
		if($q!='') $where .= ' AND `A1`.`title` like \'%'.$q.'%\'';
		if($cid>0) $where .= ' AND `A1`.`cid` in('.$cid.')';
		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`price`,`A1`.`saled`,`A1`.`stock`,`A1`.`saled`,`A2`.`picurl_0` AS `picurl`,`A2`.`w_0` AS `width`,`A2`.`h_0` AS `height`,`A1`.`addtime`';
		$this->detail = array('ProPicurl');
		$this->tbon = array('`A1`.`id`=`A2`.`id`');
		$this->where = $where;
		$this->order = $sort;
		$this->perpage = 15;
		$this->showsql = true;
	}
	function _after_index_js($js=null){
		$userinfo = $this->userinfo;
		$js['info'] = array('userinfo'=>$this->userinfo);
		return $js;
	}
	function index_json(){
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';

		$where = '`A1`.`stat`=0 AND (`A1`.`isindexhot`=1 or `A1`.`isindexqg`=1)';
		$field = '`A1`.`id`,`A1`.`title`,`A1`.`price`,`A1`.`yprice`,`A1`.`isindexhot`,`A1`.`isindexqg`,`A1`.`maxnums`,`A1`.`saled`,`A1`.`stock`,`A1`.`cid`,`A2`.`picurl_0` AS `picurl`,`A2`.`w_0` AS `width`,`A2`.`h_0` AS `height`';
		$arr['name'] = array('Pro','ProPicurl');			
		$arr['on'] = array('`A1`.`id`=`A2`.`id`');
		$arr['field'] = $field;
		$arr['where'] = $where;
		$arr['limit'] = 100;
		$arr['order'] = '`A1`.`id` DESC';		
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);

		$focuslist = M('Focus')->field('id,title,url,picurl,width,height,tid')->where('stat=1')->order('sort ASC')->select();
		$catelist = M('Catelist')->field('id,title,url,picurl,width,height')->where('stat=1')->order('sort ASC')->select();

		$js['focus'] = $focuslist;
		$js['catelist'] = $catelist;
		$js['proclass'] = M('ProClass')->field('id,title')->order('sort ASC')->where('deleted=0 AND stat=1')->select();

		$isgonggao = $this->shopinfo['isgonggao'] ?? 0;
		$isopen = $this->shopinfo['isopen'] ?? 0;
		$gonggao = $this->shopinfo['gonggao'] ?? '';
		$openurl = $this->shopinfo['openurl'] ?? '';
		$openpicurl = $this->shopinfo['openpicurl'] ?? '';
		$openpicurl_width = $this->shopinfo['openpicurl_width'] ?? 200;
		$openpicurl_height = $this->shopinfo['openpicurl_height'] ?? 200;

		$js['shopinfo'] = array('isgonggao'=>$isgonggao,'isopen'=>$isopen,'gonggao'=>$gonggao,'openurl'=>$openurl,'openpicurl'=>$openpicurl,'openpicurl_width'=>$openpicurl_width,'openpicurl_height'=>$openpicurl_height);

		$uid = getuid();
		if($uid>0){
			$js['userinfo'] = $this->userinfo;
		}

		if($list){
			$js['status'] = 1;
			$js['data'] = $list;
		}
		$this->json($js);
	}
	function item_json(){
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';

		$id = I('id',1);
		$rt = $this->get_item($id);
		$focus = null;
		if($rt){
			$picurl = $rt['picurl'] ?? '';
			$width = $rt['width'] ?? 800;
			$height = $rt['height'] ?? height;
			if($picurl!='') $focus[] = array('picurl'=>$picurl,'width'=>$width,'height'=>$height);
			$picurl = $rt['picurl_1'] ?? '';
			if($picurl!='') $focus[] = array('picurl'=>$picurl,'width'=>$width,'height'=>$height);

			$picurl = $rt['picurl_2'] ?? '';
			if($picurl!='') $focus[] = array('picurl'=>$picurl,'width'=>$width,'height'=>$height);

			$picurl = $rt['picurl_3'] ?? '';
			if($picurl!='') $focus[] = array('picurl'=>$picurl,'width'=>$width,'height'=>$height);

			$picurl = $rt['picurl_4'] ?? '';
			if($picurl!='') $focus[] = array('picurl'=>$picurl,'width'=>$width,'height'=>$height);

			$js['status'] = 1;
			
		}
		$js['focus'] = $focus;
		$js['skulist'] = M('ProSku')->field('id,title,price,stock')->where('proid='.$id.' AND deleted=0')->select();
		$js['despic'] = M('ProXqpic')->field('id,picurl,width,height')->order('sort ASC')->where('rootid='.$id)->select();
		$perty = M('ProPerty')->where('id='.$id)->getField('perty');
		$js['pertylist'] = $perty !== null ? json_decode($perty,true) : array();
		$rt['isfav'] = 0;
		

		$uid = $this->uid;
		if($uid>0){
			$stat = $rt['stat'] ?? 0;
			if($stat==0){
				$rts = M('History')->where('uid='.$uid.' AND proid='.$id)->find();
				if(!$rts){
					$data = null;
					$data['uid'] = $uid;
					$data['proid'] = $id;
					$data['addtime'] = time();
					M('History')->add($data);
				}
			}
			$isfavrt = M('Fav')->where('id,isfav')->where('uid='.$uid.' AND proid='.$id)->find();
			$isfav = $isfavrt['isfav'] ?? 0;
			$rt['isfav'] = $isfav;
			$js['userinfo'] = $this->userinfo;
		}
		$js['commentNums'] = 2;
		$commentlist = null;		
		//$commentlist[] = array('id'=>1,'nickname'=>'牛头','face'=>'face.png','content'=>'商品很不错，质量很好，推荐购买！','imglist'=>array('01.jpg','02.jpg'),'addtime'=>1787193540);
		//$commentlist[] = array('id'=>2,'nickname'=>'牛头','face'=>'face.png','content'=>'性价比很高，物流速度快，非常满意','imglist'=>[],'addtime'=>1786494660);

		$js['commentlist'] = $commentlist;		

		$js['data'] = $rt;

		$this->json($js);
	}
	//购买检测
	function buy_json(){
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';
		$id = I('id',1,'post');
		$skuid = I('skuid',1,'post');	
		$title = I('title',0,'post');
		$quantity = I('quantity',1,'post');
		$sku = I('sku',0,'post');

		$info = $this->check_sku($id,$skuid,$quantity,$sku);
		if($info['status']===false){
			$js['msg'] = $info['msg'];
			$this->json($js);
		}
		$rt = $info['rt'];		
		if($rt){
			$js['status'] = 1;
			$js['data'] = $rt;
		}
		$js['quantity'] = $quantity;
		$this->json($js);
	}
	//收藏
	function fav_json(){
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';
		$id = I('id',1,'post');
		$isfav = I('isfav',1,'post');
		if($id<1){
			$js['msg'] = '数据错误';
			$this->json($js);
		}

		$rt = M('Pro')->field('id,title,stat')->where('id='.$id)->find();
		if(!$rt){
			$js['msg'] = '数据错误';
			$this->json($js);
		}
		if($rt['stat']!=0){
			$js['msg'] = '商品可能已下架';
			$this->json($js);
		}
		$uid = $this->uid;
		$m = M('Fav');
		$where = 'uid='.$uid.' AND proid='.$id;
		
		$rts = $m->field('id,isfav')->where($where)->find();
		if(!$rts){
			$data = null;
			$data['addtime'] = time();
			$data['uid'] = $uid;
			$data['proid'] = $id;			
			$data['isfav'] = $isfav;
			$rtcmd = $m->add($data);
			if($rtcmd===false){
				$js['msg'] = '收藏失败，请重试';
				$this->json($js);
			}
			$rts = $m->field('id,isfav')->where('id='.$rtcmd)->find();
		}
		$isfav = $rts['isfav'] ?? 0;
		$isfav = $isfav==1 ? 0:1;
		$rtcmd = $m->where('id='.$rts['id'])->setField('isfav',$isfav);

		$js['isfav'] = $isfav;
				
		if($rtcmd!==false){
			$js['status'] = 1;
			$js['msg'] = $isfav==1 ? '收藏成功':'取消成功';
		}else{
			$js['msg'] = '收藏失败，请重试';
		}
		$this->json($js);
	}
	//下单
	function confirm_json(){
		parent::checklogin();
		$uid = getuid();
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';		
		$info = $this->nowbuy_and_cart();
		if($info['status']===false){
			$js['msg'] = $info['msg'];
			$this->json($js);
		}
		$list = $info['data'] ?? [];
		$quantity = I('quantity',1,'post');
		$skuid = I('skuid',1,'post');
		$id = I('id',1,'post');
		$sku = I('sku',0,'post');		
		$cartid = I('cartid',0,'post');

		$balance = 0;//D('Member')->getBalance($this->uid);

		$allprice=0;
		$payprice = 0;
		$discount = 0;

		foreach($list as $k=>$v){
			$allprice += $v['price']*$v['quantity'];
		}

		//$discount = 10000;

		$payprice = $allprice-$discount;

		$js['address'] = D('Address')->getlist($this->uid,-1);

		$data = null;
		$data['id'] = $id;		
		$data['skuid'] = $skuid;
		$data['sku'] = $sku;
		$data['quantity'] = $quantity;
		$data['cartid'] = $cartid;
		$data['allquantity'] = $info['quantity'] ?? 0;
		$data['balance'] = $balance;
		$data['allprice'] = $allprice;
		$data['payprice'] = $payprice;	
		if($list){
			$js['status'] = 1;
			$js['detail'] = $list;
		}
		$js['data'] = $data;		
		$this->json($js);
	}
	private function getpayinfo($title='',$price=0,$ordid='',$isprofit=0){
		$js['status'] = false;
		$js['msg'] = '';
		$openid = I('openid',0);
		if(strlen($ordid)<6){
			$js['msg'] = 'ORDID错误';
			return $js;
		}
		if(strlen($openid)<6){
			$js['msg'] = 'OPENID错误';
			return $js;
		}
		if(strlen($title)<6){
			$js['msg'] = '标题错误';
			return $js;
		}
		if($price<1){
			$js['msg'] = '价格错误';
			return $js;
		}
		if($this->wx_appid==''){
			$js['msg'] = '系统配置APPID错误';
			return $js;
		}
		if($this->wx_mchid==''){
			$js['msg'] = '系统配置MCHID错误';
			return $js;
		}
		if($this->wx_paykeys==''){
			$js['msg'] = '系统配置PAYKEY错误';
			return $js;
		}

		$title = utf_cutstr($title,60);

		$arr = null;			
		$arr['appid'] = $this->wx_appid;
		$arr['mchid'] = $this->wx_mchid;
		$arr['body'] = $title;
		$arr['openid'] = $openid;
		$arr['out_trade_no'] = $ordid;
		$arr['total_fee'] = $price;
		$arr['notify_url'] = $this->baseurl.'api/services/notify/';
		$arr['isprofit'] = $isprofit;
		$obj = D('WeixinPay');
		$rtzsh = $obj->orderdo($arr,$this->wx_paykeys);	
		$js['rtzsh'] = $rtzsh;

		$return_code = $rtzsh['return_code'] ?? '';
		$result_code = $rtzsh['result_code'] ?? '';
		$return_msg = $rtzsh['return_msg'] ?? '';
		if($return_code!='SUCCESS'||$result_code!='SUCCESS'){
			$js['msg'] = $return_msg;
			return $js;
		}
		$prepay_id = $rtzsh['prepay_id'] ?? '';
		if($prepay_id==''){
			$js['msg'] = 'prepay_id错误';
			return $js;
		}
		$js['status'] = true;
		$payinfo = xcx_payinfo($this->wx_appid,$this->wx_paykeys,$prepay_id);			
		$js['payinfo'] = $payinfo;	
		return $js;
	}
	function order_json(){
		parent::checklogin();
		$js['status'] = 0;
		$js['msg'] = '下单';

		$info = $this->nowbuy_and_cart();
		if($info['status']===false){
			$js['msg'] = $info['msg'];
			$this->json($js);
		}
		$list = $info['data'];
		$iscart = I('iscart',1,'post');
		$cartid = I('cartid',0,'post');
		$uid = $this->uid;
		if(!$list){
			$js['msg'] = $iscart==1 ? '购物车为空':'请选择商品';
			$this->json($js);
		}

		$title = $list[0]['title'];
		$allprice = 0;
		foreach($list as $k=>$v){
			$allprice += $v['price']*$v['quantity'];
		}
		if($allprice<1){
			$js['msg'] = '商品价格错误';
			$this->json($js);
		}
		$openid = I('openid',0);		
		$payprice = $allprice;
		$shipping = 0;

		$areaid = I('areaid',1,'post');
		if($areaid==0){
			$js['msg'] = '请选择收货地址';
			$this->json($js);
		}

		$rtaddress = D('Address')->getlist($uid,$areaid);
		if(!$rtaddress){
			$js['msg'] = '请选择收货地址';
			$this->json($js);
		}
		
		$truename = $rtaddress['truename'] ?? '';
		$mobile = $rtaddress['mobile'] ?? '';
		$countyid = $rtaddress['countyid'] ?? '';
		$address = $rtaddress['address'] ?? '';

		$paytid = I('paytid',0,'post');

		$balance = 0;//D('Member')->getBalance($uid);

		if($paytid=='balance' && $balance<$payprice){
			$js['msg'] = '余额不足，请选择微信支付';
			$this->json($js);
		}
		


		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}		

		$ordid = build_order_no();
		$tb = 'OrdersDetails';//getdetailtb($this->detailtbid);

		$isprofit = $this->website['agentstat'] ?? 0;

		$data = null;
		$data['uid'] = $uid;
		$data['addtime'] = time();
		$data['ordid'] = $ordid;
		$data['title'] = $title;
		$data['allprice'] = $allprice;
		$data['payprice'] = $payprice;
		$data['shipping'] = $shipping;
		$data['stat'] = 0;
		$data['tbid'] = $this->detailtbid;
		$data['openid'] = $openid;
		$data['isprofit'] = $isprofit;
		$m = M('Orders');
		$rtcmd = $m->add($data);
		if($rtcmd!==false){			
			$detail = null;
			foreach($list as $k=>$v){
				$detail[$k]['ordid'] = $rtcmd;
				$detail[$k]['uid'] = $uid;
				$detail[$k]['title'] = $v['title'];
				$detail[$k]['price'] = $v['price'];
				$detail[$k]['quantity'] = $v['quantity'];
				$detail[$k]['sku'] = $v['sku'];
				$detail[$k]['skuid'] = $v['skuid'];
				$detail[$k]['proid'] = $v['proid'];
				$detail[$k]['picurl'] = $v['picurl'];
			}
			$rtcmds = M($tb)->addAll($detail);
			if($rtcmd===false){
				$js['msg'] = '添加明细，请重试';
				$this->json($js);
			}

			$data_ad = null;
			$data_ad['id'] = $rtcmd;
			$data_ad['truename'] = $truename;
			$data_ad['mobile'] = $mobile;
			$data_ad['countyid'] = $countyid;
			$data_ad['address'] = $address;
			$data_ad['uid'] = $uid;
			$js['paytid'] = $paytid;

			$rtcmdss = M('OrdersAddress')->add($data_ad);

			if($paytid=='balance'){
				if($balance<$payprice){
					$js['msg'] = '余额不足，请选择微信支付';
					$this->json($js);
				}else{
					$rtcmd = D('Member')->addBalance($uid,-$payprice,0,'购买商品余额抵扣',$paytid);
					if($rtcmd!==true){
						$js['msg'] = '余额抵扣失败，请重试';
						$this->json($js);
					}else{
						if($iscart==1){
							M(getcarttb($uid))->where('id in('.$cartid.') AND uid='.$uid)->setField('quantity',0);
						}
						$data = null;
						$data['out_trade_no'] = $ordid;
						$data['paytype'] = 9;
						D('Orders')->orderhandle($data);
						$js['msg'] = '余额支付成功';
						$js['status'] = 1;
						$this->json($js);
					}
				}
			}

			$info = $this->getpayinfo($title,$payprice,$ordid,$isprofit);
			$js['info'] = $info;
			$paysucces = $info['status'] ?? false;
			$msg = $info['msg'] ?? '';
			if($paysucces!==true){
				$js['msg'] = $msg;
				$this->json($js);
			}
			$js['payinfo'] = $info['payinfo'] ?? array();
			if($iscart==1){
				M(getcarttb($uid))->where('id in('.$cartid.') AND uid='.$uid)->setField('quantity',0);
			}
			$js['status'] = 1;
			$js['msg'] = '正在调用支付';
			$js['data'] = $rtcmd;
		}else{
			$js['msg'] = '下单失败，请重试';
		}
		$this->json($js);
	}		
	//小程序支付接口
	public function payagain_json(){
		parent::checklogin();
		$uid = $this->uid;
		$js['status'] = 0;
		$js['msg'] = '';
		$id = I('id',1,'post');
		if($id<1){
			$js['msg'] = '订单号错误';
			$this->json($js);
		}
		$m = M('Orders');
		$rt = $m->where('id='.$id.' AND uid='.$uid)->find();
		if(!$rt){
			$js['msg'] = '订单号错误';
			$this->json($js);
		}
		if($rt['stat']!=0){
			$js['msg'] = '订单可能已支付';
			$this->json($js);
		}
		$title = $rt['title'] ?? '';
		$ordid = build_order_no();
		$payprice = intval($rt['payprice']);
		if($payprice<1){
			$js['msg'] = '订单金额无须支付';
			$this->json($js);
		}

		$rtcmd = $m->where('id='.$rt['id'])->setField('ordid',$ordid);
		if($rtcmd===false){
			$js['msg'] = '系统繁忙，请重试';
			$this->json($js);
		}

		$info = $this->getpayinfo($title,$payprice,$ordid);
		$paysucces = $info['status'] ?? false;
		$msg = $info['msg'] ?? '';
		if($paysucces!==true){
			$js['msg'] = $msg;
			$this->json($js);
		}
		$js['payinfo'] = $info['payinfo'] ?? array();			
		
		$js['status'] = 1;
		$js['msg'] = '正在调用支付';
		$js['data'] = $id;
		$this->json($js);
	}
	private function nowbuy_and_cart(){
		$js['status'] = false;
		$js['msg'] = '数据加载完成';
		$id = I('id',1,'post');
		$iscart = I('iscart',1,'post');
		$skuid = I('skuid',1,'post');
		$sku = I('sku',0,'post');	
		$quantity = I('quantity',1,'post');
		$cartid = I('cartid',0,'post');

		if($iscart==1){
			$list = $this->getcartlist($cartid);
		}else{
			$info = $this->check_sku($id,$skuid,$quantity,$sku);
			if($info['status']===false){
				$js['msg'] = $info['msg'];
				return $js;
			}
			$list = array($info['rt']);
		}
		if(!$list){
			$js['msg'] = $iscart==1 ? '购物车是空的':'商品可能已下架';
			return $js;
		}
		$js['status'] = true;
		$js['data'] = $list;
		$js['quantity'] = $quantity;
		return $js;
	}
	private function getcartlist($idstr=''){
		$arr = null;
		$where = '`A1`.`id` in('.$idstr.') AND `A1`.`uid`='.$this->uid.' AND `A1`.`quantity`>0';
		$arr['field'] = '`A1`.`id`,`A2`.`title`,`A2`.`stat`,IF(`A1`.`skuid`=0,`A2`.`price`,`A3`.`price`) AS `price`,IF(`A1`.`skuid`=0,`A2`.`stock`,`A3`.`stock`) AS `stock`,`A1`.`proid`,`A1`.`quantity`,IF(`A1`.`skuid`=0,\'\',`A3`.`title`) AS `sku`,`A1`.`skuid`,`A4`.`picurl_0` AS `picurl`,`A4`.`w_0` AS `width`,`A4`.`h_0` AS `height`,`A4`.`picurl_1`,`A4`.`picurl_2`,`A4`.`picurl_3`,`A4`.`picurl_4`';
		$arr['name'] = array('CartA1','Pro','ProSku','ProPicurl');
		$arr['on'] = array('`A1`.`proid`=`A2`.`id`','`A1`.`skuid`=`A3`.`id`','`A2`.`id`=`A4`.`id`');
		$arr['where'] = $where;
		$arr['order'] = '`A1`.`id` DESC';
		$arr['limit'] = 20;
		$arr['showsql'] = true;
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);
		return $list;
	}
	public function check_sku($id=0,$skuid=0,$quantity=1,$sku=''){
		$js = null;
		$js['status'] = false;
		$js['msg'] = '';
		$m = M('Pro');
		$rt = $this->get_item($id,$quantity,$sku);
		if(!$rt){
			$js['msg'] = '商品不存在';
			return $js;
		}
		if($quantity<1){
			$js['msg'] = '购买数量错误';
			return $js;
		}
		if($rt['stat']!=0){
			$js['msg'] = '商品已下架';
			return $js;
		}
		if($quantity>$rt['stock']){
			$js['msg'] = '库存不足';
			return $js;
		}
		$rt['skuid'] = 0;
		$skulist = M('ProSku')->where('`proid`='.$id.' AND deleted=0')->find();
		$skurt = null;
		if($skulist){
			$skurt = M('ProSku')->field('id,title,price,stock')->where('`id`='.$skuid.' AND `proid`='.$id.' AND deleted=0')->find();
			if(!$skurt){
				$js['msg'] = '规格可能已下架';
				return $js;
			}
			if($quantity>$skurt['stock']){
				$js['msg'] = '库存不足';
				return $js;
			}
			$rt['skuid'] = $skurt['id'];
		}
		$js['rt'] = $rt;
		$js['sku'] = $sku;
		$js['status'] = true;
		return $js;
	}
	public function get_item($id=0,$quantity=1,$sku=''){
		$where = '`A1`.`id`='.$id.' AND `A1`.`stat`=0';
		$field = '`A1`.`id`,`A1`.`title`,`A1`.`stat`,`A1`.`saled`,`A1`.`price`,`A1`.`stock`,`A1`.`id` AS `proid`,'.$quantity.' AS `quantity`,\''.$sku.'\' AS `sku`,0 AS `skuid`,`A2`.`picurl_0` AS `picurl`,`A2`.`w_0` AS `width`,`A2`.`h_0` AS `height`,`A2`.`picurl_1`,`A2`.`picurl_2`,`A2`.`picurl_3`,`A2`.`picurl_4`';
		$arr['name'] = array('Pro','ProPicurl');			
		$arr['on'] = array('`A1`.`id`=`A2`.`id`');
		$arr['field'] = $field;
		$arr['where'] = $where;
		$arr['limit'] = 1;	
		$obj = D('SubTab',1);
		$rt = $obj->mysql_list($arr);
		return $rt;
	}

	function protocol_json(){
		$js['status'] = 0;
		$js['msg'] = '数据加载完成';
		$id = I('id',1,'post');	
		$title = I('title',0,'post');
		$m = M('Protocol');
		$title = '廷煜锂电用户协议';
		$cont = readFiles(ROOT.'user_agreement.txt');
		//$rt = $m->field('id,title,cont')->where('1')->find();
		$rt = null;
		$rt['title'] = $title;
		$rt['cont'] = $cont;
		$js['status'] = 1;
		$js['data'] = $rt;
		$this->json($js);
	}
	//添加购物车
	function addcart_json(){		
		$js['status'] = 0;
		$js['msg'] = '';
		$js['isadd'] = 0;
		$js['nums'] = 0;

		$id = I('id',1,'post');
		$skuid = I('skuid',1,'post');
		$sku = I('sku',0,'post');
		$uid = $this->uid;
			
		$quantity = I('quantity',1,'post');

		if($quantity<1){
			$js['msg'] = '购买数量最少为1';
			$this->json($js);
		}

		$info = $this->check_sku($id,$skuid,$quantity,$sku);
		if($info['status']===false){
			$js['msg'] = $info['msg'];
			$this->json($js);
		}
		$rt = $info['rt'];

		$tb = getcarttb($uid);				
		
		$map['proid'] = $id;
		$map['skuid'] = $skuid;
		$map['uid'] = $uid;

		$mm = M($tb);
		$rts = $mm->where($map)->find();
		$tid = I('tid',1,'post');
		$tit = '加入';
		if($rts){			
			$rtcmd = false;
			switch ($tid){
			case 0:
			    $tit = '增加';
			    $rtcmd = $mm->where($map)->setInc('quantity',$quantity);
			    break;
			case 1:
				if($rts['quantity']<2){
					$ischeck = I('ischeck',1);
					if($ischeck==0){
						$js['status'] = 1;
						$js['msg'] = '检测成功!';
						$this->json($js);
					}
				}
				if($rts['quantity']<=$quantity){
				    $tit = '删除';					
					$rtcmd = $mm->where($map)->setField('quantity',0);
			    }else{
					$tit = '更新';
					$rtcmd = $mm->where($map)->setDec('quantity',$quantity);
				}
			    break;
			case 2:
				if($rts['quantity']<$quantity){
				    $js['msg'] = '最少保留一件商品';
					$this->json($js);
			    }
			    $tit = '修改';
			    $rtcmd = $mm->where($map)->setField('quantity',$quantity);
			    break;
			}
			if($rtcmd!==false){		
				$js['msg'] = $tit.'成功';
				$js['status'] = 1;
				$js['data'] = $mm->field('id,quantity')->where('id='.$rts['id'].' AND uid='.$uid)->find();
				$js['nums'] = $mm->where('uid='.$uid.' AND quantity>0')->count();
			}else{
				$js['msg'] = $tit.'失败，请重试';	
			}
			$this->json($js);
		}

		$data['proid'] = $id;
		$data['skuid'] = $skuid;
		$data['quantity'] = $quantity;
		$data['uid'] = $uid;
		$rtcmd = $mm->add($data);
		$js['isadd'] = 1;
		if($rtcmd!==false){
			$js['msg'] = $tit.'成功';
			$js['status'] = 1;
			$js['data'] = $mm->field('id,quantity')->where('id='.$rtcmd.' AND uid='.$uid)->find();
			$js['nums'] = $mm->where('uid='.$uid.' AND quantity>0')->count();
		}else{
			$js['msg'] = $tit.'失败，请重试';	
		}
		$this->json($js);
	}
	//会员充值与会员购买
	function vip_json(){
		$js['status'] = 0;
		$js['msg'] = '下单';

		$packageid = I('id',1,'post');
		if($packageid<1){
			$js['msg'] = '请选择充值金额';
			$this->json($js);
		}
		$rt = M('Package')->where('id='.$packageid.' AND stat=1')->find();
		if(!$rt){
			$js['msg'] = '套餐可能已下线';
			$this->json($js);
		}

		$title = $rt['title'];
		$allprice = $rt['price'] || 0;

		if($price<1){
			$js['msg'] = '金额设置错误';
			$this->json($js);
		}

		$list = null;
		$list[] = $rt;		
		
		$openid = I('openid',0);
		if(strlen($openid)<10){
			$js['msg'] = 'openid错误';
			$this->json($js);
		}

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}

		$payprice = $allprice;
		$shipping = 0;

		$ordid = build_order_no();
		$tb = 'OrdersDetails';//getdetailtb($this->detailtbid);

		$tid = $rt['tid'] ?? 2;
		$str = $rt['str'] ?? '';

		$data = null;
		$data['uid'] = $this->uid;
		$data['addtime'] = time();
		$data['ordid'] = $ordid;
		$data['title'] = $title;
		$data['allprice'] = $allprice;
		$data['payprice'] = $payprice;
		$data['shipping'] = $shipping;
		$data['stat'] = 0;
		$data['packageid'] = $packageid;
		$data['trantype'] = $tid;
		$data['str'] = $str;
		$data['tbid'] = $this->detailtbid;
		$m = M('Orders');
		$rtcmd = $m->add($data);
		if($rtcmd!==false){
			$js['status'] = 1;
			$detail = null;
			foreach($list as $k=>$v){
				$detail[$k]['ordid'] = $rtcmd;
				$detail[$k]['uid'] = $this->uid;
				$detail[$k]['title'] = $v['title'];
				$detail[$k]['price'] = $v['price'];
				$detail[$k]['quantity'] = 1;
				$detail[$k]['sku'] = '';
				$detail[$k]['skuid'] = 0;
				$detail[$k]['proid'] = 0;
				$detail[$k]['picurl'] = '';
			}
			$rtcmds = M($tb)->addAll($detail);
			if($rtcmd===false){
				$js['msg'] = '添加明细，请重试';
				$this->json($js);
			}
			
			$info = $this->getpayinfo($title,$payprice,$ordid);
			$paysucces = $info['status'] ?? false;
			$msg = $info['msg'] ?? '';
			if($paysucces!==true){
				$js['msg'] = $msg;
				$this->json($js);
			}
			$js['payinfo'] = $info['payinfo'] ?? array();			

			$cartid = I('cartid',0,'post');
			if($iscart==1){
				M(getcarttb($this->uid))->where('id in('.$cartid.') AND uid='.$this->uid)->setField('quantity',0);
			}
			$js['status'] = 1;
			$js['msg'] = '正在调用支付';
			$js['data'] = $rtcmd;
		}else{
			$js['msg'] = '下单失败，请重试';
		}
		$this->json($js);
	}
	
}