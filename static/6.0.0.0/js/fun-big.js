//大函数
var date=function(timestamp,format){
if(typeof(timestamp) == "undefined") timestamp=0;
if(timestamp=='""') timestamp=0;
if(timestamp=='') timestamp=0;
if(timestamp==0) return '';
if(typeof(format) == "undefined") format = 'Y-m-d H:i';
let a,jsdate=((timestamp)?new Date(timestamp*1000):new Date());let pad=function(n,c){if((n=n+"").length<c){return new Array(++c-n.length).join("0")+n}else{return n}};let txt_weekdays=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];let txt_ordin={1:"st",2:"nd",3:"rd",21:"st",22:"nd",23:"rd",31:"st"};let txt_months=["","January","February","March","April","May","June","July","August","September","October","November","December"];let f={d:function(){return pad(f.j(),2)},D:function(){return f.l().substr(0,3)},j:function(){return jsdate.getDate()},l:function(){return txt_weekdays[f.w()]},N:function(){return f.w()+1},S:function(){return txt_ordin[f.j()]?txt_ordin[f.j()]:'th'},w:function(){return jsdate.getDay()},z:function(){return(jsdate-new Date(jsdate.getFullYear()+"/1/1"))/864e5>>0},W:function(){let a=f.z(),b=364+f.L()-a;let nd2,nd=(new Date(jsdate.getFullYear()+"/1/1").getDay()||7)-1;if(b<=2&&((jsdate.getDay()||7)-1)<=2-b){return 1}else{if(a<=2&&nd>=4&&a>=(6-nd)){nd2=new Date(jsdate.getFullYear()-1+"/12/31");return date("W",Math.round(nd2.getTime()/1000))}else{return(1+(nd<=3?((a+nd)/7):(a-(7-nd))/7)>>0)}}},F:function(){return txt_months[f.n()]},m:function(){return pad(f.n(),2)},M:function(){return f.F().substr(0,3)},n:function(){return jsdate.getMonth()+1},t:function(){let n;if((n=jsdate.getMonth()+1)==2){return 28+f.L()}else{if(n&1&&n<8||!(n&1)&&n>7){return 31}else{return 30}}},L:function(){let y=f.Y();return(!(y&3)&&(y%1e2||!(y%4e2)))?1:0},Y:function(){return jsdate.getFullYear()},y:function(){return(jsdate.getFullYear()+"").slice(2)},a:function(){return jsdate.getHours()>11?"pm":"am"},A:function(){return f.a().toUpperCase()},B:function(){let off=(jsdate.getTimezoneOffset()+60)*60;let theSeconds=(jsdate.getHours()*3600)+(jsdate.getMinutes()*60)+jsdate.getSeconds()+off;let beat=Math.floor(theSeconds/86.4);if(beat>1000)beat-=1000;if(beat<0)beat+=1000;if((String(beat)).length==1)beat="00"+beat;if((String(beat)).length==2)beat="0"+beat;return beat},g:function(){return jsdate.getHours()%12||12},G:function(){return jsdate.getHours()},h:function(){return pad(f.g(),2)},H:function(){return pad(jsdate.getHours(),2)},i:function(){return pad(jsdate.getMinutes(),2)},s:function(){return pad(jsdate.getSeconds(),2)},O:function(){let t=pad(Math.abs(jsdate.getTimezoneOffset()/60*100),4);if(jsdate.getTimezoneOffset()>0)t="-"+t;else t="+"+t;return t},P:function(){let O=f.O();return(O.substr(0,3)+":"+O.substr(3,2))},c:function(){return f.Y()+"-"+f.m()+"-"+f.d()+"T"+f.h()+":"+f.i()+":"+f.s()+f.P()},U:function(){return Math.round(jsdate.getTime()/1000)}};return format.replace(/[\\]?([a-zA-Z])/g,function(t,s){if(t!=s){ret=s}else if(f[s]){ret=f[s]()}else{ret=s}return ret})}

