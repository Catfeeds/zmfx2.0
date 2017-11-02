/***模拟百度贴吧的分页**/
var page = {
	data:{},             //运行时的中间数据
	options:{
		PageID:1,        //当前第几页
		TotalNum:0,      //数据总数
		PageSize:50,     //每页的行数
		//下面是常用的静态设置
		PageSum:1,       //总页数
		DisplayMark:10,  //页面数字最多显示多少列
		Prev:'上一个',
		Next:'下一个',
		First:'首页',
		Last:'尾页',
		//下面的设置用于post提交的场景
		Url:'',          //地址
		Format:'',
		PageArgsStr:'page_id',  //href连接的分页name，href会增加?p=
		PageSizeArgsStr:'page_size',//页面数据的pagesize参数
		SetPageSize:true,
	},
	create:function(options){
		for(var key in this.options){
			this.options[key] = options[key] || this.options[key];
		}
		options = this.options;
		var arr = new Array();
		if(options.TotalNum < 1 ){
			arr[1] = 1;
		}
		options.PageSum = Math.ceil(options.TotalNum/options.PageSize); //总页数
		if(parseInt(options.PageSize) >= parseInt(options.TotalNum) ){
			if (parseInt(options.PageSum) < parseInt(options.PageID) ){ //无数据
				arr = null;
			}else{
				arr[1] = 1;
			}
		}else{
			if (parseInt(options.PageSum) < parseInt(options.PageID) ){ //无数据
				arr = null;
			}else {
				if ( parseInt(options.PageSum) > parseInt(options.DisplayMark) ){
					if (parseInt(options.PageID) <= Math.ceil(options.DisplayMark/2)){
						var begin = 1;
						var total = options.DisplayMark;
					} else if (parseInt(options.PageID)>Math.ceil(options.DisplayMark/2) && options.PageID < options.PageSum - Math.floor(options.DisplayMark/2)){
						var begin = options.PageID - Math.ceil(options.DisplayMark/2)+1;
						var total = options.DisplayMark;
					} else if (parseInt(options.PageID) >= options.PageSum - Math.floor(options.DisplayMark/2)) {
						var begin = options.PageSum - options.DisplayMark+1;
						var total = options.DisplayMark;
					}
				}else {
					var begin = 1;
					var total = options.PageSum;
				}
				for( var i = 0;i < total; i++ ){
					arr[begin+i] = begin+i;
				}
			}
		}
		this.data = arr;
		return this;
	},
	getHtml:function(){
		var _PageArr = this.data;
		var html     = '<nav>';
		if(_PageArr){
			html +=  '<ul class="pagination">';
			html +=  '<strong class="pagination pull-left" style="margin-top: 8px; margin-right: 5px;">'+this.options.TotalNum+'条记录 '+this.options.PageID+'/'+this.options.PageSum+'页</strong>';
			html += (this.options.PageID==1?'':'<li><a data-page-id="1" data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.First+'</a></li><li><a data-page-id="'+(Number(this.options.PageID)-1)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Prev+'</a></li>');
			for(var value in _PageArr){
				if ( value == this.options.PageID){
					html += '<li class="active"><span>'+value+'</span></li>';
				}else {
					html += '<li><a data-page-id="'+value+'" data-page-size = "'+this.options.PageSize+'"  href="javascript:void(0);">'+value+'</a></li>';
				}
			}
			html += (this.options.PageID==this.options.PageSum?'':('<li><a data-page-id='+(Number(this.options.PageID)+1)+' data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.Next+'</a></li><li><a data-page-id="'+(this.options.PageSum)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Last+'</a></li>'));
			html += '</ul>';
		}else{
			html += '';
		}
		html     += '</nav>';
		return html;
	},
}

$(function(){

	/* 分类选择 */
	$(".delete").click(function(){
		$(this).parents("a").remove();
	});

	/* help */
	$(".sidebar-list h6").click(function(){
		var H6 = $(this);
		$(this).next("ul").slideToggle(function(){
			if(H6.find("img").attr("data") == "true"){
				H6.find("img").attr("data","false");
				H6.find("img").attr("src", "/Application/Home/View/b2c_new/Styles/Img/help_10.png");
			}else{
				H6.find("img").attr("data","true");
				H6.find("img").attr("src", "/Application/Home/View/b2c_new/Styles/Img/help_07.png");
			}
		});
	});

	/* 挑选拖戒-拖戒选择 */
	$('.pps').bind('click', function(){
		$('.tiaoxuan-center img').attr('src', $(this).parents('.thumbnail').find('img').attr('src'));
		$('.tiaoxuan-center .rmb').text($(this).parents('.thumbnail').find('.rmbsum').text());
		$('.tiaoxuan-center .txname').text($(this).parents('.thumbnail').find('.list-name').text());
		location.href="#sellist";
		
	});

	/* 祼钻定制->挑选祼钻->全选全不选 */
	$('.all-checkbox').bind('click', function(){
		var AllCheckbox = $(this);
		$('#gmap-street-view table input[type="checkbox"]').each(function(){
			if(AllCheckbox.prop('checked') == false){
				$(this).prop('checked', false);
			}else{
				$(this).prop('checked', true);
			}
		});
	});

	/* 祼钻定制->挑选祼钻->全选全不选 */
	$('.user-message-checked').bind('click', function(){
		var AllCheckbox = $(this);
		$('.message-table input[type="checkbox"]').each(function(){
			if(AllCheckbox.prop('checked') == false){
				$(this).prop('checked', false);
			}else{
				$(this).prop('checked', true);
			}
		});
	});

	//返回顶部
	$(window).scroll(function(){
		  //if($(document).scrollTop() + $(window).height() >= $(document).height()){
		  if($(document).scrollTop() >= $(window).height()){
		     $('.back_top').fadeIn();
		  }else{
		     $('.back_top').fadeOut();
		  }

	});

	$('.back_top').bind('click', function(){
	    $('body,html').animate({scrollTop : 0},400);
	});

});

/*JS精准计算
 *参数：
 *	time:	2016年6月1日
 *	auth:	fangkai
 */
  /* js精准计算（加法） */
function accAdd(arg1,arg2){  
    var r1,r2,m;  
    try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}  
    try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}  
    m=Math.pow(10,Math.max(r1,r2))  
    return (arg1*m+arg2*m)/m  
}    
/* js精准计算（减法） */
function accSub(arg1,arg2){      
    return accAdd(arg1,-arg2);  
}
/* js精准计算（乘法） */
function accMul(arg1,arg2){
	var m=0,s1=arg1.toString(),s2=arg2.toString();  
	try{m+=s1.split(".")[1].length}catch(e){}  
	try{m+=s2.split(".")[1].length}catch(e){}  
	return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m)  
}
/* js精准计算（除法） */
function accDiv(arg1,arg2){  
    var t1=0,t2=0,r1,r2;  
    try{t1=arg1.toString().split(".")[1].length}catch(e){}  
    try{t2=arg2.toString().split(".")[1].length}catch(e){}  
    with(Math){  
        r1=Number(arg1.toString().replace(".",""))  
        r2=Number(arg2.toString().replace(".",""))  
        return (r1/r2)*pow(10,t2-t1);  
	}
}

