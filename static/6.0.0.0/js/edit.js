Config.editurl = Config.apipath+'index.php?s='+Config.module+'&a=edit&issj=1';
Config.updateurl = Config.apipath+'index.php?s='+Config.module+'&a=update_json&issj=1';
function _after_edit(data){
	$('#tbedit').html('');
	if(typeof(window['edit_render_before']) === "function"){
		data = edit_render_before(data);
	}	
	let datas = data.data || {};
	let te = getmbhtml('vo',[datas],'');
	let detail = datas.detail || [];	
	let title;
	if(Config.cus_edit_title==''){
		title = Config.editid>0 ? '编辑'+Config.name:'添加'+Config.name;
	}else{
		title = Config.cus_edit_title; 
	}
	$('#openbox-title').text(title);
	
	$('#tbedit').html(te);

	var subhtml = gethtmlmiddle('editdetail');
	
	if(subhtml!='' && detail.length>0){			
		let subte = getmbhtml('editdetail',detail,'');		
		$('#editdetail').html(subte);
	}

	$('input').not('[type=radio],[type=checkbox]').attr('autocomplete','off');

	if(Config.isalone==false){
		$('#openbox').removeClass('none');
		$('#mask').show();
	}
	$('#loading').hide();
	setselect();
	$('input').not('[type=radio], [type=checkbox]').attr('autocomplete','off');
	if(typeof(window['edit_render_after']) === "function"){
		edit_render_after(data);
	}
}
function _after_update(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}
	var isadd = data.isadd || 0;
	if(typeof(window['update_render_before']) === 'function'){
		update_render_before(data);
	}
	var datas = data.data || {};
	var id = datas.id || 0;
	if(typeof window['upindex'] === "function"){
		upindex(datas);
	}else{
		layer.msg(data.msg,{icon:1});
	}
	$('#openbox').addClass('none');
	$('#mask').hide();
	let jumpurls = getUrlFile()+'?id='+id;
	if(Config.module=='Pro') jumpurls += '&tid='+get('tid');
	if(Config.isalone==true && isadd==1) window.location.href = jumpurls;
}
$(document).ready(function(){
	if(Config.isalone==true){
		let id = get('id');		
		if(id==null) id = 0;
		Config.editid = id;
		getlist(Config.editurl+'&id='+id+'&tid='+get('tid')+'&hid='+get('hid'),'_after_edit');
	}
	$('#mysubmit,#mysubmit1').live('click',function(){
		let title = '';
		let cus_edit_title = Config.cus_edit_title || '';
		var cus_updateurl = $(this).data('updateurl');
		if (typeof cus_updateurl === 'undefined') cus_updateurl = '';
		var cus_formname = $(this).data('formname');
		if (typeof cus_formname === 'undefined') cus_formname = '';
		var cus_update_name = $(this).data('update_name');
		if (typeof cus_update_name === 'undefined') cus_update_name = '';
		var cus_title = $(this).data('title');
		if (typeof cus_title === 'undefined') cus_title = '';
		if(cus_edit_title==''){
			title = Config.editid>0 ? '编辑'+Config.name:'添加'+Config.name;
		}else{
			title = cus_edit_title;
		}

		if (typeof window['_before_update'] === "function"){
			let istrue = _before_update();
			if(istrue!=true){
				layer.alert(istrue,{icon:2});
				return ;
			}
		}

		var myurl = Config.updateurl;
		if(cus_updateurl!='') myurl = Config.apipath+cus_updateurl;
		var formname = 'myform';
		if(cus_formname!='') formname = cus_formname;
		var update_name = '_after_update';
		if(cus_update_name!='') update_name = cus_update_name;
		if(cus_title!='') title = cus_title;
		if(Config.module=='Cate'){
			var field = get('field');
			myurl += '&field='+field;
		}
		let arr = {'title':'确认'+title,'cont':'确定'+title+'吗'};
		update(myurl,$('#'+formname).serialize(),update_name,1,arr);
	});

});