var setpara=function(str,field){var para=$('#para').text();var newpara=field+'='+str;if(para==''){$('#para').text(newpara)}else{if(para.indexOf(field+'=')!=-1){$('#para').text(gettihuan(para,str,field))}else{$('#para').text(para+'&'+newpara)}}};
function getfilesize(bytes) {
    if (bytes === 0) return '0.00 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    // 保留两位小数
    const value = (bytes / Math.pow(k, i)).toFixed(2);
    return value + ' ' + sizes[i];
}
function gettihuan(url, targetField, newValue) {
    // 空 URL 直接返回带 ? 的查询字符串
    if (!url) {
        return '?' + targetField + '=' + (newValue !== undefined ? newValue : '');
    }

    // 分离基础地址和查询字符串
    var qIndex = url.indexOf('?');
    var base = qIndex === -1 ? url : url.substring(0, qIndex);
    var query = qIndex === -1 ? '' : url.substring(qIndex + 1);

    // 解析查询参数到对象（覆盖同名参数）
    var params = {};
    if (query) {
        var parts = query.split('&');
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            if (part) {
                var eqIndex = part.indexOf('=');
                var key, val;
                if (eqIndex === -1) {
                    key = part;
                    val = '';
                } else {
                    key = part.substring(0, eqIndex);
                    val = part.substring(eqIndex + 1);
                }
                params[key] = val; // 若重复则覆盖（与 URLSearchParams.set 行为一致）
            }
        }
    }

    // 设置目标字段（若 newValue 未定义则置为空字符串）
    params[targetField] = newValue !== undefined ? newValue : '';

    // 重新拼接查询字符串
    var newQueryParts = [];
    for (var key in params) {
        if (params.hasOwnProperty(key)) {
            newQueryParts.push(key + '=' + params[key]);
        }
    }
    var newQuery = newQueryParts.join('&');

    // 组装最终 URL
    return newQuery ? base + '?' + newQuery : base;
}

var gethjtr = function(arr, classname, hjh, hjhtext) {
    var num = $('tr.' + classname + ':eq(0) td').length;
    var te = '<tr class="' + classname + '">';
    
    // 创建一个对象来存储需要统计的列配置
    var columnsToProcess = {};
    for (var i = 0; i < arr.length; i++) {
        var colId = arr[i].id;
        columnsToProcess[colId] = arr[i];
    }
    
    for (var colIndex = 0; colIndex < num; colIndex++) {
        te += '<td>';
        
        if (colIndex === hjh) {
            // 特殊列直接输出指定文本
            te += hjhtext;
        } else {
            // 检查当前列是否在需要处理的列中
            var rt = columnsToProcess[colIndex];
            
            if (!rt) {
                // 不在配置中的列，输出'--'
                te += '--';
            } else if (rt && rt.nums === false) {
                // 配置中指定不显示数值的列，也输出'--'
                te += '--';
            } else {
                // 需要处理的列，计算该列的总和
                var hj = 0;
                $('tr.' + classname).each(function() {
                    var td = $(this).find('td:eq(' + colIndex + ')');
                    var value;
                    
                    // ========== 修改开始 ==========
                    // 检查单元格内是否存在 input 元素
                    var input = td.find('input');
                    if (input.length > 0) {
                        value = input.val();          // 存在 input 则取其值
                    } else {
                        value = td.text();            // 否则取文本内容
                    }
                    // ========== 修改结束 ==========
                    
                    // 移除可能的前缀符号和逗号，以便计算
                    value = value.replace('￥', '').replace('¥', '').replace('$', '').replace(/,/g, '');
                    hj += parseFloat(value) || 0;
                });
                
                // 获取小数位数设置
                var decimalPlaces = rt.nums;
                var formattedValue = '';
                
                // 格式化输出
                if (decimalPlaces === undefined || decimalPlaces === null) {
                    // 没有设置小数位数，使用默认整数
                    formattedValue = parseInt(hj);
                } else if (typeof decimalPlaces === 'number' && decimalPlaces >= 0) {
                    // 有设置小数位数，使用formatpno格式化
                    formattedValue = formatpno(hj, decimalPlaces);
                } else {
                    // 其他情况使用默认整数
                    formattedValue = parseInt(hj);
                }
                
                // 加入before和after参数（注意处理拼写错误befor）
                var prefix = rt.before || rt.befor || '';
                var suffix = rt.after || '';
                te += prefix + formattedValue + suffix;
            }
        }
        te += '</td>';
    }
    
    te += '</tr>';
    $('tr.' + classname + ':last').after(te);
};
/**
 * 给当前 URL 添加或替换 p 参数（注意：参数名为 p）
 */
function buildPageUrl(page) {
    var url = window.location.href;

    // 获取 #q 的文本，并去除首尾空格
    var q = trim($('#q').val());

    // 获取 #mytid 的值，并去除首尾空格
    var mytid = trim($('#mytid').val());

    // 先处理分页参数 p
    var hasPage = /[?&]p=/.test(url);
    if (hasPage) {
        url = url.replace(/([?&])p=[^&]*/, '$1p=' + page);
    } else {
        var separator = url.indexOf('?') > -1 ? '&' : '?';
        url += separator + 'p=' + page;
    }

    // 如果 #q 不为空，则追加 q 参数
    if (q !== '') {
        // 如果已经存在 q 参数，先替换，避免重复
        if (/[?&]q=/.test(url)) {
            url = url.replace(/([?&])q=[^&]*/, '$1q=' + encodeURIComponent(q));
        } else {
            var sep = url.indexOf('?') > -1 ? '&' : '?';
            url += sep + 'q=' + encodeURIComponent(q);
        }
    }

    // 如果 #mytid 不为空，则追加 mytid 参数
    if (mytid !== '') {
        // 如果已经存在 mytid 参数，先替换，避免重复
        if (/[?&]mytid=/.test(url)) {
            url = url.replace(/([?&])mytid=[^&]*/, '$1mytid=' + encodeURIComponent(mytid));
        } else {
            var sep2 = url.indexOf('?') > -1 ? '&' : '?';
            url += sep2 + 'mytid=' + encodeURIComponent(mytid);
        }
    }

    return url;
}

