//列表更新后的处理
var upindex = function(data){	
	let id = data.id || 0;
	let te = getmbhtml('list',[data],'listdetail');
	console.log('te=',te);
	let obj = $('#tblist .mylist[data-id='+id+']');	
	if(obj.length>0){
		let obj1 = $('#tblist .mylist[data-ordid='+id+']');
		if(obj1.length>0) obj1.remove();
		obj.before(te);		
		obj.remove();
	}else{		
		$('#tblist tr:first').after(te);
	}
	if($('#tblist .mylist[data-id='+id+']').length>0) $('#nodata').css('display','none');
	if(typeof(window['_after_edit_re']) === "function"){
		//_after_edit_last(data);
	}
	layer.msg('操作成功',{icon:1});
}