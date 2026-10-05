//orders/index页面js
Config.module = 'Modify';
Config.isalone = true;
Config.cus_edit_title = '修改当前账号资料';
function _after_modify(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return;
	}
	layer.alert(data.msg,{icon:1});
}
$(document).ready(function(){
	$('#modifybtn').click(function(){
		let arr = {'title':'修改个人资料','cont':'确定修改当前账号的修改个人资料'};
		update(Config.adminurl+'update_json',$('#myform').serialize(),'_after_modify',1,arr);
	});	
});
