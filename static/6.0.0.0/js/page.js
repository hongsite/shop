function _after_index(data){
	let removebq = Config.removebq;
	if(removebq.indexOf('.mylist')!=-1){
		$(removebq).remove();
	}else{
		$(removebq).html('');
	}
	$('#page').html('');
	$('#nodata').html('');
	$('#nodata').hide();
	if(typeof(window['_after_nologin'])==='function'){
		_after_nologin(data);
	}
	let total = data.total || 0;
	let perpage = data.perpage || Config.perpage;
	Config.nums = parseInt(total);
	Config.perpage = parseInt(perpage);
	Config.allpage = Math.ceil(Config.nums/Config.perpage);

	if(typeof(window['index_render_before'])==='function'){
		data = index_render_before(data);
	}
	let htmlbq = Config.htmlbq || '';	
	let te = getmbhtml('list',data.data,'listdetail');
	
	if(htmlbq.indexOf(':last')!=-1){
		$(htmlbq).after(te);
	}else{
		$(htmlbq).html(te);
	}

	if(typeof(window['index_render_after'])==='function'){
		index_render_after(data);
	}

	getpage();

	if(typeof(window['set_left_height'])==='function'){
		set_left_height();
	}

	if(data.status!=1){
		$('#nodata').html('<div><i class="iconfont icon-wushuju"></i><p class="no-data-message">暂无数据</p></div>');
		$('#nodata').show();		
		return ;
	}
}
function _after_delete(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}
	if(typeof(window['delete_render_after'])==='function'){
		delete_render_after(data);
	}
	var ids = data.data || 0;
	$('#tblist .mylist[data-id='+ids+']').remove();
	$('#tblist .blank10[data-id='+ids+']').remove();
}
$(document).ready(function(){
	$('#myadd,.myedit').live('click',function(){	
		let id = $(this).data('id');
		let extfield = $(this).data('extfield');
		let url = window.location.href;		
		Config.editid = id;
		//$('#loading').show();
		if(url.indexOf('?')==-1){
			url = url+'Config.html?id='+id;
			if(typeof(extfield)!='undefined') url += '&'+extfield;
		}else{
			let beforeQuestionMark = url.split('?')[0];
			let beforeQuestionMark1 = url.split('?')[1];
			url = beforeQuestionMark+'Config.html?id='+id+'&'+beforeQuestionMark1;
			if(typeof(extfield)!='undefined') url += '&'+extfield;
		}
		var editurl = Config.editurl+'&id='+id;
		if(Config.module=='Cate'){
			var field = get('field');
			editurl += '&field='+field;
		}
		getlist(editurl,'_after_edit');
	});
	$('#mycate').live('click',function(){
		window.location.href = Config.adminpath+'cate/?field='+Config.module;
	});
	$('#mytab a').live('click',function(){
		$('#mytab a').removeClass('cur');
		code = $(this).data('code');
		$(this).addClass('cur');		

		var nowUrl = window.location.href;
		nowUrl = gettihuan(nowUrl,'code',code);
		nowUrl = gettihuan(nowUrl,'p',1);		
		window.location.href = nowUrl;
	});
	if($('#openbox').length==0){
		let subte = '<div id="openbox" class="openboxs none flex fdc aic jcc"><div class="opensubbox"><span class="hst-setwin"><a class="hst-ico hst-close1" href="javascript:;"></a></span><div id="openbox-title" class="openboxs-title"></div><div id="openbox-cont" class="openboxs-cont">';
		let cusedithtml = trim(getstr(mbcont,'<!--[cusedit-begin]','[cusedit-end]-->'));
		if(cusedithtml==''){
			var savetitle = Config.savetitle || '提交保存';
			var cus_edit_class = Config.cus_edit_class || 'gf-grid gf';
			subte += '<form id="myform" name="myform" method="post"><div id="tbedit" class="'+cus_edit_class+'"></div><div class="tc"><a href="javascript:;" id="mysubmit" class="hst-btn hst-bg-green mt20">'+savetitle+'</a></div>';
		}else{
			subte += cusedithtml;
		}
		subte += '</div></div></div>';
		$('body').append(subte);		
	}
	let nowp = get('p');//getCookie('nowpage');
	if(nowp=='') nowp = 1;
	if(nowp==0) nowp = 1;
	let modulec = getCookie('module');
	let code_cookie = '';//getCookie('code');
	if(nowp==undefined) nowp = 1;
	if(modulec==undefined) modulec = '';	
	Config.url = gettihuan(Config.url,'p',nowp);
	Config.nowpage = nowp;
	code = get('code');
	if(code!=''){
		$('#mytab a').removeClass('cur');
		$('#mytab a[data-code='+code+']').addClass('cur');
		Config.map = gettihuan(Config.map,'code',code);
	}else if(code=='' && code_cookie!='' && Config.module==modulec){
		$('#mytab a').removeClass('cur');
		$('#mytab a[data-code='+code_cookie+']').addClass('cur');
		Config.map = gettihuan(Config.map,'code',code_cookie);
	}
	var myurl = Config.url + '&' + Config.map;
	// 获取当前页面URL的查询参数
	var params = new URLSearchParams(window.location.search);
	// 遍历所有参数，追加到myurl
	for (let [key, value] of params) {
		myurl += '&' + key + '=' + encodeURIComponent(value);
	}	
	if (Config.module == 'Cate') {
		var field = params.get('field');
		if (!field){			
		}
	}
	if(typeof(window['_before_index'])=== "function"){		
		_before_index();
	}
	if($('#myadd').length>0) $('#myadd').html('添加'+Config.name);
	if($('#mycate').length>0) $('#mycate').html(Config.name+'分类');
	let myqq = trim(get('q'));
	let mytid = trim(get('mytid'));
	if (myqq!=''){
		try{
			myqq = decodeURIComponent(myqq);
		}catch(e){
			// 如果已经解码过或格式不对，忽略
		}
		$('#q').val(myqq);
	}
	if(mytid=='0'||mytid=='1') $('#mytid').val(mytid);
	getlist(myurl,'_after_index');
});