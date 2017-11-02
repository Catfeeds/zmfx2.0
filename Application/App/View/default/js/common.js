/**
$(function(){
	var u = navigator.userAgent;
	var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
	var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
	//alert('是否是Android：'+isAndroid);
	//alert('是否是iOS：'+isiOS);
	if(isiOS == true){
		$(".header").css("margin-top","20px");
	}
});
**/
function check_num(str){
	return str.match(/\D/) == null;
}
var check_email = function(str){
	var myreg = /[A-Za-z0-9_-]+[@](\S*)(net|com|cn|org|cc|tv|[0-9]{1,3})(\S*)/g; 
	if(!myreg.test(str)){
		return false;
	}
	return true;
};
function check_mobilephone(str){
	var myreg = /^(((13[0-9]{1})|(14[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
	if(!myreg.test(str)) 
	{ 
		return false; 
	} 
	return true;
}
var check_telephone = function(str){
	var myreg = /^(([0\+]\d{2,3}-)?(0\d{2,3})-)(\d{7,8})(-(\d{3,}))?$/; 
	if(!myreg.test(str)) 
	{ 
		return false; 
	} 
	return true;

};

var getLength = function (str) {
	///<summary>获得字符串实际长度，中文2，英文1</summary>
	///<param name="str">要获得长度的字符串</param>
	var realLength = 0, len = str.length, charCode = -1;
	for (var i = 0; i < len; i++) {
		charCode = str.charCodeAt(i);
		if (charCode >= 0 && charCode <= 128) realLength += 1;
		else realLength += 2;
	}
	return realLength;
};