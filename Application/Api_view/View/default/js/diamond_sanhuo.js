setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
$(document).ready(function(){
	diamondsInit();
	$(".panel-body .lancer a").click(function(){  
		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));
		//2016-1-4-26 修复点击时cookie 为null的bug
		var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
		var url = '/SanHuo/getGoodsByParam';

		if(diamondsSanhuoUrl == null){
			$(".panel-body .lancer a").removeClass("btn-primary");	//初始化按钮状态 
			
			setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
		}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
			//diamondsSanhuoUrl = diamondsSanhuoUrl.replace("null", "getGoodsByParam.html");
			$(".panel-body .lancer a").removeClass("btn-primary");	//初始化按钮状态
			
			setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
		}
		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");
		if($(this).hasClass("selectList")){
			var thisData = request(thisName,getCookie('diamondsSanhuoUrl'));
		}
		//鼠标单击时候改变当前元素的状态
		if($(this).hasClass("btn-primary")){
			$(this).removeClass("btn-primary");
			if($(this).hasClass("selectList")){
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
			$(".panel-body .lancer a[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("btn-primary");
			$(this).addClass("btn-primary");  
			if($(this).hasClass("selectList")){
				if($(this).hasClass("radio")){
					$(".panel-body .lancer a[name='"+thisName+"']").removeClass("btn-primary");
					$(this).addClass("btn-primary");
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
	$(".panel-body .lancer a").removeClass("btn-primary");	//初始化按钮状态
	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	
	//2016-1-4-26 修复初始化时cookie 为null的bug
	var url = '/SanHuo/getGoodsByParam';
	if(diamondsSanhuoUrl == null){
		setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
		//diamondsSanhuoUrl = diamondsSanhuoUrl.replace("null", "getGoodsByParam.html");
		setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	}else{
		setBtnStatus();
	}
 
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
				$(".panel-body .lancer a[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("btn-primary").addClass("btn-primary");
			}
			if(obj.name == 'GoodsType'){
				$("select[name=GoodsType]").val(obj.data);
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

function diamondsData_callback(data){
	html = "";
	html_goods_sn = "<option value='-1'>请选择货号</option>";
	if(data.list != ''){
		$.each(data.list ,function(k ,e) {
			if(e){
				html_goods_sn += "<option value='"+e.goods_id+"'>"+e.goods_sn+"</option>";
				html += "<tbody id='chk_"+e.goods_id+"' onclick='tbSelectedId("+e.goods_id+")' class='color'>";
				html += "<tr class='head'>";
				html += "<td>"+e.goods_sn+"</td><td>"+e.goods_weight+"</td>";
				html += "<td>"+e.color+"</td><td>"+e.clarity+"</td>";
				html += "<td>"+e.cut+"</td><td>¥"+e.goods_price+"</td>";
				html += "</tr>";
				html += "<tr><td colspan='6'>详细描述: "+e.goods_4c+"</td></tr>";
				html += "</tbody>";
			}
		});
	}else{ 
		html += "<tbody class='table  text-center'><tr><td colspan='6' id='nodata'>暂无数据...</td></tr></tbody>";
	}
	$("#goods_sn").html(html_goods_sn);
	$("#snahuo").html(html); 
	var goods_sn = data.goodsSn;
	if(goods_sn){
		$('#goods_sn').val(goods_sn);
	}
	
	$("#_pageIndex").val(data.page_id);
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
	html = "";
	if(data.list != ''){
		data.list.forEach(function(e){
			html += "<tbody id='chk_"+e.goods_id+"' onclick='tbSelectedId("+e.goods_id+")' class='color'>";
			html += "<tr class='head'>";
            html += "<td>"+e.goods_sn+"</td><td>"+e.goods_weight+"</td>";
			html += "<td>"+e.color+"</td><td>"+e.clarity+"</td>";
			html += "<td>"+e.cut+"</td><td>¥"+e.goods_price+"</td>";
			html += "</tr>";
			html += "<tr><td colspan='6'>详细描述: "+e.goods_4c+"</td></tr>";
			html += "</tbody>";
		});
	}else{
	 	html += "<tbody class='table  text-center'><tr><td id='nodata' colspan='6'>暂无数据...</td></tr></tbody>";
	}
	$("#snahuo").html(html); 
	
	$("#_pageIndex").val(data.page_id);  //当前页码
}

/*提交数据执行查询*/
function submitDataNextpage(){ 
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callbackNextpage, true);
}
function diamondsData_callbackNextpage(data){
	if(data.list != ''){
		html = "";
		data.list.forEach(function(e){
			html += "<tbody id='chk_"+e.goods_id+"' onclick='tbSelectedId("+e.goods_id+")' class='color'>";
			html += "<tr class='head'>";
            html += "<td>"+e.goods_sn+"</td><td>"+e.goods_weight+"</td>";
			html += "<td>"+e.color+"</td><td>"+e.clarity+"</td>";
			html += "<td>"+e.cut+"</td><td>¥"+e.goods_price+"</td>";
			html += "</tr>";
			html += "<tr><td colspan='6'>详细描述: "+e.goods_4c+"</td></tr>";
			html += "</tbody>";
		});
		
	}else{
		html = "<tbody class='table  text-center'><tr><td id='nodata' colspan='6'>暂无数据...</td></tr></tbody>";
	}
	$("#snahuo").append(html);
	
	$("#_pageIndex").val(data.page_id);		//当前页码
}


/* 滑动到底部加载数据 */
$(window).scroll(function() {
	var condition = $('#nodata').html();
	var search 	  = $('#search-right').html();
	var display   = $('#maincont').css('display');
	if(condition != "暂无数据..." && search == '搜索' && display != 'none' ){
		var scrollTop = $(this).scrollTop();               //滚动条距离顶部的高度
		var scrollHeight = $(document).height();           //当前页面的总高度
		var windowHeight = $(this).height();               //当前可视的页面高度
		//判断滚动条是否滚到底部
		if(scrollTop + windowHeight == scrollHeight){
			//$('.load').css('height',40).html('<img src="http://img.lanrentuku.com/img/allimg/1212/5-121204193R5-50.gif" alt="">');
			if(parseInt($("#_pageIndex").val())){
				var page = parseInt($("#_pageIndex").val()) + 1;
				setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',page));
				submitDataNextpage();
			}
			//setTimeout(function(){
			//	$('.load').css('height','0').text('')
			//},1200);
		}
	}
});
 /*清空条件*/
function clearParameters(){
	setCookie('diamondsSanhuoUrl','/SanHuo/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1');	//初始化数据接口参数
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'goods_sn',''));
	$("select[name='GoodsType']").val(0);
	$(".panel-body .lancer a").removeClass("btn-primary");	//初始化按钮状态 
	$("#search-alert").hide();
	$("#maincont").show();
	$("#search-right").text("搜索");
	submitData();
}

