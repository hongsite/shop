//主要函数
var isnumeric=function(value){
	return !isNaN(parseFloat(value)) && isFinite(value);
},
isint=function(num){
	return num % 1 === 0 && num > 0;
},
formatp = function(v, nums){
    // 新增：万单位处理
    if (nums === 'wan') {
        if(!isNumber(v)) return '0.00万';
        v = parseFloat(v / 1000000);
        return v.toFixed(2) + '万';
    }
    // 原有逻辑
    nums = typeof(nums) == "undefined" ? 2 : parseInt(nums);
    if(!isNumber(v)) return (0).toFixed(nums);
    v = parseFloat(v / 100);
    v = v.toFixed(nums);
    return v;
},
formatpm = function(v,chushu,nums){
    // 新增：万单位处理
    if (nums === 'wan') {
        if(!isNumber(v)) return '0.00万';
        v = parseFloat(v / 1000000);
        return v.toFixed(2) + '万';
    }
    // 原有逻辑
    nums = typeof(nums) == "undefined" ? 2 : parseInt(nums);
	chushu = typeof(chushu) == "undefined" ? 100 : parseInt(chushu);
    if(!isNumber(v)) return (0).toFixed(nums);

    v = parseFloat(v);
	if(chushu!=0) v = v/chushu;
    v = v.toFixed(nums);
    return v;
},
formatpno=function(v, nums){
	nums = typeof(nums) == "undefined" ? 2 : parseInt(nums);
	if(!isNumber(v)) return (0).toFixed(nums);
	v = parseFloat(v);
	if (nums === 0) return Math.round(v).toString();
	return v.toFixed(nums);
},getstr=function(str, bstr, estr) {
    const startIndex = str.indexOf(bstr);
    if (startIndex === -1) return '';

    const startOffset = startIndex + bstr.length;
    const endIndex = str.indexOf(estr, startOffset);
    if (endIndex === -1) return '';

    return str.substring(startOffset, endIndex);
},replaceAll=function(str,search,replace){return str.replace(new RegExp(search,'g'), replace);};
	var zeronum=function(num) {
	return num.toString().padStart(2, '0');
};

var getCookie=function(name){
	var strCookie=document.cookie;
	var arrCookie=strCookie.split("; ");
	for(var i=0;i<arrCookie.length;i++){
		var arr=arrCookie[i].split("=");
		if(arr[0]==name) return arr[1]
	}
	return""
},
setCook=function(name,v,exp){
	var cookiestr=name+"="+v+';path=/';if(typeof(exp)=="undefined")exp='';if(exp!='')cookiestr+=exp;document.cookie=cookiestr
},
findmaxi=function(obj){var i=1;$(obj).each(function(){var id=parseInt($(this).data('i'));if(id>=i){i=id+1}});return i},
tihua_para=function(str,field,q){
	str=str+'';
	var arr=new Array();
	arr=str.split('&');
	var abs=new Array();
	if(str==''){return field+'='+q}
	if(str.indexOf(field+'=')==-1){
		arr.push(field+'='+q);
		return arr.join('&')
	}
	for(var i=0;i<arr.length;i++){
		var info=arr[i].split('=');
		if(field==info[0]){
			abs.push(field+'='+q)
		}else{
			abs.push(arr[i]);
		}
	}
	return abs.join('&');
}
var dics=function(value,list,tid='write',name='',headstr='',type='font1',classname='',id='0'){
	return dic({"list":list,"tid":tid,"name":name,"type":type,"value":value,"headstr":headstr,"isreturn":1,classname:classname,id:id});
},
ischeckbox=function(str,i){
	str=','+str+',';if(str.indexOf(','+i+',')!=-1){return true}else{return false}
};
function safeFloat(val) {
    let v = parseFloat(val);
    return isNaN(v) ? 0 : v;
}
function safeInt(val) {
    let v = parseInt(val);
    return isNaN(v) ? 0 : v;
}
var in_array=function(arr,v){if(!arr) return false;for(var i=0;i<arr.length;i++){if(v==arr[i]) return true;}return false;};
var trim = function(str) {return (str+'').trim();};

