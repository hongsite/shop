//index/index页面js
Config.module = 'Catelist';Config.name = '分类图标';
Config.htmlbq = '#tblist';
var field = get('field');
function index_render_after(data){
	const $box = $('#tblist').sortable({
        items: ".category-card",
        placeholder:"placeholder",
        threshold:3
    });

    $box.on('sortstop',function(e,ui){
        console.log('拖拽完成');
        // 获取排序数组
        const idarr = [];
		const sortarr = [];
        $('#tblist .category-card').each(function(key,b){
            idarr.push($(this).attr('data-id'));
			sortarr.push(key);
        });
        console.log(idarr.join(','));
		console.log(sortarr.join(','));
		update(Config.adminurl+'sortable_json&field='+field,{id:idarr.join(','),sort:sortarr.join(',')},'');
    });
}
