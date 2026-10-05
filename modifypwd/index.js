//orders/index页面js
Config.module = 'Modifypwd';
Config.submodule = 'modifypwd';
Config.isalone = true;
Config.cus_edit_title = '修改登录密码';
Config.editurl = Config.apipath+'index.php?s=Modify&a=edit&issj=1';
function _after_modify(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return;
	}
	layer.alert(data.msg,{icon:1});
}
$(document).ready(function(){
	$('#modifybtn').live('click',function(){
		let arr = {'title':'确认修改密码','cont':'确定修改当前账号的登录密码'};
		update(Config.adminurl+'update_json',$('#myform').serialize(),'_after_modify',1,arr);
	});
});