var time=function(o){if(wait==0){o.html('发送验证码');o.removeClass('bgccc');wait=60}else{var z='';if(wait<10){z='0'+wait}else{z=''+wait}o.html('('+wait+')秒后重新发送');wait--;setTimeout(function(){time(o)},1000)}},get_imgcode_url=function(did){var str = $('#'+did).attr('src');var num=str.indexOf('&t=');var url = str.substring(0,num);return url+'&t='+Math.random();},nowt = parseInt(new Date().getTime()/1000),isfindarr=function(arr,v){var value = false;for(var i=0;i<arr.length;i++){if(arr[i].id===v) value = parseInt(arr[i].nums);}return value;},buzero=function(value){return value<10 ? '0'+value:value;},getsyqday=function(addtime){
	const now = (typeof nowt !== 'undefined') ? nowt : Math.floor(Date.now() / 1000);
  const diffSeconds = addtime - now;
  return Math.ceil(diffSeconds / 86400);
};

var thprice=function(price){
    return price.replace(/。/g, ".");
}
var calys=function(str,b){if(str.indexOf('+')!=-1){
	var arr=str.split('+');var str1=trim(arr[0]);var str2=trim(arr[1]);if(str2.indexOf('$')!=-1){str2=str2.replace('$','');str2=b[str2]?b[str2]:0}str1=b[str1]?b[str1]:0;return parseFloat(str1)+parseFloat(str2)}else if(str.indexOf('-')!=-1){var arr=str.split('-');var str1=trim(arr[0]);var str2=trim(arr[1]);if(str2.indexOf('$')!=-1){str2=str2.replace('$','');str2=b[str2]?b[str2]:0}str1=b[str1]?b[str1]:0;return parseFloat(str1)-parseFloat(str2)}else if(str.indexOf('*')!=-1){var arr=str.split('*');var str1=trim(arr[0]);var str2=trim(arr[1]);if(str2.indexOf('$')!=-1){str2=str2.replace('$','');str2=b[str2]?b[str2]:0}str1=b[str1]?b[str1]:0;return parseFloat(str1)*parseFloat(str2)}else if(str.indexOf('/')!=-1){var arr=str.split('/');var str1=trim(arr[0]);var str2=trim(arr[1]);if(str2.indexOf('$')!=-1){str2=str2.replace('$','');str2=b[str2]?b[str2]:0}str1=b[str1]?b[str1]:0;if(parseFloat(str2)==0)return 0;return parseFloat(str1)/parseFloat(str2)}};
