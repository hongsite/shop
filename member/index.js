//orders/index页面js
Config.module = 'Member';Config.name = '人员';
Config.htmlbq = '#tblist';
var department_list,job_list;
function edit_render_before(data){
	department_list = data.department_list || [];
	job_list = data.job_list || [];
	return data;
}
function _after_choose(datas) {
    $('#wxlist').html('未找到符合条件的记录');
    var status = datas.status || 0;
    if (status != 1) return;
    var data = datas.data || [];
    var html = getmbhtml('wxlist', data);
    $('#wxlist').html(html);
    $('#openbox_choosehead').removeClass('none');
}
function _after_search() {
    getlist(Config.adminurl + 'wxlist_json&q=' + $('#qs').val()+'&chooseid='+$('#myform input[name=wxid]').val(), '_after_choose');
}
function setface() {
	//$('#myform input[name=wxid]').val('0');
	//$('#wx').text('');
	var obj = $('#wxlist li.cur');
    if(obj.length>0){		
		$('#wxnickname').text(obj.data('nickname'));
		$('#myform input[name=wxid]').val(obj.data('id'));
	}
}
$(document).ready(function(){
	$('#choose').live('click', function() {
        _after_search();
    });
	$('#choosesubmit').live('click', function() {
        setface();
		$('#openbox_choosehead').addClass('none');
    });

	// 多选交互：点击li切换cur类
    $('#wxlist li').live('click', function() {
        if ($(this).hasClass('cur')) {
            $(this).removeClass('cur');
        }else{
            $('#wxlist li').removeClass('cur');
			$(this).addClass('cur');
        }
		setface();
    });
	
});