$(document).ready(function(){  
	$(".item h3").click(function(){
	   $(this).parent().toggleClass("hover");
	});
	$(".mc ul a").click(function(){
		$(".mc").find("li").removeClass("active");
		$(this).parent().parent().parent().addClass("hover");
		
		$(this).parent().addClass("active");
	}) 
	
	setCookie("productCategory",0);    
});

/*
 * 说明：系统前端全局函数库
 * 
 * 
*/
/*cookie写入*/
function setCookie(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 86400*1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString()+";path=/;";
}

/*cookie写入(完整版)*/
function setCookie1( name, value, expires, path, domain, secure ) {
    var today = new Date();
    today.setTime( today.getTime() );
    if ( expires ) {
        expires = expires * 1000 * 60 * 60 * 24;
    }
    var expires_date = new Date( today.getTime() + (expires) );
    document.cookie = name+'='+escape( value ) +
        ( ( expires ) ? ';expires='+expires_date.toGMTString() : '' ) +
        //expires.toGMTString()
        ( ( path ) ? ';path=' + path : '' ) +
        ( ( domain ) ? ';domain=' + domain : '' ) +
        ( ( secure ) ? ';secure' : '' );
}

/*cookie读取*/
function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) return unescape(arr[2]);
    else return null
}

function delCookie(name) {
   setCookie(name, "", -1); 
}

function setCookieOfObject(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 86400*1000);
	value = JSON.stringify(value);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString()+";path=/;";
}


function setCookieNotimeOfObject(name, value) {
	value = JSON.stringify(value);
    document.cookie = name + "=" + escape(value) + ";path=/;";
}



function getCookieOfObject(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)){ 
		arr[2] = unescape(arr[2]);
		return JSON.parse(arr[2]);
	}else{
 		return null;
	}
}

/*获取验证码*/
function getCheckCodeImg(id) {
	$(id).html('<img src="checkcode.php?" onClick="this.src+=Math.random()" alt="图片看不清？点击重新得到验证码" style=" border:0;padding:0;margin:0;width:88px;height:30px;cursor:pointer;" />')
}

/*校验邮件*/
function isEmail(str) {
    var myReg = /^[-_A-Za-z0-9]+@([_A-Za-z0-9]+\.)+[A-Za-z0-9]{2,3}$/;
    if (myReg.test(str)) return true;
    return false
}

/*AJAX操作*/
function ajax_post(info_id, post_url, post_value) {
    $.ajax({
        async: false,
        cache: false,
        type: 'POST',
        url: post_url,
        data: post_value,
        success: function (return_data) {
            $(info_id).html(return_data)
        }
    })
}

/*显示与隐藏元素*/
function isDisplay(id) {
    if ($(id).css("display") == "none") {
        $(id).css("display", "block")
    } else {
        $(id).css("display", "none")
    }
}

/*激活文本框*/
function setCursor(id, position) {
    var txtFocus = document.getElementById(id);
    if ($.browser.msie) {
        var range = txtFocus.createTextRange();
        range.move("character", position);
        range.select()
    } else {
        txtFocus.setSelectionRange(position, position);
        txtFocus.focus()
    }
}

