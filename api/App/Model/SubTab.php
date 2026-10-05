<?php
/**
说明：此类函数是带分表分库相关操作的类,powered by niutou 
====mysql_list====
通用分库分表查询集合函数
必填数组项name,查询表名称驼峰命名法,一维数组
limit 可选项 输出的行数
field为空时查询单表所有数据,有值则是第一个表字段加上参数字段，自动加,号
当有多个name数组值时进行join查询,默认jointype为LEFT JOIN
on,可选项,必须为数组，当指定了这个一维数组时on后面自动匹配，这个数组的位数应该是name的位数减1,默认的主次表关联字段为itemid

where,查询条件,不要带where
order,排序不要带order by
db_config，是否是分库，配置文件里的数据库配置

ispage,就否分页
ajax,是否为ajax，当ispage为1时有效

分表相关
tabnum为第几个表自动会加上后辍
优先uid,id不为空时会自动根据ceil(id或是uid/sub_table_nums)获得到表名,当有id时sub_table_nums为必填，否则无效
====insert_on_all====

当arr参数里有merge不为空时且fenbiao=1时，当新增加表_ax的时候会自动更新从1到nums
 */
namespace Models\SubTab;
class SubTab{
	//单个表数据更新
	public function update_one($name='',$arr,$nums=null,$autofill=0){
		if($name==''){
			return false;
		}
		if(!empty($nums)){
			$name = $name.C('DB_SUB_AFT').$nums;
		}		
		$data = $arr['data'];
		$id = I('id',1,'post');
		$field = empty($arr['field']) ? '':$arr['field'];		

		if($id==0){
			return false;
		}

		$map = $field == '' ? '`id`='.$id:'`'.$arr['field'].'`='.$id;

		$db_server = empty($arr['db_server']) ? 0:intval($arr['db_server']);
		
		$showsql = isset($arr['showsql']) ? $arr['showsql']:false;
		if($showsql!==true) $showsql = false;

		//echo $map;

		if($db_server>0){
			$m = M($name,C('DB_PREFIX'),'DB_CONFIG'.$db_server);
		}else{
			$m = M($name);
		}		
		
		if(isset($data['id'])) unset($data['id']);

		$rtcmd = $m->where($map)->find();		
		if(!$rtcmd){
			if($autofill==1){
				$data['id'] = $id;
				$rt = $m->where($map)->add($data);
			}
			
		}else{
			$rt = $m->where($map)->save($data);
		}
		if($showsql===true) file_put_contents(ROOT.'static/showsql.html',$m->getlastsql());
		//echo $m->getlastsql();
		//echo '<br />';		
		//exit;
		//exit;
		return $rtcmd['id'];
	}
	//单个表数据插入
	public function insert_one($name='',$arr,$nums='',$auto_create_table=0){
		$data = $arr['data'];		

		if($name==''){
			return false;
		}

		$fenbiao = $arr['fenbiao'];
		if($fenbiao!=1){
			$fenbiao = 0;
		}


		//$nums = intval($nums);

		$db_server = empty($arr['db_server']) ? 0:intval($arr['db_server']);
		$fenbiao_type = empty($arr['fenbiao_type']) ? '':$arr['fenbiao_type'];
		$merge = empty($arr['merge']) ? '':$arr['merge'];

		
		$autocreatebzt = false;

		if($auto_create_table==1){
			if(check_table_isext($name.C('DB_SUB_AFT').$nums,$db_server)!==true){
				$rtcmd = auto_table($name,$nums,$db_server,$merge,$fenbiao,$fenbiao_type);				
				if($rtcmd===false){					
					return false;
				}else{
					$autocreatebzt = true;
				}
			}

		}
		if($fenbiao==1){
			$name = $name.C('DB_SUB_AFT').$nums;		
		}else{
			//$data['id'] = null;
		}
		if($db_server>0){
			$m = M($name,C('DB_PREFIX'),'DB_CONFIG'.$db_server);
		}else{
			$m = M($name);
		}		
		$rt = $m->add($data);
		if($fenbiao==1) return $arr['data']['id'];
		//file_put_contents(ROOT.'rt.txt',$m->getlastsql());

		return $rt;

	}
	//KEY表插入
	public function insert_key($name,$auto_create_table=0,$db_server=0){
		if($auto_create_table!=1){
			return ;
		}
		auto_table_key($name,$db_server);
		if($db_server>0){
			$m = M($name.'Key',C('DB_PREFIX'),'DB_CONFIG'.$db_server);
		}else{
			$m = M($name.'Key');
		}
		$rtcmd = $m->add(array('id1'=>1));
		if($rtcmd!==false){
			return $rtcmd;
		}else{
			return false;
		}
	}
	//单个表数据删除
	public function delete_one($name='',$arr,$nums=null,$field='id'){
		if($name==''){
			return false;
		}
		$db_server = empty($arr['db_server']) ? '':$arr['db_server'];
		if(!empty($nums)){
			$name = $name.C('DB_SUB_AFT').$nums;
		}
		if($db_server>0){
			$m = M($name,C('DB_PREFIX'),'DB_CONFIG'.$db_server);
		}else{
			$m = M($name);
		}
		$id = $arr['id'];
		$map = $field=='' ? '`id`':'`itemid`';
		$map .= '='.$id;
		return $rt;
	}
	//通用查询
	public function mysql_list($arr=null){
		$jointype = empty($arr['jointype']) ? 'LEFT JOIN':$arr['jointype'];
		$db_server = empty($arr['db_server']) ? 0:intval($arr['db_server']);
		$showsql = empty($arr['showsql']) ? false:true;
		$tb_name = $arr['name'];
		$pre = C('DB_PREFIX');

		if(!is_array($tb_name)){
			return false;
		}

		if($db_server>0){
			$m = M('',C('DB_PREFIX'),'DB_CONFIG'.$db_server);
		}else{
			$m = M('');
		}

		//查询方式，0按自动查，按指定的on查,on
		$tid=0;
		if(!empty($arr['on'])) $tid = 1;		
		$field = empty($arr['field']) ? '':$arr['field'];
		if($field==''){
			$field = '`A1`.*';
		}else{
			if(strpos($field,'A1`')!==false){
			}else{
				$field = '`A1`.*'.','.$arr['field'];
			}
		}
		$where = empty($arr['where']) ? '':$arr['where'];
		$order = empty($arr['order']) ? '':$arr['order'];
		$group=empty($arr['group']) ? '':$arr['group'];
		$countField=empty($arr['count_field'])?'':$arr['count_field'];
		$ajax = empty($arr['ajax']) ? '':$arr['ajax'];
		$ispage = empty($arr['ispage']) ? 0:1;
		$limit = empty($arr['limit']) ? '1':$arr['limit'];
		$tb_nums = empty($arr['nums']) ? 0:intval($arr['nums']);

		$tabnum = empty($arr['tabnum']) ? 0:intval($arr['tabnum']);
		

		//$limit==0 && $limit = 10;
		//$limit>50000 && $limit = 10;

		
		//判断分表
		$sub_table_nums = empty($arr['sub_table_nums']) ? 0:intval($arr['sub_table_nums']);
		$sub_table_nums == 0 && $tabnum = 0;
		$id = empty($arr['id']) ? 0:intval($arr['id']);
		$uid = empty($arr['uid']) ? 0:intval($arr['uid']);
		$fenbiao = empty($arr['fenbiao']) ? 0:intval($arr['fenbiao']);
		$nosub = empty($arr['nosub']) ? 0:1;

		$sql = '';



		if($fenbiao==1){
			if ($tb_nums) {
				$tabnum = $tb_nums;
			} else {
				if($uid>0){
					if($sub_table_nums==0) return ;
					$tabnum = get_table_nums($uid,$sub_table_nums);
				}else if($id>0){
					if($sub_table_nums==0) return ;
					$tabnum = get_table_nums($id,$sub_table_nums);
				}
			}
		}else{
			$tabnum = '';
		}

		//多表查询
		$tb_on = empty($arr['on']) ? array():$arr['on'];
		foreach($tb_name as $key=>$v){
			if($tabnum>0&&$fenbiao==1){
				if($key>0&&$nosub==1){
					//$v = $v;
				}else{
					$v = $v.C('DB_SUB_AFT').$tabnum;
				}
			}
			$v = cc_table($v);
			$tb = $pre.$v;
			$tb1 = 'A'.($key);
			$tb2 = 'A'.($key+1);

			if($key==0){
				$sql = ' `'.$tb.'` AS `A1`';
			}else{
				$sql .= ' '.$jointype.' `'.$tb.'` AS `'.$tb2.'` ON ';
				if($tid==0){
					$sql .= $tb_on;
				}else{
					$sql .= $tb_on[$key-1];
				}
			}
		}
		if($where!=''){
			$sql .= ' WHERE '.$where;
		}
		
		$timeout = empty($arr['timeout']) ? 60:intval($arr['timeout']);	

		$count = 0;

		//分页
		if($ispage==1){
			$csql = 'SELECT count(*) AS `tpcount` FROM '.$sql;
			if($countField) $csql = 'SELECT count('.$countField.') AS `tpcount` FROM '.$sql;			
			$countarr = $m->query($csql);				
			if($showsql===true) file_put_contents(ROOT.'static/showsql.html',$csql);
			if($countarr) $count = $countarr[0]['tpcount'];
			$perpage = empty($arr['limit']) ? 25:intval($arr['limit']);			
			$isnums = empty($arr['isnums']) ? 1:0;
			$pagename = empty($arr['pagename']) ? 'p':$arr['pagename'];
			$nowpage =  I($pagename,1,'');
			$nowpage==0 && $nowpage = 1;
			if($nowpage>100){
				$nowpage = 100;
				$count = 100*$perpage;
			}
			$totalpage = ceil($count/$perpage);
			if(intval($count)>0){
				$limitstr = (($nowpage-1) * $perpage).','.$limit;
				if($group!=''){
					$sql .= ' GROUP BY '.$group;
				}
				if($order!=''){
					$sql .= ' ORDER BY '.$order;
				}

				$sql ='SELECT '.$field.' FROM '.$sql.' LIMIT '.$limitstr;
				$list = $m->query($sql);
				$newarr = array('list'=>$list,'nums'=>$count,'perpage'=>$perpage);				
				if($showsql===true) file_put_contents(ROOT.'static/showsql.html',$sql);
				return $newarr;
			}else{
				return array('list'=>null,'nums'=>0,'perpage'=>$perpage);
			}
		}else{
			//非分页直接输出数组
			if($group!=''){
				$sql .= ' GROUP BY '.$group;
			}
			if($order!=''){
				$sql .= ' ORDER BY '.$order;
			}

			$sql ='SELECT '.$field.' FROM '.$sql;
			$sql .= ' LIMIT '.$limit;					
			$list = $m->query($sql);
			if($showsql===true) file_put_contents(ROOT.'static/showsql.html',$sql);
			if(!$list){
				return false;
			}
			if($limit==1){
				$newlist = $list[0];
			}else{
				$newlist = $list;
			}					
			if($showsql===true) file_put_contents(ROOT.'static/showsql.html',$sql);	
			
			return $newlist;
		}
	}
	//主表和子表数据单个删除
	public function update($arr){
		$data = $arr['data'];
		$data['id'] = null;
		$data['itemid'] = null;



		if(!is_array($data)){
			return false;
		}

		$namelist = $arr['name'];
		if(!is_array($namelist)){
			return false;
		}
		$name = $namelist[0];



		$id = I('id',1,'post');


		$sub_table_nums = intval($arr['sub_table_nums']);
		$uid = empty($arr['uid']) ? 0:intval($arr['uid']);
		$fenbiao = empty($arr['fenbiao']) ? 0:1;
		$autofill = empty($arr['autofill']) ? 0:1;

		if($sub_table_nums==0 && $fenbiao==1){
			return false;
		}


		//计算表名
		if($fenbiao==1){
			if(!empty($arr['nums'])){
				$nums = $arr['nums'];
			}else if($uid>0){
				$nums = get_table_nums($uid,$sub_table_nums,'');
			}else{
				$nums = get_table_nums($id,$sub_table_nums,'');
			}
		}else{
			$nums = null;
		}
		$rtcmd = $this->update_one($name,$arr,$nums);
		//如果有子表，循环子表
		if(count($namelist)>1){
			foreach($namelist as $key=>$v){
				if($key>0){
					$names = $v;
					$arr['field'] = 'id';
					$this->update_one($names,$arr,$nums,$autofill);
				}
			}
		}
		return $rtcmd;

	}




