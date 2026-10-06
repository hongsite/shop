//index/index页面js
Config.module = 'Cate';Config.name = '分类';
Config.htmlbq = '#tblist';
var field = get('field');
function index_render_after(data){
	
	var $box = window.Kodo('#tblist').sortable({
        items: '.cate-card',
        placeholder: 'placeholder',
        threshold: 3,          // 想更灵敏可以改成 0
        cancel: 'input,select,option,textarea,button,a'
    });
	
}