/*
 *获取URL参数
*/
function request(paras,url){
	if(!url){var url = location.href;}
    var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
    var paraObj = {};
    for (i = 0; j = paraString[i]; i++) {
        paraObj[j.substring(0, j.indexOf("=")).toLowerCase()] = j.substring(j.indexOf("=") + 1, j.length)
    }
    var returnValue = paraObj[paras.toLowerCase()];
    if (typeof (returnValue) == "undefined") {
        return null
    } else {
        return returnValue
    }
}

/*修改URL参数 
 * url所要更改参数的网址
 * para_name 参数名称
 * para_value 参数值
*/
function setUrlParam(url,para_name,para_value){
	if(!url){return url;}
	var strNewUrl=new String();
	var strUrl=url;
	if(strUrl.indexOf("?")!=-1){
		strUrl=strUrl.substr(strUrl.indexOf("?")+1);
		if(strUrl.toLowerCase().indexOf(para_name.toLowerCase())==-1){
			strNewUrl=url+"&"+para_name+"="+para_value;
			return strNewUrl;
		}else{
			var aParam=strUrl.split("&");
			for(var i=0;i<aParam.length;i++){
				if(aParam[i].substr(0,aParam[i].indexOf("=")).toLowerCase()==para_name.toLowerCase()){
					aParam[i]= aParam[i].substr(0,aParam[i].indexOf("="))+"="+para_value;
				}
			}
			strNewUrl=url.substr(0,url.indexOf("?")+1)+aParam.join("&");
			return strNewUrl;
		}
	}else{
		strUrl+="?"+para_name+"="+para_value;
		return strUrl
	}
}

/*
 * 获取滚动条位置 
 * 使用示例：
	var obj = ScollPostion();
	alert(obj.top);
*/
function  ScollPostion() {
	var t, l, w, h;
	if (document.documentElement && document.documentElement.scrollTop) {
		t = document.documentElement.scrollTop;
		l = document.documentElement.scrollLeft;
		w = document.documentElement.scrollWidth;
		h = document.documentElement.scrollHeight;
	}else if(document.body) {
		t = document.body.scrollTop;
		l = document.body.scrollLeft;
		w = document.body.scrollWidth;
		h = document.body.scrollHeight;
	}
	return { top: t, left: l, width: w, height: h };
}

/*获取当前页URL*/
function GetUrl() {
    var aParams = document.location.search.substr(1).split("&");
    var url = document.location.href.replace(document.location.search.substr(0), "");
    var reqstr = "";
    var argumentslen = arguments.length;
    var argumentstr = "&";
    if (argumentslen > 0) {
        for (var i = 0; i < argumentslen; i++) {
            argumentstr += arguments[i].toString() + "&"
        }
    }
    if (aParams.length > 0) {
        for (i = 0; i < aParams.length; i++) {
            var aParam = aParams[i].split("=");
            if (aParam[0] != "" && argumentstr.indexOf("&" + aParam[0] + "&") < 0) {
                reqstr += aParam[0] + "=" + aParam[1] + "&"
            }
        }
    }
    url = (reqstr.lastIndexOf("&") > 0) ? url + "?" + reqstr.substring(0, reqstr.length - 1) : url + "?go=1";
    return url
}

function getType(obj) {
    return typeof (obj)
}

function str2time(str){	//将日期字符串转换为时间戳
	if(!str || isNaN(str)){return 0;}
	str = str.replace(/-/g,'/'); // 将-替换成/，因为下面这个构造函数只支持/分隔的日期字符串
	var date = new Date(str); // 构造一个日期型数据，值为传入的字符串
	return str2int(((date.getTime())/1000));
}

function str2int(str) {
	if(!str){return 0;}
    var num = parseInt(str);
    return num;
}

function str2float(str){
	if(!str){return 0;}
	return parseFloat(str);
}

function ignoreSpaces(string) {
    var temp = "";
    string = '' + string;
    splitstring = string.split(" ");
    for (i = 0; i < splitstring.length; i++) {
        temp += splitstring[i]
    }
    return temp
}

function formatUrl(str) {
    return encodeURIComponent(ignoreSpaces(str))
}

function formatHtml(str) {
    return encodeURIComponent(str)
}

/*输入统计*/
function countSize(id1, id2, num) {
    $(id1).keyup(function () {
        var content_len = $(id1).val().length;
        var in_len = num - content_len;
        if (in_len >= 0) {
            $(id2).html('您还可以输入' + in_len + '字');
            $(id1).css("border", "")
        } else {
            $(id2).html('您还可以输入' + in_len + '字');
            $(id1).css("border", "1px solid #ff0000")
        }
    })
}

/*字符过滤*/
function strReplace(_keywords, _body) {
    var strs = new Array();
    strs = _keywords.split(" ");
    var m = strs.length;
    for (i = 0; i < m; i++) {
        _body = _body.replace(eval("/" + strs[i] + "/gi"), "")
    }
    return _body
}

