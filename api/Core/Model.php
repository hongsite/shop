<?php
/*
*@author 牛头
*@company 鸿思特科技
*@funtion MODEL模型
*@date 2022/10/18
*@version v1.0.0
*/
namespace Model;
use Db\Db as Db;
class Model {
    // 操作状态
    const MODEL_INSERT          =   1;
    const MODEL_UPDATE          =   2;
    const MODEL_BOTH            =   3;
    const MUST_VALIDATE         =   1;
    const EXISTS_VALIDATE       =   0;
    const VALUE_VALIDATE        =   2;
    private   $_extModel        =   null;
    protected $db               =   null;
    protected $pk               =   'id';
    protected $tablePrefix      =   '';
    protected $name             =   '';
    protected $dbName           =   '';
    protected $connection       =   '';
    protected $tableName        =   '';
    protected $trueTableName    =   '';
    protected $error            =   '';
    protected $fields           =   array();
    protected $data             =   array();
    protected $options          =   array();
    protected $_validate        =   array();
    protected $_auto            =   array();
    protected $_map             =   array();
    protected $_scope           =   array();
    protected $autoCheckFields  =   true;
    protected $patchValidate    =   false;
    protected $methods          =   array('table','order','alias','having','group','lock','distinct','auto','filter','validate','result','bind','token');   
    public function __construct($name='',$tablePrefix='',$connection='') {
        $this->_init();
        if(!empty($name)) {
            /*if(strpos($name,'.')) { // 支持 数据库名.模型名的 定义
                list($this->dbName,$this->name) = explode('.',$name);
            }else{
                $this->name   =  $name;
            }*/
			$this->name   =  $name;
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();
        }        
        if(is_null($tablePrefix)){
            $this->tablePrefix = '';
        }elseif('' != $tablePrefix) {
            $this->tablePrefix = $tablePrefix;
        }else{
            $this->tablePrefix = $this->tablePrefix?$this->tablePrefix:C('DB_PREFIX');
        }
        $this->db(0,empty($this->connection)?$connection:$this->connection);
    }    
    protected function _checkTableInfo() {
        if(empty($this->fields)) {
            if(C('DB_FIELDS_CACHE')) {
                $db   =  $this->dbName?$this->dbName:C('DB_NAME');
                $fields = F('_fields/'.strtolower($db.'.'.$this->name));
                if($fields) {
                    $version    =   C('DB_FIELD_VERSION');
                    if(empty($version) || $fields['_version']== $version) {
                        $this->fields   =   $fields;
                        return ;
                    }
                }
            }
            $this->flush();
        }
    }   
    public function flush(){        
        $this->db->setModel($this->name);
        $fields =   $this->db->getFields($this->getTableName());
        if(!$fields){
            return false;
        }
        $this->fields   =   array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach ($fields as $key=>$val){            
            $type[$key]    =   $val['type'];
            if($val['primary']) {
                $this->fields['_pk'] = $key;
                if($val['autoinc']) $this->fields['_autoinc']   =   true;
            }
        }        
        $this->fields['_type'] =  $type;
        if(C('DB_FIELD_VERSION')) $this->fields['_version'] =   C('DB_FIELD_VERSION');
        if(C('DB_FIELDS_CACHE')){            
            $db   =  $this->dbName?$this->dbName:C('DB_NAME');
            //F('_fields/'.strtolower($db.'.'.$this->name),$this->fields);
        }
    }
    public function switchModel($type,$vars=array()) {
        $class = ucwords(strtolower($type)).'Model';
        if(!class_exists($class))
            throw_exception($class.L('_MODEL_NOT_EXIST_'));        
        $this->_extModel   = new $class($this->name);
        if(!empty($vars)) {
            foreach ($vars as $var)
                $this->_extModel->setProperty($var,$this->$var);
        }
        return $this->_extModel;
    }   
    public function __set($name,$value) {
        $this->data[$name]  =   $value;
    }    
    public function __get($name) {
        return isset($this->data[$name])?$this->data[$name]:null;
    }    
    public function __isset($name) {
        return isset($this->data[$name]);
    }    
    public function __unset($name) {
        unset($this->data[$name]);
    }    
    public function __call($method,$args) {
        if(in_array(strtolower($method),$this->methods,true)) {
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            $field =  isset($args[0])?$args[0]:'*';
            return $this->getField(strtoupper($method).'('.$field.') AS tp_'.$method);
        }elseif(strtolower(substr($method,0,5))=='getby') {
            $field   =   parse_name(substr($method,5));
            $where[$field] =  $args[0];
            return $this->where($where)->find();
        }elseif(strtolower(substr($method,0,10))=='getfieldby') {
            $name   =   parse_name(substr($method,10));
            $where[$name] =$args[0];
            return $this->where($where)->getField($args[1]);
        }elseif(isset($this->_scope[$method])){
            return $this->scope($method,$args[0]);
        }else{
            throw_exception(__CLASS__.':'.$method.'_METHOD_NOT_EXIST_');
            return;
        }
    }
    protected function _init(){
	}

    
     protected function _facade($data) {
        if(!empty($this->fields)) {
            foreach ($data as $key=>$val){
                if(!in_array($key,$this->fields,true)){
                    unset($data[$key]);
                }elseif(is_scalar($val)){                    
                    $this->_parseType($data,$key);
                }
            }
        }        
        if(!empty($this->options['filter'])) {
            $data = array_map($this->options['filter'],$data);
            unset($this->options['filter']);
        }
        $this->_before_write($data);
        return $data;
     }    
    protected function _before_write(&$data) {}

