function _after_logistics(data){
	let jsons = data.logisticsTraceDetailList || [];
	if(jsons.length<1){
		return '';
	}
	const json = Array.from(jsons || []).reverse();
	var te='<table class="result-info2">';
	var k=json.length-1;
	for(var i=0;i<json.length;i++){
		te += '<tr';
	    if(i==k){
			te +=' class="last"';
		}
		te +='><td class="row1">'+json[i]['timeDesc']+'</td><td class="status';
		if(i==0){
			te +=' status-first';
		}
		if(i==k){
			te +=' status-wait';
		}
		te +='"><td class="wl-stream-text">'+json[i]['desc']+'</td></tr>';
	}
	te +='</table>';
	return te;
}