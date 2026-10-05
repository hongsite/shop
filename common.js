//通用定义js
Config.url = Config.apipath+"index.php?s="+Config.module+"&a=index&issj=1";
Config.delurl = Config.apipath+"index.php?s="+Config.module+"&a=delete_json&issj=1";
Config.adminurl = Config.apipath+"index.php?s="+Config.module+"&a=";
Config.adminpath = '/';
uploadurl = '/api/index.php?s=Upload&a=upload_json';
picpath = '/static/upload/pic1/';
descpath = '/static/upload/des/';
var defaultprovinceid=21,defaultcityid=2998;defaultcountyid=3018;
var privilege_list = [
{'id':'1','title':'可查看'},
{'id':'3','title':'可新增'},
{'id':'5','title':'可编辑'},
{'id':'7','title':'可删除'}
];
var prostatlist = [
{'id':'0','title':'销售中'},
{'id':'1','title':'已下架'},
{'id':'2','title':'回收站'}
];
var isindexlist = [
{'id':'0','title':'不推荐'},
{'id':'1','title':'首页推荐'}
];

var level_list = [
{'id':'0','title':'会员'},
{'id':'1','title':'一级'},
{'id':'2','title':'二级'},
{'id':'3','title':'三级'}
];

var isonline_list = [
{'id':'0','title':'离线'},
{'id':'1','title':'在线'}
];

var verifield_list = [
{'id':'0','title':'<span class="hst-badge hst-btn-normal">未实名</span>'},
{'id':'1','title':'<span class="hst-badge hst-bg-green">已实名认证</span>'}
];

var focus_list = [
{'id':'0','title':'首页焦点图'},
{'id':'1','title':'首页三宫格'},
{'id':'2','title':'首页中间广告'}
];

var settled_list = [
{'id':'0','title':'<span class="hst-badge hst-btn-normal">未结算</span>'},
{'id':'1','title':'<span class="hst-badge hst-bg-green">已结算</span>'}
];

var bom_stat_list = [
    {'id':'0','title':'<span class="hst-badge hst-bg-blue">待审核</span>'},
    {'id':'1','title':'<span class="hst-badge hst-bg-orange">已审核</span>'},
    {'id':'3','title':'<span class="hst-badge hst-bg-cyan">生产中</span>'},
    {'id':'5','title':'<span class="hst-badge hst-bg-green">已完工</span>'},
    {'id':'7','title':'<span class="hst-badge hst-bg-purple">已发货</span>'},
    {'id':'9','title':'<span class="hst-badge hst-bg-green">已完成</span>'},
    {'id':'11','title':'<span class="hst-badge hst-bg-red">已取消</span>'}
];

var bom_audit_list = [
    {'id':'0','title':'<span class="hst-badge hst-btn-normal">待审核</span>'},
    {'id':'1','title':'<span class="hst-badge hst-bg-blue">审核中</span>'},
    {'id':'3','title':'<span class="hst-badge hst-bg-green">审核通过</span>'},
    {'id':'5','title':'<span class="hst-badge hst-bg-red">审核驳回</span>'},
    {'id':'7','title':'<span class="hst-badge hst-bg-orange">已撤回</span>'}
];

function _after_nologin(data){
	let nologin = data.nologin ? data.nologin:0;
	if(nologin==1){
		layer.alert(data.msg,{icon:2,success:function(index){
			location.href = Config.adminpath+'login/';
		}});
		return ;
	}
	return true;
}
function setvipetime(){
	let hst_sysday = getCookie('hst_member_sysday');
	let hst_vipetime = getCookie('hst_member_vipetime');
	if(hst_vipetime>0){
		$('#hst_vipetime').text(''+date(hst_vipetime,'Y-m-d'));		
	}else{
		if(hst_sysday>0){
			$('#hst_vipetime').text('还可试用'+hst_sysday+'天');
		}else{
			$('#hst_vipetime').text('试用期已结束');
		}
		$('#hst_vipetime').show();
	}
}
$(document).ready(function(){
	setvipetime();
});