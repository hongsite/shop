function setskudefault(){	
    if (Array.isArray(skulist) && skulist.length > 0){
        skulist.forEach((item, index) =>{
            try {
                // 使用属性选择器和更具体的选择器提高性能
                const $skuRow = $('#sku-list tr[data-sku="'+item.title+'"]');                
                // 检查元素是否存在
                if ($skuRow.length) {
                    $skuRow.find('.sku-price').val(formatp(item.price));
                    $skuRow.find('.sku-stock').val(item.stock);
                }
            } catch (error) {
                
            }
        });
    } else {
        
    }
	if($('#sku-list-box tbody tr').length>0){
		$('#sku-list-box').show();
	}else{
		$('#sku-list-box').hide();
	}
}
function generateSkuList() {
    attributes = [];
    // 收集有效属性（保持原逻辑）
    $('.attr-group').each(function() {
        const $nameInput = $(this).find('.attr-name');
        const name = $nameInput.val().trim();
        const values = [];
        $(this).find('input.attrvalue').each(function() {
            const val = $(this).val().trim();
            if(val) values.push(val);
        });
        if(name && values.length > 0) {
            attributes.push({ name, values });
        }
    });

    if(attributes.length === 0) {
        $('#sku-list').empty();
        return;
    }

    // 笛卡尔积函数（保持不变）
    function cartesianProduct(arr) {
        return arr.reduce(function(a, b) {
            return a.reduce(function(r, x) {
                return r.concat(b.map(function(y) {
                    return x.concat([y]);
                }));
            }, []);
        }, [[]]);
    }

    const combinations = cartesianProduct(attributes.map(attr => attr.values));

    // 构建旧数据映射（用于匹配已有值）
    let oldSkuMap = {};
    if (window.skulist && Array.isArray(window.skulist)) {
        window.skulist.forEach(item => {
            oldSkuMap[item.title] = item;
        });
    }

    // 生成新的 SKU 数据数组（带上属性名）
    let newSkulist = combinations.map(combo => {
        // 将属性名和值组合成 "名称1:值1;名称2:值2" 的格式
        let parts = attributes.map((attr, idx) => {
            return attr.name + ':' + combo[idx];
        });
        let title = parts.join(';');   // 例如 "颜色:红色;尺码:均码"

        let old = oldSkuMap[title] || {};
        return {
            title: title,
            price: old.price !== undefined ? old.price : '',
            stock: old.stock !== undefined ? old.stock : '',
            image: old.image || ''
        };
    });

    // 使用模板渲染
    let skuhtml = getmbhtml('skulist', newSkulist);
    $('#sku-list').html(skuhtml);

    // 更新全局 skulist
    window.skulist = newSkulist;
}
// 修复getsku函数中的trim问题
function getsku(){
    const skuData = [];    
    $('#sku-list tr').each(function() {
        const $row = $(this);
        const price = parseFloat($row.find('.sku-price').val())*100;
        const stock = trim($row.find('.sku-stock').val());        
        skuData.push({
            title: trim($row.find('td:first').text()),
            price: price,
            stock: parseInt(stock),
            image: $row.find('.sku-image-preview').attr('src') || ''
        });
    });
    return skuData;
}
function getskutitle(){
	let skutitledata = [];
	let checkp = 0;
	$('.attr-group').each(function(ii,bb){
		checkp = 0;
		if(ii==0) checkp = 1;
		let title = trim($(this).find('.attr-name').val());
		let arr = $(this).find('.attrvalue');
		let lists = [];
		let check = 0;
		$(arr).each(function(i,b){
			check = 0;
			if(i==0) check = 1;
			lists.push({title:trim($(this).val()),check:check});
		});
		skutitledata.push({title:title,lists:lists,check:checkp});
	});
	return skutitledata;
}
$(document).ready(function(){
// 添加属性组（保持.live）
$('#add-attr').live('click',function() {
	const attrId = Date.now();
	const html = getmbhtml('addattr',[{}]);
	$('#attr-container').append(html);
});

// 添加属性值（保持.live）
$('.add-value').live('click', function() {
    const group = this.closest('.attr-group');
    const $group = $(group);
    const html = getmbhtml('addvalue', [{}]);
    $group.find('.attr-values').append(html);
    generateSkuList();
});

// 删除操作（保持.live）
$('.remove-attr').live('click',function() {
	if(confirm('确定删除整个属性？')) {
		this.closest('.attr-group').remove();
		generateSkuList();
	}
});

$('.remove-value').live('click', function(){
	$(this).parent().remove();
	generateSkuList();
});
$('.attr-value input[type=text]').live('blur', function(){
	generateSkuList();
});
$('.attr-name').live('blur', function(){
	generateSkuList();
});



$('#generate-sku').live('click',function(){
	generateSkuList();
});


// 图片预览（保持.live）
$('.sku-image').live('change', function(e) {
	const file = e.target.files[0];
	if (!file) return;
	
	// 简单图片验证
	if(!file.type.startsWith('image/')){
		layer.alert('请选择图片文件！');
		return;
	}
	
	const reader = new FileReader();
	reader.onload = function(e) {
		$(this).siblings('.sku-image-preview')
			  .attr('src', e.target.result);
	}.bind(this);
	reader.readAsDataURL(file);
});

});