// index/index页面js
Config.module = 'Huiyuan';
Config.name = '客户';
var hyid = 0,uid=0;
var upid = 'filelist';
// 获取当前已选中的对接人ID数组（过滤无效值）
function getSelectedPocIds() {
    var val = $('#myform input[name=pocid]').val();
    if (!val || val === '0') return [];
    return val.split(',').filter(function(id) {
        return id && id !== '0';
    });
}

// 获取当前已选中的对接人姓名数组（过滤无效值）
function getSelectedPocNames() {
    var val = $('#myform input[name=poc]').val();
    if (!val) return [];
    return val.split(',').filter(function(name) {
        return name && name.trim() !== '';
    });
}

// 同步弹窗中对接人列表的高亮状态（根据已选中的ID自动添加cur类）
function syncPocListSelection() {
    var selectedIds = getSelectedPocIds();
    $('#poclist li').each(function() {
        var id = String($(this).data('id'));
        if (selectedIds.includes(id)) {
            $(this).addClass('cur');
        } else {
            $(this).removeClass('cur');
        }
    });
}
function _after_choose(datas) {
    $('#poclist').html('未找到符合条件的记录');
    var status = datas.status || 0;
    if (status != 1) return;
    var data = datas.data || [];
    var html = getmbhtml('poclist', data);
    $('#poclist').html(html);
    // 渲染完成后自动高亮已添加的对接人
    syncPocListSelection();
    $('#openbox_choosehead').removeClass('none');
}

function _after_search() {
    getlist(Config.adminurl + 'poclist_json&q=' + $('#qs').val(), '_after_choose');
}

function _after_poc() {
    var $pocidInput = $('#myform input[name=pocid]');
    var $pocInput = $('#myform input[name=poc]');

    var currentIds = $pocidInput.val() ? $pocidInput.val().split(',') : [];
    var currentNames = $pocInput.val() ? $pocInput.val().split(',') : [];

    var poclists = [];
    $(currentIds).each(function(i, b) {
        if (currentIds[i] && currentIds[i] !== '0') { // 过滤无效ID
            poclists.push({
                id: currentIds[i],
                truename: currentNames[i] || ''
            });
        }
    });
    var html = getmbhtml('poclists', poclists);
    $('#poc').html(html);

    // 为每个删除按钮设置 data-id，便于删除时获取对应的对接人ID
    $('#poc .delpoc').each(function(index) {
        if (poclists[index] && poclists[index].id) {
            $(this).attr('data-id', poclists[index].id);
        }
    });
}
function _after_add_business(datas){
	$('#tbedit_business').html('');
	var status = datas.status || 0;
	var msg = datas.msg || '';
	if (status != 1){
		layer.msg(msg);
		return;
	}
	var data = datas.data || {};
	var html = getmbhtml('add_business_list', [data]);
	$('#tbedit_business').html(html);

	$('#filelist').html('');
	var filelist = datas.filelist || [];
	filelist.push({id:0,filename:'',oldfilename:'',title:'添加附件',ext:'upload1',isadd:1});
	var html = getmbhtml('filelist',filelist);
	$('#filelist1').html(html);

	upid = 'filelist1';

	laydate.render({
        elem: '#contract_date',
        type: 'datetime'
    });
	laydate.render({
        elem: '#etime',
        type: 'date'
    });

	$('#openbox_addbusiness').removeClass('none');
}


function _after_update_business(datas){
	var status = datas.status || 0;
	var msg = datas.msg || '';
	if (status != 1){
		layer.alert(msg);
		return;
	}
	$('#openbox_addbusiness').addClass('none');
	layer.msg(msg);
}
function edit_render_after(datas){
	$('#filelist').html('');
	upid = 'filelist';
	var filelist = datas.filelist || [];
	filelist.push({id:0,filename:'',oldfilename:'',title:'添加附件',ext:'upload1',isadd:1});
	var html = getmbhtml('filelist',filelist);
	$('#filelist').html(html);
    _after_poc();
}
function _after_upload_dec(data){
	var status = data.status || 0;
	if(status!=1){
		layer.msg(data.msg);
		return ;
	}
	var filename = data.filename || '';
	var ext = data.ext || '';
	var ispic = data.ispic || 0;
	var oldfilename = data.oldfilename || '';
	var id = data.id || 0;
	var filelist = {id:id,filename:filename,oldfilename:oldfilename,title:'附件',ext:ext,isadd:0,ispic:ispic};
	var html = getmbhtml('filelist',[filelist]);
	$('#'+upid+' a[data-isadd=1]').before(html);
}
function _after_upload_dump(data){
	layer.alert(data.msg,{icon:1,success:function(){
		//location.reload();
    }});	
}

