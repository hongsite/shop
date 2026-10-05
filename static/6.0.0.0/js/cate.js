Config.editurl = Config.apipath+'index.php?s='+Config.module+'&a=index&issj=1';
Config.updateurl = Config.apipath+'index.php?s='+Config.module+'&a=update_json&issj=1';
let picobj,delobj;
function _after_upload(data){
	if(data.status!=1){
		layer.msg(data.msg,{icon:2});
		return ;
	}
	let b = {};
	b.filename = data.filename;
	b.field = b.field;
	b.width = data.width;
	b.height = data.height;
	b.filesize = data.filesize;
	b.oldname = data.oldname;
	picobj.find('.picurl').val(b.filename);
	picobj.find('.preview').attr('src',picpath+b.filename);
	picobj.find('.width').val(b.width);
	picobj.find('.height').val(b.height);
}
function _after_delete(data){
	if(data.status!=1){
		layer.msg(data.msg,{icon:2});
		return ;
	}
	delobj.remove();	
}
function _after_updates(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}		
	layer.msg(data.msg,{icon:1});
	getlist(Config.adminurl+'index&field='+get('field'),'_after_index');
}
$(document).ready(function(){	
	$('#mysubmits').live('click',function(){
		let title = '提交保存';
		let arr = {'title':'确认'+title,'cont':'确定'+title+'吗'};
		update(Config.updateurl+'&field='+get('field'),$('#myform').serialize(),'_after_updates',1,arr);
	});
	$('#myadds').click(function(){
		let i = findmaxi($('.mylist'));
		console.log('i=',i);
		let te = getmbhtml('list',[{title:'',picurl:'',id:0,i:i,width:0,height:0,stat:0,tid:0}]);
		$('#tblist').append(te);
	});
	$('.uppic').live('click',function(){
		let obj = $(this).parent().parent().parent();
		obj.find('.uploadfile').click();
	});
	$('.uploadfile').live('change',function(){

		var fileInput = $(this).get(0);
		var files = fileInput.files;
		let file = files[0];

		let fd = new FormData();
		fd.append('fileToUpload',file);	
		fd.append('path','pic1');
		picobj = $(this).parent().parent().parent();
		uploaddo(file,fd,'_after_upload');
	});

	$('.delete').live('click',function(){
		delobj = $(this).parent().parent().parent();
		let id = delobj.data('id');
		layer.alert('确定要删除吗',{showCancel:true,success:function(){
			if(id==0){
				delobj.remove();
				return ;
			}
			update(Config.delurl+'&ischeck=1&field='+get('field'),{id:id},'_after_delete');
		}});
	});

});