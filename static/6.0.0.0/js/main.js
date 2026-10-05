//通用提示信息
var timestampb = Date.now();
var mbcont = '';
var mainth = '[abcdefghijk]';
var wait=60,veryfytext = '验证成功',veryfyerr = '验证失败，请重试',networkerr = '网络请求错误，可能系统繁忙',alert_success,sourcelist = [{'id':0,'title':'手动'},{'id':1,'title':'电脑'},{'id':3,'title':'手机'},{'id':9,'title':'小程序'}],mainlist = [{'id':0,'title':'否'},{'id':1,'title':'是'}],showhidelist = [{'id':0,'title':'隐藏'},{'id':1,'title':'显示'}],statlist = [{'id':0,'title':'<span class="waitPay">待支付</span>'},{'id':1,'title':'<span class="payed">已支付</span>'},{'id':2,'title':'<span class="waitSh">待收货</span>'},{'id':3,'title':'<span class="success">交易完成</span>'},{'id':4,'title':'<span class="cancel">交易撤消</span>'},{'id':8,'title':'<span class="refund">已退款</span>'},{'id':9,'title':'<span class="close">交易关闭</span>'}],paytypelist = [{'id':0,'title':'<span class="waitpay">未支付</span>'},{'id':1,'title':'<span class="paytype_weixin">微信</span>'},{'id':5,'title':'<span class="paytype_weixin">收款码</span>'},{'id':10,'title':'<span class="paytype_alipay">客户扫码</span>'},{'id':15,'title':'<span class="yue">余额</span>'},{'id':20,'title':'<span class="xianjin">现金</span>'},{'id':25,'title':'<span class="qita">其它</span>'},{'id':30,'title':'<span class="weixin">微信</span>'},{'id':35,'title':'<span class="alipay">支付宝</span>'},{'id':9,'title':'<span class="alipay">余额</span>'}],paytypelist1 = [{'id':5,'title':'收款码'},{'id':30,'title':'微信'},{'id':35,'title':'支付宝'},{'id':20,'title':'现金'},{'id':25,'title':'其它'},{'id':9,'title':'余额'},],paytypelist_off=[{'id':0,'title':'<span class="waitpay">未支付</span>'},{'id':1,'title':'<span class="paytype_weixin">微信</span>'},{'id':2,'title':'<span class="paytype_weixin">支付宝</span>'},{'id':3,'title':'<span class="">微信安卓</span>'}],shopinfo={},youhuiprice = 0,layers=function(){},isNumber = function(num){var exp = /^[+-]?\d*(\.\d*)?(e[+-]?\d+)?$/;return exp.test(num);};
var fbstatlist = [{'id':0,'title':'未发布'},{'id':1,'title':'已发布'}];
var qystatlist = [{'id':0,'title':'未启用'},{'id':1,'title':'已启用'}];
var logisticslist = [{'id':'WAIT_ACCEPT','title':'待揽收'},{'id':'ACCEPT','title':'已揽收'},{'id':'TRANSPORT','title':'运输中'},{'id':'DELIVERING','title':'派件中'},{'id':'AGENT_SIGN','title':'已代签收'},{'id':'SIGN','title':'已签收'},{'id':'FAILED','title':'包裹异常'}];
var sexlist = [{'id':'1','title':'先生'},{'id':'2','title':'女士'},{'id':'3','title':'保密'}];
var picpath = '/static/upload/pic1/';
var facepath = '/static/upload/face/';
var descpath = '/static/upload/des/';
var uploadurl = '/api/index.php?s=Upload&a=upload_json';
var downloadurl = '/down.php';
var sid = 0;
var iscookie = true;
var showAjaxIng = false;
var settextfield = [];
var catelist = [];
var Config = {editurl:'',cus_edit_class:'gf-grid gf',issearch:true,updateurl:'',url:'',adminurl:'',delurl:'',editid:'0',nums:0,perpage:20,allpage:0,nowpage:1,map:'',isloadlist:false,module:'',submodule:'',name:'',admin:'',apipath:'',isadd:false,adminpath:'/admins/',isalone:false,autowidth:false,removebq:'#tblist tr.mylist',htmlbq:'#tblist',editmordata:false,cus_edit_title:''};
var gethtmlmiddle = function(div){
	var strs = mbcont;
	var bstr = '<!--foreach($'+div+'){';
	var estr = '}-->';
	strs = getstr(strs, bstr, estr);
	strs = strs.replace(/\n/g, '');
	return strs;
}
function set_left_height(){
	var rh = parseInt($('#menu-right').height());
	var lh = parseInt($('#menu-left').height());
	var wh = parseInt($('html').height());
	console.log('rh=',rh);
	console.log('lh=',lh);
	if(lh<rh){
		$('#menu-left').height((rh+20)+'px');
	}	
}
function settype(){
	let type = 'date';
	tid = 0;
	let texts = $('#timeboxpre em').text();
	if(texts=='按月'){
		type = 'month';
		tid = 1;
	}
	$('#timebox span:first-of-type').html('<input type="text" id="btime" value="" />');
	laydate.render({
        elem: '#btime',
        type: type
    });
	$('#timebox span:last-of-type').html('<input type="text" id="etime" value="" />');
	laydate.render({
        elem: '#etime',
        type: type
    });
	if(texts=='按月'){
		$('#btime').val(date(nowt-86400*31,'Y-m'));
		$('#etime').val(date(nowt,'Y-m'));
	}else{
		$('#btime').val(date(nowt-86400*7,'Y-m-d'));
		$('#etime').val(date(nowt,'Y-m-d'));
	}
}
function cutSubStr(str,mylen){
    if (str === null || str === undefined || typeof str !== 'string') {
        return '';
    }
    var hasChinese = /[\u4e00-\u9fa5]/.test(str);
    if (hasChinese) {
        return str.substring(0, mylen);
    } else {
        return str.substring(0, mylen*2);
    }
}
function cutString(str, length = 10) {
  if (typeof str !== 'string') return '';
  return str.slice(0, length);
}
var mzlist=[{id:1,title:"汉族"},{id:2,title:"壮族"},{id:3,title:"回族"},{id:4,title:"满族"},{id:5,title:"维吾尔族"},{id:6,title:"苗族"},{id:7,title:"彝族"},{id:8,title:"土家族"},{id:9,title:"藏族"},{id:10,title:"蒙古族"},{id:11,title:"侗族"},{id:12,title:"布依族"},{id:13,title:"瑶族"},{id:14,title:"白族"},{id:15,title:"朝鲜族"},{id:16,title:"哈尼族"},{id:17,title:"黎族"},{id:18,title:"哈萨克族"},{id:19,title:"傣族"},{id:20,title:"畲族"},{id:21,title:"傈僳族"},{id:22,title:"东乡族"},{id:23,title:"仡佬族"},{id:24,title:"拉祜族"},{id:25,title:"佤族"},{id:26,title:"水族"},{id:27,title:"纳西族"},{id:28,title:"羌族"},{id:29,title:"土族"},{id:30,title:"仫佬族"},{id:31,title:"锡伯族"},{id:32,title:"柯尔克孜族"},{id:33,title:"景颇族"},{id:34,title:"达斡尔族"},{id:35,title:"撒拉族"},{id:36,title:"布朗族"},{id:37,title:"毛南族"},{id:38,title:"塔吉克族"},{id:39,title:"普米族"},{id:40,title:"阿昌族"},{id:41,title:"怒族"},{id:42,title:"鄂温克族"},{id:43,title:"京族"},{id:44,title:"基诺族"},{id:45,title:"德昂族"},{id:46,title:"保安族"},{id:47,title:"俄罗斯族"},{id:48,title:"裕固族"},{id:49,title:"乌孜别克族"},{id:50,title:"门巴族"},{id:51,title:"鄂伦春族"},{id:22,title:"独龙族"},{id:23,title:"赫哲族"},{id:54,title:"高山族"},{id:55,title:"珞巴族"},{id:56,title:"塔塔尔族"},{id:57,title:"未识别民族"},{id:58,title:"外国人"}],educationlist=[{id:1,title:"初中"},{id:3,title:"大专"},{id:5,title:"本科"},{id:7,title:"硕士"},{id:9,title:"博士"},{id:0,title:"其它"}];
var VAR_NAME_RE = '[a-zA-Z0-9_]+(?:\\.[a-zA-Z0-9_]+)*';
// ============ 新增：深层取值，支持 {$state.CVoltageSub} ============
function getDeepValue(obj, path) {
    if (obj === null || obj === undefined) return undefined;
    if (path === null || path === undefined) return undefined;
    path = String(path);
    if (path.indexOf('.') === -1) {
        return obj[path];
    }
    var keys = path.split('.');
    var cur = obj;
    for (var i = 0; i < keys.length; i++) {
        if (cur === null || cur === undefined) return undefined;
        cur = cur[keys[i]];
    }
    return cur;
}
function parsemb(str,b,i) {	
    // 修改正则表达式以匹配更复杂的条件语句（支持逻辑运算符 && 和 ||）
    const regex = /\{if\(([\s\S]*?)\)\}([\s\S]*?)\{\/if\}/g;

	/*const varRegex = /\{\$([a-zA-Z0-9_]+)\}/g;
    str = str.replace(varRegex, (match, varName) => {
		return b[varName] !== undefined && b[varName] !== null ? b[varName] : '';
	});*/

	const varRegex = new RegExp('\\{\\$(' + VAR_NAME_RE + ')\\}', 'g');
    str = str.replace(varRegex, (match, varName) => {
		var val = getDeepValue(b, varName);
		return val !== undefined && val !== null ? val : '';
	});

    // 辅助函数：从source中提取start和end之间的内容
    function getstr(source, start, end) {
        const startIdx = source.indexOf(start);
        const endIdx = source.indexOf(end, startIdx + start.length);
        if (startIdx === -1 || endIdx === -1) return '';
        return source.substring(startIdx + start.length, endIdx);
    }
    
    // 比较函数，根据操作符执行不同的比较
    function compareValues(operator, value1, value2) {
        switch (operator) {
            case '==': return value1 == value2;
            case '!=': return value1 != value2;
            case '>': return value1 > value2;
            case '>=': return value1 >= value2;
            case '<': return value1 < value2;
            case '<=': return value1 <= value2;
            default: return false;
        }
    }
    
    // 解析并评估单个条件（如 "$a==1"）
    function evaluateCondition(condStr, data) {
        // 正则匹配：$变量 操作符 值
        const condRegex = /^\s*\$([a-zA-Z0-9_]+)\s*(==|!=|>|>=|<|<=)\s*(.*?)\s*$/;
        const match = condStr.match(condRegex);
        if (!match) return false; // 无法解析的条件
        
        const field = match[1];
        const operator = match[2];
        let value = match[3];
        
        // 获取变量值
        const variableValue = data[field] !== undefined ? data[field] : '';
        
        // 解析比较值：可能是字符串、数字或变量
        let comparisonValue;
        if (value.startsWith('"') && value.endsWith('"') || 
            value.startsWith("'") && value.endsWith("'")) {
            // 字符串字面量
            comparisonValue = value.substring(1, value.length - 1);
        } else if (!isNaN(value)) {
            // 数字
            comparisonValue = parseFloat(value);
        } else if (value.startsWith('$')) {
            // 变量引用
            const key = value.substring(1);
            comparisonValue = data[key] !== undefined ? data[key] : '';
        } else {
            // 其他情况直接使用
            comparisonValue = value;
        }
        
        return compareValues(operator, variableValue, comparisonValue);
    }
    
    // 评估整个条件表达式（支持 && 和 ||）
    function evaluateExpression(expr, data) {
        // 拆分表达式为逻辑单元（条件和运算符）
        const tokens = expr.split(/(&&|\|\|)/).map(token => token.trim()).filter(Boolean);
        let result = null;
        let currentOperator = null;
        
        for (let token of tokens) {
            if (token === '&&' || token === '||') {
                currentOperator = token;
            } else {
                const conditionResult = evaluateCondition(token, data);
                if (result === null) {
                    result = conditionResult;
                } else if (currentOperator === '&&') {
                    result = result && conditionResult;
                } else if (currentOperator === '||') {
                    result = result || conditionResult;
                }
            }
        }
        
        return result === null ? false : result;
    }
    
    // 处理每个匹配到的条件块
    return str.replace(regex, (match, expr, content) => {
        // 评估整个条件表达式
        const conditionMet = evaluateExpression(expr, b);
        
        // 检查是否存在else分支
        const hasElse = content.includes('{else}');
        const ifContent = hasElse ? getstr('[aaabbbccc]' + content, '[aaabbbccc]', '{else}') : content;
        const elseContent = hasElse ? getstr(content + '[aaabbbccc]', '{else}', '[aaabbbccc]') : '';
        
        return conditionMet ? ifContent : (hasElse ? elseContent : '');
    });
}
function replaceVar(template,data,i) {
  return template.replace(/\{\$([a-zA-Z0-9_]+)\}/g, (match, key) => {
	  if (key === 'key'){
		return i + 1;
		} else if (data[key]===null){
		return '';
		} else {
		return data[key] !== undefined ? data[key] : match;
		}
  });
}
function safeCallFunction(params) {
    // 对参数进行安全处理，字符串类型添加引号
    const safeParams = params.map(param => {
        if (typeof param === 'string') {
            // 转义字符串中的引号
            return `'${param.replace(/'/g, "\\'")}'`;
        }
        return param;
    }).join(',');

    return safeParams;
}
const safeEval = function(fnName,args){
	const fn = window[fnName];
	return typeof fn === 'function' ? fn(...args) : '';
}
function processTemplate(str, arr, b, ii) {
    if (!arr) return '';	
    for (var i = 0; i < arr.length; i++) {
        var field = arr[i];
        var vv = field;
        field1 = field;

        var thzfstr = '[abcdefghijklmsn]';

        if (field.indexOf('|') > 0) {
            field = getstr(thzfstr + field, thzfstr, '|');
            field1 = field;
            const operators = ['+', '-', '*', '/'];
            const foundOperator = operators.find(op => field.includes(op));

            if (foundOperator !== undefined) {
                v = calys(field, b);
                const strToProcess = `${thzfstr}${field}`;
                field = trim(getstr(strToProcess, thzfstr, foundOperator));
            } else {
                v = (b[field] === undefined) ? '""' : b[field];
            }
            if (v == null) v = '';
            if (v == 'null') v = '';			

            // 提取参数配置部分
            const [fun, csBase] = [getstr(vv, '|', '=###'), getstr(vv + thzfstr, '=###', thzfstr)];
            let cs = csBase ? `###${csBase}` : '';

            if (cs.includes(',')) {
                // 处理多参数情况
                const paramStr = getstr(vv + thzfstr, '###,', thzfstr);
                const arr1 = paramStr.split(',').map(trim);

                // 构建替换字符串和参数数组
                const thstr = `{$${field1}|${fun}=###${arr1.map(p => `,${p}`).join('')}}`;
                const parameters = [v, ...arr1.map(p => {
                    p = trim(p);
                    
                    // 处理 $ 开头的变量 (新增逻辑)
                    if (p.startsWith('$') && /^\$[a-zA-Z_][a-zA-Z0-9_]*$/.test(p)) {
                        const key = p.substring(1); // 去掉开头的 $						
                        return b[key] !== undefined ? b[key] : '';
                    }
                    
                    // 处理引号包裹的字符串
                    if (/(^["']|["']$)/.test(p)) {
                        return p.slice(1, -1);
                    }
                    if (isNumber(p)) {
                        return p;
                    }
                    return window[p] || p;
                })];

                // 执行替换
                v = safeEval(fun, parameters);
                str = str.replace(thstr, v);
            } else {
                // 处理无参数情况
                const template = `{$${field1}|${fun}=###}`;
                v = safeEval(fun, [v]);
                str = str.replace(template, v);
            }
        } else {
            const operators = ['+', '-', '*', '/'];
            const placeholder = `{\$${field}}`;

            // 处理特殊字段和运算
            if (field === 'key') {				
                v = ii + 1;
            } else if (operators.some(op => field.includes(op))) {
                v = calys(field, b);
            } else {
                v = b[field] || '';
            }

            // 处理空值并替换字符串
			if (v == null) v = '';
            if (v == 'null') v = '';			
            str = str.replace(placeholder, v);
        }
    }
    return str;
}
// ============ 新增：深层取值，支持 {$state.CVoltageSub} ============
function getDeepValue(obj, path) {
    if (obj === null || obj === undefined) return undefined;
    if (path === null || path === undefined) return undefined;
    path = String(path);
    if (path.indexOf('.') === -1) {
        return obj[path];
    }
    var keys = path.split('.');
    var cur = obj;
    for (var i = 0; i < keys.length; i++) {
        if (cur === null || cur === undefined) return undefined;
        cur = cur[keys[i]];
    }
    return cur;
}
var getmbhtml = function (div, json, detailkey) {
    var strs = gethtmlmiddle(div);
    detailkey = detailkey || '';
    var subhtml = gethtmlmiddle(detailkey);

    function extractVars(html) {
        if (!html) return [];
        var re = /{\$([^}]+)}/g, m, arr = [];
        while ((m = re.exec(html))) arr.push(m[1]);
        return arr;
    }

    function replaceSimpleVars(html, b) {
        if (!html) return '';
        return html.replace(/\{\$([a-zA-Z0-9_]+)\}/g, function (m, key) {
            return (b[key] !== undefined && b[key] !== null) ? b[key] : '';
        });
    }

    var subarr = extractVars(subhtml);
    var te = '';

    if (json) {
        $(json).each(function (i, b) {
            b.key = i;
            if (!b.hasOwnProperty('i')) b.i = i + 1;            
            var tpl = replaceSimpleVars(strs, b);
            var subte = parsemb(tpl, b, i);
            subte = replaceVar(subte, b, i);
            var arr = extractVars(subte);
            subte = processTemplate(subte, arr, b, i);

            // 处理子项
            var detail = b.detail || [];
            if (detail.length > 0 && subhtml !== '' && detailkey!='') {
                var childHtml = '';
                $(detail).each(function (iii, bbb) {
                    bbb.key = iii;
                    if (!bbb.hasOwnProperty('i')) bbb.i = iii + 1;                    
                    var subTpl = replaceSimpleVars(subhtml, bbb);
                    var subsubte = parsemb(subTpl, bbb, iii);
                    subsubte = processTemplate(subsubte, subarr, bbb, iii);
                    childHtml += subsubte;
                });

                if (subte.indexOf(detailkey + '">') !== -1) {
                    subte = subte.replace(detailkey + '">', detailkey + '">' + childHtml);
                } else {
                    subte += childHtml;
                }
            }

            te += subte;
        });
    }
    return te;
};
function getUrl() {
    var str = window.location.href;
	return str;
}
function uploaddo(file,fd,fun){
	$('#loading').show();
	$('#loading p').text('正在上传中');
	var reads = new FileReader();
	let url = uploadurl;
	reads.readAsDataURL(file);
	reads.onload = function(e){		
		$.ajax({
			url: url,
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
// 初始化拖拽排序（在 DOM 加载完成后执行）
function livesort() {
    // 检查容器是否存在且需要启用排序（例如带有 .mysort 标识）
    if ($('#tblist.mysort').length == 0) return;
    
    // 对表格 tbody 应用 sortable
    $('#tblist tbody').sortable({
        revert: false,          // 拖拽结束时是否回滚动画
        opacity: 0.8,           // 克隆元素透明度（注意最终版本 clone 强制不透明，但占位符不受影响）
        placeholder: "hightlight", // 占位符 CSS 类名（需提前定义样式）
        connectWith: "tr.mylist",  // 可选：其他连接列表的选择器
        cursor: "move",         // 拖拽时鼠标样式
        cancel: "input,select,option,textarea", // 禁止拖拽的元素
        threshold: 5            // 鼠标移动超过 5px 才开始拖拽（防止点击误触发）
    }).bind('sortstop', function(event) {
        // 拖拽排序结束后的回调
        event.stopPropagation();   // 现在已支持，不会报错
        
        var new_order = [];
        var new_id = [];
        var k = 1;
        
        // 遍历排序后的所有行（.mylist 类）
        $('.mylist').each(function() {
            new_id.push($(this).data('id'));   // 假设每个 tr 有 data-id 属性
            new_order.push(k);
            k++;
        });
        
        var newid = new_id.join(',');
        var newsortid = new_order.join(',');
        
        // 构造保存排序结果的 URL
        let upurl = Config.adminurl + 'sortable_json&field=';
        if (Config.module == 'Classify') {
            upurl += get('field');   // 假设 get() 是取 URL 参数的函数
        }
        
        // 发送 AJAX 请求保存新顺序
        $.ajax({
            type: 'post',
            url: upurl,
            data: {
                'sort': newsortid,
                'id': newid
            },
            success: function(data) {
                // 可选：更新成功后的 UI 提示
                console.log('排序已保存', data);
            },
            error: function() {
                console.error('保存排序失败');
            }
        });
    });
}
// ============ 从当前 URL 提取参数对象 ============
function getParams() {
    var params = {};
    var url = window.location.href;           // 改为 var
    var queryString = url.split('?')[1];
    if (queryString) {
        queryString = queryString.split('#')[0];
        var pairs = queryString.split('&');
        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].split('=');
            var key = decodeURIComponent(pair[0] || '');
            var value = decodeURIComponent(pair[1] || '');
            if (key) {
                if (params.hasOwnProperty(key)) {
                    // 判断是否为数组，使用 instanceof（IE6+ 支持）
                    if (params[key] instanceof Array) {
                        params[key].push(value);
                    } else {
                        params[key] = [params[key], value];
                    }
                } else {
                    params[key] = value;
                }
            }
        }
    }
    return params;
}
function serializeParams(params) {
    var parts = [];
    for (var key in params) {
        if (params.hasOwnProperty(key)) {
            var value = params[key];
            // 处理 null/undefined，转为空字符串
            if (value === null || value === undefined) {
                value = '';
            }
            // 判断是否为数组（兼容 IE 使用 instanceof）
            if (value instanceof Array) {
                // 数组：拆成多个同名的 key=value
                for (var i = 0; i < value.length; i++) {
                    var item = value[i];
                    if (item === null || item === undefined) {
                        item = '';
                    }
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(item));
                }
            } else {
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            }
        }
    }
    return parts.join('&');
}
function selectedmenu(){
	if(Config.module=='Index') $('#menu-left li[data-module=Index]').addClass('cur');
	$('.submenu dl').each(function() {
		var $dl = $(this);
		var moduleStr = $dl.data('module') || '';
		var fieldAttr = $dl.data('field') || '';
		var valueAttr = $dl.data('value') || '';

		var currentModule = Config.module;
		var currentField = get('field');

		var shouldAdd = false;
		if (moduleStr === 'Classify') {
			if (fieldAttr && fieldAttr === currentField) {
				shouldAdd = true;
			}
		} 
		else {
			// 判断是否为多模块（逗号分隔）
			if (moduleStr.indexOf(',') === -1) {
				// 单一模块
				if (currentModule === moduleStr) {
					var fieldValue = fieldAttr ? get(fieldAttr) : ''; // 获取 data-field 对应的实际值
					// 原逻辑：当 getvalue 为空且 value 为空，或 getvalue 非空且相等时匹配
					if ((fieldValue === '' && valueAttr === '') || 
						(fieldValue !== '' && fieldValue === valueAttr)) {
						shouldAdd = true;
					}
				}
			} else {
				// 多模块（逗号分隔）
				var modules = moduleStr.split(',').map(function(s) { return s.trim(); });
				// 条件1：当前模块在列表中
				if (modules.indexOf(currentModule) !== -1) {
					shouldAdd = true;
				} 
				// 条件2：模块不匹配时，若字段名匹配当前 get('field') 也添加（原逻辑）
				else if (fieldAttr && fieldAttr === currentField) {
					shouldAdd = true;
				}
			}
		}

		if (shouldAdd) {
			$dl.addClass('cur');
		}
	});
}
function getsearchurl(){
	var q = $('#q').val();
	Config.nowpage = 1;
	var search = window.location.search;
	var baseUrl = Config.url;
	if (search) {
		search = search.startsWith('?') ? search.substring(1) : search;
		// 解析 baseUrl 中已有的查询参数（提取 ? 后面的部分）
		var baseQueryIndex = baseUrl.indexOf('?');
		var baseQuery = baseQueryIndex !== -1 ? baseUrl.substring(baseQueryIndex + 1) : '';
		var baseParams = {};
		if (baseQuery) {
			baseQuery.split('&').forEach(pair => {
				var [key, val] = pair.split('=');
				if (key) baseParams[key] = val || '';
			});
		}

		// 过滤 search：只保留 baseParams 中不存在的键
		var filteredPairs = [];
		search.split('&').forEach(pair => {
			var [key, val] = pair.split('=');
			if (key && !(key in baseParams)) {
				filteredPairs.push(pair);
			}
		});

		var filteredSearch = filteredPairs.join('&');
		if (filteredSearch) {
			var connector = baseQueryIndex !== -1 ? '&' : '?';
			var url = baseUrl + connector + filteredSearch;
		} else {
			var url = baseUrl;
		}
	} else {
		var url = baseUrl;
	}

	console.log('url=', url);
	let codes = $('#mytab a.cur').data('code');
	url = gettihuan(url, 'p', 1);
	url = gettihuan(url, 'code', codes);
	url = gettihuan(url, 'q', q);
	return url;
}
$(document).ready(function(){
	mbcont = $('body').html();
	mbcont = getstr(mbcont,'<!--moban-begin-->','<!--moban-end-->');
	if (typeof(cuspicpath)!== 'undefined') picpath = cuspicpath;
	$('.select-input').live('click', function(){
		var obj = $(this).parent();
		obj.addClass('selected');
    });
	$('.select dl dd').live('click',function(){
		var value = $(this).data('value');
		var title = $(this).text();
		var obj = $(this).parent().parent();	
		//if(value=='') title = '';
		obj.find('.select-input').val(title);		
		obj.removeClass('selected');
		var name = obj.data('name');
		$('select[name=\''+name+'\']').val(value);
		obj.find('dl dd').removeClass('cur');
		$(this).addClass('cur');
	});
	$('.select-input').live('blur', function() {
		var obj = $(this).parent();
        var css = obj.find('dl').css('display');		
		if(css!='none'){
			setTimeout(function (){
				obj.removeClass('selected');
			},300);			
		}
    });	
	
	$('.openboxs .hst-close1').live('click',function(){
		var colse = $(this).data('colse');
		if(typeof(colse)=="undefined") colse='1';
		var obj = $(this).parent().parent().parent();
		var len = $('.openboxs:not(.none)').length;
		obj.addClass('none');		
		if(colse=='1' && len<2) $('#mask').hide();
	
	});
	$('.mydelete').live('click',function(){	
		var id = $(this).data('id');		
		layer.alert('确定要删除吗', {showCancel:true,success:function(){				
			update(Config.delurl+'&ischeck=1',{id:id},'_after_delete');			
		}});
	});
	$('#logoutadmin').click(function (){
		layer.alert('确定要退出系统吗', {showCancel:true,success:function(){			
			getlist(Config.apipath+'index.php?s=Login&a=logout_json','_after_admin_logout');
		}});
	});
	$('#logout,#logout1').live('click',function (){
		layer.alert('确定要退出系统吗', {showCancel:true,success:function(){			
			getlist(Config.apipath+'index.php?s=Login&a=logout_json','_after_logout');
		}});
	});
	$('#logoutq').live('click',function (){
		layer.alert('确定要退出系统吗', {showCancel:true,success:function(){			
			getlist(Config.apipath+'index.php?s=Login&a=logout_json','_after_logout_q');
		}});
	});
	$('#batchbtn').click(function (){
		layer.alert('确定要批量修改吗', {showCancel:true,success:function(){			
			update(Config.adminurl+'batch_json',$('#myformbatch').serialize(),'_after_update_batch');
		}});
	});
	$('.searchbox').html('<a href="javascript:;" class="searchbuttons"><i class="iconfont icon-search"></i></a>');
	$('.searchbuttons').live('click',function(){
	    var obj = $(this).parent();
		if(obj.find('.seartb').length==0){
		    obj.append('<span class="seartb" style="display: inline;"><label><input type="text" class="searchinputs hst-input" placeholder="输入关键字查询" /></label><button type="button" class="searchbtns">搜索</button></span>');
		}else{
			var css = obj.find('.seartb').css('display');
			if(css=='none'){
				obj.find('.seartb').show();
			}else{
				obj.find('.seartb').hide();
			}
		}
	});
	$('.searchbtns').live('click',function(){
		var obj = $(this).parent().parent();
		_searchs(obj);
	});
	$('.searchbox .icon-close').live('click',function(){
		var obj = $(this).parent().parent();
		var field = obj.data('field');
		Config.map = gettihuan(Config.map,field,'');
		obj.find('.seartb').hide();
		obj.find('.searched').hide();
		obj.find('.searchbuttons').show();
		obj.find('.searchinputs').val('');
		Config.curpage = 1;
		getlist(Config.url+'&'+Config.map,'_after_index');
	});
	$('.searchinputs').live('keydown',function(event){
        var obj = $(this).parent().parent().parent();		
		if(event.keyCode == 13){
			_searchs(obj);
		}
    });	
	$('.hstalert .hst-close-alert').live('click',function(){
		var obj = $(this).parent().parent().parent();
		obj.addClass('none');
		$('#masklayer').hide();
	});
	$('.hstalert .alert-cancel').live('click',function(){
		var obj = $(this).parent().parent().parent();
		obj.addClass('none');
		$('#masklayer').hide();
	});
	$('.hstalert .alert-success').live('click',function(){
		var obj = $(this).parent().parent().parent();
		obj.addClass('none');
		$('#masklayer').hide();
		if(typeof alert_success ==='function') alert_success();
	});

	$('.openbox_close').live('click',function(){
		var obj = $(this).parent().parent();
		var id = obj.data('id');
		obj.remove();
		$('.mask[data-id='+id+']').remove();
	});

	

	
	$('input').not('[type=radio],[type=checkbox]').attr('autocomplete','off');
	//setselect();

	$('.upload_picurl').live('click',function(){
		var obj = $(this).parent();
		obj.find('.mypicurl').click();
	});
	$('.mypicurl').live('change',function(){
		var field = $(this).data('field');
		var zipwidth = $(this).data('zipwidth');
		var iszip = $(this).data('iszip');
		if(typeof(field) == "undefined") field = '';
		if(typeof(zipwidth) == "undefined") zipwidth = '0';
		if(typeof(iszip) == "undefined") iszip = '0';
		if(field=='') field = 'picurl';
		var obj = $(this).parent();
		var file = $(this).get(0).files[0];
		var arr = {'path':'pic1','field':field,'zipwidth':zipwidth,'iszip':iszip,'thumb_width':'90,200,500','calfun':'thum'}
		uptoserver(file,obj,arr);
	});
	$('.clearpicurl').live('click',function(){
		var obj = $(this).parent();
		var picurlfield = obj.data('field');
		layer.alert('确定要清除吗',{showCancel:true,success:function(index){			
			$('#myform input[name='+picurlfield+']').val('');
			obj.find('.imgbox img').attr('src',picpath+'nopic.png');
		}});
	});
	$('.hoverhide').live('hover',function(){
	});

	if(Config.issearch!==false){
		$('#search').click(function(){
			var url = getsearchurl();
			getlist(url,'_after_index');
		});
		$('#q').live('keypress',function(e) {
			if (e.key === 'Enter' || e.keyCode === 13) {
				var url = getsearchurl();
				getlist(url+'&'+Config.map,'_after_index');
				e.preventDefault();
			}
		});
	}

	$('#mybatchupload').live('change',function(){	

		var fileInput = $('#mybatchupload').get(0);
		var files = fileInput.files;	

		// 遍历所有文件进行上传
		for (var i = 0; i < files.length; i++) {
			var file = files[i];
			var rep = /jpeg|png|gif|bmp/ig;
			var gstyle = file.type.split('/')[1];
			if(!rep.test(gstyle)){
				layer.alert('图片格式不正确，请上传jpeg|png|gif|bmp格式的图片');
				return ;
			}
			if(file.size>102400*20){
				layer.alert( '不能上传大于2M的图片',{icon:2});
				return ;
			}
		}		
		
		for (var i = 0; i < files.length; i++) {
			var file = files[i];

			let fd = new FormData();
			fd.append('fileToUpload',file);	
			fd.append('path','des');
			fd.append('thumb_width','90,200,310,500');

			upload_to_server_batch(file,fd,'_after_upload_news_insert');
		}		
		
	});	

	selectedmenu();
	
	if($('#mask').length==0) $('body').append('<div id="mask" class="mask"></div>');
	if($('#mask1').length==0) $('body').append('<div id="mask1" class="mask1"></div>');

	livesort();

	



});