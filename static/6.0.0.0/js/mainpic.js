function _after_zhutu(data){
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
	let obj = $('#mainpic ul li');
	obj.each(function(){
		if($(this).find('.picurl').val()==''){
			$(this).find('.picurl').val(b.filename);
			$(this).find('.picurlw').val(b.width);
			$(this).find('.picurlh').val(b.height);
			$(this).find('img').attr('src',picpath+b.filename+'_200.jpg');
			return false;
		}
	});
}
function ztupload(did){
	var fileInput = $('#'+did).get(0);
	var files = fileInput.files;
	const maxFiles = 5;
	
    
    // 清除无效选择
    if (files.length > maxFiles) {
        layer.alert('最多只能选择'+maxFiles+'个图片');
        return;
    }    

	// 遍历所有文件进行上传
	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		var rep = /jpeg|png|gif|bmp/ig;
		var gstyle = file.type.split('/')[1];
		if(!rep.test(gstyle)){
			layer.alert('图片格式不正确，请上传jpeg|png|gif|bmp格式的图片');
			return ;
		}
		if(file.size>5*1024*1024){
			layer.alert( '不能上传大于5M的图片',{icon:2});
			return ;
		}
	}
	for (var i = 0; i < files.length; i++) {
		var file = files[i];

		let fd = new FormData();
		fd.append('fileToUpload',file);	
		fd.append('path','pic1');
		fd.append('thumb_width','90,200,500');
		uploaddo(file,fd,'_after_zhutu');
	}
	
}
$(document).ready(function() {		
	$('.mainpicupbtn').live('click',function(){
		$('#ztupload').click();
	});
	$('#ztupload').change(function(){
		//layer.msg('上传');
		ztupload('ztupload');
	});
});