$(document).ready(function(){
	setCookie("page",1); 
	diamondsInit();
	$(".sanhuo dd").click(function(){

		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));
		
		//2016-1-4-26 修复初始化时cookie 为null的bug
		var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
		var url = '/Goods/getGoodsByParam';
		if(diamondsSanhuoUrl == null){
			$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态
			setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
		}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
			$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态
			setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
		}else{
			setBtnStatus();
		}
		
		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");
		if($(this).hasClass("selectList")){
			var thisData = request(thisName,getCookie('diamondsSanhuoUrl'));
		}
		
		//鼠标单击时候改变当前元素的状态
		if($(this).hasClass("selectd")){
			$(this).removeClass("selectd");
			if($(this).hasClass("selectList")){
				/* thisData = thisData.replace(',' + thisRef, '');
				thisData = thisData.replace(thisRef + ',', '');
				thisData = thisData.replace(thisRef, ''); */
				var tmpdata = thisData.replace(',' + thisRef, '');
				if(tmpdata == thisData){
					tmpdata = thisData.replace(thisRef + ',', '');
				}
				if(tmpdata == thisData){
					tmpdata = thisData.replace(thisRef, '');
				}
				thisData = tmpdata;
				thisUrl = setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisData);
				setCookie('diamondsSanhuoUrl',thisUrl); 
				var clicknum=$(".tip .selectd").length;		//确定用户点击多少事件。
				if(clicknum==1){			
					setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');
				}
				submitData();
				 
			}
		}else{
			
			$(".sanhuo dd[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("selectd");
			$(this).addClass("selectd");   
			if($(this).hasClass("selectList")){
				if($(this).hasClass("radio")){
					$(".sanhuo dd[name='"+thisName+"']").removeClass("selectd");
					$(this).addClass("selectd");
					setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisRef));
				}else{
					if (thisData) {
						thisData = thisData + ',' + thisRef
					} else {
						thisData = thisRef
					};
					thisData = $.trim(thisData);
					if (thisData.charAt(0) == ',') {
						thisData = thisData.substr(1)
					};
					thisUrl = setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisData);
					setCookie('diamondsSanhuoUrl',thisUrl);
				}
				
				submitData();
			}
		}
	});
});

/*裸钻数据列表初始化*/
function diamondsInit(){
	var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
	$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态
	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	
	//2016-1-4-26 修复初始化时cookie 为null的bug
	var url = '/Goods/getGoodsByParam';
	if(diamondsSanhuoUrl == null){
		setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
		setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	}else{
		setBtnStatus();
	}
	
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'goods_sn',''));
	submitData(); 
}

/*根据cookie设置按钮状态*/
function setBtnStatus(){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));	//将页数设置为1
	var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');	//从cookie获取URL
	if(null != diamondsSanhuoUrl && typeof(diamondsSanhuoUrl) != "undefined"){
	var urlParameters = [
       	{'name':'GoodsType','data':request('GoodsType',diamondsSanhuoUrl)},
       	{'name':'Weight','data':request('Weight',diamondsSanhuoUrl)},
       	{'name':'Color','data':request('Color',diamondsSanhuoUrl)},
       	{'name':'Clarity','data':request('Clarity',diamondsSanhuoUrl)},
       	{'name':'Cut','data':request('Cut',diamondsSanhuoUrl)},         
       	{'name':'Location','data':request('Location',diamondsSanhuoUrl)}
    ];
	$.each(urlParameters, function(i, obj) {
		if(obj.name != 'Price' && obj && obj.data && obj.name){
			var temp = new Array();
			temp = obj.data.split(",");
			for(m=0;m<temp.length;m++){
				$(".sanhuo dd[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("selectd").addClass("selectd");
			}
		}/* else if(obj.name == 'Weight' && obj && obj.data && obj.name){
			var Weight = new Array();
			Weight = obj.data.split("-");
			$("input[name='minWeight']").val(Weight[0]);
			$("input[name='maxWeight']").val(Weight[1]);
		} */
		else if(obj.name == 'Price' && obj && obj.data && obj.name){
			var Price = new Array();
			Price = obj.data.split("-");
			$("input[name='minPrice']").val(Price[0]);
			$("input[name='maxPrice']").val(Price[1]);
		}
	});
	}
}

