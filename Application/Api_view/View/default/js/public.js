
var init_data = {};
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
	tan(obj.top);
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
		tan(e);
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
		materialId = $("#associateMaterial .active").attr("ref");
		diamondId = $("#associateLuozuan .active").attr("data-content"); 
		deputystoneId = $("#deputystone .active").attr("ref");  
		diamondWeight = $("#associateLuozuan .active").attr("ref");		
		hand   = $("#hand").val();
		word   = $("#word").val(); 
		param   = materialId+"_"+diamondId+"_"+deputystoneId+"_"+diamondWeight+"_"+hand+"_"+word;
		var param1 = goods_id+"_"+materialId+"_"+diamondId+"_"+deputystoneId+"_"+diamondWeight+"_"+hand+"_"+word;    
		setCookie('goods_id_'+goods_id,param);
		setCookie('Gid_Material_Diamond',param1);
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

/*提交定制商品到购物车 */
function diySubmit(){
	var step1 = getCookie('step1');
	var step2 = getCookie('step2');

	if(step1==null || step1 == ''){tan('您还没有挑选钻石!');return false;}
	if(step2==null || step2 == ''){tan('您还没有挑选戒托!');return false;}
	$.post("/Home/Goods/submitDingzhiPanduan",{Action:"post"},function(data){
	     if(data.status == 0){
	     	tan(data.info);
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
	$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:gidMaterialDiamonds[0],goods_type:4,materialId:gidMaterialDiamonds[1],diamondId:gidMaterialDiamonds[2],deputystoneId:gidMaterialDiamonds[3],hand:gidMaterialDiamonds[5],word:gidMaterialDiamonds[6],luozuan:step1},function(data){
	     if(data.status == 1){
	  	 	window.location.href="/Home/Order/orderCart";   	
	     }else{
	     	tan(data.msg);
	     }
	});
	                                                   
}
                      
// 添加成品或戒托到购物车
function addToCart(type,goods_id,goods_type){
	if(goods_type == 3 || goods_type == 6){   
		// 成品    
		var sku_sn = 0;  
		sku_sn = $(".oGoods .active").attr("ref");    
		$.post("/Home/Order/add2cart",{Action:'post',goods_type:goods_type,gid:goods_id,sku_sn:sku_sn},function(data){
			tan(data.msg);
			window.location.href = '/Home/Order/orderCart';
		});
	}else if(goods_type == 4){
		// 	戒托
		diamondWeight = $("#associateLuozuan .active").attr("ref");
		diamondId = $("#associateLuozuan .active").attr("data-content");
		materialId = $("#associateMaterial .active").attr("ref"); 
		deputystoneId = $("#deputystone .active").attr("ref"); 
		hand = $("#hand").val();
		word = $("#word").val(); 
		param = goods_id+"_"+materialId+"_"+diamondId+"_"+diamondWeight+"_"+hand+"_"+word;  
		setCookie("Gid_Material_Diamond",param);
		if(type == 1){
			diyStep(2,goods_id,param);    
	  		window.location.href="/Home/Goods/goodsDiy";                                   
		}else{
			$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:goods_id,goods_type:4,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word},function(data){
			     if(data.status == 1){
			 		window.location.href="/Home/Order/orderCart";  
			     }else{
	     			tan(data.msg);
			     }
			});
		}
	}else if(goods_type == 5){
		var gidMaterialDiamond = getCookie("Gid_Material_Diamond");
		diamondWeight = $("#associateLuozuan .active").attr("ref");
		diamondId = $("#associateLuozuan .active").attr("data-content");
		materialId = $("#associateMaterial .active").attr("ref"); 
		deputystoneId = $("#deputystone .active").attr("ref"); 
		hand = $("#hand").val();
		word = $("#word").val(); 
		
		$.post("/Home/Order/addZMGoods2cart",{Action:"post",gid:goods_id,goods_type:5,deputystoneId:deputystoneId,materialId:materialId,diamondId:diamondId,hand:hand,word:word},function(data){ 
		     if(data.status == 1){
	  	 		window.location.href="/Home/Order/orderCart";   	
		     }else{
	     		tan(data.msg);
		     }
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

/*添加裸钻或者散货到购物车
 *参数：
 *	thisIDsData: 选中的裸钻gid或者散货goods_id
 *	type:		1--裸钻添加购物车；2--散货添加购物车：
 *	time:	2016年5月27日
 *	auth:	fangkai
 */
var thisIDsData = '';	//用来存储用户选定的数据
thisIDs = {
	add:function(productID){
		if (thisIDsData!='') {
			thisIDsData = thisIDsData + ',' + productID
		} else {
			thisIDsData = productID
		};
		thisIDsData = $.trim(thisIDsData);
		if (thisIDsData.charAt(0) == ',') {
			thisIDsData = thisIDsData.substr(1)
		};
	},
	del:function(productID){
		thisIDsData = thisIDsData.replace(',' + productID, '');
		thisIDsData = thisIDsData.replace(productID + ',', '');
		thisIDsData = thisIDsData.replace(productID, '');
	},
	init:function(){
		return true;
	}
}
/* 用户选择的数据 */
function tbSelectedId(productID){//用户选定数据操作
	if(!$("#chk_"+productID).hasClass("active_select")){
		$("#chk_"+productID).addClass('active_select');
		thisIDs.add(productID);
	}else{
		$("#chk_"+productID).removeClass('active_select');
		thisIDs.del(productID);
	}
}

/* 裸钻，散货添加到购物车 
 *参数：
 *	type:1--裸钻添加购物车；2--散货添加购物车；3--裸钻立即购买；4--散货立即购买
*/

function addCart(type){
	var active = type; 
  	if(thisIDsData==''){
		tan('请选择商品');
		return false;
	}
	if(type == 3){
		type = 1;
	}else if(type == 4){
		type = 2;
	}
	var data = thisIDsData.split(',');
	$.post('/Cart/addCart',{Action:'post',goodId:data,type:type},function(data){
		tan(data.info);
		setTimeout("ai_mutual('4')",2000);
	});
}
/*匹配裸钻列表页和购物车主页面删除 
 *参数：
 *	cart_ids: 选中的商品在购物车中的ID
 *	type:		1--匹配裸钻列表页面删除；2--购物车主页面删除：
 *	time:	2016年5月31日
 *	auth:	fangkai
 */
function deleteCart(type){
	var cart_ids = '';
	$('.media .icheckbox_square-blue').each(function(){
		if($(this).hasClass('checked')){
			cart_ids += $(this).children('input').val()+',';
		}
	})
	if(!cart_ids){
		tan('请选择商品');
		return false;
	}
	cart_ids = cart_ids.substring(0,cart_ids.length-1);
	delteCartF(cart_ids,type);
}

function delteCartF(cart_ids,type){
	layer.open({
		content: '您确定要删除选中的商品吗？',
		btn: ['确定', '取消'],
		yes: function(index){
			layer.close(index);
			var data = cart_ids.split(',');
			$.ajax({
				type: 'POST',
				url	: '/Cart/deleteCart' ,
				dataType:'json',
				data: {'cart_ids':data,'type':type},
				success: function (ref) {
					tan(ref.msg);
					setTimeout("ai_mutual('4')",2000);
				}
			})
		}
	});	
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

/**
*	通过val删除所在数组中值
*	zhy
*	2016年10月26日 14:23:56
*/
function RemoveArrayValue(arr, val) {
  for(var i=0; i<arr.length; i++) {
	if(arr[i] == val) {
	  arr.splice(i, 1);
	  break;
	}
  }
}

/**
*  弹出层
*  zhy
*  2016年5月26日 18:04:53
*/
function tan(cc){
	layer.open({
	content: cc,
	//style: 'background-color:#09C1FF; color:#fff; border:none;',
	time: 2
	});
}

/*
	url_tab标识
	1：首页
		11：钻石列表			/LuoZuan/luoZuan.html
		12：珠宝定制	
		13：珠宝现货	
		14：珠宝试戴	
		15：门店预约	
		16：证书查询			/LuoZuan/zhengshu
		17：搜索		
		18：搜索列表	
		19：准时抢列表
	2：分类
		21：产品分类
		22：品牌系列
		23：产品详情			/Goods/goodsInfo/goods_id/1109/type/3.html
	3：咨询
		31：咨询详情页			/Article/getArticleInfo/agent_id/7/aid/700280
	4：购物车
		41：提交订单页			/Cart/Confirm_Cart
		42：提交订单商品清单页  /Cart/Cart_List.html
		43：提交订单成功页面    /Cart/Corder_succeed
		44：匹配裸钻页面		/Cart/cartMatch/cart_id/12751.html
		45：编辑裸钻页面		/Cart/cartMatchList/cart_id/12751.html
	5：个人
		51：个人信息
		52：我的订单			/Userorder
		521：我的订单（去支付） /Userorder/payment
		53：售后服务（申请售后）/OrderService/oList.html
		54：售后服务（售后进度）/OrderService/sList.html
		55：地址管理
		56：账户安全
		57：关于我们			/Article/aboutUs
		58：消息
		59：登录
	6：刷新页面	
*/

/**
*	安卓ios交互接口跳转页面
*	zhy		find404@foxmail.com
*	2016年12月7日 10:36:05
*/
function ai_mutual(url_tab){
	console.log(url_tab);
	window.demo.demoTest(url_tab);
}



/**
*	JS获取URL参数,方法出自《JavaScript 权威指南》
*	zhy		find404@foxmail.com
*	2017年1月13日 10:58:29
*/
// agent_init=function(){
    // var query = location.search.substring(1);
    // var pairs = query.split("&"); 
    // for(var i = 0; i < pairs.length; i++) {
	// var pos = pairs[i].indexOf('=');
	// if (pos == -1) continue;
		// var argname = pairs[i].substring(0,pos);
		// var value = pairs[i].substring(pos+1);
		// value = decodeURIComponent(value);
		// init_data[argname] = value;
    // }	
// }();
