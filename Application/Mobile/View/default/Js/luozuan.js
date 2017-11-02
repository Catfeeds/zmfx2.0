 var luozuan_type = 0,goods_sn_keyword = '';
$(document).ready(function(){
	//进入页面获取当前页面是彩钻还是白钻页面
	luozuan_type = $('input[name="luozuan_type"]').val();
	goods_sn_keyword = $('input[name="goods_sn"]').val();
	if(luozuan_type == 'caizuan'){
		luozuan_type = 1;
	}else{
		luozuan_type = 0;
	}
	diamondsInit();
	$(".diamond-para dl dd").click(function(){
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page','1'));
		//2016-1-4-26 修复初始化时cookie 为null的bug
		var diamondsDataUrl = getCookie('diamondsDataUrl');
		var url = '/Goods/getGoodsDiamondDiy';
		if(diamondsDataUrl == null){
			$("input[name='minWeight']").val('');
			$("input[name='maxWeight']").val('');
			$("input[name='minPrice']").val('');
			$("input[name='maxPrice']").val('');
			$("div[class=diamond-para] dd").each(function(){
				if($(this).hasClass("selectd"))
					$(this).removeClass("selectd");
			});
			setCookie('diamondsDataUrl','/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else if(diamondsDataUrl.indexOf(url) == -1 ){
			$("input[name='minWeight']").val('');
			$("input[name='maxWeight']").val('');
			$("input[name='minPrice']").val('');
			$("input[name='maxPrice']").val('');
			$("div[class=diamond-para] dd").each(function(){
				if($(this).hasClass("selectd"))
					$(this).removeClass("selectd");
			});

			setCookie('diamondsDataUrl','/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else{
			setBtnStatus();
		}
		
		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");

		if($(this).hasClass("selectList")){
			var thisData = request(thisName,getCookie('diamondsDataUrl'));
		}
		//鼠标单击时候改变当前元素的状态
		if($(this).hasClass("selectd")){
			$(this).removeClass("selectd");
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
			$(".diamond-para dl dd[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("selectd");
			$(this).addClass("selectd");
			if($(this).hasClass("selectList")){
				if($(this).hasClass("radio")){
					$(".diamond-para dl dd[name='"+thisName+"']").removeClass("selectd");
					$(this).addClass("selectd");
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

		var thisIndex = $(this).attr("index");	//索引存放排序关键字
		var thisData = $(this).attr("data");	//数据属性存放排序方式
		$("._dataSort span").removeClass('asc').removeClass('desc');	//还原默认排序背景图标
		switch(thisData){
			case 'ASC':
				$(this).attr("data",'DESC').find("span").removeClass('asc').addClass('desc');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderby',thisIndex));
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','DESC'));
				break;
			case 'DESC':
				$(this).attr("data",'ASC').find("span").removeClass('desc').addClass('asc');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderby',thisIndex));
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','ASC'));
				break;
			default:
				$(this).attr("data",'ASC').find("span").removeClass('desc').addClass('asc');
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderby',thisIndex));
				setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway','ASC'));
				break;
		}
		submitData();
	});


    $('.sorting').bind('click', function(){
        var _orderBy;
        var _orderWay;

        //清除排序
        $('.sorting').find('i').html('');
        //重新排序
        if( $(this).attr('orderway') == 'ASC' ){
            $(this).find('i').html('↓');
            $(this).attr('orderway', 'DESC');
        }else{
            $(this).find('i').html('↑');
            $(this).attr('orderway', 'ASC');
        }

        _orderBy = $(this).attr("orderby");
        _orderWay = $(this).attr("orderway");

        setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderby',_orderBy));
        setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'orderway',_orderWay));

        setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',1));

        submitData();
    });




	// 自定义重量
	$("#weightCustomBtn").click(function(){
		if($(this).attr("class").indexOf("selectd")>0){
			$(this).addClass("CustomBtnSelectd");
			$(this).css("border-bottom","none");
			$("#weightCustom").css("display",'block');
		}
	});

	// 自定义价格
	$("#priceCustomBtn").click(function(){
		if(JQ(this).attr("class").indexOf("selectd")>0){
			JQ(this).removeClass("CustomBtnSelectd");
			JQ("#priceCustom").css("display",'block');
		}
	});
});

function iFrameHeight() {
	var ifm= document.getElementById("iframeVideo");
	var subWeb = document.frames ? document.frames["iframeVideo"].document : ifm.contentDocument;
	if(ifm != null && subWeb != null) {
		ifm.height = subWeb.body.scrollHeight;
		ifm.width = subWeb.body.scrollWidth;
	}
}
function showDialog(gid,type){
	$(".ui-dialog-titlebar-close").html("×");
	$.post("/Goods/getAjaxLuozuanByGid",{Action:"post",gid:gid},function(e){
		if(e.status == 1){
			headerHtml = "<button class='btn close' onclick='closeDialog()'>关闭</button><button class='btn'><a target='_blank' href='http://www.gia.edu/search?q="+e.data.certificate_number+"'>查看证书</a></button>";
			if(e.data.imageurl != '-'){
				imgHtml = "<img src="+e.data.imageurl+">";
				$("#diamondImg").html(imgHtml);
			}else{
				$("#diamondImg").html("");
			}
			if(e.data.videourl != '-'){
				videoHtml = "<iframe width='360px' height='290' id='frmMiniFramevideo' data-ng-src='"+e.data.videourl+"' style='border:none;height:215px;' src='"+e.data.videourl+"'></iframe>";
				$("#diamondVideo").html(videoHtml);
				$("#viewVideo").attr("ref",e.data.videourl);
				headerHtml += "<button class='btn viewVideo' ref='"+e.data.videourl+"' onclick=\"viewVideo('"+e.data.videourl+"')\">查看视频</button>";
			}else{
				$("#diamondVideo").html("");
			}
			$("#model-header1").html(headerHtml);
			html = "";
			html += "<div class='span1'>";
			html += "<table>";
			html += "<tr><th width='100'>证书编号：</th><td>"+e.data.certificate_number+"</td></tr><tr><th>形状：</th><td>"+e.data.shape+"</td></tr>";
			html += "<tr><th>重量：</th><td>"+e.data.weight+"</td></tr><tr><th>颜色：</th><td>"+e.data.color+"</td></tr>";
			html += "<tr><th>净度：</th><td>"+e.data.clarity+"</td></tr><tr><th>切工：</th><td>"+e.data.cut+"</td></tr>";
			html += "<tr><th>抛光：</th><td>"+e.data.polish+"</td></tr><tr><th>对称：</th><td>"+e.data.symmetry+"</td></tr>";
			html += "<tr><th>尺寸：</th><td>"+e.data.dia_size+"</td></tr></table></div>";

			html += "<div class='span1 ml12'><table>";
			html += "<tr><th width='100'>证书类型：</th><td>"+e.data.certificate_type+"</td></tr><tr><th>荧光：</th><td>"+e.data.fluor+"</td></tr>";
			html += "<tr><th>全深比：</th><td>61.30</td></tr><tr><th>台宽比：</th><td>"+e.data.dia_table+"</td></tr>";
			html += "<tr><th>奶色：</th><td>"+e.data.milk+"</td></tr><tr><th>咖色：</th><td>"+e.data.milk+"</td></tr>";
			html += "<tr><th>编号：</th><td>"+e.data.goods_name+"</td></tr><tr><th>价格：</th><td>"+e.data.price+"元</td></tr>";
			html += "<tr><th>所在地：</th><td>"+e.data.location+"</td></tr>";
			html += "";
			html += "</table></div>";
			$("#luozuanDetail").html(html);
		}
	});

	$('#dialog').css('margin-left', ((document.body.offsetWidth-1000)/2)+'px');
	if(type ==1){
		height = 316;
	}else if(type == 2){
		height = 603;
	}else if(type == 3){
		height = 603;
	}
	$('#dialog').css('margin-top', (((window.screen.availHeight-50-height)/2)+'px'));
	$("#filter").show(600);
	$("#dialog").show(700);
}
/*裸钻数据列表初始化*/
function diamondsInit(){
	var diamondsDataUrl = getCookie('diamondsDataUrl');
	$("#diamond_para dl dd").removeClass("selectd");	//初始化按钮状态
	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	
	//2016-1-4-26 修复初始化时cookie 为null的bug
	var url = '/Goods/getGoodsDiamondDiy';
	if(diamondsDataUrl == null){
		setCookie('diamondsDataUrl','/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else if(diamondsDataUrl.indexOf(url) == -1 ){
		setCookie('diamondsDataUrl','/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else{
		var goods_sn=goods_sn_keyword;
		if(goods_sn =="" || goods_sn ==undefined || goods_sn ==null){ 
			
			setBtnStatus();
		}else{
			$("input[name='keyword']").val(goods_sn);
			setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'goods_sn',goods_sn));
			submitData(); 
			return false;
		}
	}
	clearParameters();
}

function setPage(id){
	setCookie('page',id);
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',id));
	submitData();
}
/*提交数据执行查询*/
function submitData(){
	//$("#_textLoading").html('{$Think.lang.L57}...');
	//customWeightCheck();
	//customPriceCheck();
	
	_ajax(getCookie('diamondsDataUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback, true);
}
/*处理程序返回的JSON查询数据*/
function diamondsData_callback(data){
	if(data.data && data.data != ''){
		var html = "";
		data.data.forEach(function(e){
			html += "<li class='am-list-item-dated'>";
			html += "<span class='luozuan_data_view'>";
			html += "<label>"+e.shape+"</label>";
			html += "</span><span class='luozuan_data_cnt'>";
			html += "<i>编号："+e.goods_name+"</i><br>";
			html += "<a href='/Search/detail?zs_id="+e.certificate_number+"&zs_type=";
			if(e.certificate_type=='GIA'){
				html += 1;
			}else if(e.certificate_type=='IGI'){
				html += 2;
			}else if(e.certificate_type=='HRD'){
				html += 3;
			}else if(e.certificate_type=='NGTC'){
				html += 4;
			}
			html += "'><i>"+e.certificate_type+"："+e.certificate_number+"</i></a>";
			html += "重量："+e.weight+"CT 颜色："+e.color+" 净度："+e.clarity+"<br>";
			html += "切工："+e.cut+" "+e.polish+" "+e.symmetry+" 荧光："+e.fluor+" "+ e.milk+" "+ e.coffee;
			if(e.luozuan_type == 0) {
				if(data.price_display_type != 1) {
					html += " 折扣："+e.dia_discount_all+" 每卡单价："+e.cur_price + " 国际报价："+e.dia_global_price +" <br>";
					html += "<i>售价：&yen;"+e.price+"</i></span>";
				}else{
					html += " 每卡单价："+e.cur_price + "<br>";
					html += "<i>售价：&yen;"+e.price+"</i></span>";
				}
			}else{
				html += "色度："+e.intensity;
				html += "<br>";
				html += "<i>售价：&yen;"+e.price+"</i></span>";
			}
			if(data.uType > 2){
				html += "<span class='luozuan_data_input'><input type='button' onclick='diyStep(1,"+e.gid+")' name='tbSelectedId' id='chk_"+e.gid+"' value='定制' /></span>";
			}else{
				html += "<span class='luozuan_data_input'><input type='checkbox' onclick='tbSelectedId("+e.gid+")' name='tbSelectedId' id='chk_"+e.gid+"' value='"+e.gid+"' /></span>";
			}
			html += "</li>";
		});
		$("#diamondData").html(html);		//写入数据

	}else{
		$("#diamondData").html("<li><div style='width:100%;text-align:center;'>暂无数据...</div></li>");
	}
	
	updateLastDataNum(data);

	// 有数据时显示排序项
    if(data.count > 0){
        $("p.sort-box").show().find('.red').html(data.count);
	}else{
    	$("p.sort-box").hide();
	}

	
	$("#page").html(data.page);
	//$("#lastDataNum").html("下一页"+data.count);

	$("#_pageIndex").val(data.thisPage);		//当前页码
	//$("#_pageSize").html(data.page_size);	//每页显示数量
	$("#_totalPage").html(data.totalpage);	//总页数
	diaSelectCheckbox();//分页后保持checkbox选定状态
}

function submitDataNextpage(){
	_ajax(getCookie('diamondsDataUrl'),'t='+Math.random()+'&type='+request('type'), 'GET',diamondsData_callbackNextpage, true);
}

/*处理程序返回的JSON查询数据*/
function diamondsData_callbackNextpage(data){
	if(data.data && data.data != ''){
		var html = "";
		data.data.forEach(function(e){
			html += "<li class='am-list-item-dated'>";
			html += "<span class='luozuan_data_view'>";
			html += "<label>"+e.shape+"</label>";
			if(e.videourl != '-' && e.videourl != ''){
				//html += "<button class='view_common'></button>";
			}else if(e.imageurl != '-' && e.imageurl != ''){
				//html += "<button class='view_common'></button>";
			}else{
			//	html += "<button class='view_common'></button>";
			}
			html += "</span><span class='luozuan_data_cnt'>";
			html += "<i>编号："+e.goods_name+"</i><br>";
			html += "<i>"+e.certificate_type+"："+e.certificate_number+"</i><br>";
			html += "重量："+e.weight+"CT 颜色："+e.color+" 净度："+e.clarity+"<br>";
			html += "切工："+e.cut+" "+e.polish+" "+e.symmetry+" 荧光："+e.fluor+" "+ e.milk+" "+ e.coffee;
			if(e.luozuan_type == 0) {
				if(data.price_display_type != 1) {
					html += "折扣："+e.dia_discount_all+" 每卡单价："+e.cur_price + " 国际报价："+e.dia_global_price +" <br>";
					html += "<i>售价：&yen;"+e.price+"</i></span>";
				}else{
					html += " 每卡单价："+e.cur_price + "<br>";
					html += "<i>售价：&yen;"+e.price+"</i></span>";
				}
			}else{
				html += "色度："+e.intensity;
				html += "<br>";
				html += "<i>售价：&yen;"+e.price+"</i></span>";
			}

			if(data.uType > 2){
				html += "<span class='luozuan_data_input'><input type='button' onclick='diyStep(1,"+e.gid+")' name='tbSelectedId' id='chk_"+e.gid+"' value='定制' /></span>";
			}else{
				html += "<span class='luozuan_data_input'><input type='checkbox' onclick='tbSelectedId("+e.gid+")' name='tbSelectedId' id='chk_"+e.gid+"' value='"+e.gid+"' /></span>";
			}
			html += "</li>";
		});
		$("#diamondData").append(html);		//写入数据

	}else{
		$("#diamondData").append("<li><div style='width:100%;text-align:center;'>暂无数据...</div></li>");
	}

	//$("#diamondData").append(data.rows);		//写入数据
	$("#_pageIndex").val(data.thisPage);		//当前页码
	updateLastDataNum(data);

}

/**更新下页数据量
 *
 */
function updateLastDataNum(data){
	var lastData = data.count-data.thisPage*data.page_size;	//待加载数据量  
	if(lastData<0){lastData=0;}
	if(lastData == 0){
		$("#lastDataNum").hide();
	}else{
		$("#lastDataNum").show();
		$("#lastDataNum").html("下一页"+lastData);
	}
}

function loading(){//分页加载数据
	//if(parseInt($("#_pageIndex").val())<=10){
	if(parseInt($("#_pageIndex").val())){
		var page = parseInt($("#_pageIndex").val()) + 1;
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page',page));
		submitDataNextpage();
	}
}

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
					$(".diamond-para dl dd[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("selectd").addClass("selectd");
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
function tbSelectedId(productID){//用户选定数据操作
	if($("#chk_"+productID).is(":checked")){
		thisIDs.add(productID);
	}else{
		thisIDs.del(productID);
	}
}
/*设置checkbox选定状态*/
function diaSelectCheckbox(){
	if(thisIDsData==''){return false;}
	var data = thisIDsData.split(',');
	data.forEach(function(e){
		$("#chk_"+e).attr("checked",true);
	});
}

/*清空条件*/
function clearParameters(){
	setCookie('diamondsDataUrl','/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	$(".filterContent ul li").removeClass("selectd");	//初始化按钮状态
	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	$("input[name='minWeight']").val('');
	$("input[name='maxWeight']").val('');
	$("input[name='minPrice']").val('');
	$("input[name='maxPrice']").val('');
	setCookie("page",1);
	$("div[class=diamond-para] dd").each(function(){
		if($(this).hasClass("selectd"))
			$(this).removeClass("selectd");
	});

	submitData();
}

/*选定当前页数据*/
/*选定当前页数据*/
function selectThisPage(){
	var status = $("#selectThisPage").is(":checked");
	$("input[name = tbSelectedId]:checkbox").prop("checked", status);
	$("input[name = tbSelectedId]:checkbox").each(function(){
		var thisID = $(this).attr('id').replace('chk_','');
		tbSelectedId(thisID);
	});
}


function addThisPageToCart(){
	if(thisIDsData==''){
		return false;
	}
	var data = thisIDsData.split(',');

	$.post('/Goods/add2cart',{Action:'post',gid:data},function(data){
		if(data.status == 1){
			alert(data.info);
			$('#cart').html(data.count);
		}else{
			alert(data.info);
		}

	});
}

function closeDialog(){
	$("#dialog").hide();
	$("#filter").hide(600);
}
function closeVideoDialog(){
	$("#dialogVideo").hide();
	$("#filterVideo").hide(600);
}

function exportData(type){
	$.post("/Goods/export",{"type":type,"page":request('page',getCookie('diamondsDataUrl')),"orderby":request('orderby',getCookie('diamondsDataUrl')),"orderway":request('orderway',getCookie('diamondsDataUrl')),"goods_ids":thisIDsData},function(data){
		if(data.type=="expSerAll"){
			if(data.status == 1){
				alert();
			}else{
				location.href="/"+data.info;
			}
		}
	});
}

function viewVideo(videoURL){
	html = "<iframe onLoad='iFrameHeight()' id='iframeVideo' width='700px' height='600px' border='auto' src='"+videoURL+"'></iframe>";
	$("#videoDetail").html(html);
	$('#dialogVideo').css('margin-left', ((document.body.offsetWidth-700)/2)+'px');
	$('#dialogVideo').css('margin-top', (((window.screen.availHeight-720)/2)+'px'));
	$("#filterVideo").show(600);
	$("#dialogVideo").show(700);
}