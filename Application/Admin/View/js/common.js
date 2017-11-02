//后台JS公共函数库
// 创建时间 2015/06/06 15:57
// Author adcbguo

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