/*
	获取select中的所有item，并且组装所有的值为一个字符串，值与值之间用逗号隔开
	@param objSelectId 目标select组件id
	@return select中所有item的值，值与值之间用逗号隔开
*/
function getAllItemValuesByString(objSelectId) {
	var selectItemsValuesStr = "";
	var objSelect = document.getElementById(objSelectId);
	if (null != objSelect && typeof(objSelect) != "undefined") {
		var length = objSelect.options.length;
		for(var i = 0; i < length; i = i + 1) {
			if(objSelect.options[i].selected){
				if (0 == i) {
					selectItemsValuesStr = objSelect.options[i].value;
				} else {
					selectItemsValuesStr = selectItemsValuesStr + "," + objSelect.options[i].value;
				}
			}
		}
	}
	if(selectItemsValuesStr.substring(0,1)==','){
		selectItemsValuesStr = selectItemsValuesStr.substring(1);
	}
	return selectItemsValuesStr;
}

/*JQUERY AJAX请求 
 * 参数：①请求地址 ②参数 ③提交方式 ④返回值处理函数 ⑤是否异步请求
*/
function _ajax(post_url, post_value, method, refunction, async){
	$.ajax({
		async: async,
		cache: false,
		type: method,
		url: post_url,
		data: post_value,
		success: refunction
	})
}

/*AJAX 提交表单
 * 参数：①表单ID	②返回值处理函数		③是否异步，默认为异步
*/
function ajaxForm(id,refunction,async){
	var ajax_url = $(id).attr("action");
	var ajax_type = $(id).attr('method');
	var ajax_data = $(id).serialize();
	if(async===false){
		async = false;
	}else{
		async = true;
	}
	_ajax(ajax_url, ajax_data, ajax_type, refunction, async);
}

/*判断变量是否存在
 * 
 * */
function isset(e){
	if(!e){
		return false;
	}else{
		return true;
	}
}

/*将百分数转换为小数
 * 
 */
function percent2float(number,m){
	if(!number){return 0;}
	if(!m){m=3;}
	if(number.indexOf('%')>0){
		number = (parseFloat(number)/100).toFixed(m);
	}
	return number;
}

/*判断字符串2在字符串1中是否存在
 *
 */
function strstr(str1,str2){
	if(!isset(str1)){
		return false;
	}else{
		if(str1.indexOf(str2)>0){
			return true;
		}else{
			return false;
		}
	}
}


function focusClearInput(e){	//对象获取焦点时候清空该对象默认内容
	$(e).click(function(){//用户点击时候自动清空内容
		$(this).val("");
	});
	$(e).focus(function(){
		if(this.defaultValue==$(this).val()){
			$(this).val("");
		}
	}).blur(function(){
		if($(this).val()==""){
			$(this).css("color", "#FF0000");
			$(this).val(this.defaultValue);
		}else{
			$(this).css("color", "#000000");
		}
	});
}

/*输出调试信息*/
function debugLog(e){
	if(e){
		alert(e);
	}
}

/*获取当前页面的文件名*/
function getPageName(){
	var strUrl=window.location.pathname;
	var arrUrl=strUrl.split("/");
	var strPage=arrUrl[arrUrl.length-1];
	return strPage;
}

/*设置导航条当前页面为激活状态*/
function setActivePage(thisPageName){
	$("#nav ul li").removeClass("active");
	if(!thisPageName){
		$("#nav ul li:first").addClass("active");
	}else{
		$("#nav ul li[index='"+thisPageName+"']").addClass("active");
	}
}

/******** JS 兼容补丁 开始 ********/
//让IE兼容forEach方法
//Array.forEach implementation for IE support..
//https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/forEach
if (!Array.prototype.forEach) {
      Array.prototype.forEach = function(callback, thisArg) {
              var T, k;
              if (this == null) {
                      throw new TypeError(" this is null or not defined");
              }
              var O = Object(this);
              var len = O.length >>> 0; // Hack to convert O.length to a UInt32
              if ({}.toString.call(callback) != "[object Function]") {
                      throw new TypeError(callback + " is not a function");
              }
              if (thisArg) {
                      T = thisArg;
              }
              k = 0;
              while (k < len) {
                      var kValue;
                      if (k in O) {
                              kValue = O[k];
                              callback.call(T, kValue, k, O);
                      }
                      k++;
              }
      };
}
/******** JS 兼容补丁 结束 ********/

/*页面初始化*/
function init(){
  var _clientWidth = document.body.clientWidth; //网页可见区域宽
  var _clientHeight = document.body.clientHeight; //网页可见区域高
  var _offsetWidth = document.body.offsetWidth; //网页可见区域宽(包括边线的宽)
  var _offsetHeight = document.body.offsetHeight; //网页可见区域高(包括边线的高)
  var _scrollWidth = document.body.scrollWidth; //网页正文全文宽
  var _scrollHeight = document.body.scrollHeight; //网页正文全文高
  var _scrollTop = document.body.scrollTop; //网页被卷去的高
  var _scrollLeft = document.body.scrollLeft; //网页被卷去的左
  var _screenTop = window.screenTop; //网页正文部分上
  var _screenLeft = window.screenLeft; //网页正文部分左
  var _screenHeight = window.screen.height; //屏幕分辨率的高
  var _screenWidth = window.screen.width; //屏幕分辨率的宽
  var _availHeight = window.screen.availHeight; //屏幕可用工作区高度
  var _availWidth = window.screen.availWidth; //屏幕可用工作区宽度
  var _clientHeight1 = document.documentElement.clientHeight;
  var _clientWidth1 = document.documentElement.clientWidth;

  var mainbodyHeight = _clientHeight1 - $("#head").height() - $("#footer").height();
  $("#mainbody").css({'min-height':mainbodyHeight+'px','min-width':'1180px'});
  $("html body").css({'max-width':_clientWidth+'px'});
}

