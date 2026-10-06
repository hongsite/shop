//orders/index页面js
Config.module = 'Pro';
Config.name = '商品';
Config.isalone = true;
var skulist = [];
var sid = '0';
var catelist = [];
let objdelxqpic;
let mainpicarr = [{picurl:'',width:0,height:0},{picurl:'',width:0,height:0},{picurl:'',width:0,height:0},{picurl:'',width:0,height:0},{picurl:'',width:0,height:0}];
Config.editurl += '&tid='+get('tid');
function _after_upload_dec(data){
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
	getxqhtml(b.filename,0,b.width,b.height);
}
function getxqhtml(filename,id,width,height){
    var i = findmaxi('#xqhtml img');
	var str = '<span class="classlist">';
	str += '<input type="hidden" value="'+i+'" name="xqpic[]" />';
	str += '<input type="hidden" value="'+filename+'" name="xqpicurl_'+i+'" />';
	str += '<input type="hidden" value="'+width+'" name="width_'+i+'" />';
	str += '<input type="hidden" value="'+height+'" name="height_'+i+'" />';
	str += '<input type="hidden" value="'+id+'" name="xqpicfield_'+i+'" />';
	str += '<img src="'+descpath+filename+'_500.jpg" data-i="'+i+'" data-id="'+id+'" class="xqimg" /><a href="javascript:;" class="delxqpic" data-id="'+id+'"><svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6L18 18"></path></svg></a></span>';
	$('#xqhtml').append(str);
}
function descupload(did){

	var fileInput = $('#'+did).get(0);
	var files = fileInput.files;

	const maxFiles = 5;    
    

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
		fd.append('path','des');
		fd.append('thumb_width','90,200,310,500');

		upload_to_server_batch(file,fd,'_after_upload_dec');
	}

	//
	
}
function edit_render_before(data){
	catelist = data.catelist || [];
	return data;
}
function edit_render_after(data){
	
	//editor=$('#editor').xheditor({tools:'full',skin:'default'});
	//editor.focus();
	//if(maintid==1) $('.lxbox').show() else $('.lxbox').hide();
	let rt = data.data || {};
	mainpicarr[0].picurl = rt.picurl_0 || '';
	mainpicarr[0].width = rt.w_0 || 0;
	mainpicarr[0].height = rt.h_0 || 0;
	
	mainpicarr[1].picurl = rt.picurl_1 || '';
	mainpicarr[1].width = rt.w_1 || 0;
	mainpicarr[1].height = rt.h_1 || 0;

	mainpicarr[2].picurl = rt.picurl_2 || '';
	mainpicarr[2].width = rt.w_2 || 0;
	mainpicarr[2].height = rt.h_2 || 0;

	mainpicarr[3].picurl = rt.picurl_3 || '';
	mainpicarr[3].width = rt.w_3 || 0;
	mainpicarr[3].height = rt.h_3 || 0;

	mainpicarr[4].picurl = rt.picurl_4 || '';
	mainpicarr[4].width = rt.w_4 || 0;
	mainpicarr[4].height = rt.h_4 || 0;

	/*mainpicarr[5].picurl = rt.picurl_5 || '';
	mainpicarr[5].width = rt.w_5 || 0;
	mainpicarr[5].height = rt.h_5 || 0;

	mainpicarr[6].picurl = rt.picurl_6 || '';
	mainpicarr[6].width = rt.w_6 || 0;
	mainpicarr[6].height = rt.h_6 || 0;*/





	let tezt = getmbhtml('mainpic',mainpicarr);
    $('#mainpic').html('<div class="upimg"><ul class="clearfix" id="mainpicul">'+tezt+'<a href="javascript:;" class="fr hst-btn hst-btn-ml mainpicupbtn" style="margin-top:50px;">上传图片</a></ul></div>');
	$("#mainpic ul").sortable({
		placeholder: "ui-sortable-placeholder",
		helper: "clone",
		opacity: 0.7,
		cursor: "move",
		update: function(event, ui) {           // 排序完成回调
			
		}
	});
	

	let id = rt.id || 0;

    let pertylist = data.pertylist || [];
	// 从 skulist 解析属性组定义
	window.skulist = data.skulist || [];
	let parsedAttrs = [];
	if (skulist.length > 0) {
		// 解析所有 SKU title，提取属性名和值
		let attrMap = {}; // key: 属性名, value: Set of values
		window.skulist.forEach(item => {
			let title = item.title || '';
			// 按分号分割每个属性对
			let pairs = title.split(';');
			pairs.forEach(pair => {
				let parts = pair.split(':');
				if (parts.length === 2) {
					let name = parts[0].trim();
					let value = parts[1].trim();
					if (name && value) {
						if (!attrMap[name]) attrMap[name] = new Set();
						attrMap[name].add(value);
					}
				}
			});
		});
		// 转换为数组格式，保持顺序（按第一次出现的顺序）
		// 由于遍历 skulist 的顺序，我们可以按照首次出现的属性顺序
		let orderNames = [];
		let nameSet = new Set();
		window.skulist.forEach(item => {
			let title = item.title || '';
			let pairs = title.split(';');
			pairs.forEach(pair => {
				let parts = pair.split(':');
				if (parts.length === 2) {
					let name = parts[0].trim();
					if (!nameSet.has(name)) {
						nameSet.add(name);
						orderNames.push(name);
					}
				}
			});
		});
		// 构建 parsedAttrs
		parsedAttrs = orderNames.map(name => {
			let values = Array.from(attrMap[name] || []);
			// 每个值对象: {title: 值, check: 0或1}，第一个值设为check:1
			let lists = values.map((v, idx) => ({
				title: v,
				check: idx === 0 ? 1 : 0
			}));
			return {
				title: name,
				lists: lists,
				check: 1 // 属性组默认选中? 原逻辑中每个属性组有一个check字段，但似乎未使用，可设为1
			};
		});
	}

	// 清空属性容器并渲染
	$('#attr-container').empty();
	let strs = gethtmlmiddle('addattr');
	if (parsedAttrs.length > 0) {
		parsedAttrs.forEach(attr => {
			let te = strs.replace('{$title}', attr.title);
			$('#attr-container').append(te);
			let obj = $('.attr-group:last');
			let sub_te = getmbhtml('addvalue', attr.lists);
			obj.find('.attr-values').append(sub_te);
		});
	} else {		
	}
    
	
		
	generateSkuList();
	setskudefault();

	let pertyhtml = getmbhtml('pertylist',pertylist);

	$('#pertyGrid').html(pertyhtml);

	var $box = window.Kodo('#mainpicul').sortable({
		items: '.lilist',
		placeholder: 'placeholder',
		threshold: 3,
		animation: 180,       // 动画时长，0 关闭
		easing: 'cubic-bezier(0.2, 0, 0, 1)'
	});

	/*$box.on('sortstart', function (e, ui) { console.log('start'); });
	$box.on('sort',      function (e, ui) { console.log('sort'); });
	$box.on('sortstop',  function (e, ui) { console.log('stop'); });*/

	getlist(Config.adminurl+'loadxqpic_json&id='+id,'_after_loadxqpic');

	sid = rt.sid || 0;

	set_left_height();
}
function _after_loadxqpic(data){
	if(data.status!=1) return ;
    var list = data.data || [];
	$('#xqhtml').html('');
	$(list).each(function(i,b){
		getxqhtml(b.picurl,b.id,b.width,b.height);
	});	

	$("#xqhtml").sortable({
		placeholder: "ui-sortable-placeholder",
		helper: "clone",
		opacity: 0.7,
		cursor: "move",
		connectWith:".classlist",
		update: function(event, ui){
			
		}
	});
}
function _before_update(){

	let istrue = '';

	$('#attr-container .attr-group').each(function(){
		let sxname = $(this).find('.attr-name').val();
		if(sxname==''){
			istrue = '请输入属性名称';
		}
		if($(this).find('.attr-value').length==0){
			istrue = '请添加属性名为【'+sxname+'】的下级值';
		}
		$(this).find('.attrvalue').each(function(){
			let va = $(this).val();
			if(va==''){
				istrue = '请填写属性【'+sxname+'】的值名称';
			}
		});
	});

	let minPrice = Infinity;
	let priceValid = true;

	$('.sku-price').each(function(){
		let raw = $(this).val().trim();
		let obj = $(this).parent().parent();
		let price = parseFloat(raw);
		let sku = obj.data('sku');
		if (isNaN(price)) {
			istrue = '请填写'+sku+'的商品价格';
			priceValid = false;
			return false;
		}
		if (price < 0.01) {
			istrue = '商品'+sku+'价格不能为负数';
			priceValid = false;
			return false;
		}
		if (price > 100000000) {
			istrue = '商品'+sku+'价格超出上限';
			priceValid = false;
			return false;
		}
		if (price < minPrice) {
			minPrice = price;
		}
	});

	if (!priceValid) {
		return istrue;
	}
	let allstock = 0;
	$('.sku-stock').each(function(){
		let value = $(this).val();
		if(!isNumber(value)){
			istrue = '请填写商品'+sku+'库存';
		}
		allstock += parseInt(value);
		
	});

	//if($('.sku-stock').length>0){
		//$('#myform input[name=stock]').val(allstock);
		//$('#myform input[name=price]').val(minPrice);		
	//}	

	if(istrue!=''){
		return istrue;
	}else{
		return true;
	}

}

