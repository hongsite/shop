<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 产品管理
* @date 2018/7/19
* @version hst-v1.0.0
*/
namespace hst\Pro;
use hst\Common;
class Pro extends Common{
	
	function _init(){
		parent::_init();

		$this->allowindex = true;
		$this->allowedit = true;
		$this->allowupdate = true;
		$this->allowdelete = true;


		$searinfo[] = array('field'=>'title','afield'=>'`A1`.','isnumeric'=>0,'type'=>'like');
		$this->seararr=$searinfo;
	}	

	function _before_update(){
		$this->detail = array('ProPicurl','ProDesc');
		$price = I('price',1,'post');
		if($price<0.01){
			return '商品价格最少0.01元';
		}
		if($price>90000000){
			return '商品价格最多10000万';
		}
		$mainPriceCents = $price * 100; // 主价格转成分

		$sku = I('sku',0,'post');
		$hasSamePrice = false; // 标记是否存在相同价格
		if($sku){
			foreach($sku as $k=>$v){
				$skuprice = I('price_'.$v,1,'post');
				$skupriceCents = $skuprice * 100;				
				if($skupriceCents == $mainPriceCents){
					$hasSamePrice = true;
					break;
				}
			}
		}else{
			return true;
		}

		if(!$hasSamePrice){
			return 'SKU价格中必须包含与主价格相同的价格';
		}
		return true;
	}	
	function _before_edit(){
		$this->detail = array('ProPicurl');
		$this->tbon = array('`A1`.`id`=`A2`.`id`','`A1`.`sid`=`A3`.`id`');
		$this->field = '`A2`.`picurl_0`,`A2`.`picurl_1`,`A2`.`picurl_2`,`A2`.`picurl_3`,`A2`.`picurl_4`,`A2`.`picurl_5`,`A2`.`poster`,`A2`.`videourl`,`A2`.`w_0`,`A2`.`h_0`,`A2`.`w_1`,`A2`.`h_1`,`A2`.`w_2`,`A2`.`h_2`,`A2`.`w_3`,`A2`.`h_3`,`A2`.`w_4`,`A2`.`h_4`,`A2`.`w_5`,`A2`.`h_5`,`A2`.`poster_w`,`A2`.`poster_h`,`A2`.`isup`';		
	}

	function _after_edit_json($id=0,$js=null){
		$tid = I('tid',1);
		$tb = 'Pro';
		$js['catelist'] = M($tb.'Class')->field('id,title')->where('deleted=0')->order('sort ASC')->select();
		$js['skulist'] = M('ProSku')->field('id,title,price,stock')->where('proid='.$id.' AND deleted=0')->select();
		$perty = M('ProPerty')->where('id='.$id)->getField('perty');
		$js['pertylist'] = $perty !== null ? json_decode($perty,true) : array();
		return $js;
	}
	function _before_json($js=null){
		$tid = I('tid',1);
		$tb = 'Pro';
		$js['catelist'] = M($tb.'Class')->field('id,title')->order('sort ASC')->select();
		return $js;
	}
	
	function _before_index(){
		$rt['allnums'] = 0;
		$rt['waitpaynums'] = 0;
		$rt['successums'] = 0;

		$this->noload = 1;

		$this->tb = 'Pro';

		

		$code = I('code',0);
		$tid = I('tid',1);

		$where = '`A1`.`tid`='.$tid;
		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`price`,`A1`.`stock`,`A1`.`saled`,`A1`.`saledplus`,`A1`.`stat`,`A1`.`cid`,`A2`.`picurl_0` AS `picurl`,`A3`.`title` AS `cname`';
		switch ($code){
			case 'show':
				$where .= ' AND `A1`.`stat`=0';
				break;
			case 'xiajia':
				$where .= ' AND `A1`.`stat`=1';
				break;
			case 'hsz':
				$where .= ' AND `A1`.`stat`=2';
				break;			
			case 'all':	
				$where .= ' AND `A1`.`stat` in(0,1,2)';
				break;
			default:
				$where .= ' AND `A1`.`stat`=0';
		}		

		$keyword = I('keyword',0);
		if($keyword!=''){
			if(is_numeric($keyword) && strlen($keyword)<8){
				$where .= ' AND `A1`.`id`='.$keyword;
			}else{
				$where .= ' AND `A1`.`title` like \'%'.$keyword.'%\'';
			}
		}		

		$this->detail = array('ProPicurl','ProClass');
		$this->tbon = array('`A1`.`id`=`A2`.`id`','`A1`.`cid`=`A3`.`id`');

		$ppid = I('ppid',1,'');
		$pid = I('pid',1,'');		
		$cid = I('cid',1,'');
		$this->where=$where;
		$this->perpage = 10;
	}
	function stat_json(){
		$uid = $this->uid;
		$js['status'] = 0;
		$js['msg'] = '';		
		$id = I('id',1);
		$tid = I('tid',1);
		if($id==0){
			$js['msg'] = '数据不存在';
			$this->json($js);
		}		
		$m = M(MODULE_NAME);
		$rt = $m->where('id='.$id)->find();
		if(!$rt){
			$js['msg'] = '数据不存在';
			$this->json($js);
		}

		$stats = 0;
		$tit = '下架商品';

		switch ($tid){
			case 0:
				$stats = 0;
				$tit = '上架商品';
				break;
			case 1:
				$stats = 1;
				$tit = '下架商品';
				break;
			case 2:
				$stats = 2;
				$tit = '删除商品';
				break;
			case 4:
				$stats = 9;
				$tit = '彻底删除';
				break;
		}

		$rtcmd = $m->where('id='.$id)->setField('stat',$stats);		
		
		if($rtcmd!==false){
			$js['status'] = 1;
			$js['msg'] = $tit.'成功';
		}else{
			$js['msg'] = $tit.'失败';
		}
		$this->json($js);
	}

