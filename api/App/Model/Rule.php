<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 金币规则
* @date 2018/7/2
* @version hst-v1.0.0
*/
namespace Models\Rule;
use Model\Model;
class Rule extends Model {
	private $moneylist;
	public function getinfo($id=0){
		foreach($this->moneylist as $k=>$v){
			if($id==$v['id']) return $v;
		}
		return false;
	}
	public function _init(){
        $this->moneylist=M('JifenRule')->select();
    }
	public function addValue($uid=0,$id=0){
		//dump($this->moneylist);
		$info = $this->getinfo($id);
		if($info!==false) D('Jifen')->addValue(0,$uid,$info['value'],$info['title'],1);
	}
}
?>