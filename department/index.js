//orders/index页面js
Config.module = 'Department';Config.name = '部门';
Config.htmlbq = '#tblist';
function _after_choose(datas){
	$('#headlist').html('未找到符合条件的记录');
	var status = datas.status || 0;
	if(status!=1) return ;
	var data = datas.data || [];
	var html = getmbhtml('headlist',data);
	$('#headlist').html(html);
	$('#openbox_choosehead').removeClass('none');
}
function _after_search(){
	getlist(Config.adminurl+'headlist_json&q='+$('#qs').val(),'_after_choose');
}
$(document).ready(function(){
	$('#search').click(function(){
		var q = $('#q').val();		
		let codes = $('#mytab a.cur').data('code');
		Config.map = 'q='+q+'&code='+codes;
		getlist(Config.url+'&'+Config.map,'_after_index');
	});
	$('#q').live('keypress',function(e) {
		if (e.key === 'Enter' || e.keyCode === 13) {
			var q = $('#q').val();			
			let codes = $('#mytab a.cur').data('code');
			Config.map = 'q='+q+'&code='+codes;
			getlist(Config.url+'&'+Config.map,'_after_index');
			e.preventDefault();
		}
	});
	$('#choose').live('click',function(){
		_after_search();		
	});
	$('#searchs').live('click',function(){
		_after_search();		
	});

	$('#qs').live('keypress',function(e) {
		if (e.key === 'Enter' || e.keyCode === 13) {
			_after_search();
		}
	});

	$('#headlist li').live('click',function(){
		$('#headlist li').removeClass('cur');
		$(this).addClass('cur');
	});

	$('#choosesubmit').live('click',function(){
		var obj = $('#headlist li.cur');
		if(obj.length<1){
			layer.alert('请选择一个负责人');
			return ;
		}
		$('#myform input[name=headid]').val(obj.data('id'));
		$('#header').html(obj.data('truename'));
		$('#openbox_choosehead').addClass('none');
	});
	$('#clearhead').live('click',function(){
		layer.alert('确定清空当前负责人吗', {showCancel:true,success:function(){		
			$('#myform input[name=headid]').val('0');
			$('#header').html('');
			layer.msg('清空成功，请点保存生效');
		}});
		
	});

	

	
	
});