//点击弹出内容，方便复制。
function copyToClipboard(notice,text) {
  window.prompt(notice, text);
}
function trim(text) {
  if (typeof text === "string") {
    return text.replace(/^\s*|\s*$/g, "");
  } else {
    return text;
  }
}
function isEmpty(val) {
  var result = true;
  switch (typeof val) {
  case 'string':
    if (val === '0') {
      result = true;
    } else {
      result = val.trim().length === 0 ? true : false;
    }
    break;
  case 'number':
    result = val === 0;
    break;
  case 'object':
    result = val === null;
    break;
  case 'array':
    result = val.length === 0;
    break;
  default:
    result = true;
  }
  return result;
}
function isNumber(val) {
  var reg = /^[\d|\.|,]+$/;
  return reg.test(val);
}
function isTel(tel) {
  var reg = /^[\d|\-|\s|\_]+$/; //只允许使用数字-空格等
  return reg.test(tel);
}

/*定制流程处理 
 *参数：
 *	①step: 1--裸钻；2--钻托
 *	②goods_id:	商品ID
*/
function diyStep(step,goods_id,param){
	if(step==1){setCookie('step1',goods_id);}
	if(step==2){setCookie('step2',goods_id);}  
	if(step == 2){
		var materialId    = $("#associateMaterial .active").attr("ref");
		var diamondId     = $("#associateLuozuan .active").attr("data-content"); 
		var deputystoneId = $("#deputystone .active").attr("ref");  
		var diamondWeight = $("#associateLuozuan .active").attr("ref");		
		var hand   = $("#hand").val();
		if(hand == undefined){
			hand = '';
		}
		var word   = $("#word").val(); 
		var sd_id  = $("input[name='sd_id']").val();
		var param   = materialId+"_"+diamondId+"_"+deputystoneId+"_"+diamondWeight+"_"+hand+"_"+word+'_'+sd_id;
		var param1 = goods_id+"_"+materialId+"_"+diamondId+"_"+deputystoneId+"_"+diamondWeight+"_"+hand+"_"+word+'_'+sd_id;
		setCookie('goods_id_'+goods_id,param);
		setCookie('Gid_Material_Diamond',param1);
		setCookie('dingzhi_szzmzb_step',0);
	}
	if(getCookie('step1')==null || getCookie('step1')=='' ){
		window.location.href='/Home/Goods/goodsDiy';	//跳转到裸钻页面
		return true;
	}  
	
	if(getCookie('step2')==null || getCookie('step2')=='' ){  
		window.location.href='/Home/Goods/diy/';	//跳转到钻托页面
		return true;
	}
	
	window.location.href='/Home/Goods/goodsDiy';	//跳转到裸钻页面
	return true;
}

/*重置定制商品  */
function diyStepReset(step,goods_id){
	if(step==1){delCookie('step1');window.location.href='/Home/Goods/goodsDiy';return true;}
	if(step==2){delCookie('step2');window.location.href='/Home/Goods/diy';return true;}
	window.location.href='/Home/Goods/goodsDiy';	//跳转到裸钻页面
	delCookie("goods_id_"+goods_id);
	return true;
}
/*添加裸钻到购物车 */
function addDiamondsToCart(goodsId){
  var gids = new Array();
  gids[0] = goodsId;
  $.post('/Home/Goods/add2cart',{Action:'post',gid:gids},function(data){
  	   if(data.status == 1){
  	   		alert(data.info);
  	   		$('#totalCart').html(data.count);
			$('#cart').html(data.count);
  	   }else{
  	   		alert(data.info);			
			return false;
  	   } 
  });
}

/*提交定制商品到购物车 */
function diySubmit(){
	var step1 = getCookie('step1');
	var step2 = getCookie('step2');

	if(step1==null || step1 == ''){alert('您还没有挑选钻石!');return false;}
	if(step2==null || step2 == ''){alert('您还没有挑选戒托!');return false;}
	$.post("/Home/Goods/submitDingzhiPanduan",{Action:"post"},function(data){
	     if(data.status == 0){
	     	alert(data.info);
			return false;
	     }else{
			addDiamondsToCart(step1);	//添加裸钻到购物车
			dingzhi(step1);			    //添加戒托到购物车
			
			//加入购物车后，清空定制列表
			delCookie('step1');
			delCookie('step2');
			return true; 
		 }
	});
	
	
}

