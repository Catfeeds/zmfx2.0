 var goods_sn_keyword = '';
			setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
$(document).ready(function(){
	goods_sn_keyword = $('input[name="goods_sn"]').val();
	diamondsInit();
	$(".panel-body .col-xs-9 a").click(function(){
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page','1'));
		//2016-1-4-26 修复初始化时cookie 为null的bug
		var diamondsDataUrl = getCookie('diamondsDataUrl');
		var url = '/LuoZuan/getGoodsDiamondDiy';
		if(diamondsDataUrl == null){
			$("input[name='minWeight']").val('');
			$("input[name='maxWeight']").val('');
			$("input[name='minPrice']").val('');
			$("input[name='maxPrice']").val('');
			$("div[class=col-xs-9] a").each(function(){
				if($(this).hasClass("btn-primary"))
					$(this).removeClass("btn-primary");
			});
			setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else if(diamondsDataUrl.indexOf(url) == -1 ){
			$("input[name='minWeight']").val('');
			$("input[name='maxWeight']").val('');
			$("input[name='minPrice']").val('');
			$("input[name='maxPrice']").val('');
			$("div[class=col-xs-9] a").each(function(){
				if($(this).hasClass("btn-primary"))
					$(this).removeClass("btn-primary");
			});

			setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else{
			setBtnStatus();
		}
		
		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");

		if($(this).hasClass("selectList")){
			var thisData = request(thisName,getCookie('diamondsDataUrl'));
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
				thisUrl = setUrlParam(getCookie('diamondsDataUrl'),thisName,thisData);
				setCookie('diamondsDataUrl',thisUrl);
				submitData();
			}
		}else{
			$(".panel-body .col-xs-9 a[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("btn-primary");
			$(this).addClass("btn-primary");
			if($(this).hasClass("selectList")){
				if($(this).hasClass("radio")){
					$(".panel-body .col-xs-9 a[name='"+thisName+"']").removeClass("btn-primary");
					$(this).addClass("btn-primary");
					setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),thisName,thisRef));
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
					thisUrl = setUrlParam(getCookie('diamondsDataUrl'),thisName,thisData);
					setCookie('diamondsDataUrl',thisUrl);
				}

				submitData();
			}
		}
	});
	$("._dataSort").click(function(){
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page','1'));   //排序时，初始化页数
		var thisIndex = $(this).attr("index");	//索引存放排序关键字
		var thisData = $(this).attr("data");	//数据属性存放排序方式
		$("._dataSort").children('span').html('↑↓');	//还原默认排序背景图标
		switch(thisData){
			case 'ASC':
				$(this).attr("data",'DESC').removeClass('asc').addClass('desc');
				$('.'+thisIndex).html('↓');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','DESC'));
				break;
			case 'DESC':
				$(this).attr("data",'ASC').removeClass('desc').addClass('asc');
				$('.'+thisIndex).html('↑');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','ASC'));
				break;
			default:
				$(this).attr("data",'ASC').removeClass('desc').addClass('asc');
				$('.'+thisIndex).html('↑');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','ASC'));
				break;
		}
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderby',thisIndex));
		submitData();
	});

	// 自定义重量
	$("#weightCustomBtn").click(function(){
		if($(this).attr("class").indexOf("btn-primary")>0){
			$(this).addClass("CustomBtnSelectd");
			$(this).css("border-bottom","none");
			$("#weightCustom").css("display",'block');
		}
	});

	// 自定义价格
	$("#priceCustomBtn").click(function(){
		if(JQ(this).attr("class").indexOf("btn-primary")>0){
			JQ(this).removeClass("CustomBtnSelectd");
			JQ("#priceCustom").css("display",'block');
		}
	});
});

/*裸钻数据列表初始化*/
function diamondsInit(){
	var diamondsDataUrl = getCookie('diamondsDataUrl');
	$(".panel-body .col-xs-9 a").removeClass("btn-primary");	//初始化按钮状态
	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	
	//2016-1-4-26 修复初始化时cookie 为null的bug
	var url = '/LuoZuan/getGoodsDiamondDiy';
	if(diamondsDataUrl == null){
		setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else if(diamondsDataUrl.indexOf(url) == -1 ){
		setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else{
		var goods_sn=goods_sn_keyword;
		if(goods_sn =="" || goods_sn ==undefined || goods_sn ==null){
			setBtnStatus();
		}else{
			$("input[name='keyword']").val(goods_sn);
			clearCondition();	//清空条件
			setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'goods_sn',goods_sn));
			submitData(); 
			return false;
		}

	}
	//setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'goods_sn',''));
	submitData();
}

