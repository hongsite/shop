//index/index页面js
Config.module = 'Index';Config.name = '后台首页';
function _after_index(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2,closeBtn:true,success:function(){
			location.href = '/login/';
		}});
		return ;
	}

	let orderlist = data.orderlist || [];
	let tjlist = data.tjlist || [];
	let paihanglist = data.paihanglist || [];
	let daylist = data.daylist || [];	
	let userinfo = data.userinfo;
	let username = userinfo.username || '';

	let orderhtml = getmbhtml('orderlist',orderlist);

	$('#orderhtml').html(orderhtml);

	let tjhtml = getmbhtml('tjlist',tjlist);
	$('#indextop').html(tjhtml);

	let paihanghtml = getmbhtml('paihanglist',paihanglist);
	$('#hotGoods').html(paihanghtml);

	$('#username').text(username);

	

	var numarr = [];
	var titarr = [];

	if(daylist.length>0){
		$(daylist).each(function(i,b){
			numarr.push(b.nums);
			titarr.push(b.day);
		});
	}
	var optionss = {
	did:'sale',
	title:false,
	data:[numarr],
	str:titarr
	};
	var chars = new nchars(optionss);
	chars.init();

	const palette = [
		'#1e3a8a', // 深蓝
		'#dc2626', // 红
		'#16a34a', // 绿
		'#9333ea', // 紫
		'#ea580c', // 橙
		'#0891b2', // 青
		'#db2777', // 玫红
		'#65a30d', // 橄榄绿
		'#7c3aed', // 蓝紫
		'#b45309'  // 棕橙
	];

	let catezb = data.catezb || [];

	const chartData = catezb.map((item, index) => ({
		label: item.label,
		value: item.value,
		color: item.label === '其他'
			? '#6b7280'                     // “其他”固定灰色
			: palette[index % palette.length]
	}));

	console.log('chartData=',chartData);

	const chart1 = createDonutChart('#chart1', chartData, {
		title: '商品分类销售占比',
		size: 140,
		thickness: 20,
		centerLabel: '订单总数',
		showLabels: true,
		animate: true,
	});

	set_left_height();
}
$(document).ready(function(){
	getlist(Config.apipath+'index.php?s=Index&a=index_json','_after_index');
	
});
