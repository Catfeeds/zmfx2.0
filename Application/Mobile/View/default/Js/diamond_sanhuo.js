$(document).ready(function(){
	diamondsInit();
	$(".sanhuo dd").click(function(){  
		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));
		//2016-1-4-26 修复点击时cookie 为null的bug
		var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
		var url = '/Goods/getGoodsByParam';

		if(diamondsSanhuoUrl == null){
			$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态 
			
			setCookie('diamondsSanhuoUrl','/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
		}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
			//diamondsSanhuoUrl = diamondsSanhuoUrl.replace("null", "getGoodsByParam.html");
			$(".sanhuo dd").removeClass("selectd");	//初始化按钮状态
			
			setCookie('diamondsSanhuoUrl','/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
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
		setCookie('diamondsSanhuoUrl','/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
		//diamondsSanhuoUrl = diamondsSanhuoUrl.replace("null", "getGoodsByParam.html");
		setCookie('diamondsSanhuoUrl','/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
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
       	{'name':'Location','data':request('Location',diamondsSanhuoUrl)},
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
		data.data.forEach(function(e){
			html_goods_sn += "<option value='"+e.goods_id+"'>"+e.goods_sn+"</option>";
		 	html += "<li class='am-list-item-dated'>";
		 	html += "<div class='sanhuo_attr'>";          
			html += "<span class='sanhuo_data_cnt'><i>货号："+e.goods_sn+"</i>&nbsp;&nbsp;库存："+e.goods_weight+"<br>";
           	html += "颜色："+e.color+" 净度："+e.clarity+" 切工："+e.cut+"<br>";
          	html += "<i>统走定价：￥"+e.goods_price+"</i></span>";
			html += "<span class='luozuan_data_input'><input id='sanhuoCHK_"+e.goods_id+"' type='checkbox' onclick='tbSelectedId("+e.goods_id+")' name='tbSelectedId' value='"+e.goods_id+"' /></span>";
			html += "</div>";
			html += "<div class='intro'>";
			html += "<p>详细描述："+e.goods_4c+"</p></div>"; 
		}); 
		$("#_dataRows").html(html);

		$("#goods_sn").html(html_goods_sn);
		
	 }else{
	 	$("#_dataRows").html("<tr><td colspan='11'>暂无数据</td></tr>");  
  
	 }
	 
	var goods_sn = data.goodsSn;
	
	if(goods_sn){
		$('#goods_sn').val(goods_sn);
	}
	
	updateLastDataNum(data);
	
	$("#_pageIndex").val(data.thisPage);
}

function setPage(page){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',page));	//将页数设置为1
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
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback2, true);
}

function diamondsData_callback2(data){
	if(data.data != ''){
		html = "";                                                
		data.data.forEach(function(e){ 
		 	html += "<li class='am-list-item-dated'>";
		 	html += "<div class='sanhuo_attr'>";          
			html += "<span class='sanhuo_data_cnt'><i>货号："+e.goods_sn+"</i>&nbsp;&nbsp;库存："+e.goods_weight+"<br>";
           	html += "颜色："+e.color+" 净度："+e.clarity+" 切工："+e.cut+"<br>";
          	html += "<i>统走定价：￥"+e.goods_price+"</i></span>";
			html += "<span class='luozuan_data_input'><input id='sanhuoCHK_"+e.goods_id+"' type='checkbox' onclick='tbSelectedId("+e.goods_id+")' name='tbSelectedId' value='"+e.goods_id+"' /></span>";
			html += "</div>";
			html += "<div class='intro'>";
			html += "<p>详细描述："+e.goods_4c+"</p></div>";
		});
		$("#_dataRows").html(html);
      
	 }else{
	 	$("#_dataRows").html("<tr><td colspan='11'>暂无数据</td></tr>");   
	 }
	 
	updateLastDataNum(data);
	
	$("#_pageIndex").val(data.thisPage);  //当前页码
}

function loading(){
	if(parseInt($("#_pageIndex").val())){
		var page = parseInt($("#_pageIndex").val()) + 1;
		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',page));
		submitDataNextpage();
	}
}
/*提交数据执行查询*/
function submitDataNextpage(){ 
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callbackNextpage, true);
}
function diamondsData_callbackNextpage(data){
	if(data.data != ''){
		html = "";                                                
		data.data.forEach(function(e){ 
		 	html += "<li class='am-list-item-dated'>";
		 	html += "<div class='sanhuo_attr'>";          
			html += "<span class='sanhuo_data_cnt'><i>货号："+e.goods_sn+"</i>&nbsp;&nbsp;库存："+e.goods_weight+"<br>";
           	html += "颜色："+e.color+" 净度："+e.clarity+" 切工："+e.cut+"<br>";
          	html += "<i>统走定价：￥"+e.goods_price+"</i></span>";
			html += "<span class='luozuan_data_input'><input id='sanhuoCHK_"+e.goods_id+"' type='checkbox' onclick='tbSelectedId("+e.goods_id+")' name='tbSelectedId' value='"+e.goods_id+"' /></span>";
			html += "</div>";
			html += "<div class='intro'>";
			html += "<p>详细描述："+e.goods_4c+"</p></div>"; 
		});  
		$("#_dataRows").append(html);      
	 }else{
	 	$("#_dataRows").append("<tr><td colspan='11'>暂无数据</td></tr>");   
	 }
  
	updateLastDataNum(data);					//更新页码数
	
	$("#page").html(data.page); 
	
	$("#_pageIndex").val(data.thisPage);		//当前页码
}
/**更新下页数据量
 * 
 */
function updateLastDataNum(data){
	var lastData = data.count-data.thisPage*data.pagesize;	//待加载数据量  
	if(lastData<0){lastData=0;} 

	if(lastData == 0){
		$("#lastDataNum").hide();
	}else{
		$("#lastDataNum").show();
		$("#lastDataNum").html("下一页"+lastData);	
	}
}

 /*清空条件*/
function clearParameters(){
	setCookie('diamondsSanhuoUrl','/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
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
	if($("#selectThisPage").is(":checked")){ 
		$("[name = tbSelectedId]:checkbox").attr("checked", true);
	}else{
		$("[name = tbSelectedId]:checkbox").attr("checked", false);
		$("[name = tbSelectedId]:checkbox").attr("id");
	}
	$("[name = tbSelectedId]:checkbox").each(function(){
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
	$.post('/Goods/addSanhuo2cart',{Action:'post',goods_id:data},function(data){
  	   	 if(data.status == 1){
  	   		alert(data.info);
  	   		$('#totalCart').html(data.count);
  	   }else{
  	   		alert(data.info);
  	   }
 	});
}

/*添加裸钻到购物车 */
function addDiamondsToCart(goodsId){    
  var gids = new Array();
  gids[0] = goodsId;
  $.post('/Goods/addSanhuo2cart',{Action:'post',goods_id:gids},function(data){
  	   if(data.status == 1){
  	   		alert(data.info);
  	   		$('#totalCart').html(data.count);
  	   }else{
  	   		alert(data.info);
  	   } 
  });
}