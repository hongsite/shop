//orders/index页面js
Config.module = 'Staff';Config.name = '员工';
Config.htmlbq = '#tblist';
var department_list,job_list;
function edit_render_before(data){
	department_list = data.department_list || [];
	job_list = data.job_list || [];
	return data;
}
function edit_render_after(data){
	$('#tbedit').addClass('gf-grid-2');
}
$(document).ready(function(){
});
