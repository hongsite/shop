//orders/index页面js
Config.module = 'Website';Config.isalone = true;
Config.cus_edit_title = '保存系统配置';
$(document).ready(function(){	
	$('.upload_picurl').live('click',function(){
		var obj = $(this).parent();
		obj.find('.mypicurl').click();
	});
	$('.mypicurl').live('change',function(){
		var obj = $(this).parent();
		var file = $(this).get(0).files[0];
		var arr = {'path':'pic1','field':'picurl','thumb_width':'90,300','calfun':'thum'}
		uptoserver(file,obj,arr);
	});
	$('.clearpicurl').live('click',function(){
		var obj = $(this).parent();
		var picurlfield = obj.data('field');
		layer.alert('确定要清除吗',{showCancel:true,success:function(index){			
			$('#myform input[name='+picurlfield+']').val('');
			obj.find('.imgbox img').attr('src','');
		}});
	});	
});