/*提交数据执行查询*/
function submitData(){
	//$("#_textLoading").html('{$Think.lang.L57}...');
	//customWeightCheck();
	//customPriceCheck();
	
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback, true);
}
function showGoods4C(goods_id){
    if($("#tr_temp_"+goods_id).is(":hidden")){
		 
    	$("#tr_temp_"+goods_id).show();
    }else{                             
    	$("#tr_temp_"+goods_id).hide();
    }
}
function diamondsData_callback(data){
	if(data.data != ''){
		html = "";
		html_goods_sn = "<option value='-1'>请选择货号</option>";
		i = 1;
		data.data.forEach(function(e){ 
			if(i %2 == 0){
				html += "<tr class='gray' style='cursor:pointer;' id='tr_"+e.goods_id+"'>";
			}else{
		 		html += "<tr style='cursor:pointer;' id='tr_"+e.goods_id+"'>";
			}
		 	html += "<td>"+e.type_name+"</td>";
		 	html += "<td>"+e.location+"</td>";
		 	html += "<td>"+e.goods_sn+"</td>";
		 	html_goods_sn += "<option value='"+e.goods_id+"'>"+e.goods_sn+"</option>";
		 	html += "<td>"+e.goods_weight+"</td>";
		 	html += "<td>"+e.color+"</td>";
		 	html += "<td>"+e.clarity+"</td>";
		 	html += "<td>"+e.cut+"</td>";
		 	html += "<td><font style='color:red'>"+e.goods_price+"</font></td>";
		 	//html += "<td>"+e.goods_price2+"</td>";
		 	html += "<td><input type='checkbox' onclick='tbSelectedId("+e.goods_id+")' id='sanhuoCHK_"+e.goods_id+"' name='tbSelectedId' value="+e.goods_id+" /></td>";
		 	html += "<td onclick='showGoods4C("+e.goods_id+")'>查看详细</td>";

			html += "<td>";
			if(e.collection_id>0){
				html += "<span class='buy' collection_id='"+e.collection_id+"' onclick='addSanhuoToCollect($(this),"+e.goods_id+")'>已收藏</span>";
			}else{
				html += "<span class='buy' onclick='addSanhuoToCollect($(this),"+e.goods_id+")'>收藏</span>";
			}
			html += "<span class='buy' onclick='addDiamondsToCart("+e.goods_id+")'>购买</span>";
			html += "</td>";

		 	html += "</tr>";
		 	if(i %2 == 0){
		 	html += "<tr class='gray' id='tr_temp_"+e.goods_id+"' style='display:none;line-height:25px;'><td colspan='12' class='tdLeft'>"+e.goods_4c+"</td></tr>";       
			}else{
				html += "<tr id='tr_temp_"+e.goods_id+"' style='display:none;line-height:25px;'><td colspan='12' class='tdLeft'>"+e.goods_4c+"</td></tr>";        
			}
		 	i++; 
		});
		
		
		$("#_dataRows").html(html);
		$("#page").html(data.page);
		$("#goods_sn").html(html_goods_sn); 
		$("#totalCart").html(data.count);
		$('#cart').html(data.count);
	 }else{
	 	$("#_dataRows").html("<tr><td colspan='12'>暂无数据</td></tr>");  
	 	$("#page").html(data.page);  
	 }
	var goods_sn = data.goodsSn;
	
	if(goods_sn){
		$('#goods_sn').val(goods_sn);
	}
}

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

function setPage(page){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',page));	
	setCookie("page",page);
	submitData();
}

