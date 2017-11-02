/*
 * 说明：系统前端全局函数库
 * 
 * 
*/
/*cookie写入*/
function setCookie(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 86400);
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

function getCookieVal(offset)
{
	var endstr = document.cookie.indexOf(";", offset);
	if(endstr == -1)
	{
		endstr = document.cookie.length;
	}
	return unescape(document.cookie.substring(offset, endstr));
}

/*cookie读取*/
function getCookie(name) {
	var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) return unescape(arr[2]);
    else return null
/**	
	var result = null;
	var arg = name + "=";
	var alen = arg.length;
	var clen = document.cookie.length;
	var i = 0;
	while(i < clen)
	{
		var j = i + alen;
		if (document.cookie.substring(i, j) == arg)
		{
			//console.log(getCookieVal(j));
			result = getCookieVal(j);
			break;
		}
		i = document.cookie.indexOf(" ", i) + 1;
		if(i == 0) break;
	}
	console.log(result);
	if(name=='diamondsDataUrl' && result){
			var init_diamondsDataUrl = '/Goods/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&p=&rp=&orderby=&orderway=&act=getGoodsDiamondDiy';
			setCookie('diamondsDataUrl',init_diamondsDataUrl);
			return init_diamondsDataUrl;
	}	
	return '';
	**/
	
	/**
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) return unescape(arr[2]);
    else {
		if(name=='diamondsDataUrl'){
			console.log('----1');
			var init_diamondsDataUrl = '/Goods/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&p=&rp=&orderby=&orderway=&act=getGoodsDiamondDiy';
			setCookie('diamondsDataUrl',init_diamondsDataUrl);
			return init_diamondsDataUrl;
		}
		return null;
	}
	**/
}

function delCookie(name) {
   setCookie(name, "", -1); 
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
	return false;
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
		setCookie('goods_id_'+goods_id,param);
	}
	if(getCookie('step1')==null){
		window.location.href='/Goods/goodsDiy';	//跳转到裸钻页面
		return true;
	}    
	if(getCookie('step2')==null){  
		window.location.href='/Goods/diy';	//跳转到钻托页面
		return true;
	}
	window.location.href='/Goods/goodsDiy';	//跳转到裸钻页面
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
  	   }else{
  	   		alert(data.info);
  	   } 
  });
}
/*提交定制商品到购物车 */
function diySubmit(){
	var step1 = getCookie('step1');
	var step2 = getCookie('step2');
	if(step1==null || step1 == ''){alert('您还没有挑选钻石!');return false;}
	if(step2==null || step2 == ''){alert('您还没有挑选戒托!');return false;}
	
	addDiamondsToCart(step1);	//添加裸钻到购物车
	dingzhi(step1);					//添加戒托到购物车
	
	//加入购物车后，清空定制列表
	delCookie('step1');
	delCookie('step2');
	return true;
}

function dingzhi(step1){  
	var gidMaterialDiamond = getCookie("Gid_Material_Diamond"); 
	var gidMaterialDiamonds = gidMaterialDiamond.split('_'); 
	$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:gidMaterialDiamonds[0],goods_type:4,materialId:gidMaterialDiamonds[1],diamondId:gidMaterialDiamonds[2],hand:gidMaterialDiamonds[4],word:gidMaterialDiamonds[5],luozuan:step1},function(data){
	     if(data.status == 1){
	  	 	window.location.href="/Home/Order/orderCart";   	
	     }else{
	     	alert(data.msg);
	     }
	});
	                                                   
} 
                      
// 添加成品或戒托到购物车
function addToCart(type,goods_id,goods_type){
	if(goods_type == 3 ){   
		// 成品    
		var specification_id = 0;  
		specification_id = $(".oGoods .active").attr("ref");
		if(getCookie("goods_id_"+goods_id)){
			specification_id = getCookie("goods_id_"+goods_id);
		}
		$.post("/Home/Order/add2cart",{Action:'post',goods_type:goods_type,gid:goods_id,specification_id:specification_id},function(data){
			alert(data.msg); 
			if(type == 1){
				window.location.href = '/Home/Order/orderCart';       
			}
		});
	}else if(goods_type == 4){
		// 	戒托
		diamondWeight = $("#associateLuozuan .active").attr("ref");
		diamondId     = $("#associateLuozuan .active").attr("data-content");
		materialId    = $("#associateMaterial .active").attr("ref"); 
		deputystoneId = $("#deputystone .active").attr("ref"); 
		hand          = $("#hand").val();
		word          = $("#word").val(); 
		sd_id         = $("input[name='sd_id']").val();
		param = goods_id+"_"+materialId+"_"+diamondId+"_"+diamondWeight+"_"+hand+"_"+word+'_' + sd_id;  
		setCookie("Gid_Material_Diamond",param);
		if(type == 1){
			diyStep(2,goods_id,param);    
	  		window.location.href="/Home/Goods/goodsDiy";                                   
		}else{
			$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word,sd_id:sd_id},function(data){
			     if(data.status == 1){
			 		window.location.href="/Home/Order/orderCart";  
			     }else{
	     			alert(data.msg);
			     }
			});
		}
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
  //2016-3-31
  //新增头部固定滑动效果
  if($('.diamond-list').length > 0){
    var offtop = $('.diamond-list').offset().top;
    $(window).scroll(function(){  
      var height = $(this).scrollTop();
      if(height > offtop){
        $('.diamond-list tbody td').width($('.table-nav th').width() + 10);
        $('.default-nav').css('visibility','hidden');
        $('.table-nav').css('visibility','visible');
        $('.am-serch').css({'position':'fixed','top':0, 'left': 0});
      }else{
        $('.default-nav').css('visibility','visible');
        $('.table-nav').css('visibility','hidden');
        $('.am-serch').css({'position':'relative'});
        $('.nav_body').css('padding-top',0);
      }
    });
  }
});
    
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