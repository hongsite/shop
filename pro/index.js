//orders/index页面js
Config.module = 'Pro';
Config.name = '商品管理';
var maintid  = '0';
var catelist = [];
Config.htmlbq = '#tblist';
function _before_index_render(data){
	catelist = data.catelist || [];
	return data;
}
$(document).ready(function(){
	$('.stat').live('click',function(){	
		var id = $(this).data('id');
		var tid = $(this).data('tid');
		var obj = $('.mylist[data-id='+id+']');
		var title = '';
		switch (parseInt(tid)){
			case 0:
				title = '上架';
				break;
			case 1:
				title = '下架';
				break;
			case 2:
				title = '删除';
				break;
			case 4:
				title = '彻底删除';
				break;
		
		}
		layer.alert('确定要'+title+'商品吗',{showCancel:true,success:function(){			
			$('#loading').show();
			$('#loading p').text('数据请求中');
			$.ajax({
				url: Config.adminurl+'stat_json&id='+id+'&tid='+tid,
				type: 'POST',
				dataType: 'json',
				data: {id:id},
				timeout: 60000,
				success: function (data) {
					$('#loading').hide();
					if(data.status==1){
						layer.msg(data.msg);
						obj.remove();
					}else{
						layer.alert(data.msg,{icon:2});
					}
				},error: function(XMLHttpRequest, textStatus, errorThrown) {
					$('#loading').hide();
					layer.alert('网络请求错误，可能系统繁忙',{icon:2});
				},complete: function(XMLHttpRequest, textStatus) {
					this;
				}
			});
		}
		});
	});
	$('.myedits').live('click',function(){
		window.location.href = $(this).data('url')+'&tid='+get('tid');
	});
	maintid = get('tid');
	Config.map = Config.map=='' ? 'tid='+maintid:'&tid='+maintid;
});