var getpage = function() {
    // 1. 解析并计算分页参数（优先使用外部传入，若未传则自动计算）
    let nowpage = parseInt(Config.nowpage, 10) || 1;
    const nums = parseInt(Config.nums, 10) || 0;
    const perpage = parseInt(Config.perpage, 10) || 1; // 防止除零
    let allpage = parseInt(Config.allpage, 10);

	if(nums<1) return ;

    // 如果外部未提供 allpage 或提供的值不准确，则自动计算
    if (isNaN(allpage) || allpage < 1) {
        allpage = Math.ceil(nums / perpage);
    }
    // 边界修正
    if (nowpage < 1) nowpage = 1;
    if (allpage < 1) allpage = 1;
    if (nowpage > allpage) nowpage = allpage;
    // 回写 Config（便于外部使用）
    Config.nowpage = nowpage;
    Config.allpage = allpage;

    const $pageObj = $('#page');
    $pageObj.empty();

    // 如果总页数<=1，可以选择隐藏分页（取消注释即可）
    if (allpage <= 1) {
        // return;  // 取消注释则单页不显示任何内容
        // 若仍想显示统计信息，可只保留统计行
        // $pageObj.append(`<span class="pagenums">${nums}条记录,${perpage}条每页,1/1页</span>`);
        // return;
    }

    // 辅助函数：构建链接 HTML
    function buildLink(page, text, className) {
        const isCur = (page === nowpage);
        const href = isCur ? 'javascript:;' : buildPageUrl(page);
        const cls = className ? ` class="${className}"` : '';
        return `<a href="${href}"${cls}>${text || page}</a>`;
    }

    // 首页 & 上一页
    $pageObj.append(buildLink(1, '首页', 'firstpage'));
    const prevPage = nowpage - 1;
    $pageObj.append(buildLink(prevPage > 0 ? prevPage : 1, '上一页', 'prepage'));

    // 上五页
    if (nowpage > 5) {
        $pageObj.append(buildLink(nowpage - 5, '上五页', 'pre5page'));
    }

    // 中间页码（显示5个）
    const visibleRange = 5;
    let startPage = Math.max(1, nowpage - Math.floor(visibleRange / 2));
    let endPage = Math.min(allpage, nowpage + Math.floor(visibleRange / 2));
    // 调整起始位置，确保始终显示5个（若总页数>=5）
    if (endPage - startPage + 1 < visibleRange && allpage >= visibleRange) {
        if (startPage === 1) endPage = Math.min(allpage, startPage + visibleRange - 1);
        else if (endPage === allpage) startPage = Math.max(1, endPage - visibleRange + 1);
    }

    // 左侧省略号
    if (startPage > 1) {
        $pageObj.append(buildLink(1, 1));
        if (startPage > 2) {
            $pageObj.append('<span class="ellipsis">...</span>');
        }
    }

    // 页码循环
    for (let i = startPage; i <= endPage; i++) {
        const isCur = (i === nowpage);
        const className = isCur ? 'cur' : '';
        $pageObj.append(buildLink(i, i, className));
    }

    // 右侧省略号
    if (endPage < allpage) {
        if (endPage < allpage - 1) {
            $pageObj.append('<span class="ellipsis">...</span>');
        }
        $pageObj.append(buildLink(allpage, allpage));
    }

    // 下五页
    if (nowpage < allpage - 4) {
        $pageObj.append(buildLink(nowpage + 5, '下五页', 'next5page'));
    }

    // 下一页 & 尾页
    const nextPage = nowpage + 1;
    $pageObj.append(buildLink(nextPage <= allpage ? nextPage : allpage, '下一页', 'nextpage'));
    $pageObj.append(buildLink(allpage, '尾页', 'lastpage'));

    // 统计信息
    $pageObj.append(`<span class="pagenums">${nums}条记录,${perpage}条每页,${nowpage}/${allpage}页</span>`);

    // 禁用样式控制（用更精确的选择器，或使用 addClass/removeClass）
    const $firstPrev = $('#page .firstpage, #page .prepage');
    const $lastNext = $('#page .lastpage, #page .nextpage');
    if (nowpage <= 1) {
        $firstPrev.addClass('pagedisabled');
    } else {
        $firstPrev.removeClass('pagedisabled');
    }
    if (nowpage >= allpage) {
        $lastNext.addClass('pagedisabled');
    } else {
        $lastNext.removeClass('pagedisabled');
    }
};

