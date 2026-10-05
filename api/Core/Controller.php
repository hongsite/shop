<?php
/*
* @author 牛头
* @company 鸿思特科技
* @Controller 控制器类
* @date 2022/10/18
* @version v1.0.0
*/
namespace Controller;
class Controller{   
    protected $tVar     =   array();
    private   $name     =  '';      
    protected $config   =   array();
	protected $theme    =   '';
    public function __construct(){
		if(method_exists($this,'_init'))
            $this->_init();
    }
	function load($name='',$md='',$iserror=0){
		if($md=='') $md = MODULE_NAME;
		if($name=='') $name = ACTION_NAME;
		if($iserror==1){
			$this->theme = 'Pc/';
		}
		$theme = $this->theme;
		if($theme!=''){
			if(substr($this->theme,-1)!='/') $this->theme = $theme.'/';
		}
		$filename = APP_PATH.'View/'.$this->theme.$md.'/'.$name.'.php';
		if(is_file($filename)){
			extract($this->tVar);
			require $filename;
		}
	}
	protected function assign($name,$value=''){
		if(empty($value)) $value = '';
        if(is_array($name)) {
            $this->tVar=array_merge($this->tVar,$name);
        }else {
            $this->tVar[$name] = $value;
        }
		return $this;
    }
    protected function display($templateFile=''){
		if(!$this->theme) $this->theme('');
		$a = ACTION_NAME;
        $a=='' && $a='index';

		$theme = $this->theme;
		if($theme!=''){
			if(substr($this->theme,-1)!='/') $this->theme = $theme.'/';
		}

		if($templateFile==''){
		    $fname = APP_PATH.'View/'.$this->theme.MODULE_NAME.'/'.$a.'.php';
		}else{			
			if(strpos($templateFile,'/')===false){
			     $fname = APP_PATH.'View/'.$this->theme.MODULE_NAME.'/'.$templateFile.'.php';
			}else{
				$fname = APP_PATH.'View/'.$this->theme.$templateFile.'.php';
			}
		}
		if(is_file($fname)){
			extract($this->tVar);
			/*header('Content-Type:text/html; charset=utf-8');
			header('Cache-control:private');
			header('X-Powered-By:Hstphp');*/
			require $fname;
		}else{
			die('模板错误'.$fname);
		}
    }
    
    protected function theme($theme=''){
        if($this->theme){ 
            $theme = $this->theme.'/';
        }else{
            //if(C('THEME')===true){
				if($theme==''){
					$theme =  C('DEFAULT_THEME');
					$this->theme = $theme.'/';
				}else{
					$this->theme = $theme.'/';
				}
			//}else{
				//$this->theme = '';
			//}
        }
        //if(empty(THEME_NAME)) define('THEME_NAME',$this->theme);
		//if(empty(TEMP_PATH)) define('TEMP_PATH',APP_PATH.'View/'.THEME_NAME);
    }    
    protected function error($title='',$jumpUrl='') {
        $this->dispatchJump($title,0,$jumpUrl);
    }
    protected function success($title='',$jumpUrl='') {
        $this->dispatchJump($title,1,$jumpUrl);
    }
    protected function json($data,$type='JSON'){
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET['callback']) ? $_GET['callback']:'jsonpReturn';
                exit($handler.'('.json_encode($data).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
        }
    }
    
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
        redirect($url,$delay,$msg);
    }
    private function dispatchJump($title='',$status=1,$jumpUrl='',$ajax=false) {
        
		$issj = I('issj',1);

		if($issj==1){
			$js['status'] = 0;
			$js['msg'] = $title;
			$this->json($js);
			exit;
		}
		$fname = ROOT.'Core/View/success.php';		
		if(is_file($fname)){
			extract($this->tVar);			
			require $fname;
		}else{
			die('模板错误'.$fname);
		}
		exit;
    }
    public function __destruct() {
        //执行后续操作		
    }
}