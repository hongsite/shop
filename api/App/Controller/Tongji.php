<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 数据统计
* @date 2024/10/13
* @version hst-v1.0.0
*/
namespace Hst\Tongji;
use Hst\Common;
class Tongji extends Common{
    public function _init(){
		$this->allowindex = true;
        parent::_init();	
    }
	function calculateMonthDifference($timestamp1, $timestamp2) {
		$date1 = new \DateTime();
		$date1->setTimestamp($timestamp1);
	 
		$date2 = new \DateTime();
		$date2->setTimestamp($timestamp2);
	 
		$interval = $date1->diff($date2);
		$months = $interval->y * 12 + $interval->m + $interval->d / 30;
	 
		return (int) floor($months);
	}
	public function index(){	
		$js['status'] = 0;
		$js['msg'] = '还没有数据';
		$btime = I('btime',0);
		$etime = I('etime',0);
		$tid = I('tid',1);
		if($btime>$etime){
			$js['msg'] = '开始日期不能大于结束日期';
			$this->json($js);
		}
		$btime = strtotime($btime);
		$etime = strtotime($etime);
		if(ceil(($etime-$btime)/86400)>31 && $tid==0){
			$js['msg'] = '按天查询，最多查询31天';
			$this->json($js);
		}
		$monthcha = 0;
		if($tid==1){
			$monthcha = $this->calculateMonthDifference($btime,$etime);
			if($monthcha>24){
				$js['msg'] = '按月查询，最多查询24个月';
				$this->json($js);
			}
		}
		$day = $tid==0 ? ceil(($etime-$btime)/86400):$monthcha;
		$types = $tid==0 ? 'day':'month';
		$formatd = $tid==0 ? 'm-d':'y-m';
		$list = null;
		$m = M('Orders');
		$nowt = time();
		for($i=0;$i<=$day;$i++){
			$mytime = strtotime('-'.$i.' '.$types,$nowt);
			if($tid==0){
				$btime = strtotime(date('Y-m-d 00:00:00',$mytime));
				$etime = strtotime(date('Y-m-d 23:59:59',$mytime));
			}else{
				$btime = strtotime(date('Y-m-01 00:00:00',$mytime));
				$etime = strtotime(date('Y-m-t 23:59:59',$mytime));

			}
			$nums = 0;
			$nums = $m->where('deleted=0 AND tid=0 AND addtime between '.$btime.' AND '.$etime)->sum('price');
			//echo $m->getlastsql();
			$value = $nums ? $nums:0;
			$list[] =array('name'=>date($formatd,$mytime),'value'=>$value);
		}
		if($list){
			$js['status'] = 1;
			$js['data'] = $list;
		}
		$this->json($js);
    }
	public function index_json(){
		$uid = getuid();
		$js['status'] = 0;
		$js['msg'] = '还没有数据';
		$btime = I('btime',0);
		$etime = I('etime',0);
		$tid = I('tid',1);
		if($btime>$etime){
			$js['msg'] = '开始日期不能大于结束日期';
			$this->json($js);
		}
		$btime = strtotime($btime);
		$etime = strtotime($etime);
		if(ceil(($etime-$btime)/86400)>365 && $tid==0){
			$js['msg'] = '按天查询，最多查询365天';
			$this->json($js);
		}

		$nowt = time();

		$allprice = M('Business')->where('deleted=0')->sum('price');
		$allnums = M('Huiyuan')->where('deleted=0')->count();

		$daoqi_nums = M('Business')->where('deleted=0 AND etime<'.$nowt)->count();

		



		$list = null;
		$list[] = array('title'=>'7天到期','subtitle'=>'需特别关注','nums'=>$this->getwhere(7),'chushu'=>'0','type'=>'0','url'=>'/business/?code=daoqi7');
		$list[] = array('title'=>'15天到期','subtitle'=>'即将到期','nums'=>$this->getwhere(15),'chushu'=>'0','type'=>'0','url'=>'/business/?code=daoqi15');
		$list[] = array('title'=>'30天到期','subtitle'=>'需要关注','nums'=>$this->getwhere(30),'chushu'=>'0','type'=>'0','url'=>'/business/?code=daoqi30');
		$list[] = array('title'=>'90天到期','subtitle'=>'临近期','nums'=>$this->getwhere(90),'chushu'=>'0','type'=>'0','url'=>'/business/?code=daoqi90');		
		$list[] = array('title'=>'总客户数','subtitle'=>'总的客户','nums'=>$allnums,'chushu'=>'0','type'=>'0','url'=>'/huiyuan/');
		$list[] = array('title'=>'已过期','subtitle'=>'总的客户','nums'=>$daoqi_nums,'chushu'=>'0','type'=>'0','url'=>'/business/?code=guoqi');
		$list[] = array('title'=>'业务金额','subtitle'=>'应收总额','nums'=>$allprice,'chushu'=>'100','type'=>'wan','url'=>'javascript:;');

		$js['list'] = $list;

		$data = M('Business')->field('id,company,etime,price,tid')->where('deleted=0 AND etime>'.$nowt.' AND etime<'.($nowt+30*86400))->limit(10)->order('etime ASC')->select();

		$js['data'] = $data;

		$this->json($js);
    }
	private function getwhere($nums=30){
		$nowt = time();
		$where = 'deleted=0 AND etime>'.$nowt.' AND etime<'.($nowt+$nums*86400);
		$nums = M('Business')->where($where)->count();
		return $nums;
	}

}