function _after_delxqpic(data){
	if(data.status!=1){
		layer.alert(data.msg,{icon:2});
		return ;
	}
	layer.msg(data.msg);
	objdelxqpic.remove();	
}
function _after_updates(data){
	console.log('_after_updates_data=',data);
	if(data.status!=1){
		layer.alert(data.msg);
		return ;
	}
	let isadd = data.isadd || 0;
	let id = data.data || 0;
	if(isadd>0){
		window.location.href = '/pro/edit.html?id='+id;
		return ;
	}
	layer.msg(data.msg,{icon:1});
	getlist(Config.adminurl+'loadxqpic_json&id='+Config.editid,'_after_loadxqpic');
}

$(document).ready(function(){
    let attributes = [];
	$('#mainpic .myclose').live('click',function(){
		let obj = $(this).parent();
		obj.find('img').attr('src',picpath+'nopic.png');
		obj.find('input.picurl').val('');
	});

	$('#filedesc').change(function(){
		//layer.msg('上传');
		descupload('filedesc');
	});
	$('#updesc').live('click',function(){
		$('#filedesc').click();
	});
	$('.delxqpic').live('click',function(){
		let id = $(this).data('id');
		objdelxqpic = $(this).parent();
		layer.alert('确认删除吗', {showCancel:true,success:function(){
			if(id==0){
				objdelxqpic.remove();
				return ;
			}
			update(Config.adminurl+'xqpicdel_json',{id:id},'_after_delxqpic');			
		}});
	});

	$('#mytab a').click(function(){
		let index = $(this).index();
		$('#mytab a').removeClass('cur');
		$(this).addClass('cur');
		$('.proeditbox').hide();
		$('.proeditbox:eq('+index+')').show();
	});

	$('#add-perty').live('click',function(){
		let pertylist = [{name:'',val:''}];
		let pertyhtml = getmbhtml('pertylist',pertylist);
		$('#pertyGrid').append(pertyhtml);
	});
	$('.perty-delete').live('click',function(){
		let obj = $(this).parent();
		layer.alert('确认删除吗', {showCancel:true,success:function(){
			obj.remove();		
		}});

	});

	

	

	//layer.alert('测试下');
	
});
