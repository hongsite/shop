<?php
//能用关联查询
namespace Models\Join;
use Model\Model;
class Join extends Model {
	//字段名,join类型,表名,on条件,where,排序,限制,是否分页
	public function getValue($field,$jointype='LEFT JOIN',$arr,$arron,$where='',$order='',$limit=10,$ispage=0,$ajax='',$mem='',$db=''){
		$jointype == '' && $jointype = 'JOIN';
		if($db!=''){
			$m = M('',C('DB_PREFIX'),$db);
		}else{
			$m = M();
		}
		if($arr){
			$pre = C('DB_PREFIX');
			foreach($arr as $key=>$v){
				$tb = $pre.$v;
				$tbjx = 'A'.($key+1);
				if($key==0){
				    $sql = '`'.$tb.'` AS `'.$tbjx.'`';
				}else{
					$sql .= ' '.$jointype.' `'.$tb.'` AS `'.$tbjx.'` ON '.$arron[$key-1];
				}
			}			
			$limit==0 && $limit = 10;
			$limit>500 && $limit = 10;
			$perpage = $limit;
			if($where!=''){
				$sql .= ' WHERE '.$where;
			}			
			if($ispage==1){
				$csql = 'SELECT count(*) AS `tpcount` FROM '.$sql;				
				$iscount = false;				
				if($iscount!==true){
					$countarr = $m->query($csql);
					if($countarr){
						$count = $countarr[0]['tpcount'];
					}
				}
				$nowpage =  I('p',1,'');
				$nowpage==0 && $nowpage = 1;
				$totalpage = ceil($count/$perpage);
				if(intval($count)>0){
				    $limitstr = (($nowpage-1) * $perpage).','.$perpage;
					if($order!=''){
						$sql .= ' ORDER BY '.$order;
					}
					$sql ='SELECT '.$field.' FROM '.$sql.' LIMIT '.$limitstr;
					
					$list = $m->query($sql);
					
					$newarr = array('list'=>$list,'nums'=>$count);					
					return $newarr;
				}			
			}else{
				if($order!=''){
					$sql .= ' ORDER BY '.$order;
				}
				$sql ='SELECT '.$field.' FROM '.$sql;			
				$sql .= ' LIMIT '.$limit;				
				$list = $m->query($sql);
				//echo $m->getlastsql();
				if($limit=='1'){
					$newlist = $list[0];
				}else{
					$newlist = $list;
				}				
				//
				return $newlist;
			}
		}
	}
	public function getTb($mem=0,$db='',$field='*',$tb='',$map='',$ord='',$limit='10'){
		if($tb=='') return ;
		$sql = 'f_'.$field;
		$sql .= '_t_'.$tb;
		$map!='' && $sql .= '_m_'.$map;
		$ord!='' && $sql .= '_o_'.$ord;
		$limit!='' && $sql .= '_l_'.$limit;			
		if($db!=''){
			$m = M($tb,C('DB_PREFIX'),$db);
		}else{
			$m = M($tb);
		}
		$list = $m->field($field)->where($map)->order($ord)->limit($limit)->select();
		if($list){
			if($limit==1){
				$list = $list[0];
			}			
			return $list;
		}
	}
	
	//跨库 copy function getValue
	public function getValueCopy($sql,$field,$jointype='LEFT JOIN',$arr,$arron,$where='',$order='',$limit=10,$ispage=0,$ajax='',$mem='',$db=''){
		$jointype == '' && $jointype = 'JOIN';
		$m = M();
		if($arr){
			
			$limit==0 && $limit = 10;
			$limit>500 && $limit = 10;
			$perpage = $limit;
			if($where!=''){
				$sql .= ' WHERE '.$where;
			}
			
			if($ispage==1){
				$csql = 'SELECT count(*) AS `tpcount` FROM '.$sql;				
				$iscount = false;
				
				if($iscount!==true){
					$countarr = $m->query($csql);
					if($countarr){
						$count = $countarr[0]['tpcount'];
					}
				}
				$nowpage =  I('p',1,'');
				$nowpage==0 && $nowpage = 1;
				$totalpage = ceil($count/$perpage);
				if(intval($count)>0){
				    $limitstr = (($nowpage-1) * $perpage).','.$perpage;
					if($order!=''){
						$sql .= ' ORDER BY '.$order;
					}
					$sql ='SELECT '.$field.' FROM '.$sql.' LIMIT '.$limitstr;
					$list = $m->query($sql);
					
					$newarr = array('list'=>$list,'nums'=>$count);
					return $newarr;
				}			
			}
		}
	}
	public function getField($arr,$letter='`A1`'){		
		$pre = C('DB_PREFIX');
		foreach($arr as $key=>$v){			
			$sql = 'SHOW FULL COLUMNS FROM `'.$pre.cc_table($v).'`';			
			$arr = M('')->query($sql);
			foreach($arr as $kkk=>$vvv){
				if(strtolower($vvv['Field'])!='id'){
					$fieldarr[] = $letter.'.`'.$vvv['Field'].'`';					
				}
			}
		}
		return implode(',',$fieldarr);
	}
}
?>