function setPage(id){
	setCookie('page',id);
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',id));
	submitData();
}
/*提交数据执行查询*/
function submitData(){
	_ajax(getCookie('diamondsDataUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback, true);
}
/*处理程序返回的JSON查询数据*/
function diamondsData_callback(data){
	if(data.data && data.data != ''){
		var orderby = request('orderby',getCookie('diamondsDataUrl'));
		var orderway= request('orderway',getCookie('diamondsDataUrl'))
		if(orderby == 'price'){
			data	= func_data_sort(data,orderway);
		}
		
		var html = "",dia_global_price="",dia_discount="",cur_price="";
        data.data.forEach(function(e){
			if(data.price_display_type != 1){
				dia_global_price = "国际: "+e.dia_global_price;
				dia_discount = "折扣: <b>"+e.dia_discount_all+"</b>";
				cur_price = "每卡: <b>"+e.cur_price+"</b>";
			}
			html += "<table class='table  text-center'><tbody id='chk_"+e.gid+"' onclick='tbSelectedId("+e.gid+")' class='color' >";
			html += "<tr class='head'><td>"+e.shape+"</td><td colspan='3'>"+e.certificate_type+":"+e.certificate_number+"</td><td>"+dia_global_price+"</td></tr>";
			html += "<tr><td><b>"+e.weight+"</b></td><td>"+e.color+"</td><td>"+e.clarity+"</td><td>"+e.certificate_type+"</td><td class='red'>"+dia_discount+"</td></tr>";
			html += "<tr><td>"+e.location+"</td><td>"+e.cut+"</td><td>"+e.polish+"</td><td>"+e.symmetry+"</td><td>"+cur_price+"</b></td></tr>";
			html += "<tr><td>"+e.goods_name+"</td><td>"+e.milk+"</td><td>"+e.coffee+"</td><td>"+e.fluor+"</td><td class='red'>每粒: <b>"+e.price+"</b></td></tr>";
			html += "</tbody></table>";
		});
		$("#luozuan").html(html);		//写入数据
	}else{
		$("#luozuan").html("<table class='table  text-center'><tbody><tr class='head' id='nodata'><td>暂无数据...</td></tr></tbody></table>");
	}
	if(data.price_display_type != 1){
		$("#dollar_huilv").html(data.dollar_huilv);
	}
	$("#count").html(data.count);
	$("#_pageIndex").val(data.thisPage);		//当前页码

	diaSelectCheckbox();//分页后保持checkbox选定状态
}

function submitDataNextpage(){
	_ajax(getCookie('diamondsDataUrl'),'t='+Math.random()+'&type='+request('type'), 'GET',diamondsData_callbackNextpage, true);
}

/*下一页：处理程序返回的JSON查询数据*/
function diamondsData_callbackNextpage(data){
	if(data.data && data.data != ''){
		var orderby = request('orderby',getCookie('diamondsDataUrl'));
		var orderway= request('orderway',getCookie('diamondsDataUrl'))
		if(orderby == 'price'){
			data	= func_data_sort(data,orderway);
		}
		
		var html = "",dia_global_price="",dia_discount="",cur_price="";
        data.data.forEach(function(e){
			if(data.price_display_type != 1){
				dia_global_price = "国际: "+e.dia_global_price;
				dia_discount = "折扣: <b>"+e.dia_discount_all+"</b>";
				cur_price = "每卡: <b>"+e.cur_price+"</b>";
			}
			html += "<table class='table  text-center'><tbody id='chk_"+e.gid+"' onclick='tbSelectedId("+e.gid+")' class='color' >";
			html += "<tr class='head'><td>"+e.shape+"</td><td colspan='3'>"+e.certificate_type+":"+e.certificate_number+"</td><td>"+dia_global_price+"</td></tr>";
			html += "<tr><td><b>"+e.weight+"</b></td><td>"+e.color+"</td><td>"+e.clarity+"</td><td>"+e.certificate_type+"</td><td class='red'>"+dia_discount+"</b></td></tr>";
			html += "<tr><td>"+e.location+"</td><td>"+e.cut+"</td><td>"+e.polish+"</td><td>"+e.symmetry+"</td><td>"+cur_price+"</b></td></tr>";
			html += "<tr><td>"+e.goods_name+"</td><td>"+e.milk+"</td><td>"+e.coffee+"</td><td>"+e.fluor+"</td><td class='red'>每粒: <b>"+e.price+"</b></td></tr>";
			html += "</tbody></table>";
		});
		$("#luozuan").append(html);		//写入数据

	}else{
		$("#luozuan").append("<table class='table  text-center'><tbody><tr class='head' id='nodata'><td>暂无数据...</td></tr></tbody></table>");
		
	}
	$("#count").html(data.count);
	$("#_pageIndex").val(data.thisPage);		//当前页码

}

/**更新下页数据量
 *
 */
/* 滑动到底部加载数据 */
$(window).scroll(function() {
	var condition = $('#nodata td').html();
	var search 	  = $('#search-right').html();
	var display   = $('#maincont').css('display');
	if(condition != "暂无数据..." && search == '搜索' && display != 'none' ){
		var scrollTop = $(this).scrollTop();               //滚动条距离顶部的高度
		var scrollHeight = $(document).height();           //当前页面的总高度
		var windowHeight = $(this).height();               //当前可视的页面高度
		//判断滚动条是否滚到底部
		if(scrollTop + windowHeight == scrollHeight){
			//alert('11111');
			//$('.load').css('height',40).html('<img src="http://img.lanrentuku.com/img/allimg/1212/5-121204193R5-50.gif" alt="">');
			if(parseInt($("#_pageIndex").val())){
				var page = parseInt($("#_pageIndex").val()) + 1;
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',page));
				submitDataNextpage();
			}
			//setTimeout(function(){
			//	$('.load').css('height','0').text('')
			//},1200);
		}
	}
});
/*function loading(){//分页加载数据
	//if(parseInt($("#_pageIndex").val())<=10){
	if(parseInt($("#_pageIndex").val())){
		var page = parseInt($("#_pageIndex").val()) + 1;
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',page));
		submitDataNextpage();
	}
}*/

/*根据cookie设置按钮状态*/
function setBtnStatus(){
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page','1'));	//将页数设置为1
	var diamondsDataUrl = getCookie('diamondsDataUrl');	//从cookie获取URL
	if(null != diamondsDataUrl && typeof(diamondsDataUrl) != "undefined"){
		var urlParameters = [
			{'name':'Shape','data':request('Shape',diamondsDataUrl)},
			{'name':'Color','data':request('Color',diamondsDataUrl)},
			{'name':'Clarity','data':request('Clarity',diamondsDataUrl)},
			{'name':'Cert','data':request('Cert',diamondsDataUrl)},
			{'name':'Cut','data':request('Cut',diamondsDataUrl)},
			{'name':'Flour','data':request('Flour',diamondsDataUrl)},
			{'name':'Polish','data':request('Polish',diamondsDataUrl)},
			{'name':'Location','data':request('Location',diamondsDataUrl)},
			{'name':'Symmetry','data':request('Symmetry',diamondsDataUrl)},
			{'name':'Weight','data':request('Weight',diamondsDataUrl)},
			{'name':'Price','data':request('Price',diamondsDataUrl)},
			{'name':'Stock','data':request('Stock',diamondsDataUrl)},
			{'name':'NaiKa','data':request('NaiKa',diamondsDataUrl)},
			{'name':'Dollar','data':request('Dollar',diamondsDataUrl)}
		];
		$.each(urlParameters, function(i, obj) {
			if(obj.name != 'Weight' && obj.name != 'Price' && obj && obj.data && obj.name){
				var temp = new Array();
				temp = obj.data.split(",");
				for(m=0;m<temp.length;m++){
					$(".panel-body .col-xs-9 a[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("btn-primary").addClass("btn-primary");
				}
			}else if(obj.name == 'Weight' && obj && obj.data && obj.name){
				var Weight = new Array();
				Weight = obj.data.split("-");
				$("input[name='minWeight']").val(Weight[0]);
				$("input[name='maxWeight']").val(Weight[1]);
			}else if(obj.name == 'Price' && obj && obj.data && obj.name){
				var Price = new Array();
				Price = obj.data.split("-");
				$("input[name='minPrice']").val(Price[0]);
				$("input[name='maxPrice']").val(Price[1]);
			}
		});
	}
}

function getPriceAndWeight(){
	var minWeight = $("input[name='minWeight']").val();
	var maxWeight = $("input[name='maxWeight']").val();
	var minPrice = $("input[name='minPrice']").val();
	var maxPrice = $("input[name='maxPrice']").val();
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'Weight',minWeight+'-'+maxWeight));
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'Price',minPrice+'-'+maxPrice));
	$("#_pageIndex").val(1);
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',$("#_pageIndex").val()));
	submitData()
}