layers.prototype.msg=function(tit,arr){if($('#hstmsg').length==0) $('body').append('<div id="hstmsg" class="hstmsg default-icon"><i class="hst-ico"></i><span></span></div>');$('#hstmsg').show();$('#hstmsg span').html(tit);if(typeof(arr)=="undefined")arr={};if(typeof(arr.icon)=="undefined")arr.icon='';if(typeof(arr.time)=="undefined")arr.time=2000;$('#hstmsg i').attr('class','hst-ico');if(arr.icon!=''){$('#hstmsg i').addClass('hst-ico'+arr.icon)}if(arr.icon=='')$('#hstmsg i').hide();setTimeout(()=>{$('#hstmsg span').html('');$('#hstmsg').hide();if(typeof arr.success==='function'){arr.success()}},arr.time)};
layers.prototype.alert=function(tit,arr){if($('#hstalert-container').length==0)$('body').append('<div id="hstalert-container" class="hstmsg-box flex none"><div id="hstalert" class="hstalert"><div class="hst-title" style="cursor: move;" move="ok">友情提示</div><div class="hst-cont hst-padding"><i class="hst-ico"></i><span></span></div><span class="hst-setwin"><a class="hst-ico hst-close hst-close-alert" href="javascript:;"></a></span><div class="alert-btn tc"><a class="hst-btn hst-btn0 hst-btn alert-success">确定</a><a class="hst-btn hst-btn0 hst-btn alert-cancel">取消</a></div></div></div>');$('#hstalert-container').removeClass('none');if($('#masklayer').length==0)$('body').append('<div id="masklayer" class="masklayer"></div>');$('#masklayer').show();$('#hstalert .hst-cont span').html(tit);if(typeof(arr)=="undefined")arr={};if(typeof(arr.icon)=="undefined")arr.icon='';if(typeof(arr.time)=="undefined")arr.time=2000;if(typeof(arr.close)=="undefined")arr.close=false;if(typeof(arr.closeBtn)=="undefined")arr.closeBtn=false;if(typeof(arr.showCancel)=="undefined")arr.showCancel=false;if(typeof(arr.promp)=="undefined")arr.promp=false;$('#hstalert .hst-cont i').attr('class','hst-ico');if(arr.icon==''){$('#hstalert .hst-cont i').hide()}else{$('#hstalert .hst-cont i').addClass('hst-ico'+arr.icon);$('#hstalert .hst-cont i').show()}if(arr.promp==true){arr.showCancel==false;arr.close==false}if(arr.showCancel==false){$('#hstalert .alert-cancel').hide()}else{$('#hstalert .alert-cancel').show()}if(arr.closeBtn==true){$('#hstalert .hst-close-alert').hide()}else{$('#hstalert .hst-close-alert').show()}if(arr.promp==true){$('#hstalert .hst-cont span').html('<input type="price" class="hst-input w200" id="prompinput" />')}if(typeof arr.success==='function'){alert_success=arr.success}else{alert_success=undefined}if(arr.close===true){setTimeout(()=>{$('#hstalert .hst-cont span').html('');$('#hstalert').hide();if(typeof arr.success==='function'){arr.success()}},arr.time)}};
var paystatlist = [
{'id':0,'title':'未申请'},
{'id':1,'title':'审核中'},
{'id':3,'title':'通过审核'},
{'id':5,'title':'退回申请'},
{'id':9,'title':'拒绝申请'},
];
var editor;
var cusedittitle = '';
var code = '';
layers.prototype.open=function(arr){
	var nums = 0;
	$('.openbox_open').each(function(){
		var id = parseInt($(this).data('id'));
		if(id>nums){
			nums = id;
		}
	});
	nums += 1;
	var openhtml = '<div class="openbox_open" data-id="'+nums+'"><div class="openbox_content"><a href="javascript:;" class="openbox_close"><i class="iconfont icon-cuowu"></i></a><div class="openbox_container"><iframe class="openbox_iframe"></iframe></div></div></div>';
	var mask = '<div class="mask" data-id="'+nums+'"></div>';
	$('body').append(openhtml);
	$('body').append(mask);
	var objmask = $('.mask[data-id='+nums+']');
	var zindex = 9;
	zindex += nums;
	var obj = $('.openbox_open[data-id='+nums+']');
	var type = typeof(arr.type)=="undefined" ? 1:arr.type;	
	const modalContent = $('#openbox_content');
	if(type==2){	
		obj.find('.openbox_container').html('<iframe class="openbox_iframe"></iframe>');
		$('.openbox_open').css('style','all:unset');
		const externalIframe = obj.find('.openbox_iframe');
		externalIframe.attr('src',arr.content);
	}else{
		obj.find('.openbox_container').html(arr.content);
	}
	var area = typeof(arr.area)=="undefined" ? ['500px','80%']:arr.area;
	// 从area数组中获取宽度和高度
	obj.css('width',area[0]);
	obj.css('height',area[1]);
	obj.css('z-index',zindex);
	obj.show();
	objmask.show();
	objmask.css('z-index',zindex-1);
}
var layer = new layers();

