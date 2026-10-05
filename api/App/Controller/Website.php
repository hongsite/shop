<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 设置
*@date 2024/8/27
*@version hst-v1.0.0
*/
namespace Hst\Website;
use Hst\Common;
class Website extends Common {
    public function _init(){
		$searinfo[] = array('field'=>'title','type'=>'like','afield'=>'','isnumeric'=>0);
		$this->seararr=$searinfo;
        parent::_init();
    }
	public function edit(){		
		$uid = getuid();
		$userinfo = D('Member')->getValue($uid);
		$nowt = $this->nowtime;

		$rt = M('Website')->where('id=1')->find();
		
		$js['status'] = 1;
		$js['msg'] = '';
		$js['data'] = $rt;
		$this->json($js);
    }
	//保存配置
	function update_json() {
        $js['status'] = 0;
		$js['msg'] = '';
		$js['isadd'] = 0;
		$nowt = $this->nowtime;
		$uid = getuid();

		$userinfo = D('Member')->getValue($uid);

		$picurl = I('picurl',0,'post');

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}		

		$data = $_POST;		

		$wx_appid = I('wx_appid',0,'post');
		$wx_appsecret = I('wx_appsecret',0,'post');
		$wx_mchid = I('wx_mchid',0,'post');		
		$wx_paykeys = I('wx_paykeys',0,'post');
		$agentstat = I('agentstat',0,'post');
		$isgonggao = I('isgonggao',1,'post');
		$isopen = I('isopen',1,'post');
		$gonggao = I('gonggao',0,'post');
		$openurl = I('openurl',0,'post');
		$openpicurl = I('openpicurl',0,'post');

		if(isset($data['id'])) unset($data['id']);
		if(isset($data['addtime'])) unset($data['addtime']);
		if(isset($data['xcxtid'])) unset($data['xcxtid']);
		if(isset($data['appid'])) unset($data['appid']);
		if(isset($data['appsecret'])) unset($data['appsecret']);
		if(isset($data['app_auth_token'])) unset($data['app_auth_token']);
		

		if(isset($data['wx_appid'])) $data['wx_appid'] = $wx_appid;
		if(isset($data['wx_appsecret'])) $data['wx_appsecret'] = $wx_appsecret;
		if(isset($data['wx_mchid'])) $data['wx_mchid'] = $wx_mchid;
		if(isset($data['wx_paykeys'])) $data['wx_paykeys'] = $wx_paykeys;
		if(isset($data['agentstat'])) $data['agentstat'] = $agentstat;
		if(isset($data['isopen'])) $data['isopen'] = $isopen;
		if(isset($data['isgonggao'])) $data['isgonggao'] = $isgonggao;
		if(isset($data['gonggao'])) $data['gonggao'] = $gonggao;
		if(isset($data['openurl'])) $data['openurl'] = $openurl;
		if(isset($data['openpicurl'])) $data['openpicurl'] = $openpicurl;

		
		//if(isset($data['picurl'])) $data['picurl'] = $picurl;
			
		$m = M('Website');		
        $rtcmd = $m->where('id=1')->save($data);
        if($rtcmd!==false){	
			loadcache(2,true);
			$js['status'] = 1;
			$js['msg'] = '保存成功!';
        }else{
            $js['msg'] = '保存失败';
        }
		$this->json($js);
    }	
}
