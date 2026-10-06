//orders/index页面js
Config.module = 'Focus';Config.name = '焦点图';
Config.htmlbq = '#tblist';
function index_render_after(data){
	
	var $box = window.Kodo('#tblist').sortable({
        items: '.focus-card',
        placeholder: 'placeholder',
        threshold: 3,          // 想更灵敏可以改成 0
        cancel: 'input,select,option,textarea,button,a'
    });
	
}
$(document).ready(function(){	
	
});