var verkey = '',vermd5='';
var getverify=function(md,tid){$('#loading').show();$('#loading p').html('验证请求中');var isfish=false;$.ajax({url:Config.apipath+'index.php?s='+md+'&a=verify_json&t='+Math.random(),type:'GET',dataType:'json',async:false,timeout:6000,success:function(data){$('#loading').hide();if(data.status==1){isfish=true;$(tid).val(data.data)}},error:function(XMLHttpRequest,textStatus,errorThrown){$('#loading').hide()},complete:function(XMLHttpRequest,textStatus){this}});return isfish};
function htmlToText(html){	
	if(typeof(html) == "undefined") html = '';
	html += '';
    html = html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
	return html;
}
//通用字典
var dic = function(b) {
	if (typeof(b.tid) == "undefined") b.tid = 'select';
	if (typeof(b.value) == "undefined") b.value = '';
	if (typeof(b.type) == "undefined") b.type = 'numeric';
	if (typeof(b.name) == "undefined") b.name = '';
	if (typeof(b.iskh) == "undefined") b.iskh = 0;
	if (typeof(b.text) == "undefined") b.text = '';
	if (typeof(b.ishead) == "undefined") b.ishead = 1;
	if (typeof(b.id) == "undefined") b.id = '';
	if (typeof(b.classname) == "undefined") b.classname = '';
	if (typeof(b.isreturn) == "undefined") b.isreturn = 2;
	if (typeof(b.list) == "undefined") return '';
	if (typeof(b.list1) == "undefined") b.list1 = [];
	if (typeof(b.headstr) == "undefined") b.headstr = '';
	if (typeof(b.dataname) == "undefined") b.dataname = '';
	if (typeof(b.isempty) == "undefined") b.isempty = 0;
	if (typeof(b.iseval) == "undefined") b.iseval = '0';
	if (typeof(b.css) == "undefined") b.css = '';
	if (typeof(b.data) == "undefined") b.data = '';
	if (typeof(b.datafield) == "undefined") b.datafield = '';
	if (typeof(b.disabled) == "undefined") b.disabled = '';
	if (b.iseval == '1') b.list = eval(b.list);
	var te = '';
	switch (b.tid) {
	case 'select':
		if (b.classname == '') b.classname = '';
		if (b.ishead == 1) {
			te += '<select name="' + b.name;
			if (b.iskh == 1) {
				te += '[]'
			}
			te += '"';
			if (b.dataname != '') te += ' data-name="' + b.dataname + '"';
			if(b.id && b.id!='0' && b.id!='00') te += ' id="' + b.id + '"';
			te += ' class="' + b.classname + '" data-id="' + b.id + '">'
		}
		if (b.headstr != '') te += b.headstr;
		$(b.list).each(function(i, bb) {
			switch (b.type) {
			case 'numeric':
				if (i == 0 && bb == '') te += '';
				else te += '<option value="';
				if ((i == 0 && b.isempty == 0) || i > 0) {
					if (i == 0 && bb == '') te += '';
					else te += i + ''
				} else {
					te += ''
				}
				if (i == 0 && bb == '') te += '';
				else te += '"';
				if (i == 0 && bb == '') {
					te += ''
				} else {
					if (b.value == i && b.value != '') te += ' selected'
				}
				if (b.disabled == '1' && (bb.path == 1 || bb.path == 2)) te += ' disabled="disabled"';
				if (i == 0 && bb == '') te += '';
				else te += '>';
				break;
			case 'font':
				te += '<option value="';
				te += htmlToText(bb) + '"';
				if (b.value == bb && b.value != '') te += ' selected';
				if (b.disabled == '1' && (bb.path == 1 || bb.path == 2)) te += ' disabled="disabled"';
				te += '>';
				break;
			case 'font1':
				te += '<option value="';
				te += htmlToText(bb.id) + '"';
				if (b.value == bb.id && b.value != '') te += ' selected';
				if (b.disabled == '1' && (bb.path == 1 || bb.path == 2)) te += ' disabled="disabled"';
				te += '>';
				break;
			case 'font2':
				te += '<option value="';
				te += htmlToText(b.list1[i]) + '"';
				if (b.value == b.list1[i] && b.value != '') te += ' selected';
				if (b.disabled == '1' && (bb.path == 1 || bb.path == 2)) te += ' disabled="disabled"';
				te += '>';
				break
			}
			if (bb.path == 2) {
				te += '&nbsp;&nbsp;&nbsp;&nbsp;|-'
			}
			if (bb.path == 3) {
				te += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|-'
			}
			if (b.type == 'font1') te += htmlToText(bb.title);
			else te += bb;
			if (i == 0 && bb == '' && b.type == 'numeric') te += '';
			else te += '</option>'
		});
		if (b.ishead == 1) te += '</select>';
		break;
	case "radio":
		$(b.list).each(function(i, bb) {
			te += '<em class="radio"><label><input type="radio" value="';
			switch (b.type) {
			case 'numeric':
				te += i + '"';
				if (b.value == i) te += ' checked';
				break;
			case 'font':
				te += htmlToText(bb) + '"';
				if (b.value == bb) te += ' checked';
				break;
			case 'font1':
				te += htmlToText(bb.id) + '"';
				if (b.value == bb.id) te += ' checked';
				break
			}
			if (b.name != '') te += ' name="' + b.name + '"';
			switch (b.type) {
			case 'numeric':
				if (b.name != '') te += ' title="' + bb + '"';
				break;
			case 'font':
				if (b.name != '') te += ' title="' + htmlToText(bb) + '"';
				break;
			case 'font1':
				if (b.name != '') te += ' title="' + htmlToText(bb.title) + '"';
				break
			}
			if (b.classname != '') te += ' class="' + b.classname + '"';
			//if (b.id != '') te += ' id="' + b.id + i + '"';
			te += '>';
			if (b.type == 'font1') te += bb.title;
			else te += bb;
			te += '</label></em>'
		});
		break;
	case "checkbox":
		$(b.list).each(function(i, bb) {
			te += '<em class="checkbox"><label><input type="checkbox" value="';
			switch (b.type) {
			case 'numeric':
				te += i+ '"';
				if (ischeckbox(b.value, i) == true) te += ' checked="checked"';
				break;
			case 'font':
				te += htmlToText(bb) + '"';
				if (ischeckbox(b.value, bb) == true) te += ' checked="checked"';
				break;
			case 'font1':
				te += bb.id + '"';
				if (ischeckbox(b.value, bb.id) == true) te += ' checked="checked"';
				break
			}
			if (b.name != '') te += ' name="' + b.name + '[]"';
			if (b.classname != '') te += ' class="' + b.classname + '"';
			//if (b.id != '') te += ' id="' + b.id + i + '"';
			te += '>';
			if (b.type == 'font1') te += htmlToText(bb.title);
			else te += htmlToText(bb);
			te += '</label></em>'
		});
		break;
	case 'write':
		var subarr = [];
		$(b.list).each(function(i, bb) {
			switch (b.type) {
			case 'numeric':
				if (b.value == i && b.value != '') {
					if (b.css != '') te += '<em class="' + b.css + i + '">' + bb + '</em>';
					else te += bb
				}
				break;
			case 'font':
				if (b.value == bb && b.value != '') te += bb;
				break;
			case 'font1':
				var isyou = false;
				if (typeof(b.value) == "undefined") b.value = '';
				var value = b.value ? b.value: '';
				value = value + '';
				if (value.indexOf(',') != -1) {
					isyou = ischeckbox(value, bb.id)
				} else {
					if (value == bb.id) {
						te += bb.title
					}
				}
				if (isyou == true) {
					var subte = '';
					//subte += '';
					//if (b.css != '') subte += '' + b.css + bb.id;
					//if (b.classname != '') subte += ' ' + b.classname;
					subte += '';
					//if (b.data != '') subte += ' data-' + b.datafield + '="' + b.data + '"';
					//subte += '>';
					subte += bb.title + '';
					//subte += '';
					subarr.push(subte)
				}
				break
			}
		});
		if (subarr.length > 0) {
			te += subarr.join(',')
		}
		break
	}
	switch (b.isreturn) {
	case 1:
		return te;
	case 2:
		document.write(te);
		break
	}
};