function dingzhi(step1){
	var gidMaterialDiamond = getCookie("Gid_Material_Diamond");
	var gidMaterialDiamonds = gidMaterialDiamond.split('_');
	$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:gidMaterialDiamonds[0],goods_type:4,materialId:gidMaterialDiamonds[1],diamondId:gidMaterialDiamonds[2],deputystoneId:gidMaterialDiamonds[3],hand:gidMaterialDiamonds[5],word:gidMaterialDiamonds[6],sd_id:gidMaterialDiamonds[7],luozuan:step1},function(data){
	     if(data.status == 100){
	     	//alert(data.msg);
	  	 	window.location.href="/Home/Order/orderCart";
	     }else{
	     	alert(data.msg);
	     }
	});

}
                      
/*	成品或戒托添加购物车,立即购买
 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
 *	id_type: 用来区分是加入购物车（'addTOCart'）还是立即购买（'rapidBuy'）
*/
function addToCart(type,goods_id,goods_type,id_type){
	if( goods_type == 3 ){   
		// 成品    
		var sku_sn = 0;  
		sku_sn = $(".oGoods .active").attr("ref");    
		$.post("/Home/Order/add2cart",{Action:'post',goods_type:goods_type,gid:goods_id,sku_sn:sku_sn,id_type:id_type},function(res){
			if(res.status == 100){
				if(id_type == 'rapidBuy'){
					var id = res.id;
					window.location.href="/Home/Order/orderConfirm?id="+id;
				} else {
					alert(res.msg);
				}
			}else{
				alert(res.msg);
				return false;
			}
		});
	}else if(goods_type == 4){
		// 	戒托
		diamondWeight = $("#associateLuozuan .active").attr("ref");
		diamondId     = $("#associateLuozuan .active").attr("data-content");
		materialId    = $("#associateMaterial .active").attr("ref"); 
		deputystoneId = $("#deputystone .active").attr("ref"); 
		hand = $("#hand").val();
		word = $("#word").val(); 
		sd_id  = $("input[name='sd_id']").val();
		param = goods_id+"_"+materialId+"_"+diamondId+"_"+diamondWeight+"_"+hand+"_"+word+'_'+sd_id;  
		setCookie("Gid_Material_Diamond",param);
		if(type == 1){
			diyStep(2,goods_id,param);    
	  		window.location.href="/Home/Goods/goodsDiy";                                   
		}else{
			$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word,id_type:id_type,sd_id:sd_id},function(res){
			    if(res.status == 100){
					if(id_type == 'rapidBuy'){
						var id = res.id;
						window.location.href="/Home/Order/orderConfirm?id="+id;
					}else{
						alert(res.msg); 
					}
			 	}else{
	     			alert(res.msg);
			    }
			});
		}
	}
}



/* 添加成品或戒托到购物车
	2016-9-23 增加立即购买
	type : 为空则为添加到购物车，为'rapidBuy'则为立即购买
	如果为立即购买，则添加完购物车之后直接跳转到确认下单页面，不用跳转到购物车页面
	thisid	 :	thisid 为商品在购物车中的id
*/
function confirmOrder(thisid){
	if(!thisid){
		layer.msg('添加失败',{		shift :6,	});
		return false;
	}
	$.ajax({
		type: 'POST',
		url	: '/Home/Order/choseOrder' ,
		dataType:'json',
		data: {'thisid':thisid},
		success: function (ref) {
			if(ref.status == 100){
				 window.location.href="/Home/Order/choseOrder?id="+thisid;
			}else if(ref.status == 101){
				url_l=ref.url;
				layer.msg(ref.msg,{				shift :6,	});	
				if(url_l)	setTimeout("window.location.href=url_l",2000);					
			}else{
				layer.msg('网络错误！',{		shift :6,	});
			}
		}
	})
	
}

function addToCart_b2c(goods_id,goods_type,data, goods_number,id_type){
	if(goods_type == 3 || goods_type == 6){   
		// 成品    
		var sku_sn = 0;  
		sku_sn     = data.sku_id;
		$.post("/Home/Order/add2cart",{Action:'post',goods_type:goods_type,gid:goods_id,sku_sn:sku_sn,goods_number:goods_number,id_type:id_type},function(data){
			if(data.status == 100){
				if(id_type == 'rapidBuy'){
					confirmOrder(data.id);
				}else{
					layer.msg(data.msg,{ shift:6, });
				} 
			}else{
				layer.msg(data.msg,{ shift:6, });
			}
		});
	}else if(goods_type == 4){
		// 	戒
		var diamondWeight = data.weights_name;
		var diamondId     = data.gal_id;
		var materialId    = data.material_id;
		var deputystoneId = data.gad_id;
		var hand          = data.hand;
		var word          = data.word;
		var sd_id         = data.sd_id;
		var hand1         = data.hand1;
		var word1         = data.word1;
		var sd_id1        = data.sd_id1;
		$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word,sd_id:sd_id,hand1:hand1,word1:word1,sd_id1:sd_id1,goods_number:goods_number,id_type:id_type},function(data){
			 if(data.status == 100){
				if(id_type == 'rapidBuy'){
					confirmOrder(data.id);
				}else{
					layer.msg(data.msg,{ shift:6, });
				} 
			}else{
				layer.msg(data.msg,{ shift:6, });
			}
		});
	}
}