function _after_logout(data){
	if(data.status!=1){
		lay.msg(data.msg, {
			icon: 2
		});
		return ;
	}
	location.href = Config.adminpath + 'login/'
}
function _after_admin_logout(data){
	if (data.status != 1) {
		layer.msg(data.msg, {
			icon: 2
		});
		return
	}
	layer.msg(data.msg,{time:1000,success:function(){
		location.href = Config.adminpath + 'login/';
	}});
	
}
function getFileExt(filename) {
    const index = filename.lastIndexOf('.');
    return index !== -1 ? filename.substring(index + 1) : '';
}
function settext(data) {
    // 添加参数检查
    if (!data || !settextfield) {
        console.error('settext: 缺少必要参数或参数类型不正确');
        return;
    }

    $(settextfield).each(function(i, b) {
        // 使用解构赋值提高可读性
        const {
            field = '',
            type = 'string',
            format = 'Y-m-d H:i',
            before = '',
            after = '',
            is100 = 0,
            xsd = 2
        } = b;
        
        let value = '';
        const fieldValue = data[field];
        
        try {
            switch (type) {
                case 'string':
                    value = fieldValue !== undefined && fieldValue !== null ? String(fieldValue) : '';
                    break;
                    
                case 'price':
                    var numValue = parseFloat(fieldValue) || 0;
                    const adjustedValue = parseInt(is100) > 0 ? numValue / parseInt(is100) : numValue;
                    value = formatpno(adjustedValue, parseInt(xsd));
                    break;
				 case 'wan':
                    var numValue = parseFloat(fieldValue) || 0;
                    value = formatpno(numValue/1000000,parseInt(xsd));
                    break;
                    
                case 'date':
                    var timestamp = parseInt(fieldValue) || 0;
                    value = timestamp > 0 ? date(timestamp, format) : '';
                    break;
                    
                default:
                    value = fieldValue !== undefined ? String(fieldValue) : '';
                    break;
            }
        } catch (error) {
            console.error(`settext: 处理字段 ${field} 时出错:`, error);
            value = '';
        }
        
        // 添加前后缀
        value = before + value + after;
        
        // 查找并设置元素内容
        if (field) {
            const $element = $('#' + field);
            if ($element.length > 0) {
                $element.html(value);
            } else {
                console.warn(`settext: 未找到ID为 ${field} 的元素`);
            }
        }
    });
}
var getpicurl = function(vv, trantype, tid, ext, nopic, mysid) {
  var subpath = 'pic1';
  if (vv == undefined) vv = '';
  if (vv == '""') vv = '';
  if (tid == undefined) tid = '';
  if (nopic == undefined) nopic = 'nopic.png';
  if (tid == 'desc') subpath = 'des';
  if (tid == 'face') subpath = 'face';
  if (typeof(mysid) == 'undefined') mysid = '';
  if (typeof(cusuploadurl) !== 'undefined') picpath1 = cusuploadurl;

  // 修正：用正则提取协议+host，兼容带协议/不带协议/相对路径
  const match = picpath.match(/^(https?:\/\/[^\/]+|\/\/[^\/]+)/i);
  var picpath1 = (match ? match[1] : '') + '/static/upload/';
  if (mysid != '') picpath1 += mysid + '/';
  picpath1 += subpath + '/';

  if (trantype == 1) {
    return picpath1 + 'vip.png';
  } else if (trantype == 2) {
    return picpath1 + 'charge.png';
  }

  if (ext == undefined) ext = '';
  if (ext != '') ext = '_' + ext + '.jpg';

  // 兼容以 /static/ 开头的本地路径
  if (vv != '' && vv.indexOf('/static/') === 0) {
    return vv;
  }

  vv = vv == '' ? picpath + nopic : picpath1 + vv + ext;
  return vv;
}
function get(name) {
    const search = window.location.search;
    const nameEQ = name + '=';
    const parts = search.substring(1).split('&');
    for (var i = 0; i < parts.length; i++) {
        var part = parts[i];
        while (part.charAt(0) === '?') part = part.substring(1);
        if (part.indexOf(nameEQ) === 0) return part.substring(nameEQ.length, part.length);
    }
    return '';
}

function ischinese(qqs){
    const hasChinese = /[\u4e00-\u9fa5]/.test(qqs);
    return hasChinese ? true : false;
}
function getMobileLast(phone) {
    // 移除非数字字符
    const digits = String(phone).replace(/\D/g, "");
    // 取后四位（若不足四位则返回全部）
    return digits.slice(-4);
}
function getUrlPath() {
    var pathname = window.location.pathname;
    // 找到最后一个斜杠的位置
    var lastIndex = pathname.lastIndexOf('/');
    // 如果最后一个斜杠是第一个字符（即根目录），则返回根目录
    if (lastIndex === 0) return '/';
    // 截取从开始到最后一个斜杠的部分
    return pathname.substring(0, lastIndex)+'/';
}
function getUrlFile() {
    var url = window.location.href;
    var index = url.indexOf('?');
    if (index === -1) {
        return url;
    }
    return url.substring(0, index);
}
function getFirstChar(str) {
    // 处理 null 或 undefined
    if (!str) return '';
    return [...str][0] || '';
}