var getlist = function(url, fun) {
	if (typeof(fun) == "undefined") fun = '';
	if(showAjaxIng==true) $('#loading').show();
	$.ajax({
		url: url,
		type: 'GET',
		dataType: 'json',
		timeout: 6000,
		async: true,
		success: function(data) {
			if (fun != '') {
				if (typeof(window[fun]) === "function") {
					var parameters = [JSON.stringify(data)];
					eval(fun + '(' + parameters + ')')
				}
			}
			$('#loading').hide();
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$('#loading').hide();
			layer.msg(networkerr, {
				icon: 2
			})
		},
		complete: function(XMLHttpRequest, textStatus) {
			$('#loading').hide();
			this
		}
	})
};
var update = function(url, dataform, fun, isconfirm, confirm) {
	if (typeof(isconfirm) == "undefined") isconfirm = 0;
	var confirm_tit = '提交保存';
	var confirm_cont = '您确定要提交吗!';
	if (typeof(confirm) != "undefined") {
		confirm_tit = confirm.title;
		confirm_cont = confirm.cont
	}
	if (isconfirm == 1) {
		$.ajax({
			url: url,
			type: 'POST',
			dataType: 'json',
			data: dataform,
			cache: false,
			timeout: 6000,
			success: function(data) {
				$('#loading').hide();
				if (data.status == 1) {
					layer.alert(confirm_cont, {
						showCancel: true,
						success: function() {
							if(showAjaxIng) $('#loading').show();
							$.ajax({
								url: url + '&ischeck=1',
								type: 'POST',
								dataType: 'json',
								data: dataform,
								timeout: 6000,
								success: function(data) {
									if (fun != '') {
										if (typeof(window[fun]) === "function") {
											var parameters = [JSON.stringify(data)];
											eval(fun + '(' + parameters.join(',') + ')')
										}
									}
									$('#loading').hide()
								},
								error: function(XMLHttpRequest, textStatus, errorThrown) {
									layer.msg(networkerr, {
										icon: 2
									});
									//$('#loading').hide()
								},
								complete: function(XMLHttpRequest, textStatus) {
									this;
									$('#loading').hide()
								}
							})
						}
					})
				} else {
					layer.alert(data.msg, {
						icon: 2
					})
				}
				$('#loading').hide()
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				layer.alert(networkerr, {
					icon: 2
				});
				$('#loading').hide()
			},
			complete: function(XMLHttpRequest, textStatus) {
				this;
				$('#loading').hide()
			}
		})
	} else {
		if(showAjaxIng) $('#loading').show();
		$.ajax({
			url: url,
			type: 'POST',
			dataType: 'json',
			data: dataform,
			timeout: 6000,
			success: function(data) {
				if (fun != '') {
					if (typeof(window[fun]) === "function") {
						var parameters = [JSON.stringify(data)];
						eval(fun + '(' + parameters.join(',') + ')')
					}
				}
				$('#loading').hide();
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				layer.msg(XMLHttpRequest.responseText, {
					icon: 2
				});
				$('#loading').hide();
			},
			complete: function(XMLHttpRequest, textStatus) {
				this;
				$('#loading').hide();
			}
		})
	}
};
var _searchs = function(obj) {
	var field = obj.data('field');
	var q = obj.find('.searchinputs').val();
	if (q == '') {
		layer.alert('请输入关键字搜索', {
			icon: 2
		});
		return
	}
	Config.map = gettihuan(Config.map, field, q);
	obj.find('.seartb').hide();
	obj.append('<a href="javascript:;" class="searched"><em>' + q + '</em><i class="iconfont icon-close"></i></a>');
	Config.curpage = 1;
	getlist(Config.url + '&' + Config.map, '_after_index')
},
_after_update_batch = function(data) {
	if (data.status == 1) {
		layer.msg(data.msg, {
			icon: 1
		})
	} else {
		layer.msg(data.msg, {
			icon: 1
		})
	}
},
setselect = function() {
	return ;
	$('select').each(function() {
		var name = $(this).attr('name');
		var value = $(this).val();
		if (!name) name = '';
		var title = '';
		var obj = $(this).find('option');
		$(obj).each(function(ii, bb) {
			if (value == $(this).val()) title = $(this).text()
		});
		var te = '<span class="select" data-name="' + name + '"><input type="text" placeholder="" value="' + title + '" class="select-input"><i class="layui-edge"></i><dl class="">';
		$(obj).each(function(ii, bb) {
			te += '<dd data-value="' + $(this).val() + '" class="';
			if (value == $(this).val()) {
				te += 'cur';
				$('select[name=\'' + name + '\']').val(value);
				title = $(this).text()
			}
			te += '">' + $(this).text() + '</dd>'
		});
		te += '</dl></span>';
		var h = $('select[data-name=' + name + '] dl').height();
		$(this).after(te)
	})
};
var upload_success=function(obj,b){
var json = obj.msg;
var filename = b.filename;
var filename1 = picpath+b.filename;
let width = b.width || 0;
let height = b.height || 0;
if(typeof(b.field)=="undefined") b.field='picurl';
$('#myform input[name='+b.field+']').val(filename);
$('#myform input[name='+b.field+'_oldname]').val(b.oldname);
$('#myform input[name=width]').val(b.width);
$('#myform input[name=height]').val(b.height);
obj.find('.imgbox').html('<img src="'+filename1+'_200.jpg" width="90"/>');
}
function uptoserver(file,obj,b){
    var reads = new FileReader();
	if(!file){
		layer.alert('您还未选择文件',{icon:2});
		return ;
	}
	if(typeof(b.field)=="undefined") b.field='picurl';
	if(typeof(b.path)=="undefined") b.path='pic1';
	if(typeof(b.zipwidth)=="undefined") b.zipwidth=0;
	if(typeof(b.iszip)=="undefined") b.iszip=0;
	if(typeof(b.thumb_width)=="undefined") b.thumb_width='';
	if(typeof(b.thumb_width)=="undefined") b.thumb_width='';
	var size = parseInt(file.size);
	var size1 = parseFloat(size/1024);
	if(size1>1024*2){
	    layer.alert('图片太大了，超过2M了',{icon:2});
	    return ;
	}
	var rep = /jpeg|jpg|png/ig;
	var gstyle = file.type.split("/")[1];		
	if(!rep.test(gstyle)){
		layer.alert("图片格式不正确，请上传 jpeg|png 格式的图片",{icon:2});
		return ;
	}		
	reads.readAsDataURL(file);
	reads.onload = function(e){				
		var fd = new FormData();				
		fd.append('calfun','thum');
		fd.append('path',b.path);
		fd.append('zipwidth',b.zipwidth);
		fd.append('iszip',b.iszip);
		if(b.thumb_width!='') fd.append('thumb_width',b.thumb_width);			
		fd.append('zipwidth',b.zipwidth);
		fd.append("fileToUpload",file);
		$('#loading p').html('图片上传中');
		$('#loading').show();
		$.ajax({
			url: uploadurl,
			type: 'POST',
			cache: false,
			data: fd,
			processData: false,
			contentType: false,
			dataType:"json",
			success : function(data){
				$('#loading').hide();
				if(data.status == 1){
					b.filename = data.filename;
					b.field = b.field;
					b.width = data.width;
					b.height = data.height;
					b.filesize = data.filesize;
					b.oldname = data.oldname;
					if (typeof(window['after_upload']) === "function") {
						after_upload(obj,b);
					}else{
						upload_success(obj,b);
					}
				}else{
					layer.alert(data.msg,{icon:2});
				}
			},error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#loading').hide();
				layer.alert('网络错误请检查网络');
			},complete: function(XMLHttpRequest, textStatus) {
				this;//调用本次AJAX请求时传递的options参数
			}
		});
	}
}