//添加裸砖到收藏
function addDiamondsToCollection(current,goodsId,type){
	//type 1代表B2C 2代表B2B 3代表B2C详情
	if(current.attr('collection_id')>0){
		if(type==1){
			layer.msg('请勿重复收藏',{ shift:6, });
		}else if(type==3){
			layer.confirm('请勿重复添加收藏!', {
				btn: ['查看我的收藏','好的'] //按钮
			}, function(){
				window.location.href="/Home/Goods/getCollectionList.html";
			});
		}else{
			alert('请勿重复收藏');
		}
		return false;
	}

	$.post('/Home/Goods/addToCollection',{type_from:1,goods_type:1,gid:goodsId},function(data){
		if(type==3){
			var data_src = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
			shoucangmsg(current,data,data_src,1);
			return;
		}
		if(data.status == 100){
			current.attr('collection_id',data.id);
			current.html('已收藏');
			if(type==1){
				layer.msg(data.msg,{ shift:2, });
			}else{
				alert(data.msg);
			}
		}else if(data.status == -100){
			if(type==1){
				$('#quick').modal('show');
			}else{
				$('.quick-login').show();
				$('.qubg').show();
			}
		}else{
			if(type==1){
				layer.msg(data.msg,{ shift:2, });
			}else{
				alert(data.msg);
			}
		}

	});
}
//添加散货到收藏
function addSanhuoToCollect(current,goodsId,type){
	if(current.attr('collection_id')>0){
		if(type==1){
			layer.msg('请勿重复收藏',{ shift:6, });
		}else{
			alert('请勿重复收藏');
		}
		return false;
	}
	$.post('/Home/Goods/addToCollection',{type_from:2,goods_type:2,gid:goodsId},function(data){
		if(data.status == 100){
			current.attr('collection_id',data.id);
			current.html('已收藏');
			if(type==1){
				layer.msg(data.msg,{ shift:2, });
			}else{
				alert(data.msg);
			}
		}else if(data.status == -100){
			if(type==1){
				$('#quick').modal('show');
			}else{
				$('.quick-login').show();
				$('.qubg').show();
			}
		}else{
			if(type==1){
				layer.msg(data.msg,{ shift:2, });
			}else{
				alert(data.msg);
			}
		}

	});
}

//B2C商品详情添加到收藏
function addToCollection(current,goods_id,goods_type,data, goods_number,id_type){
	var data_src = '/Application/Home/View/b2c_new/Styles/Img/quxiaosc.png';
	if(goods_type == 3){
		// 成品
		var sku_sn = 0;
		sku_sn     = data.sku_id;
		$.post("/Home/Goods/addToCollection",{type_from:3,goods_type:goods_type,gid:goods_id,sku_sn:sku_sn,goods_number:goods_number,id_type:id_type},function(data){
			shoucangmsg(current,data,data_src,1);
		});
	}else if(goods_type == 4){
		// 	戒
		var diamondWeight = data.weights_name;
		var diamondId     = data.gal_id;
		var materialId    = data.material_id;
		var deputystoneId = data.gad_id;
		var hand          = data.hand;
		var word          = data.word;
		var sd_id         = data.sd_id;
		var hand1         = data.hand1;
		var word1         = data.word1;
		var sd_id1        = data.sd_id1;
		$.post("/Home/Goods/addToCollection",{type_from:4,gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word,sd_id:sd_id,hand1:hand1,word1:word1,sd_id1:sd_id1,goods_number:goods_number,id_type:id_type},function(data){
			shoucangmsg(current,data,data_src,1);
		});
	}
}
//b2b 商品详情添加到收藏
function addToCollection_b2b(current,goods_id,goods_type,id_type){
	var data_src = '/Application/Home/View/default/Styles/Img/quxiaosc.png';
	if(current.attr('collection_id')>0){
		layer.confirm('请勿重复添加收藏!', {
			btn: ['查看我的收藏','好的'] //按钮
		}, function(){
			window.location.href="/Home/Goods/getCollectionList.html";
		});
		return;
	}
	if( goods_type == 3 ){
		// 成品
		var sku_sn = 0;
		sku_sn = $(".oGoods .active").attr("ref");
		$.post("/Home/Goods/addToCollection",{type_from:3,goods_type:goods_type,goods_type:goods_type,gid:goods_id,sku_sn:sku_sn,id_type:id_type},function(data){
			shoucangmsg(current,data,data_src);
		});
	}else if(goods_type == 4){
		// 	戒托
		diamondWeight = $("#associateLuozuan .active").attr("ref");
		diamondId     = $("#associateLuozuan .active").attr("data-content");
		materialId    = $("#associateMaterial .active").attr("ref");
		deputystoneId = $("#deputystone .active").attr("ref");
		hand = $("#hand").val();
		word = $("#word").val();
		sd_id  = $("input[name='sd_id']").val();
		$.post("/Home/Goods/addToCollection",{type_from:4,goods_type:goods_type,gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word,id_type:id_type,sd_id:sd_id},function(data){
			shoucangmsg(current,data,data_src);
		});
	}
}
function shoucangmsg(current,data,data_src,type){
	var bool_status = data.status;
	var bool_msg = data.msg;
	var bool_id = data.id;
	if(bool_status == -100){
		if(type==1){
			$('#quick').modal('show');
		}else{
			$('.quick-login').show();
			$('.qubg').show();
		}
		return;
	}
	if(bool_status == 100){
		current.attr('src', data_src);
		current.attr('collection_id', bool_id);
		layer.confirm(bool_msg, {
			btn: ['查看我的收藏','好的'] //按钮
		}, function(){
			window.location.href="/Home/Goods/getCollectionList";
		});
	}else{
		layer.confirm(bool_msg, {
			btn: ['查看我的收藏','好的'] //按钮
		}, function(){
			window.location.href="/Home/Goods/getCollectionList";
		});
	}
}
$(document).ready(function(){
	$(".diy_select_diamond").hover( 
		function(){     
		    $(this).addClass("diy_select_diamond_on");
		    $(this).removeClass("diy_select_diamond");
		},
		function(){
		    $(this).addClass("diy_select_diamond");
		    $(this).removeClass("diy_select_diamond_on");
		}
		);
		$(".diy_select_ring").hover( 
		function(){     
		    $(this).addClass("diy_select_ring_on");
		    $(this).removeClass("diy_select_ring");
		},
		function(){
		    $(this).addClass("diy_select_ring");
		    $(this).removeClass("diy_select_ring_on");
		}
	);       
});