	public function xqpicdel_json(){
		$js['status'] = 0;
		$js['msg'] = '';

		$id = I('id',1,'post');

		if($id==0){
			$js['msg'] = '数据错误';
			$this->json($js);
		}
		$m = M('ProXqpic');
		$map['id'] = $id;
		$rt = $m->where($map)->find();
		if(!$rt){
			$js['msg'] = '数据可能已经删除了';
			$this->json($js);
		}
		$rtcmd = $m->where($map)->delete();
		$tit = '详情图片删除';
		if($rtcmd!==false){
			$js['status'] = 1;
			$js['msg'] = $tit.'成功';
			$this->json($js);
		}else {
			$js['msg'] = $tit.'失败，请重试';
			$this->json($js);
		}
	}
	function loadxqpic_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$id = I('id',1,'');
		if($id==0){
			$this->json($js);
		}
		$list = M('ProXqpic')->where('rootid='.$id)->order('sort ASC')->select();
		if($list){
			$js['status'] = 1;
			$js['data'] = $list;
		}
		$this->json($js);
	}

	//添加入库 
	public function update_json(){		
		$js['status'] = 0;
		$js['msg'] = '';
		$js['isadd'] = 0;		
		if(method_exists($this,'_before_update')){			
			$istrue = $this->_before_update();			
			if($istrue!==true){
				$js['msg'] = $istrue;
				$this->json($js);
			}
		}
		
		$status = 0;
		$msg = '';	
		$id = I('id',1,'post');
		$shiptmp = I('shiptmp',1,'post');
		$saleplus = I('saleplus',1,'post');
		
		$title = I('title',0,'post');

		$picurl = I('picurl',0,'post');
		if(!$picurl){
			$js['msg'] = '请上传最少一张主图';
			$this->json($js);
		}

		$validUrls = array_values(array_filter($picurl, function($url) {
			return !empty($url);
		}));


		$validWidth = array_values(array_filter(I('picurlw',0,'post'), function($url) {
			return !empty($url);
		}));

		$validHeight = array_values(array_filter(I('picurlh',0,'post'), function($url) {
			return !empty($url);
		}));


		$picurl_0 = $validUrls[0] ?? '';
		$picurl_1 = $validUrls[1] ?? '';
		$picurl_2 = $validUrls[2] ?? '';
		$picurl_3 = $validUrls[3] ?? '';
		$picurl_4 = $validUrls[4] ?? '';
		$picurl_5 = '';
		$picurl_6 = '';

		if($picurl_0==''){
			$js['msg'] = '请上传最少一张主图';
			$this->json($js);
		}

		$sku = I('sku',0,'post');

		$poster = I('poster',0,'post');
		$videourl = I('videourl',0,'post');
		$price = I('price',1,'post');		
		$stat = I('stat',1,'post');
		$cid = I('cid',1,'post');
		$bh = I('bh',0,'post');
		$stock = I('stock',1,'post');
		$content = I('content',0,'post');
		$sharedesc = I('sharedesc',0,'post');
		$tid = I('tid',1,'post');
		$minnums = I('minnums',1,'post');
		$maxnums = I('maxnums',1,'post');
		$isindexhot = I('isindexhot',1,'post');
		$isindexqg = I('isindexqg',1,'post');
		$desc = I('desc',0,'post');
		$istel = I('istel',0,'post');
		$islxr = I('islxr',0,'post');
		$saled = I('saled',1,'post');
		$saledplus = I('saledplus',1,'post');

		$minnums<1 && $minnums=1;
		$maxnums>100000 && $maxnums=100000;

		$content = I('content',0,'post');

		$yprice = I('yprice',1,'post');
		$yprice *= 100;
		
		

		if(strlen($title)<4){
			$js['msg'] = '标题最少两个字符';
			$this->json($js);
		}

		if($cid==0){
			//$js['msg'] = '请输入分类';
			//$this->json($js);
		}		
			

		$map['id'] = $id;

		$price *= 100;	
		
		$data = null;

		$data['title'] = $title;
		$data['shiptmp'] = $shiptmp;
		$data['picurl'] = $picurl_0;
		$data['minnums'] = $minnums;
		$data['maxnums'] = $maxnums;
		$data['saled'] = $saled;
		$data_picurl['picurl_0'] = $picurl_0;
		$data_picurl['picurl_1'] = $picurl_1;
		$data_picurl['picurl_2'] = $picurl_2;
		$data_picurl['picurl_3'] = $picurl_3;
		$data_picurl['picurl_4'] = $picurl_4;
		$data_picurl['picurl_5'] = $picurl_5;
		$data_picurl['picurl_6'] = $picurl_6;		

		$data_picurl['w_0'] = $validWidth[0] ?? 0;
		$data_picurl['w_1'] = $validWidth[1] ?? 0;
		$data_picurl['w_2'] = $validWidth[2] ?? 0;
		$data_picurl['w_3'] = $validWidth[3] ?? 0;
		$data_picurl['w_4'] = $validWidth[4] ?? 0;
		$data_picurl['w_5'] = '';
		$data_picurl['w_6'] = '';

		$data_picurl['h_0'] = $validHeight[0] ?? 0;
		$data_picurl['h_1'] = $validHeight[1] ?? 0;
		$data_picurl['h_2'] = $validHeight[2] ?? 0;
		$data_picurl['h_3'] = $validHeight[3] ?? 0;
		$data_picurl['h_4'] = $validHeight[4] ?? 0;
		$data_picurl['h_5'] = '';
		$data_picurl['h_6'] = '';

		$data_picurl['poster'] = $poster;
		$data_picurl['poster_w'] = I('poster_w',1,'post');
		$data_picurl['poster_h'] = I('poster_h',1,'post');
		$data_picurl['videourl'] = $videourl;
		if($bh!='') $data['bh'] = $bh;
		$data['price'] = $price;
		$data['cid'] = $cid;
		$data['stat'] = $stat;
		$data['stock'] = $stock;
		$data['saleplus'] = $saleplus;
		if($id==0) $data['tid'] = $tid;		
		$data['isindexhot'] = $isindexhot;
		$data['isindexqg'] = $isindexqg;
		$data['desc'] = $desc;
		$data['content'] = $content;
		$data['yprice'] = $yprice;//
		$data['istel'] = $istel;
		$data['islxr'] = $islxr;
		
		//$data['desc'] = str_fc_code($title);

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}


		if($id==0){
			$maxid = M(MODULE_NAME)->max('sort');
			$data['sort'] = $maxid+1;			
		}

		$m = M(MODULE_NAME);

		$rtcmd = false;

		if($id>0){
			
			$rt = $m->where($map)->find();
			if(!$rt){
				$js['msg'] = '商品不存在';
				$this->json($js);
			}
			$rtcmd = $id;
			$js['data'] = $rtcmd;			
			$m->where($map)->save($data);
		}else{
			$data['addtime'] = time();
			$rtcmd = $m->add($data);
			$js['isadd'] = 1;
			$js['data'] = $rtcmd;
		}

		$mm = M('ProPicurl');
		
		

		$tit = $id>0 ? '编辑商品':'添加商品';
			
		
		if($rtcmd!==false){

			$rts = $mm->where('id='.$rtcmd)->find();

			if(!$rts){
				$data_picurl['id'] = $rtcmd;
				$mm->add($data_picurl);
			}else{
				$mm->where('id='.$rtcmd)->save($data_picurl);
			}
			
			$xqpic = I('xqpic',0,'post');			
			$kkk = 0;
			$jjj = 0;
			$dataall = null;
			$saveall = null;
			if($xqpic){
			foreach($xqpic as $kk=>$vv){
				$myfid = I('xqpicfield_'.$vv,0,'post');
				$picurl = I('xqpicurl_'.$vv,0,'post');
				$width = I('width_'.$vv,1,'post');
				$height = I('height_'.$vv,1,'post');
				if($myfid==0){
					$dataall[$kkk]['rootid'] = $rtcmd;
					$dataall[$kkk]['picurl'] = $picurl;
					$dataall[$kkk]['width'] = $width;
					$dataall[$kkk]['height'] = $height;
					$dataall[$kkk]['sort'] = $kk+1;
					$kkk++;
				}else{
					$saveall[$jjj]['id'] = $myfid;
					$saveall[$jjj]['picurl'] = $picurl;
					$saveall[$jjj]['width'] = $width;
					$saveall[$jjj]['height'] = $height;
					$saveall[$jjj]['sort'] = $kk+1;
					$jjj++;
				}
			}
			}
			
			if($dataall) M('ProXqpic')->addAll($dataall);

			if($saveall) M('')->saveAll($saveall,'ProXqpic');

			$datassss['content'] = $content;
			$datassss['sharedesc'] = $sharedesc;


			
			$mmmm = M('ProDesc');
			$rtsss = $mmmm->where('id='.$rtcmd)->find();
			if(!$rtsss){
				$datassss['id'] = $rtcmd;
				$mmmm->add($datassss);
			}else{
				$mmmm->where('id='.$rtcmd)->save($datassss);
			}

			$ms = M('ProSku');

			$delarr = null;
			$adddata = null;
			$updata = null;
			$i = 0;
			$j = 0;

			if($sku){				
				foreach($sku as $k=>$v){
					$title = I('title_'.$v,0,'post');
					$price = I('price_'.$v,1,'post');
					$stock = I('stock_'.$v,1,'post');
					$price *= 100;
					if($title!=''){
						$rtsku = $ms->where('proid='.$rtcmd.' AND `title`=\''.$title.'\'')->find();
						if(!$rtsku){
							$adddata[$i]['title'] = $title;
							$adddata[$i]['proid'] = $rtcmd;
							$adddata[$i]['price'] = $price;
							$adddata[$i]['stock'] = $stock;
							$i++;
						}else{
							$updata[$j]['title'] = $title;
							$updata[$j]['id'] = $rtsku['id'];						
							$updata[$j]['price'] = $price;
							$updata[$j]['stock'] = $stock;
							$j++;
						}
					}
				}

			}
			$js['addsku'] = $adddata;
			$js['upsku'] = $updata;
			if($adddata) $ms->addAll($adddata);
			if($updata) M('')->saveAll($updata,'ProSku');

			$skulist = $ms->field('id,title')->where('proid='.$rtcmd.' AND deleted=0')->select();
			if($skulist){
				foreach($skulist as $k=>$v){
					$issku = $this->checksku($sku,$v['title']);
					if($issku===false){
						$delarr[] = $v['id'];
					}

				}
			}

			if($delarr) $ms->where('id in('.implode(',',$delarr).')')->setField('deleted',1);

			$perty_name_arr = I('perty_name',0,'post');
			$perty_val_arr = I('perty_val',0,'post');

			$perty_arr = [];

			if($perty_name_arr && $perty_val_arr){
				if(count($perty_name_arr)==count($perty_val_arr)){
					foreach($perty_name_arr as $kkk=>$vvv){
						$perty_arr[] = array('name'=>$vvv,'val'=>$perty_val_arr[$kkk]);
					}
					
				}
			}

			$perty = json_encode($perty_arr,JSON_UNESCAPED_UNICODE);

			$mper = M('ProPerty');
			$rtperty = $mper->where('id='.$rtcmd)->find();
			if($rtperty){
				$mper->where('id='.$rtcmd)->setField('perty',$perty);
			}else{
				$dataper = null;
				$dataper['id'] = $rtcmd;
				$dataper['perty'] = $perty;
				$mper->add($dataper);
			}
			$js['status'] = 1;
			$js['msg'] = $tit.'成功';
			$this->json($js);
		}else {
			$js['msg'] = $tit.'失败，请重试';
			$this->json($js);
		}
	}
	function checksku($sku=null,$title=''){
		if(!$sku) return false;
		foreach($sku as $k=>$v){
			$ftitle = I('title_'.$v,0,'post');
			if($ftitle==$title) return true;
		}
		return false;
	}
}