function setGoodsType(obj){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',1));	//将页数设置为1
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsType',obj.value));	
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',-1));	
	submitData();
}

function setGoodsSn(obj){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',1));	//将页数设置为1
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',obj.value));	
	
 
	submitData2();
}
/*提交数据执行查询*/
function submitData2(){
	//$("#_textLoading").html('{$Think.lang.L57}...');
	//customWeightCheck();
	//customPriceCheck();
	
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback2, true);
}
function diamondsData_callback2(data){
	if(data.data != ''){
		html = "";                                                
		data.data.forEach(function(e){ 
		 	html += "<tr>";
		 	html += "<td>"+e.type_name+"</td>";
		 	html += "<td>"+e.location+"</td>";
		 	html += "<td>"+e.goods_sn+"</td>";
		 	html += "<td>"+e.goods_weight+"</td>";
		 	html += "<td>"+e.color+"</td>";
		 	html += "<td>"+e.clarity+"</td>";
		 	html += "<td>"+e.cut+"</td>";
		 	html += "<td>"+e.goods_price+"</td>";
		 	//html += "<td>"+e.goods_price2+"</td>";
		 	html += "<td><input type='checkbox' id='sanhuoCHK_"+e.goods_id+"' name='tbSelectedId' value="+e.goods_id+" /></td>";
		 	html += "<td onclick='showGoods4C("+e.goods_id+")'>查看详细</td>";      
		 	html += "<td><span class='buy' onclick='addDiamondsToCart("+e.goods_id+")'>购买</span></td>";
		 	html += "</tr>";
		 	if(i %2 == 0){
		 	html += "<tr class='gray' id='tr_temp_"+e.goods_id+"' style='display:none;line-height:25px;'><td colspan='12' class='tdLeft'>"+e.goods_4c+"</td></tr>";       
			}else{
				html += "<tr id='tr_temp_"+e.goods_id+"' style='display:none;line-height:25px;'><td colspan='12' class='tdLeft'>"+e.goods_4c+"</td></tr>";        
			}
		 	i++; 
		});
		$("#_dataRows").html(html);
		$("#page").html(data.page);        
	 }else{
	 	$("#_dataRows").html("<tr><td colspan='11'>暂无数据</td></tr>");  
	 	$("#page").html(data.page);  
	 }
}
 /*清空条件*/
function clearParameters(){
	setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态
	
	submitData();
}
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

/*选定当前页数据*/
function selectThisPage(){ 
	var status = $("#selectThisPage").is(":checked");    
	$("input[name = tbSelectedId]:checkbox").prop("checked", status);  
	$("input[name = tbSelectedId]:checkbox").each(function(){  
		var thisID = $(this).attr('id').replace('sanhuoCHK_',''); 
		tbSelectedId(thisID);
	});
}
function tbSelectedId(productID){//用户选定数据操作    
	if($("#sanhuoCHK_"+productID).is(":checked")){
		thisIDs.add(productID);
	}else{
		thisIDs.del(productID);
	}
}
function addThisPageToCart(){ 
	if(thisIDsData==''){
		return false;
	}
	var data = thisIDsData.split(',');
	$.post('/Home/Goods/addSanhuo2cart',{Action:'post',goods_id:data},function(data){
		if(data.status == 1){
  	   		alert(data.info);
  	   		$('#totalCart').html(data.count);
  	   		$('#cart').html(data.count);
  	    }else{
  	   		alert(data.info);
  	    }
 	});
}

/*添加裸钻到购物车 */
function addDiamondsToCart(goodsId)
{

  var gids = new Array();
  gids[0]  = goodsId;
  $.post('/Home/Goods/addSanhuo2cart',{Action:'post',goods_id:gids},function(data){
  	   if(data.status == 1){
  	   		alert(data.info);
  	   		$('#totalCart').html(data.count);
		    $('#cart').html(data.count);
  	   }else{
  	   		alert(data.info);
  	   }

  });
}