    public function add($data='',$options=array(),$replace=false) {
        if(empty($data)){            
            if(!empty($this->data)) {
                $data           =   $this->data;
                $this->data     = array();
            }else{
                $this->error    = '_DATA_TYPE_INVALID_';
                return false;
            }
        }
        $options    =   $this->_parseOptions($options);
        $data       =   $this->_facade($data);
        if(false === $this->_before_insert($data,$options)) {
            return false;
        }
        $result = $this->db->insert($data,$options,$replace);
        if(false !== $result ) {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                $data[$this->getPk()]  = $insertId;
                $this->_after_insert($data,$options);
                return $insertId;
            }
            $this->_after_insert($data,$options);
        }
        return $result;
    }    
    protected function _before_insert(&$data,$options) {}
    protected function _after_insert($data,$options) {}

    public function addAll($dataList,$options=array(),$replace=false){
        if(empty($dataList)) {
            $this->error = '_DATA_TYPE_INVALID_';
            return false;
        }
        $options =  $this->_parseOptions($options);
        foreach ($dataList as $key=>$data){
            $dataList[$key] = $this->_facade($data);
        }
        $result = $this->db->insertAll($dataList,$options,$replace);
        if(false !== $result ) {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                return $insertId;
            }
        }
        return $result;
    }
	public function saveAll($dataList, $model, $where = ''){
		if (empty($dataList)) {
			return false;
		}
		$model || $model = $this->name;
		$model = C('DB_PREFIX') . cc_table($model);
		$pk = $this->getPk();

		$arr = array();
		foreach ($dataList as $key => $v) {
			$arr[$key] = $this->_facade($v);
		}

		$lists = array();
		$ids = array();
		foreach ($arr as $data) {
			foreach ($data as $key => $value) {
				if ($pk === $key) {
					$ids[] = $value;
				} else {
					$value = $this->db->escapeString($value);
					$sqlcmdstr = sprintf("WHEN %d THEN '%s' ", $data[$pk], $value);
					if (isset($lists[$key])) {
						$lists[$key] .= $sqlcmdstr;
					} else {
						$lists[$key] = $sqlcmdstr;
					}
				}
			}
		}

		if (empty($ids)) {
			return false;
		}

		$sql = '';
		foreach ($lists as $key => $value) {
			$sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
		}
		$sql = sprintf(
			'UPDATE `%s` SET %s WHERE %s IN ( %s ) %s',
			$model,
			rtrim($sql, ','),
			$pk,
			implode(',', $ids),
			$where
		);
		return M()->execute($sql);
	}
    public function selectAdd($fields='',$table='',$options=array()) {
        $options =  $this->_parseOptions($options);
        if(false === $result = $this->db->selectInsert($fields?$fields:$options['field'],$table?$table:$this->getTableName(),$options)){
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            return $result;
        }
    }
    public function save($data='',$options=array()) {
        if(empty($data)) {
            if(!empty($this->data)) {
                $data           =   $this->data;
                $this->data     =   array();
            }else{
                $this->error    =   'DATA_TYPE_INVALID';
                return false;
            }
        }
        $data       =   $this->_facade($data);
        $options    =   $this->_parseOptions($options);
        $pk         =   $this->getPk();
        if(!isset($options['where']) ) {
            if(isset($data[$pk])) {
                $where[$pk]         =   $data[$pk];
                $options['where']   =   $where;
                unset($data[$pk]);
            }else{
                $this->error        =   L('_OPERATION_WRONG_');
                return false;
            }
        }
        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue    =   $options['where'][$pk];
        }        
        if(false === $this->_before_update($data,$options)) {
            return false;
        }        
        $result     =   $this->db->update($data,$options);
        if(false !== $result) {
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_update($data,$options);
        }
        return $result;
    }
    protected function _before_update(&$data,$options){}
    protected function _after_update($data,$options){}    
    public function delete($options=array()) {
        if(empty($options) && empty($this->options['where'])) {
            if(!empty($this->data) && isset($this->data[$this->getPk()]))
                return $this->delete($this->data[$this->getPk()]);
            else
                return false;
        }
        $pk   =  $this->getPk();
        if(is_numeric($options)  || is_string($options)) {
            if(strpos($options,',')) {
                $where[$pk]     =  array('IN', $options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }
        $options =  $this->_parseOptions($options);
        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue    =   $options['where'][$pk];
        }
        $result  =    $this->db->delete($options);
        if(false !== $result) {
            $data = array();
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_delete($data,$options);
        }
        return $result;
    }
    protected function _after_delete($data,$options) {}

    public function select($options=array()) {
        if(is_string($options) || is_numeric($options)) {
            // 根据主键查询
            $pk   =  $this->getPk();
            if(strpos($options,',')) {
                $where[$pk]     =  array('IN',$options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }elseif(false === $options){
            $options            =  array();            
            $options            =  $this->_parseOptions($options);
            return  '( '.$this->db->buildSelectSql($options).' )';
        }
        $options    =  $this->_parseOptions($options);
        $resultSet  = $this->db->select($options);
        if(false === $resultSet) {
            return false;
        }
        if(empty($resultSet)) { // 查询结果为空
            return null;
        }
        $this->_after_select($resultSet,$options);
        return $resultSet;
    }
    protected function _after_select(&$resultSet,$options){}
    public function buildSql($options=array()) {
        $options =  $this->_parseOptions($options);
        return  '( '.$this->db->buildSelectSql($options).' )';
    }
    protected function _parseOptions($options=array()) {
        if(is_array($options))
            $options =  array_merge($this->options,$options);
        $this->options  =   array();
        if(!isset($options['table'])){
            $options['table']   =   $this->getTableName();
            $fields             =   $this->fields;
        }else{
            $fields             =   $this->getDbFields();
        }

        if(!empty($options['alias'])) {
            $options['table']  .=   ' '.$options['alias'];
        }
        $options['model']       =   $this->name;

        if(isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            foreach ($options['where'] as $key=>$val){
                $key            =   trim($key);
                if(in_array($key,$fields,true)){
                    if(is_scalar($val)) {
                        $this->_parseType($options['where'],$key);
                    }
                }elseif(!is_numeric($key) && '_' != substr($key,0,1) && false === strpos($key,'.') && false === strpos($key,'(') && false === strpos($key,'|') && false === strpos($key,'&')){
                    unset($options['where'][$key]);
                }
            }
        }
        $this->_options_filter($options);
        return $options;
    }
    protected function _options_filter(&$options) {}    
    protected function _parseType(&$data,$key) {
        if(empty($this->options['bind'][':'.$key])){
            $fieldType = strtolower($this->fields['_type'][$key]);
            if(false !== strpos($fieldType,'enum')){
            }elseif(false === strpos($fieldType,'bigint') && false !== strpos($fieldType,'int')) {
                $data[$key]   =  floatval($data[$key]);
            }elseif(false !== strpos($fieldType,'float') || false !== strpos($fieldType,'double')){
                $data[$key]   =  floatval($data[$key]);
            }elseif(false !== strpos($fieldType,'bool')){
                $data[$key]   =  (bool)$data[$key];
            }
        }
    }
    public function find($options=array()) {
        if(is_numeric($options) || is_string($options)) {
            $where[$this->getPk()]  =   $options;
            $options                =   array();
            $options['where']       =   $where;
        }
        $options['limit']   =   1;
        $options            =   $this->_parseOptions($options);
        $resultSet          =   $this->db->select($options);
        if(false === $resultSet) {
            return false;
        }
        if(empty($resultSet)){
            return null;
        }
        $this->data         =   $resultSet[0];
        $this->_after_find($this->data,$options);
        if(!empty($this->options['result'])) {
            return $this->returnResult($this->data,$this->options['result']);
        }
        return $this->data;
    }
    protected function _after_find(&$result,$options) {}
    protected function returnResult($data,$type=''){
        if ($type){
            if(is_callable($type)){
                return call_user_func($type,$data);
            }
            switch (strtolower($type)){
                case 'json':
                    return json_encode($data);
                case 'xml':
                    return xml_encode($data);
            }
        }
        return $data;
    }    
    public function parseFieldsMap($data,$type=1) {
        if(!empty($this->_map)) {
            foreach ($this->_map as $key=>$val){
                if($type==1){
                    if(isset($data[$val])) {
                        $data[$key] =   $data[$val];
                        unset($data[$val]);
                    }
                }else{
                    if(isset($data[$key])) {
                        $data[$val] =   $data[$key];
                        unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }
    public function setField($field,$value='') {
        if(is_array($field)) {
            $data           =   $field;
        }else{
            $data[$field]   =   $value;
        }
        return $this->save($data);
    }    
    public function setInc($field,$step=1) {
        return $this->setField($field,array('exp',$field.'+'.$step));
    }    
    public function setDec($field,$step=1) {
        return $this->setField($field,array('exp',$field.'-'.$step));
    }    
    public function getField($field,$sepa=null) {
        $options['field']       =   $field;
        $options                =   $this->_parseOptions($options);
        $field                  =   trim($field);
        if(strpos($field,',')){
            if(!isset($options['limit'])){
                $options['limit']   =   is_numeric($sepa)?$sepa:'';
            }
            $resultSet          =   $this->db->select($options);
            if(!empty($resultSet)) {
                $_field         =   explode(',', $field);
                $field          =   array_keys($resultSet[0]);
                $key            =   array_shift($field);
                $key2           =   array_shift($field);
                $cols           =   array();
                $count          =   count($_field);
                foreach ($resultSet as $result){
                    $name   =  $result[$key];
                    if(2==$count) {
                        $cols[$name]   =  $result[$key2];
                    }else{
                        $cols[$name]   =  is_string($sepa)?implode($sepa,$result):$result;
                    }
                }
                return $cols;
            }
        }else{
            if(true !== $sepa) {
                $options['limit']   =   is_numeric($sepa)?$sepa:1;
            }
            $result = $this->db->select($options);
            if(!empty($result)) {
                if(true !== $sepa && 1==$options['limit']) return reset($result[0]);
                foreach ($result as $val){
                    $array[]    =   $val[$field];
                }
                return $array;
            }
        }
        return null;
    }    
    public function create($data='',$type='') {
        if(empty($data)) {
            $data   =   $_POST;
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        if(empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        $data = $this->parseFieldsMap($data,0);
        $type = $type?$type:(!empty($data[$this->getPk()])?self::MODEL_UPDATE:self::MODEL_INSERT);

        if(isset($this->options['field'])) { // $this->field('field1,field2...')->create()
            $fields =   $this->options['field'];
            unset($this->options['field']);
        }elseif($type == self::MODEL_INSERT && isset($this->insertFields)) {
            $fields =   $this->insertFields;
        }elseif($type == self::MODEL_UPDATE && isset($this->updateFields)) {
            $fields =   $this->updateFields;
        }
        if(isset($fields)) {
            if(is_string($fields)) {
                $fields =   explode(',',$fields);
            }
            if(C('TOKEN_ON'))   $fields[] = C('TOKEN_NAME');
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }
            }
        }
        if(!$this->autoValidation($data,$type)) return false;
        if(!$this->autoCheckToken($data)) {
            $this->error = '_TOKEN_ERROR_';
            return false;
        }

        if($this->autoCheckFields){
            $fields =   $this->getDbFields();
			$isnull = empty($fields) ? false:true;
            foreach ($data as $key=>$val){
                if($isnull===false){
					unset($data[$key]);
				}else if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }elseif(MAGIC_QUOTES_GPC && is_string($val)){
                    $data[$key] =   stripslashes($val);
                }
            }
        }
        $this->autoOperation($data,$type);
        $this->data =   $data;
        return $data;
     }

    public function autoCheckToken($data) {
        if(isset($this->options['token']) && !$this->options['token']) return true;
        if(C('TOKEN_ON')){
            $name   = C('TOKEN_NAME');
            if(!isset($data[$name]) || !isset($_SESSION[$name])) {
                return false;
            }
            list($key,$value)  =  explode('_',$data[$name]);
            if($value && $_SESSION[$name][$key] === $value){
                unset($_SESSION[$name][$key]); // 验证完成销毁session
                return true;
            }
            if(C('TOKEN_RESET')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }    
    public function regex($value,$rule) {
        $validate = array(
            'require'   =>  '/.+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
        );
        if(isset($validate[strtolower($rule)]))
            $rule       =   $validate[strtolower($rule)];
        return preg_match($rule,$value)===1;
    }    
    private function autoOperation(&$data,$type) {
        if(!empty($this->options['auto'])) {
            $_auto   =   $this->options['auto'];
            unset($this->options['auto']);
        }elseif(!empty($this->_auto)){
            $_auto   =   $this->_auto;
        }
        // 自动填充
        if(isset($_auto)) {
            foreach ($_auto as $auto){
                if(empty($auto[2])) $auto[2] = self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    switch(trim($auto[3])) {
                        case 'function':
                        case 'callback':
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore':
                            if(''===$data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default:
                            $data[$auto[0]] = $auto[1];
                    }
                    if(false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }    
    protected function autoValidation($data,$type) {
        if(!empty($this->options['validate'])) {
            $_validate   =   $this->options['validate'];
            unset($this->options['validate']);
        }elseif(!empty($this->_validate)){
            $_validate   =   $this->_validate;
        }
        if(isset($_validate)) {
            if($this->patchValidate){
                $this->error = array();
            }
            foreach($_validate as $key=>$val) {
                if(empty($val[5]) || $val[5]== self::MODEL_BOTH || $val[5]== $type ) {
                    if(0==strpos($val[2],'{%') && strpos($val[2],'}'))
                        $val[2]  =  L(substr($val[2],2,-1));
                    $val[3]  =  isset($val[3])?$val[3]:self::EXISTS_VALIDATE;
                    $val[4]  =  isset($val[4])?$val[4]:'regex';                    
                    switch($val[3]) {
                        case self::MUST_VALIDATE:  
                            if(false === $this->_validationField($data,$val)) 
                                return false;
                            break;
                        case self::VALUE_VALIDATE:
                            if('' != trim($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                            break;
                        default:
                            if(isset($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                    }
                }
            }
            if(!empty($this->error)) return false;
        }
        return true;
    }    
    protected function _validationField($data,$val) {
        if(false === $this->_validationFieldItem($data,$val)){
            if($this->patchValidate) {
                $this->error[$val[0]]   =   $val[2];
            }else{
                $this->error            =   $val[2];
                return false;
            }
        }
        return ;
    }
    protected function _validationFieldItem($data,$val) {
        switch(strtolower(trim($val[4]))) {
            case 'function':// 使用函数进行验证
            case 'callback':// 调用方法进行验证
                $args = isset($val[6])?(array)$val[6]:array();
                if(is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                if(is_array($val[0])){
                    // 支持多个字段验证
                    foreach($val[0] as $field)
                        $_data[$field] = $data[$field];
                    array_unshift($args, $_data);
                }else{
                    array_unshift($args, $data[$val[0]]);
                }
                if('function'==$val[4]) {
                    return call_user_func_array($val[1], $args);
                }else{
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm': // 验证两个字段是否相同
                return $data[$val[0]] == $data[$val[1]];
            case 'unique': // 验证某个值是否唯一
                if(is_string($val[0]) && strpos($val[0],','))
                    $val[0]  =  explode(',',$val[0]);
                $map = array();
                if(is_array($val[0])) {
                    // 支持多个字段验证
                    foreach ($val[0] as $field)
                        $map[$field]   =  $data[$field];
                }else{
                    $map[$val[0]] = $data[$val[0]];
                }
                if(!empty($data[$this->getPk()])) { // 完善编辑的时候验证唯一
                    $map[$this->getPk()] = array('neq',$data[$this->getPk()]);
                }
                if($this->where($map)->find())   return false;
                return true;
            default:  // 检查附加规则
                return $this->check($data[$val[0]],$val[1],$val[4]);
        }
    }    
    public function check($value,$rule,$type='regex'){
        $type   =   strtolower(trim($type));
        switch($type) {
            case 'in':
            case 'notin':
                $range   = is_array($rule)? $rule : explode(',',$rule);
                return $type == 'in' ? in_array($value ,$range) : !in_array($value ,$range);
            case 'between':
            case 'notbetween':          
                if (is_array($rule)){
                    $min    =    $rule[0];
                    $max    =    $rule[1];
                }else{
                    list($min,$max)   =  explode(',',$rule);
                }
                return $type == 'between' ? $value>=$min && $value<=$max : $value<$min || $value>$max;
            case 'equal':
            case 'notequal':         
                return $type == 'equal' ? $value == $rule : $value != $rule;
            case 'length':
                $length  =  mb_strlen($value,'utf-8');
                if(strpos($rule,',')){
                    list($min,$max)   =  explode(',',$rule);
                    return $length >= $min && $length <= $max;
                }else{
                    return $length == $rule;
                }
            case 'expire':
                list($start,$end)   =  explode(',',$rule);
                if(!is_numeric($start)) $start   =  strtotime($start);
                if(!is_numeric($end)) $end   =  strtotime($end);
                return NOW_TIME >= $start && NOW_TIME <= $end;
            case 'ip_allow':
                return in_array(get_client_ip(),explode(',',$rule));
            case 'ip_deny':
                return !in_array(get_client_ip(),explode(',',$rule));
            case 'regex':
            default:
                return $this->regex($value,$rule);
        }
    }    
    public function query($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->query($sql);
    }
    public function execute($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->execute($sql);
    }    
    protected function parseSql($sql,$parse) {
        // 分析表达式
        if(true === $parse) {
            $options =  $this->_parseOptions();
            $sql    =   $this->db->parseSql($sql,$options);
        }elseif(is_array($parse)){ // SQL预处理
            $parse  =   array_map(array($this->db,'escapeString'),$parse);
            $sql    =   vsprintf($sql,$parse);
        }else{
            $sql    =   strtr($sql,array('__TABLE__'=>$this->getTableName(),'__PREFIX__'=>C('DB_PREFIX')));
        }
        $this->db->setModel($this->name);
        return $sql;
    }    
    public function db($linkNum='',$config='',$params=array()){
        if(''===$linkNum && $this->db) {
            return $this->db;
        }
        static $_linkNum    =   array();
        static $_db = array();
        if(!isset($_db[$linkNum]) || (isset($_db[$linkNum]) && $config && $_linkNum[$linkNum]!=$config) ) {
            // 创建一个新的实例
            if(!empty($config) && is_string($config) && false === strpos($config,'/')) { // 支持读取配置参数
                $config  =  C($config);
            }
            $_db[$linkNum]            =    Db::getInstance($config);
        }elseif(NULL === $config){
            $_db[$linkNum]->close(); // 关闭数据库连接
            unset($_db[$linkNum]);
            return ;
        }
        if(!empty($params)) {
            if(is_string($params))    parse_str($params,$params);
            foreach ($params as $name=>$value){
                $this->setProperty($name,$value);
            }
        }
        // 记录连接信息
        $_linkNum[$linkNum] =   $config;
        // 切换数据库连接
        $this->db   =    $_db[$linkNum];
        $this->_after_db();
        // 字段检测
        if(!empty($this->name) && $this->autoCheckFields)    $this->_checkTableInfo();
        return $this;
    }    
    protected function _after_db() {}    
    public function getModelName() {
        if(empty($this->name))
            $this->name =   get_class($this);
        return $this->name;
    }    
    public function getTableName() {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }else{
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return (!empty($this->dbName)?$this->dbName.'.':'').$this->trueTableName;
    }   
    public function startTrans() {
        $this->commit();
        $this->db->startTrans();
        return ;
    }
    public function commit() {
        return $this->db->commit();
    }
    public function rollback() {
        return $this->db->rollback();
    }
    public function getError(){
        return $this->error;
    }
    public function getDbError() {
        return $this->db->getError();
    }
    public function getLastInsID() {
        return $this->db->getLastInsID();
    }
    public function getLastSql() {
        return $this->db->getLastSql($this->name);
    }
    public function _sql(){
        return $this->getLastSql();
    }
    public function getPk() {
        return isset($this->fields['_pk'])?$this->fields['_pk']:$this->pk;
    }
    public function getDbFields(){
        if(isset($this->options['table'])){
            $fields     =   $this->db->getFields($this->options['table']);
            return  $fields?array_keys($fields):false;
        }
        if($this->fields) {
            $fields     =  $this->fields;
            unset($fields['_autoinc'],$fields['_pk'],$fields['_type'],$fields['_version']);
            return $fields;
        }
        return false;
    }
    public function data($data=''){
        if('' === $data && !empty($this->data)) {
            return $this->data;
        }
        if(is_object($data)){
            $data   =   get_object_vars($data);
        }elseif(is_string($data)){
            parse_str($data,$data);
        }elseif(!is_array($data)){
            throw_exception(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }    
    public function join($join) {
        if(is_array($join)) {
            $this->options['join']      =   $join;
        }elseif(!empty($join)) {
            $this->options['join'][]    =   $join;
        }
        return $this;
    }    
    public function union($union,$all=false) {
        if(empty($union)) return $this;
        if($all) {
            $this->options['union']['_all']  =   true;
        }
        if(is_object($union)) {
            $union   =  get_object_vars($union);
        }
        if(is_string($union) ) {
            $options =  $union;
        }elseif(is_array($union)){
            if(isset($union[0])) {
                $this->options['union']  =  array_merge($this->options['union'],$union);
                return $this;
            }else{
                $options =  $union;
            }
        }else{
            throw_exception(L('_DATA_TYPE_INVALID_'));
        }
        $this->options['union'][]  =   $options;
        return $this;
    }    
    public function cache($key=true,$expire=null,$type=''){
        if(false !== $key)
            $this->options['cache']  =  array('key'=>$key,'expire'=>$expire,'type'=>$type);
        return $this;
    }    
    public function field($field,$except=false){
        if(true === $field) {
            $fields     =  $this->getDbFields();
            $field      =  $fields?$fields:'*';
        }elseif($except){
            if(is_string($field)) {
                $field  =  explode(',',$field);
            }
            $fields     =  $this->getDbFields();
            $field      =  $fields?array_diff($fields,$field):$field;
        }
        $this->options['field']   =   $field;
        return $this;
    }    
    public function scope($scope='',$args=NULL){
        if('' === $scope) {
            if(isset($this->_scope['default'])){               
                $options    =   $this->_scope['default'];
            }else{
                return $this;
            }
        }elseif(is_string($scope)){
            $scopes         =   explode(',',$scope);
            $options        =   array();
            foreach ($scopes as $name){
                if(!isset($this->_scope[$name])) continue;
                $options    =   array_merge($options,$this->_scope[$name]);
            }
            if(!empty($args) && is_array($args)) {
                $options    =   array_merge($options,$args);
            }
        }elseif(is_array($scope)){
            $options        =   $scope;
        }
        
        if(is_array($options) && !empty($options)){
            $this->options  =   array_merge($this->options,array_change_key_case($options));
        }
        return $this;
    }    
    public function where($where,$parse=null){
        if(!is_null($parse) && is_string($where)) {
            if(!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map(array($this->db,'escapeString'),$parse);
            $where =   vsprintf($where,$parse);
        }elseif(is_object($where)){
            $where  =   get_object_vars($where);
        }
        if(is_string($where) && '' != $where){
            $map    =   array();
            $map['_string']   =   $where;
            $where  =   $map;
        }        
        if(isset($this->options['where'])){
            $this->options['where'] =   array_merge($this->options['where'],$where);
        }else{
            $this->options['where'] =   $where;
        }
        
        return $this;
    }   
    public function limit($offset,$length=null){
        $this->options['limit'] =   is_null($length)?$offset:$offset.','.$length;
        return $this;
    }    
    public function page($page,$listRows=null){
        $this->options['page'] =   is_null($listRows)?$page:$page.','.$listRows;
        return $this;
    }    
    public function comment($comment){
        $this->options['comment'] =   $comment;
        return $this;
    }    
    public function setProperty($name,$value) {
        if(property_exists($this,$name))
            $this->$name = $value;
        return $this;
    }

}