//变量类型为string且不为空时为rue  
function isValid(obj){
	var flag=false;
	if(typeof obj == "string" && obj!="")
		flag = true;
	return flag;
}

/*JS精准计算
 *参数：
 *	time:	2016年6月1日
 *	auth:	fangkai
 */
  /* js精准计算（加法） */
function accAdd(arg1,arg2){  
    var r1,r2,m;  
    try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}  
    try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}  
    m=Math.pow(10,Math.max(r1,r2))  
    return (arg1*m+arg2*m)/m  
}    
/* js精准计算（减法） */
function accSub(arg1,arg2){      
    return accAdd(arg1,-arg2);  
}
/* js精准计算（乘法） */
function accMul(arg1,arg2){
	var m=0,s1=arg1.toString(),s2=arg2.toString();  
	try{m+=s1.split(".")[1].length}catch(e){}  
	try{m+=s2.split(".")[1].length}catch(e){}  
	return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m)  
}
/* js精准计算（除法）*/
function accDiv(arg1,arg2){  
    var t1=0,t2=0,r1,r2;  
    try{t1=arg1.toString().split(".")[1].length}catch(e){}  
    try{t2=arg2.toString().split(".")[1].length}catch(e){}  
    with(Math){  
        r1=Number(arg1.toString().replace(".",""))  
        r2=Number(arg2.toString().replace(".",""))  
        return (r1/r2)*pow(10,t2-t1);  
	}
}



/**
*	根据type导出数据
*	zhy		find404@foxmail.com
*	2016年11月25日 10:45:02
*/
function etdata(type,isColor){
	if(type){
		var etd_id='';
		if($("input[name=tbSelectedId]").val()){
			//B2B	
			if(type=='1'){
				$("input[name=tbSelectedId]").each(function(i){		 
					etd_id += $(this).val()+',';
				});
				etd_id=etd_id.substring(0,etd_id.length - 1);
			}else if(type=='2'){
				if(thisIDsData){
					etd_id=thisIDsData;
				}else{
					alert('请先选择要导出的数据！');		
					return false;
				}
			}else{
				alert('状态有误！');
				return false;
			}
		}else{
			if(type=='1'){
				$(".gid").each(function(i){		 
					etd_id += $(this).val()+',';
				});
			}else if(type=='2'){
		        if($.isEmptyObject( luozuan.gid )){
					layer.msg('请先选择要导出的数据！',{ shift:6, });		
					return false;
				}
				for( var key in luozuan.gid ){
					etd_id += luozuan.gid[key]+',';
				}
			}else{
				layer.msg('状态有误！',{ shift:6, });		
				return false;
			}
				etd_id=etd_id.substring(0,etd_id.length - 1);
		}
		if(etd_id && isColor){
			location.href = '/Home/Search/expertcertificatedata?etd_id='+etd_id+'&isColor='+isColor;
		}else{
			return false;
		}
	}else{
		layer.msg('状态有误！',{ shift:6, });	
		return false;
	}
}

/*根据裸钻数组对裸钻列表进行价格排序
 *参数：data：数组，sort：排序(desc:倒序，asc：顺序)
 *	data:	2016年6月1日
 *	auth:	fangkai
 */
function func_data_sort(data,sort){
	if(data['data'] && sort){
		 //外层循环n-1
		for(var h=0; h<data['data'].length-1; h++){
			for(var i=0; i<data['data'].length-h-1; i++){
				//判断数组大小，颠倒位置 
				if(sort == 'DESC'){
					if(parseInt(data['data'][i]['price']) < parseInt(data['data'][i+1]['price'])){
						var temp			= data['data'][i+1];  
						data['data'][i+1]	= data['data'][i]; 
						data['data'][i]		= temp;
					}
				}else{
					if(parseInt(data['data'][i]['price']) > parseInt(data['data'][i+1]['price'])){
						var temp			= data['data'][i+1];  
						data['data'][i+1]	= data['data'][i]; 
						data['data'][i]		= temp;
					}
				}
			}
		}
	}
	return data;
}