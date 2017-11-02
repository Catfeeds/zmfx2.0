function accAdd(arg1,arg2){
	var r1,r2,m;
	try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}
	try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}
	m=Math.pow(10,Math.max(r1,r2))
	return (arg1*m+arg2*m)/m
}

var func_select_all = function (name,obj){
	if($(obj).prop('checked')) {
		$('input[name="' + name + '"]').prop('checked',true);
	}else{
		$('input[name="' + name + '"]').prop('checked',false);
	}
}

//解决数字相加出现多个小数的问题
function toDecimal(x) {    
	var val = Number(x)   
	if(!isNaN(parseFloat(val))) {val = val.toFixed(2);}    
	return  val;     
}
//两个数字计算百分比
function Percentage(num, total) { 
    return (Math.round(num / total * 10000) / 100.00 + "%");
}
function openWindows(href,iWidth,iHeight) {
	var url; //转向网页的地址;
	var name; //网页名称，可为空;
	var iWidth; //弹出窗口的宽度;
	var iHeight; //弹出窗口的高度;
//window.screen.height获得屏幕的高，window.screen.width获得屏幕的宽
	var iTop = (window.screen.height-30-iHeight)/2; //获得窗口的垂直位置;
	var iLeft = (window.screen.width-10-iWidth)/2; //获得窗口的水平位置;
	window.open(href,name,'height='+iHeight+',,innerHeight='+iHeight+',width='+iWidth+',innerWidth='+iWidth+',top='+iTop+',left='+iLeft+',toolbar=no,menubar=no,scrollbars=auto,resizeable=no,location=no,status=no');
}