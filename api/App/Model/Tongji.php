<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 图片处理
* @date 2020/7/15
* @version hst-v1.0.0
*/
namespace Models\Tongji;
use Model\Model;
class Tongji extends Model{
    //统计类
	public function get_tj_nums_cache($arr){
		/*
		tb需要统计的表
		tjtype需要统计的类型一般为count,sum
		field 需要限制查询的时间字段默认为addtime
		tid today今天，all为所有
		day 要统计的日期,格式为20170809
		tjfield统计类型的字段，默认为price
		*/
		$mytime = time();
		$where = empty($arr['where']) ? '1':$arr['where'];
		$tid = empty($arr['tid']) ? 'today':$arr['tid'];//0为今日1为昨日999为所有
		$field = empty($arr['field']) ? 'addtime':$arr['field'];
		$tjfield = empty($arr['tjfield']) ? 'count(*)':$arr['tjfield'];
		if(!$arr['tb']) return 0;		

		$tb = $arr['tb'];
		$tbon = empty($arr['tbon']) ? array():$arr['tbon'];

		if($tid=='yestoday'){
			$day = date('Ymd',strtotime('-1 days'));
		}else if($tid=='day'){
			$day = empty($arr['day']) ? date('Ymd'):$arr['day'];
		}else if($tid=='week'){
			$day = empty($arr['day']) ? date('W'):$arr['day'];
		}else if($tid=='month'){
			$day = empty($arr['day']) ? date('Ym'):$arr['day'];
		}else{
			$day = empty($arr['day']) ? date('Ym'):$arr['day'];
		}

		$mystr = $where.'_'.$tjfield.'_'.$field.'_'.$day.'_'.implode('_',$tbon);

		if($tid=='day'){
			$myzeroday = $this->get_zero_day($day);		

			if($myzeroday>=strtotime(date('Y-m-d 00:00:01'))){
				return $this->get_tj_nums($arr);
			}			
		}

		if($tid=='month'){
			$myzeroday = $this->get_zero_month($day);		
			if($myzeroday>=strtotime(date('Y-m-01 00:00:01'))){
				return $this->get_tj_nums($arr);
			}			
		}

		if($tid=='week'){

			$date=date('Y-m-d');//当前日期 
			$first=1;//$first =1 表示每周星期一为开始日期 0表示每周日为开始日期	 
			$w=date('w',strtotime($date)); 
			$now_start=date('Y-m-d 00:00:01',strtotime($date.' -'.($w?$w-$first:6).' days'));	 
			$now_end=date('Y-m-d 23:59:59',strtotime($now_start.' +6 days'));			

			$now_start = strtotime($now_start);
			$now_end = strtotime($now_end);
			
			if($now_end>=time()){
				return $this->get_tj_nums($arr);
			}			
		}

		if(strpos($tid,'days')!==false){
			$nums = str_replace('','days',$tid);
			if(is_numeric($nums)){
				return $this->get_tj_nums($arr);
			}				
		}



		$str = crc32($mystr);
		$tbs = crc32(implode('_',$tb));
		
		
		if(in_array($tid,array('yestoday','day','month','lastmonth'))){	

			if($tid=='lastmonth'){
				$day = date('Ym',strtotime('-1 month',strtotime(date('Y-m-01'))));				
			}			
			
			$m = M('Tongji');
			$map['day'] = $day;
			$map['tb'] = $tbs;
			$map['str'] = $str;
			$rtcmd = $m->where($map)->find();			
			if($rtcmd){				
				return $rtcmd['nums'];
			}else{
				$nums = $this->get_tj_nums($arr);
				if($day==date('Ymd')) return $nums;
				$data['day'] = $day;
				$data['tbs'] = implode('_',$tb);
				$data['tb'] = $tbs;
				$data['str'] = $str;
				$data['nums'] = $nums;
				$m->add($data);
				return $nums;
			}			
		}else{			
			return $this->get_tj_nums($arr);
		}
	}
	public function get_tj_nums($arr){
		/*
		tb需要统计的表
		tjtype需要统计的类型一般为count,sum
		field 需要限制查询的时间字段默认为addtime
		tid 统计的类型，昨天，今天或所有 默认为今天,当typeid为998时，为指定日期
		day 要统计的日期,格式为20170809
		tjfield统计类型的字段，默认为price
		*/
		
		$mytime = time();
		$where = empty($arr['where']) ? '1':$arr['where'];
		$tid = empty($arr['tid']) ? 'today':$arr['tid'];//0为今日1为昨日999为所有
		$field = empty($arr['field']) ? 'addtime':$arr['field'];
		$tjfield = empty($arr['tjfield']) ? 'count(*)':$arr['tjfield'];			
		if($tid=='day'){
			$day = empty($arr['day']) ? date('Ymd'):$arr['day'];
		}else{
			$day = empty($arr['day']) ? date('Ym'):$arr['day'];
		}
		
		
		$year = empty($arr['year']) ? date('Y'):$arr['year'];

		$showsql = empty($arr['showsql']) ? false:true;

		if(!$arr['tb']) return 0;		

		$tb = $arr['tb'];
		$tbon = empty($arr['tbon']) ? array():$arr['tbon'];		

		switch ($tid){
			case 'all':				
				break;
			case 'day':
				$zerotime = $this->get_zero_day($day);
				$btime = strtotime(date('Y-m-d 00:00:00',$zerotime));
				$etime = strtotime(date('Y-m-d 23:59:59',$zerotime));
				$where .= ' AND '.$field.' between '.$btime.' AND '.$etime;
				//echo ' AND '.$field.' between '.date('Y-m-d H:i',$btime).' AND '.date('Y-m-d H:i',$etime);
				//echo '<hr />';
				break;
			case 'month':
				$btime = $this->get_zero_month($day);			   
				$etime = strtotime('+1 month',$btime)-2;				
				$where .= ' AND '.$field.' between '.$btime.' AND '.$etime;				
				break;
			case 'lastmonth':
				$where .= ' AND '.$this->getmonthinfo($field,'lastmonth');			
				break;
			default:
				if(strpos($tid,'days')!==false){
					$nums = str_replace('days','',$tid);
					if(is_numeric($nums)){
						if($nums>0){
							$btime = strtotime('-'.$nums.'day',$mytime);
							$where .= ' AND '.$field.' between '.$btime.' AND '.$mytime;	
						}else{
							$where .= ' AND 1=2';
						}
					}else{
						$where .= ' AND 1=2';
					}
				}else{
					$where .= ' AND '.$this->getmonthinfo($field,$tid);
				}
				
		}
		$pre = C('DB_PREFIX');
		$sql = 'select '.$tjfield.' as `num` from ';
		$tblen = count($tb);
		if($tblen>1){
			foreach($tb as $kk=>$vv){
				$sql .= '`'.$pre.cc_table($vv).'` AS `A'.($kk+1).'`';
				if($kk<$tblen-1){
					$sql .= ' LEFT JOIN ';
				}
				if($kk>0){
					$sql .= ' ON '.$tbon[$kk-1];
				}
			}
		}else{
			$sql .= '`'.$pre.cc_table($tb[0]).'`';
		}
		if($where!=''){
			$sql .= ' where '.$where;	
		}
		$info = M('')->query($sql);		
		/*if($tid=='month'){
		echo $sql;
		echo '<br />';
		}*/
		if($showsql===true){
			echo $sql.'<br />';
			dump($info);
			echo '<hr />';
		}
		if(!$info[0]['num']) return 0;
		$nums = $info[0]['num'];
		return $nums;
	}
	function getmonthinfo($field='',$tid='today',$isnow=true){
		$addtime = time();
		$daybtime = strtotime(date('Y-m-d 00:00:00',$addtime));
		switch ($tid){
			case 'today':
				$dayetime = $addtime;
				return $field.' between '.$daybtime.' AND '.$dayetime;
				break;
			case 'yestoday':
				$yestodaybtime = $daybtime-86400;
				$yestodayetime = $yestodaybtime+86399;
				return $field.' between '.$yestodaybtime.' AND '.$yestodayetime;
				break;		
			case 'week':
				//当前日期
				$sdefaultDate = date("Y-m-d");
				//$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
				$first=1;
				//获取当前周的第几天 周日是 0 周一到周六是 1 - 6
				$w=date('w',strtotime($sdefaultDate));
				//获取本周开始日期，如果$w是0，则表示周日，减去 6 天
				$week_start=date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
				//本周结束日期
				$week_end= date('Y-m-d',strtotime("$week_start +6 days"));		
				return $field.' between '.strtotime($week_start.' 00:00:00').' AND '.strtotime($week_end.' 23:59:59');
				break;
			case 'lastweek':
				$lastweekbtime = mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y"));
				$lastweeketime = mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y"));
				return $field.' between '.$lastweekbtime.' AND '.$lastweeketime;
				break;
			case 'month':
				$monthbtime = strtotime(date('Y-m-01 00:00:00',$addtime));
				$monthetime = $isnow===true ? $addtime:strtotime(date("Y-m-d H:i:s",strtotime('+1 month -1 second',$monthbtime)));
				return $field.' between '.$monthbtime.' AND '.$monthetime;
				break;
			case 'lastmonth':
				$lastmonthetime = strtotime(date('Y-m-01 00:00:00',$addtime))-1;
				$lastmonthbtime = strtotime(date("Y-m-d 23:59:59",strtotime('-1 month',$lastmonthetime)))+1;
				return $field.' between '.$lastmonthbtime.' AND '.$lastmonthetime;
				break;
			case 'season':
				$season = ceil(date('n')/3);
				$seasonbtime = strtotime(date('Y-m-01',mktime(0,0,0,($season - 1) *3 +1,1,date('Y'))));
				$seasonetime = $isnow===true ? $addtime:strtotime(date('Y-m-t 23:59:59',mktime(0,0,0,$season * 3,1,date('Y'))));
				return $field.' between '.$seasonbtime.' AND '.$seasonetime;
				break;
			case 'lastseason':
				$season = ceil(date('n')/3);
				$lastseasonbtime = strtotime(date('Y-m-01',mktime(0,0,0,($season - 2) * 3 +1,1,date('Y'))));
				$lastseasonetime = strtotime(date('Y-m-t 23:59:59',mktime(0,0,0,($season - 1) * 3,1,date('Y'))));
				return $field.' between '.$lastseasonbtime.' AND '.$lastseasonetime;
				break;
			case 'year':
				$yearbtime = strtotime(date('Y-01-01'));
				$yearetime = $isnow===true ? $addtime:strtotime(date('Y-12-31 23:59:59'));
				return $field.' between '.$yearbtime.' AND '.$yearetime;
				break;
			case 'lastyear':
				$lastyearbtime = strtotime(date('Y-01-01',strtotime('-1 year')));
				$lastyearetime = strtotime(date('Y-12-31 23:59:59',strtotime('-1 year')));
				return $field.' between '.$lastyearbtime.' AND '.$lastyearetime;
				break;
			default:
				if(strpos($tid,'weeks')===false && strpos($tid,'months')===false) return false;
				if(strpos($tid,'weeks')===false){
					$months = str_replace('months','',$tid);
					if(!is_numeric($months)) return false;
					$months = intval($months);
					$now_start = strtotime(date('Y-m-01 00:00:01'));
					$btime = strtotime('-'.$months.' month',$now_start);
					$etime = strtotime('+1 month',$btime)-2;

					if($etime>$addtime) $etime = $addtime;

					$btime = date('Y-m-d H:i:s',$btime);
					$etime = date('Y-m-d H:i:s',$etime);
					
				}else{
					$weeks = str_replace('weeks','',$tid);				
					if(!is_numeric($weeks)) return false;
					$weeks = intval($weeks);
					$date=date('Y-m-d');//当前日期 
					$first=1;//$first =1 表示每周星期一为开始日期 0表示每周日为开始日期	 
					$w=date('w',strtotime($date)); 
					$now_start=date('Y-m-d 00:00:01',strtotime($date.' -'.($w?$w-$first:6).' days'));
					$now_start = strtotime($now_start);
					$btime = $now_start-(86400*7)*$weeks;
					$etime = ($btime+(86400*7))-2;
				}
				return $field.' between '.$btime.' AND '.$etime;
		}
	}
	function get_zero_day($day){
		$days = strtotime(substr($day,0,4).'-'.substr($day,4,2).'-'.substr($day,-2).' 00:00:01');
		return $days;
	}
	function get_zero_month($month){
		$monthtime = strtotime(substr($month,0,4).'-'.substr($month,4,2).'-01 00:00:01');
		return $monthtime;
	}
}