//index/index页面js
Config.module = 'Orders';Config.name = '订单管理';
Config.htmlbq = '#tblist';
function edit_render_after(data){
	let detail = data.detail || [];
	let te = '';
	$('#detail tbody').html('');
	console.log('detail=',detail);
	if(detail.length>0){
		te = getmbhtml('listdetail',detail);
		$('#detail tbody').html(te);
	}
	$('#openbox_detail').removeClass('none');
}
$(document).ready(function(){
	$('.order-card').live('click',function(){
		var id = $(this).data('id');
		getlist(Config.adminurl+'edit&id='+id,'_after_vo');		
	});
});