	//主表和子表数据单个插入
	function insert($arr){
		$data = $arr['data'];
		if(!is_array($data)){
			return false;
		}

		$namelist = $arr['name'];
		if(!is_array($namelist)){
			return false;
		}
		$sub_table_nums = intval($arr['sub_table_nums']);


		$name = $namelist[0];


		$detail = $arr['detail'];
				
		$db_server = empty($arr['db_server']) ? 0:intval($arr['db_server']);
		$auto_create_table = $arr['auto_create_table']===1 ? 1:0;
		$tb_nums = empty($arr['nums']) ? 0:intval($arr['nums']);

		$uid = empty($arr['uid']) ? 0:intval($arr['uid']);
		$fenbiao = empty($arr['fenbiao']) ? 0:1;
		$fenbiao_type = empty($arr['fenbiao_type']) ? '':$arr['fenbiao_type'];

		if($sub_table_nums==0 && $fenbiao==1){
			return false;
		}		


		
		if($fenbiao===1){

			$id = $this->insert_key($name,$auto_create_table,$db_server);

			if($id===false){
				return false;
			}
			$arr['data']['id'] = $id;

		}else{
			$id = 0;
		}
		
		//$arr['data']['itemid'] = $id;

		//计算表名
		if($fenbiao_type!=''){
			$nums = $fenbiao_type;
		}else if($uid>0){
			$nums = get_table_nums($uid,$sub_table_nums,'');
		}else if($tb_nums>0){
			$nums = $tb_nums;
		}else if($id>0){
			$nums = get_table_nums($id,$sub_table_nums,'');
		}
		
		$rt = $this->insert_one($name,$arr,$nums,$auto_create_table);
		if($rt===false){
			return false;
		}
		$id = $rt;

		//如果有子表，循环子表
		if(count($namelist)>1){			
			$arr['data']['id'] = $rt;
			foreach($namelist as $key=>$v){
				if($key>0){
					$names = $name.$v;
					$rtcmd = $this->insert_one($names,$arr,$nums,$auto_create_table);
					if($rtcmd===false){
						//return false;
					}
				}
			}
		}
		return $id;
	}