$(document).ready(function() {
    $('#choose').live('click', function() {
        _after_search();
    });

	

    $('#searchs').live('click', function() {
        _after_search();
    });

    $('#qs').live('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            _after_search();
        }
    });

	$('.add1').live('click', function(){
		var hid = $(this).data('id');
        getlist(Config.apipath + 'index.php?s=Business&a=edit&id=0&hid='+hid,'_after_add_business');
    });

    // 多选交互：点击li切换cur类
    $('#poclist li').live('click', function() {
        if ($(this).hasClass('cur')) {
            $(this).removeClass('cur');
        } else {
            $(this).addClass('cur');
        }
    });

    // 提交选中的多个对接人（全量覆盖保存）
    $('#choosesubmit').live('click', function() {
        var $selectedItems = $('#poclist li.cur');
        var $pocidInput = $('#myform input[name=pocid]');
        var $pocInput = $('#myform input[name=poc]');

        // 收集当前弹窗中所有勾选的对接人
        var selectedIds = [];
        var selectedNames = [];
        $selectedItems.each(function() {
            var id = String($(this).data('id'));
            var name = $(this).data('truename');
            if (id && id !== '0') {
                selectedIds.push(id);
                selectedNames.push(name);
            }
        });

        // 更新表单域（全量覆盖）
        if (selectedIds.length === 0) {
            $pocidInput.val('0');
            $pocInput.val('');
        } else {
            $pocidInput.val(selectedIds.join(','));
            $pocInput.val(selectedNames.join(','));
        }

        // 刷新已选对接人显示区域
        _after_poc();

        // 提示保存成功
        layer.msg('已保存 ' + selectedIds.length + ' 个对接人');

        // 关闭选择弹窗
        $('#openbox_choosehead').addClass('none');
    });

    // 删除单个对接人
    $('.delpoc').live('click', function() {
        var $this = $(this);
        var idToRemove = $this.attr('data-id');
        if (!idToRemove) return;

        
		layer.alert('确定删除当前对接人吗？', {showCancel:true,success:function(){		
			var $pocidInput = $('#myform input[name=pocid]');
			var $pocInput = $('#myform input[name=poc]');

			var currentIds = getSelectedPocIds();
			var currentNames = getSelectedPocNames();

			var index = currentIds.indexOf(String(idToRemove));
			if (index !== -1) {
				currentIds.splice(index, 1);
				currentNames.splice(index, 1);

				if (currentIds.length === 0) {
					$pocidInput.val('0');
					$pocInput.val('');
				} else {
					$pocidInput.val(currentIds.join(','));
					$pocInput.val(currentNames.join(','));
				}

				_after_poc();
				layer.msg('已删除对接人');
			}
		}});
    });

    // 清空所有对接人
    $('#clearhead').live('click', function() {
        layer.confirm('确定清空当前对接人吗？', {
            btn: ['确定', '取消']
        }, function() {
            $('#myform input[name=pocid]').val('0');
            $('#myform input[name=poc]').val('');
            $('#poc').html('');
            layer.msg('清空成功，请点保存生效');
        });
    });

	$('.myfile').live('click', function(e){
		e.stopPropagation();
		 var id = $(this).data('id');
		 var isadd = $(this).data('isadd');
         var filename = $(this).data('filename');
		 if(isadd==1){
			$('#uploadfile').click();
		 }else{
			window.open(filename, '_blank');
			//layer.msg('执行了');
		 }
		 return false;
    });
	$('.myfile span').live('click', function(e){
		e.stopPropagation();
		var obj = $(this).parent();
		var title = obj.data('oldfilename');
		layer.alert('确定要删除，'+title+' 吗', {showCancel:true,success:function(){
			layer.msg('删除成功，点提交保存生效');
			obj.remove();					
		}});
		return false;
	});

	

	$('#uploadfile').live('change',function(){
		var fileInput = $(this).get(0);
		var files = fileInput.files;
		const maxFiles = 5;
		var allowedExts = ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'doc', 'docx', 'pdf', 'xlsx', 'xls', 'ppt', 'pptx', 'txt'];
		var filenums = files.length;
		if(filenums<1){
			layer.alert('请选择最少一个文件');
			return ;
		}
		if(filenums>maxFiles){
			layer.alert('一次最多'+maxFiles+'个文件');
			return ;
		}
		// 遍历所有文件进行上传
		for (var i = 0; i < files.length; i++) {
			var file = files[i];
			// 获取文件扩展名
			var ext = file.name.split('.').pop().toLowerCase();
			if (!allowedExts.includes(ext)) {
				layer.alert('格式不正确，请上传 ' + allowedExts.join('|') + ' 格式的文件');
				return;
			}
			if(file.size>102400*50){
				layer.alert( '不能上传大于5M的文件',{icon:2});
				return ;
			}
		}

		
		
		for (var i = 0; i < files.length; i++) {
			var file = files[i];
			let fd = new FormData();
			fd.append('fileToUpload',file);	
			fd.append('path','pic1');
			fd.append('uid',$('#uploadfile').data('uid'));
			fd.append('hid',$('#uploadfile').data('hid'));
			fd.append('aid',$('#uploadfile').data('aid'));
			fd.append('module_name',Config.module);
			fd.append('thumb_width','90,200,310,500');					
			upload_to_server_batch(file,fd,'_after_upload_dec');
		}
    });

	$('.hoverhide').live('hover', function() {
		var $this = $(this);
		var pos = $this.offset();
		var html = $this.data('tip');
		if(html=='') return ;
		$('#tipbox').removeClass('none');
		$('#tipbox').css('left',pos.left+'px');
		$('#tipbox').css('top',(pos.top - 24)+'px');
		$('#tipbox').html(html);				
	}, function(){	
		$('#tipbox').addClass('none');
	});
	$('#tipbox').live('hover', function(){
		$('#tipbox').removeClass('none');
	}, function(){	
		$('#tipbox').addClass('none');
	});

	$('#downmoban').attr('href','/moban_huiyuan.xlsx');
	
    _after_poc();
});
