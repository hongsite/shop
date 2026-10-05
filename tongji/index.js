//index/index页面js
Config.module = 'Tongji';Config.name = '到期统计';
function _after_index(data){
	var list = data.list || [];	
	var listhtml = getmbhtml('list',list);
	$('#statsContainer').html(listhtml);

	var datas = data.data || [];

	var html = getmbhtml('data',datas);
	$('#tableBody').html(html);

	if(typeof(window['set_left_height'])==='function') set_left_height();
	
}
$(document).ready(function(){
	$('#timeboxpre dl dt').click(function(e){
		let text = $(this).text();
		let obj = $(this).parent().parent();
		obj.find('dt').removeClass('cur');
		$(this).addClass('cur');
		obj.find('em').text(text);
		obj.find('dl').hide();

		settype();

		e.stopPropagation();
	});
	$('#timeboxpre').click(function(e){
		let obj = $(this).find('dl');
		let css = $(this).find('dl').css('display');
		if(css=='none'){
			obj.show();
		}else{
			obj.hide();
		}		
	});


	
	$('#chooseday a').click(function(e){
		let obj = $(this).parent();
		obj.find('a').removeClass('cur');
		$(this).addClass('cur');
		let ts = $(this).data('tid');
		if(ts=='7day'){
			$('#btime').val(date(nowt-86400*7,'Y-m-d'));
			$('#etime').val(date(nowt,'Y-m-d'));
		}else if(ts=='month'){
			let now = new Date();
			// 将日期设置为本月的第一天
			now.setDate(1);
			// 获取本月月初的时间戳
			let startTimeStamp = parseInt(now.getTime());
			$('#btime').val(date(startTimeStamp/1000,'Y-m-01'));
			$('#etime').val(date(nowt,'Y-m-d'));
		}else if(ts=='lastmonth'){
			let now = new Date();
			// 获取当前月份（注意：月份是从0开始计数的，0代表一月，11代表十二月）
			let currentMonth = now.getMonth();

			// 获取当前年份
			let currentYear = now.getFullYear();

			// 计算上月的月份（当当前月份为0，即一月时，上月为十二月）
			let lastMonth = currentMonth === 0? 11 : currentMonth - 1;

			// 计算上月的年份（当上月为十二月时，年份需减1）
			let lastYear = lastMonth === 11 && currentMonth === 0? currentYear - 1 : currentYear;

			// 获取上月的第一天
			let firstDayOfLastMonth = new Date(lastYear, lastMonth,1);

			// 获取上月的最后一天（利用Date构造函数特性，传入下月的0号，会得到本月的最后一天）
			let lastDayOfLastMonth = new Date(lastYear, lastMonth +1,0);

			// 将日期格式化为字符串
			function formatDate(date) {
				let year = date.getFullYear();
				let month = String(date.getMonth()+1).padStart(2,'0');
				let day = String(date.getDate()).padStart(2, '0');
				return `${year}-${(month)}-${day}`;
			}
			$('#btime').val(formatDate(firstDayOfLastMonth));
			$('#etime').val(formatDate(lastDayOfLastMonth));
		}
		$('#timesearch').click();
	});

	let texts = $('#timeboxpre em').text();
	
	$('#timeboxpre dl dt').each(function(){
		if($(this).text()==texts) $(this).addClass('cur');
	});

	$('#timesearch').live('click',function(){
		getlist(Config.adminurl+'index_json','_after_index');
	});

	

	settype();

	laydate.render({
        elem: '#btime',
        type: 'date'
    });

	laydate.render({
        elem: '#etime',
        type: 'date'
    });


	getlist(Config.adminurl+'index_json','_after_index');

});