	/*删除功能*/
	//主表和子表数据删除
	function del($arr){

		$namelist = $arr['name'];
		if(!is_array($namelist)){
			return false;
		}
		$name = $namelist[0];
		$sub_table_nums = intval($arr['sub_table_nums']);




		if($sub_table_nums==0){
			return false;
		}
		$db_server = empty($arr['db_server']) ? 0:intval($arr['db_server']);
		$uid = empty($arr['uid']) ?'':intval($arr['uid']);


		$id = intval($arr['id']);
		if($id==0){
			return false;
		}


		//计算表名
		$fenbiao = empty($arr['fenbiao']) ? 0:1;
		if($fenbiao===1){
			if($uid>0){
				$nums = get_table_nums($uid,$sub_table_nums,'');
			}else{
				$nums = get_table_nums($id,$sub_table_nums,'');
			}
		}else{
			$nums = null;
		}

		$rt = $this->delete_one($name,$arr,$nums,'');
		if($rt===false){
			return false;
		}
		//如果有子表，循环子表
		if(count($namelist)>0){
			foreach($namelist as $key=>$v){
				if($key>0){
					$names = $name.$v;
					$arr['name'] = $names;
					$rtcmd = $this->delete_one($names,$arr,$nums,'itemid');
					if($rtcmd===false){
						return false;
					}
				}

			}
		}
		return $id;

	}

}
?>