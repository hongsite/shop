<?php
/*
*@author 牛头
*@company 鸿思特科技
*@Controller 数据库中间层
*@date 2026/9/28
*@version v1.0.0
*/
namespace Db;
class Db {    
    protected $dbType     = null;   
    protected $autoFree   = false;
    protected $model      = '_hst_';
    protected $pconnect   = false;
    protected $queryStr   = '';
    protected $modelSql   = array();
    protected $lastInsID  = null;
    protected $numRows    = 0;
    protected $numCols    = 0;
    protected $transTimes = 0;
    protected $error      = '';
    protected $linkID     = array();
    protected $_linkID    = null;
    protected $queryID    = null;
    protected $connected  = false;
    protected $config     = '';
    protected $exp = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN','not in'=>'NOT IN','between'=>'BETWEEN','notbetween'=>'NOT BETWEEN','not between'=>'NOT BETWEEN');
    protected $selectSql  = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
    protected $bind       = array();
    public static function getInstance() {
        $args = func_get_args();
        return get_instance_of(__CLASS__,'factory',$args);
    }    
    public function factory($db_config=''){
		// 读取数据库配置
        $db_config = $this->parseConfig($db_config);
        if(empty($db_config['DB_TYPE']))
            throw_exception('_NO_DB_CONFIG_');
        // 数据库类型
        $this->dbType = 'Mysqli';
        $class = 'DbMysqli\\DbMysqli';
        $db = new $class($db_config);
        return $db;
    }
    protected function _getDsnType($dsn) {
        $match  =  explode(':',$dsn);
        $dbType = strtoupper(trim($match[0]));
        return $dbType;
    }
    private function parseConfig($db_config=''){
		if(is_array($db_config)){
			//$db_config =   array_change_key_case($db_config);
            $db_config = array(
                  'DB_TYPE'=>$db_config['DB_TYPE'],
                  'DB_USER'=>$db_config['DB_USER'],
                  'DB_PWD'=>$db_config['DB_PWD'],
                  'DB_HOST'=>$db_config['DB_HOST'],
                  'DB_PORT'=>$db_config['DB_PORT'],
                  'DB_NAME'=>$db_config['DB_NAME'],
                  //'DB_DSN'=>$db_config['DB_DSN'],
                  //'DB_PARAMS'=>$db_config['DB_PARAMS'],
             );
        }else if(empty($db_config)){
           $db_config = array(
                    'DB_TYPE'=>C('DB_TYPE'),
                    'DB_USER'=>C('DB_USER'),
                    'DB_PWD'=>C('DB_PWD'),
                    'DB_HOST'=>C('DB_HOST'),
                    'DB_PORT'=>C('DB_PORT'),
                    'DB_NAME'=>C('DB_NAME'),
                    //'DB_DSN'=>C('DB_DSN'),
                    //'DB_PARAMS'=>C('DB_PARAMS'),
                );
        }
        return $db_config;
    }
    protected function initConnect($master = true){
		if (!$this->connected) {
			$this->_linkID = $this->connect();
		}
	}
    protected function debug() {
        $this->modelSql[$this->model]   =  $this->queryStr;
        $this->model  =   '_hst_';
    }	
    protected function multiConnect($master=false){
        
		static $_config = array();
        if(empty($_config)) {
            // 缓存分布式数据库配置解析
            foreach ($this->config as $key=>$val){
                $_config[$key]      =   explode(',',$val);
            }
        }
        // 数据库读写是否分离
        $r = floor(mt_rand(0,count($_config['DB_HOST'])-1));
        $db_config = array(
            'DB_USER'  =>  isset($_config['DB_USER'][$r])?$_config['DB_USER'][$r]:$_config['DB_USER'][0],
            'DB_PWD'  =>  isset($_config['DB_PWD'][$r])?$_config['DB_PWD'][$r]:$_config['DB_PWD'][0],
            'DB_HOST'  =>  isset($_config['DB_HOST'][$r])?$_config['DB_HOST'][$r]:$_config['DB_HOST'][0],
            'DB_PORT'  =>  isset($_config['DB_PORT'][$r])?$_config['DB_PORT'][$r]:$_config['DB_PORT'][0],
            'DB_NAME'  =>  isset($_config['DB_NAME'][$r])?$_config['DB_NAME'][$r]:$_config['DB_NAME'][0],
        );
        return $this->connect($db_config,$r);	
		
		
    }
    protected function parseLock($lock=false) {
        if(!$lock) return '';
        if('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }
    protected function parseSet($data) {
        $set = null;
		foreach ($data as $key=>$val){
            if(is_array($val) && 'exp' == $val[0]){
                $set[]  =   $this->parseKey($key).'='.$val[1];
            }elseif(is_scalar($val) || is_null(($val))){ // 过滤非标量数据
              if(C('DB_BIND_PARAM') && 0 !== strpos($val,':')){
                $name   =   md5($key);
                $set[]  =   $this->parseKey($key).'=:'.$name;
                $this->bindParam($name,$val);
              }else{
                $set[]  =   $this->parseKey($key).'='.$this->parseValue($val);
              }
            }
        }
		$setstr = '';
		if($set) $setstr = implode(',',$set);
        return ' SET '.$setstr;
    }
    protected function bindParam($name,$value){
        $this->bind[':'.$name]  =   $value;
    }
    protected function parseBind($bind){
        $bind           =   array_merge($this->bind,$bind);
        $this->bind     =   array();
        return $bind;
    }
    protected function parseKey(&$key) {
        return $key;
    }
    protected function parseValue($value){
		if (is_string($value)) {
			$value = '\'' . $this->escapeString($value) . '\'';
		} elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
			$value = $this->escapeString($value[1]);
		} elseif (is_array($value)) {
			$value = array_map(array($this, 'parseValue'), $value);
		} elseif (is_bool($value)) {
			$value = $value ? '1' : '0';
		} elseif (is_null($value)) {
			$value = 'null';
		}
		return $value;
	}
    protected function parseField($fields) {
        if(is_string($fields) && strpos($fields,',')) {
            $fields    = explode(',',$fields);
        }
        if(is_array($fields)){           
            $array   =  array();
            foreach ($fields as $key=>$field){
                if(!is_numeric($key))
                    $array[] =  $this->parseKey($key).' AS '.$this->parseKey($field);
                else
                    $array[] =  $this->parseKey($field);
            }
            $fieldsStr = implode(',', $array);
        }elseif(is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->parseKey($fields);
        }else{
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }
    protected function parseTable($tables) {
        if(is_array($tables)){
            $array   =  array();
            foreach ($tables as $table=>$alias){
                if(!is_numeric($table))
                    $array[] =  $this->parseKey($table).' '.$this->parseKey($alias);
                else
                    $array[] =  $this->parseKey($table);
            }
            $tables  =  $array;
        }elseif(is_string($tables)){
            $tables  =  explode(',',$tables);
            array_walk($tables, array(&$this, 'parseKey'));
        }
        return implode(',',$tables);
    }   
    // 修复 parseWhere 的拼接逻辑
	protected function parseWhere($where){
		$whereStr = '';
		if (is_string($where)) {
			$whereStr = $where;
		} else {
			$operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
			if (in_array($operate, array('AND', 'OR', 'XOR'))) {
				$operate = ' ' . $operate . ' ';
				unset($where['_logic']);
			} else {
				$operate = ' AND ';
			}
			$conditions = array();
			foreach ($where as $key => $val) {
				if (is_numeric($key)) {
					$key = '_complex';
				}
				if (0 === strpos($key, '_')) {
					$conditions[] = $this->parseThinkWhere($key, $val);
				} else {
					if (!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/', trim($key))) {
						throw_exception(L('_EXPRESS_ERROR_') . ':' . $key);
					}
					$multi = is_array($val) && isset($val['_multi']);
					$key = trim($key);
					if (strpos($key, '|')) {
						$array = explode('|', $key);
						$str = array();
						foreach ($array as $m => $k) {
							$v = $multi ? $val[$m] : $val;
							$str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
						}
						$conditions[] = implode(' OR ', $str);
					} elseif (strpos($key, '&')) {
						$array = explode('&', $key);
						$str = array();
						foreach ($array as $m => $k) {
							$v = $multi ? $val[$m] : $val;
							$str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
						}
						$conditions[] = implode(' AND ', $str);
					} else {
						$conditions[] = $this->parseWhereItem($this->parseKey($key), $val);
					}
				}
			}
			if (!empty($conditions)) {
				$whereStr = implode($operate, $conditions);
			}
		}
		return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
	}
    protected function parseWhereItem($key,$val) {
        $whereStr = '';
        if(is_array($val)) {
            if(is_string($val[0])) {
				$exp	=	strtolower($val[0]);
                if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i',$val[0])){
                    $whereStr .= $key.' '.$this->exp[$exp].' '.$this->parseValue($val[1]);
                }elseif(preg_match('/^(NOTLIKE|LIKE)$/i',$val[0])){
                    if(is_array($val[1])) {
                        $likeLogic  =   isset($val[2])?strtoupper($val[2]):'OR';
                        if(in_array($likeLogic,array('AND','OR','XOR'))){
                            $like       =   array();
                            foreach ($val[1] as $item){
                                $like[] = $key.' '.$this->exp[$exp].' '.$this->parseValue($item);
                            }
                            $whereStr .= '('.implode(' '.$likeLogic.' ',$like).')';                          
                        }
                    }else{
                        $whereStr .= $key.' '.$this->exp[$exp].' '.$this->parseValue($val[1]);
                    }
                }elseif('exp'==$exp){
                    $whereStr .= ' ('.$key.' '.$val[1].') ';
                }elseif(preg_match('/^(NOTIN|NOT IN|IN)$/i',$val[0])){
                    if(isset($val[2]) && 'exp'==$val[2]) {
                        $whereStr .= $key.' '.$this->exp[$exp].' '.$val[1];
                    }else{
                        if(is_string($val[1])) {
                             $val[1] =  explode(',',$val[1]);
                        }
                        $zone      =   implode(',',$this->parseValue($val[1]));
                        $whereStr .= $key.' '.$this->exp[$exp].' ('.$zone.')';
                    }
                }elseif(preg_match('/^(NOTBETWEEN|NOT BETWEEN|BETWEEN)$/i',$val[0])){
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $whereStr .=  ' ('.$key.' '.$this->exp[$exp].' '.$this->parseValue($data[0]).' AND '.$this->parseValue($data[1]).' )';
                }else{
                    throw_exception(L('_EXPRESS_ERROR_').':'.$val[0]);
                }
            }else {
                $count = count($val);
                $rule  = isset($val[$count-1])?strtoupper($val[$count-1]):'';
                if(in_array($rule,array('AND','OR','XOR'))) {
                    $count  = $count -1;
                }else{
                    $rule   = 'AND';
                }
                for($i=0;$i<$count;$i++) {
                    $data = is_array($val[$i])?$val[$i][1]:$val[$i];
                    if('exp'==strtolower($val[$i][0])) {
                        $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                    }else{
                        $op = is_array($val[$i])?$this->exp[strtolower($val[$i][0])]:'=';
                        $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                    }
                }
                $whereStr = substr($whereStr,0,-4);
            }
        }else{            
            if(C('DB_LIKE_FIELDS') && preg_match('/^('.C('DB_LIKE_FIELDS').')$/i',$key)) {
                $val  =  '%'.$val.'%';
                $whereStr .= $key.' LIKE '.$this->parseValue($val);
            }else {
                $whereStr .= $key.' = '.$this->parseValue($val);
            }
        }
        return $whereStr;
    }   
    protected function parseThinkWhere($key,$val) {
        $whereStr   = '';
        switch($key) {
            case '_string':
                $whereStr = $val;
                break;
            case '_complex':
                $whereStr   =   is_string($val)? $val : substr($this->parseWhere($val),6);
                break;
            case '_query':
                parse_str($val,$where);
                if(isset($where['_logic'])) {
                    $op   =  ' '.strtoupper($where['_logic']).' ';
                    unset($where['_logic']);
                }else{
                    $op   =  ' AND ';
                }
                $array   =  array();
                foreach ($where as $field=>$data)
                    $array[] = $this->parseKey($field).' = '.$this->parseValue($data);
                $whereStr   = implode($op,$array);
                break;
        }
        return $whereStr;
    }
    protected function parseLimit($limit) {
        return !empty($limit)?   ' LIMIT '.$limit.' ':'';
    }
    protected function parseJoin($join) {
        $joinStr = '';
        if(!empty($join)) {
            if(is_array($join)) {
                foreach ($join as $key=>$_join){
                    if(false !== stripos($_join,'JOIN'))
                        $joinStr .= ' '.$_join;
                    else
                        $joinStr .= ' LEFT JOIN ' .$_join;
                }
            }else{
                $joinStr .= ' LEFT JOIN ' .$join;
            }
        }
		$joinStr = preg_replace_callback("/__([A-Z_-]+)__/sU",function($match){return C("DB_PREFIX").strtolower($match[1]);},$joinStr);
        return $joinStr;
    }    
    protected function parseOrder($order) {
        if(is_array($order)) {
            $array   =  array();
            foreach ($order as $key=>$val){
                if(is_numeric($key)) {
                    $array[] =  $this->parseKey($val);
                }else{
                    $array[] =  $this->parseKey($key).' '.$val;
                }
            }
            $order   =  implode(',',$array);
        }
        return !empty($order)?  ' ORDER BY '.$order:'';
    }    
    protected function parseGroup($group) {
        return !empty($group)? ' GROUP BY '.$group:'';
    }    
    protected function parseHaving($having) {
        return  !empty($having)?   ' HAVING '.$having:'';
    }    
    protected function parseComment($comment) {
        return  !empty($comment)?   ' /* '.$comment.' */':'';
    }    
    protected function parseDistinct($distinct) {
        return !empty($distinct)?   ' DISTINCT ' :'';
    }    
    protected function parseUnion($union) {
        if(empty($union)) return '';
        if(isset($union['_all'])) {
            $str  =   'UNION ALL ';
            unset($union['_all']);
        }else{
            $str  =   'UNION ';
        }
        foreach ($union as $u){
            $sql[] = $str.(is_array($u)?$this->buildSelectSql($u):$u);
        }
        return implode(' ',$sql);
    }    
    public function insert($data,$options=array(),$replace=false) {
        $values  =  $fields    = array();
        $this->model  =   $options['model'];
        foreach ($data as $key=>$val){
            if(is_array($val) && 'exp' == $val[0]){
                $fields[]   =  $this->parseKey($key);
                $values[]   =  $val[1];
            }elseif(is_scalar($val) || is_null(($val))){
              $fields[]   =  $this->parseKey($key);
              if(C('DB_BIND_PARAM') && 0 !== strpos($val,':')){
                $name       =   md5($key);
                $values[]   =   ':'.$name;
                $this->bindParam($name,$val);
              }else{
                $values[]   =  $this->parseValue($val);
              }                
            }
        }
        $sql   =  ($replace?'REPLACE':'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql   .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
        $sql   .= $this->parseComment(!empty($options['comment'])?$options['comment']:'');
        return $this->execute($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
    }    
    public function selectInsert($fields,$table,$options=array()) {
        $this->model  =   $options['model'];
        if(is_string($fields))   $fields    = explode(',',$fields);
        array_walk($fields, array($this, 'parseKey'));
        $sql   =    'INSERT INTO '.$this->parseTable($table).' ('.implode(',', $fields).') ';
        $sql   .= $this->buildSelectSql($options);
        return $this->execute($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
    }    
    public function update($data,$options) {
        $this->model  =   $options['model'];
        $sql   = 'UPDATE '
            .$this->parseTable($options['table'])
            .$this->parseSet($data)
            .$this->parseWhere(!empty($options['where'])?$options['where']:'')
            .$this->parseOrder(!empty($options['order'])?$options['order']:'')
            .$this->parseLimit(!empty($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock'])?$options['lock']:false)
            .$this->parseComment(!empty($options['comment'])?$options['comment']:'');
        return $this->execute($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
    }    
    public function delete($options=array()) {
        $this->model  =   $options['model'];
        $sql   = 'DELETE FROM '
            .$this->parseTable($options['table'])
            .$this->parseWhere(!empty($options['where'])?$options['where']:'')
            .$this->parseOrder(!empty($options['order'])?$options['order']:'')
            .$this->parseLimit(!empty($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock'])?$options['lock']:false)
            .$this->parseComment(!empty($options['comment'])?$options['comment']:'');
        return $this->execute($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
    }    
    public function select($options=array()) {
        $this->model  =   $options['model'];
        $sql    = $this->buildSelectSql($options);
        $cache  =  isset($options['cache'])?$options['cache']:false;
        if($cache) {
            $key    =  is_string($cache['key'])?$cache['key']:md5($sql);
            $value  =  S($key,'',$cache);
            if(false !== $value) {
                return $value;
            }
        }
        $result   = $this->query($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
        if($cache && false !== $result ){
            S($key,$result,$cache);
        }
        return $result;
    }   
    public function buildSelectSql($options=array()) {
        if(isset($options['page'])){            
            if(strpos($options['page'],',')) {
                list($page,$listRows) =  explode(',',$options['page']);
            }else{
                $page = $options['page'];
            }
            $page    =  $page?$page:1;
            $listRows=  isset($listRows)?$listRows:(is_numeric($options['limit'])?$options['limit']:20);
            $offset  =  $listRows*((int)$page-1);
            $options['limit'] =  $offset.','.$listRows;
        }
        if(C('DB_SQL_BUILD_CACHE')){
            $key    =  md5(serialize($options));
            $value  =  S($key);
            if(false !== $value) {
                return $value;
            }
        }
        $sql  =   $this->parseSql($this->selectSql,$options);
        $sql .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
        if(isset($key)){
            //S($key,$sql,array('expire'=>0,'length'=>C('DB_SQL_BUILD_LENGTH'),'queue'=>C('DB_SQL_BUILD_QUEUE')));
        }
        return $sql;
    }
    public function parseSql($sql, $options = array()){
		$where = isset($options['where']) ? $options['where'] : '';
		$where = parseDatePlaceholder($where);

		$table = isset($options['table']) ? $options['table'] : '';
		$field = !empty($options['field']) ? $options['field'] : '*';
		$join = !empty($options['join']) ? $options['join'] : '';
		$group = !empty($options['group']) ? $options['group'] : '';
		$having = !empty($options['having']) ? $options['having'] : '';
		$order = !empty($options['order']) ? $options['order'] : '';
		$limit = !empty($options['limit']) ? $options['limit'] : '';
		$union = !empty($options['union']) ? $options['union'] : '';
		$comment = !empty($options['comment']) ? $options['comment'] : '';
		$distinct = isset($options['distinct']) ? $options['distinct'] : false;

		$sql = str_replace(
			array('%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%COMMENT%'),
			array(
				$this->parseTable($table),
				$this->parseDistinct($distinct),
				$this->parseField($field),
				$this->parseJoin($join),
				$this->parseWhere($where),
				$this->parseGroup($group),
				$this->parseHaving($having),
				$this->parseOrder($order),
				$this->parseLimit($limit),
				$this->parseUnion($union),
				$this->parseComment($comment)
			),
			$sql
		);
		return $sql;
	}
    public function getLastSql($model='') {
        return $model?$this->modelSql[$model]:$this->queryStr;
    }   
    public function getLastInsID() {
        return $this->lastInsID;
    }   
    public function getError() {
        return $this->error;
    }    
    public function escapeString($str) {
        return addslashes($str);
    }    
    public function setModel($model){
        $this->model =  $model;
    }  
    public function __destruct(){       
        if ($this->queryID){
            $this->free();
        }       
        $this->close();		
    }    
    public function close(){
	}
}