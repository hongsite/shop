var groupid;
function _after_login(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}	
	layer.msg(data.msg,{
		time: 1000,
		icon:1,
		success:function(){
			window.location.href = Config.adminpath;
		}
	});
}
function dologin(){
	let obj = $('#myform');
	let field = obj.find('input[name=mobile]').length>0 ? 'mobile':'username';
	let username = obj.find('input[name='+field+']').val();
	let password = obj.find('input[name=password]').val();
	if(username.length<3){
		layer.alert('请输入登录账号',{icon:2});
		return ;
	}
	if(password.length<3){
		layer.alert('请输入登录密码',{icon:2});
		return ;
	}
	update(Config.apipath+'index.php?s=Login&a=login_json&ischeck=1&isverify=1',$('#myform').serialize(),'_after_login');

}
function _after_reg(data){
	if(data.status!=1){		
		layer.alert(data.msg,{icon:2});
		return ;
	}
	layer.alert(data.msg,{icon:1,closeBtn:true,success:function(){
		var jumpurls = Config.adminpath;
		if(typeof(cusjumpurl)!=="undefined") jumpurls = cusjumpurl;
		window.location.href = jumpurls;		
	}});
}
function doreg(){
	$('#loading p').html('注册请求中');
	let arr = {'title':'确认注册','cont':'确定注册吗'};
	update(Config.apipath+'index.php?s=Login&a=reg_json&isverify=1&ispc=1',$('#myform').serialize(),'_after_reg',1,arr);
}
function _after_forget(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}
	layer.alert(data.msg,{icon:1,closeBtn:true,success:function(){
		window.location.href = Config.adminpath;		
	}});	
}
function doforget(){
	$('#loading p').html('服务请求中');
	let arr = {'title':'确认找回密码','cont':'确定找回密码吗'};
	update(Config.apipath+'index.php?s=Login&a=forget_json&isverify=1',$('#myform').serialize(),'_after_forget',1,arr);
}
function _after_sendcode_forget(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}
	let obj = $('#sendmsg_forget');
	obj.addClass('bgccc');
	time(obj);	
}
function _after_sendcode_reg(data){
	if(data.status!=1){

		var fun = '_before_reg';
		if(typeof(window[fun])==="function"){
			var parameters=[JSON.stringify(data)];
			eval(fun+'('+parameters+')');
			return ;
		}

		layer.alert(data.msg,{icon:2});
		return ;
	}
	let obj = $('#sendmsg_reg');
	obj.addClass('bgccc');
	time(obj);	
}
$(document).ready(function(){	
	$('#myform input[name=username],#myform input[name=password]').keydown(function(e) {
		if (e.which === 13) {
			e.preventDefault();
			dologin();
		}
	});
	$('#dobtn,#dobtnadmin').click(function(){
		dologin();
	});
	$('#verify_login').click(function(){
		var isfish = getverify('Login','#myform input[name=verify]');
		var obj = $(this);
		if(isfish==true){
			obj.html(veryfytext);
		}else{
			obj.html(veryfyerr);
		}
	});
	$('#dobtnreg').click(function(){
		doreg();
	});
	$('#sendmsg_reg').click(function(){		
		if(!$(this).hasClass('bgccc')){			
			$('#loading p').html('验证码获取中');
			update(Config.apipath+'index.php?s=Login&a=sendcode_reg_json&isverify=1',$('#myform').serialize(),'_after_sendcode_reg');			
		}
	});
	$('#dobtnforget').click(function(){
		doforget();
	});
	$('#sendmsg_forget').click(function(){		
		if(!$(this).hasClass('bgccc')){			
			$('#loading p').html('验证码获取中');
			update(Config.apipath+'index.php?s=Login&a=sendcode_forget_json&isverify=1',$('#myform').serialize(),'_after_sendcode_forget');
		}
	});
	$('#verify_login').click();	
});