function upload_to_server_batch(file,fd,fun){
	$('#loading').show();
	$('#loading p').text('正在上传中');
	var reads = new FileReader();
	reads.readAsDataURL(file);
	reads.onload = function(e){		
		$.ajax({
			url: uploadurl,
			type: 'POST',
			cache: false,
			async:false,
			data: fd,
			processData: false,
			contentType: false,
			dataType:"json",
			success : function(data){
				$('#loading').hide();
				{if(typeof(window[fun])==="function"){
					var parameters=[JSON.stringify(data)];eval(fun+'('+parameters+')')}
					}
								
			},error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#loading').hide();
				layer.alert('网络错误请检查网络');
			},complete: function(XMLHttpRequest, textStatus) {
				this;//调用本次AJAX请求时传递的options参数
			}
		});	
	};
}

function upload_excel(file,fd,fun){
	$('#loading').show();
	$('#loading p').text('正在上传中');
	var reads = new FileReader();
	reads.readAsDataURL(file);
	reads.onload = function(e){		
		$.ajax({
			url: Config.adminurl+'excel_json',
			type: 'POST',
			cache: false,
			async:false,
			data: fd,
			processData: false,
			contentType: false,
			dataType:"json",
			success : function(data){
				$('#loading').hide();
				{if(typeof(window[fun])==="function"){
					var parameters=[JSON.stringify(data)];eval(fun+'('+parameters+')')}
					}
								
			},error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#loading').hide();
				layer.alert('网络错误请检查网络');
			},complete: function(XMLHttpRequest, textStatus) {
				this;//调用本次AJAX请求时传递的options参数
			}
		});	
	};
}
function _after_upload_news_insert(data){
	let status = data.status || 0;
	let msg = data.msg || '';
	if(status!=1){
		layer.msg(msg);
		return ;
	}
	let picurl = data.picurl || '';
	let oldname = data.oldname || '';
	editor.pasteHTML('<img src="'+descpath+picurl+'" alt="'+oldname+'" />');
}
function mybatchupload(){
	$('#mybatchupload').click();
}
function _after_vo(datas){
	let status = datas.status || 0;
	let msg = datas.msg || '';
	if(status!=1){
		layer.msg(msg,{'icon':2});
		return ;
	}
	let data= datas.data || {};
	if(typeof(window['edit_render_before']) === "function"){
		data = edit_render_before(datas);
	}
	let te = getmbhtml('vo',[data]);
	$('#tbedit').html(te);
	if(typeof(window['edit_render_after']) === "function"){
		edit_render_after(datas);
	}
}
function after_update(datas){
	let status = datas.status || 0;
	let msg = datas.msg || '';
	if(status!=1){
		layer.msg(msg,{'icon':2});
		return ;
	}
	layer.msg(msg,{'icon':1});
}
function downfilename(filename,oldfilename){


	window.open(Config.downloadurl+'&filename='+filename+'&oldfilename='+oldfilename, '_blank');


	
}

function getordid(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/^\d+-/, ''); // 移除开头的数字和短横线
}