/*自定义价格查询*/
function customPriceCheck(){
	var minPrice = $("input[name='minPrice']").val();
	var maxPrice = $("input[name='maxPrice']").val();
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'Price',minPrice+'-'+maxPrice));
	$("#txtPrice").html(minPrice+'-'+maxPrice);
	$("#priceCustom").hide();
	submitData();
}

/*设置checkbox选定状态*/
function diaSelectCheckbox(){
	if(thisIDsData==''){return false;}
	var data = thisIDsData.split(',');
	data.forEach(function(e){
		$("#chk_"+e).addClass("active_luozuan");
	});
}

/*清空条件*/
function clearCondition(){
	setCookie('diamondsDataUrl','/LuoZuan/getGoodsDiamondDiy.html?Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	$(".filterContent ul li").removeClass("btn-primary");	//初始化按钮状态
	//$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	$("._dataSort").children('span').html('↑↓');	//还原默认排序背景图标
	$("._dataSort").attr("data",'').removeClass('desc').removeClass('asc');
	$("input[name='minWeight']").val('');
	$("input[name='maxWeight']").val('');
	$("input[name='minPrice']").val('');
	$("input[name='maxPrice']").val('');
	
	setCookie("page",1);
	$(".panel-body .col-xs-9 a").each(function(){
		if($(this).hasClass("btn-primary"))
			$(this).removeClass("btn-primary");
	});
	$("#search-alert").fadeOut();
	$("#maincont").fadeIn();
	$("#search-right").text("搜索");
}

 /*清空条件并提交*/
function clearParameters(){
	clearCondition();
	submitData();
}
