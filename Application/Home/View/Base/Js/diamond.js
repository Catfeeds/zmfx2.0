 var luozuan_type = 0,goods_sn_keyword = '',custom_view_show = '';
 $(document).ready(function(){
	//进入页面获取当前页面是彩钻还是白钻页面
	luozuan_type 	 = $('input[name="luozuan_type"]').val();
	goods_sn_keyword = $('input[name="goods_sn"]').val();
	custom_view_show = $('input[name="custom_view_show"]').val();
	if(luozuan_type == 'caizuan'){
		luozuan_type = 1;
	}else{
		luozuan_type = 0;
	}
	setCookie("page",1); 
	diamondsInit();
	$(".filterContent ul li").click(function(){
       
		setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'page','1'));
		//2016-1-4-26 修复初始化时cookie 为null的bug
		var diamondsDataUrl = getCookie('diamondsDataUrl');  
		var url = '/Goods/getGoodsDiamondDiy';
		if(diamondsDataUrl == null){
			$(".filterContent ul").each(function(){
				if($(this).data('isclick') != 'no'){
					$(this).children('li').removeClass("selectd");
				}	
			});	//初始化按钮状态	

			setCookie('diamondsDataUrl','/Home/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else if(diamondsDataUrl.indexOf(url) == -1 ){
			$(".filterContent ul").each(function(){
				if($(this).data('isclick') != 'no'){
					$(this).children('li').removeClass("selectd");
				}	
			});	//初始化按钮状态	

			setCookie('diamondsDataUrl','/Home/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
		}else{
			setBtnStatus();
		}
		
		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");

		if($(this).hasClass("selectList")){
			var thisData = request(thisName,getCookie('diamondsDataUrl'));
		}
		if($(this).parent().data('isclick') == 'no'){		 	
			return false;			
		}	
		if(thisRef=="undefined"){
			return false;
		}
		if(thisName=='Weight'){
			$("#weightCustom").hide();
		}
		if(thisName=='Price'){
			$("#priceCustom").hide();
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
				//thisData = thisData.replace(',' + thisRef, '');
				//thisData = thisData.replace(thisRef + ',', '');
				//thisData = thisData.replace(thisRef, '');
				thisUrl = setUrlParam(getCookie('diamondsDataUrl'),thisName,thisData);
				setCookie('diamondsDataUrl',thisUrl);
				submitData();
			}
		}else{
			$(".filterContent ul li[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("selectd");
			$(this).addClass("selectd");
			if($(this).hasClass("selectList")){
				if($(this).hasClass("radio")){
					$(".filterContent ul li[name='"+thisName+"']").removeClass("selectd");
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
	$.post("/Home/Goods/getAjaxLuozuanByGid",{Action:"post",gid:gid},function(e){
		if(e.status == 1){ 
			headerHtml = "<button class='btn close' onclick='closeDialog()'>关闭</button><a class='btn' target='_blank' href='http://www.gia.edu/cs/Satellite?pagename=GST%2FDispatcher&childpagename=GIA%2FPage%2FReportCheck&c=Page&cid=1355954554547&reportno="+e.data.certificate_number+"'>查看证书</a>"; 
			
			if(e.data.imageurl !='' && e.data.imageurl != '-' && e.data.imageurl != null && e.data.imageurl.length > '3'){
				if(e.data.imageurl.substr(-4)==".png" || e.data.imageurl.substr(-4)==".jpg" || e.data.imageurl.substr(-5) ==".jpeg"){
					imgHtml = "<img src="+e.data.imageurl+">";
				}else{
					imgHtml = "<iframe src="+e.data.imageurl+" style='width:395px;height:395px;border:0px'></iframe>";
				}
				
				$("#diamondImg").html(imgHtml);
			}else{
				$("#diamondImg").html("");  	
			}

			if(e.data.videourl != '-' && e.data.videourl != ''){
				videoHtml = "<iframe id='frmMiniFramevideo' data-ng-src='"+e.data.videourl+"' style='width: 100%; height: 290px; border:none;' src='"+e.data.videourl+"'></iframe>";
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
			html += "<tr><th>证书类型：</th><td>"+e.data.certificate_type+"</td></tr><tr><th>荧光：</th><td>"+e.data.fluor+"</td></tr>";
			html += "<tr><th>全深比：</th><td>"+e.data.dia_depth+"</td></tr><tr><th>台宽比：</th><td>"+e.data.dia_table+"</td></tr>";
			html += "<tr><th>奶色：</th><td>"+e.data.milk+"</td></tr><tr><th>咖色：</th><td>"+e.data.coffee+"</td></tr>";                
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
		setCookie('diamondsDataUrl','/Home/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else if(diamondsDataUrl.indexOf(url) == -1 ){
		setCookie('diamondsDataUrl','/Home/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Flour=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	}else{
		
		var goods_sn=goods_sn_keyword;
		if(goods_sn =="" || goods_sn ==undefined || goods_sn ==null){
			setBtnStatus();
		}else{
			$("input[name='keyword']").val(goods_sn);
			clearCondition();	//清空条件
			setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'goods_sn',goods_sn));
			setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'luozuan_type',luozuan_type));
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
	_ajax(getCookie('diamondsDataUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback, true);
}
/*处理程序返回的JSON查询数据*/
function diamondsData_callback(data){
	var orderby = request('orderby',getCookie('diamondsDataUrl'));
	var orderway= request('orderway',getCookie('diamondsDataUrl'))
	if(orderby == 'price'){
		data	= func_data_sort(data,orderway);
	}
	
	if(data.data && data.data != ''){
		var html = "";
		data.data.forEach(function(e){
				html += "<tr>";    
				if(e.videourl != '-' && e.videourl != '' && e.videourl.length > '3'){
					html += "<td>&nbsp;<button onclick='showDialog("+e.gid+",3)' class='opener video' id='opener'></button>&nbsp;</td>";
				}else if(e.imageurl != '-' && e.imageurl != '' && e.imageurl.length > '3'){
					html += "<td>&nbsp;<button onclick='showDialog("+e.gid+",2)' class='opener image' id='opener'></button>&nbsp;</td>";
				}else{
					html += "<td>&nbsp;<button onclick='showDialog("+e.gid+",1)' class='opener common' id='opener'></button>&nbsp;</td>";
				}
				
				html += "<td>"+e.location+"</td>";
				html += "<td>"+e.shape+"</td>";
				html += "<td>";
				if(e.certificate_type=='GIA'){		
					html +='<a href="/Home/Search/detail.html?zs_id='+e.certificate_number+'&zs_type=1" target="_blank" style="color: blue">';
				}else if(e.certificate_type=='IGI'){
					html +='<a href="/Home/Search/detail.html?zs_id='+e.certificate_number+'&zs_type=2" target="_blank" style="color: blue">';
				}else if(e.certificate_type=='HRD'){
					html +='<a href="/Home/Search/detail.html?zs_id='+e.certificate_number+'&zs_type=3" target="_blank" style="color: blue">';
				}else if(e.certificate_type=='NGTC'){
					html +='<a href="" >';
				}else{
					html +='<a href="" >';
				}
				html += e.certificate_type + ' ' + e.certificate_number+"</td>";
				html += "</a>";
				html += "<td>"+e.goods_name+"</td>";
				html += "<td>"+e.weight+"</td>";
				html += "<td>"+e.color+"</td>";
				if(e.luozuan_type == 1){
					html += "<td>"+e.intensity+"</td>";
				}
				html += "<td>"+e.clarity+"</td>";
				if(e.luozuan_type != 1){
					html += "<td>"+e.cut+"</td>";
				}
				html += "<td>"+e.polish+"</td>";
				html += "<td>"+e.symmetry+"</td>";
				html += "<td>"+e.fluor+"</td>";
				html += "<td>"+e.dia_depth+"</td>";
				html += "<td>"+e.dia_table+"</td>";
				if(e.luozuan_type == 1){
					html += "<td>&yen;" + e.cur_price + "</td>";
				}else{
					html += "<td>"+e.milk+"</td>";
					html += "<td>"+e.coffee+"</td>";
					if(data.price_display_type != 1) {
						html += "<td>$" + e.dia_global_price + "</td>";
						html += "<td>&yen;" + e.cur_price + "</td>";
						html += "<td class='zhekou'>" + e.dia_discount_all + "</td>";
					}
				}
				html += "<td style='color:red'>&yen;"+e.price+"</td>";
				html += "<td><input type='checkbox' name='tbSelectedId' id='chk_"+e.gid+"' onclick='tbSelectedId("+e.gid+")' value='"+e.gid+"' /></td>";
				html += "<td>";
				if(e.collection_id>0){
					html += "<span class='buy' collection_id='"+e.collection_id+"' onclick='addDiamondsToCollection($(this),"+e.gid+")'>已收藏</span>";
				}else{
					html += "<span class='buy' onclick='addDiamondsToCollection($(this),"+e.gid+")'>收藏</span>";
				}
				if(custom_view_show != 1){
					html += "<span class='buy' onclick='diyStep(1,"+e.gid+")'>定制</span>"; 
				}
				html += "<span class='buy' onclick='addDiamondsToCart("+e.gid+")'>购买</span>";
				html += "</td>";
				html += "</tr>";
		});
		html += '';
		$("#_dataRows").html(html);		//写入数据
		
	}else{
		if(data.price_display_type!=1) {
			i = 20;
		}else{
			i = 17;
		}
		$("#_dataRows").html("<tr><td colspan=" + i + "><div style='text-align:center;'>暂无数据...</div></td></tr>");
	}
	$("#page").html(data.page); 
	$("#count").html(data.count);
	$("#dollar_huilv").html(data.dollar_huilv);
	$("#_pageIndex").val(data.page);		//当前页码
	$("#_pageSize").html(data.pagesize);	//每页显示数量
	$("#_totalPage").html(data.totalpage);	//总页数
	diaSelectCheckbox();//分页后保持checkbox选定状态
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
       	{'name':'Fluor','data':request('Fluor',diamondsDataUrl)},
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
				$(".filterContent ul li[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("selectd").addClass("selectd");
			}
		}else if(obj.name == 'Weight' && obj && obj.data && obj.name){
			var Weight = new Array();
			Weight = obj.data.split("-");
			if(Weight!="undefined"){
				$("input[name='minWeight']").val(Weight[0]);
				$("input[name='maxWeight']").val(Weight[1]);
			}
		}else if(obj.name == 'Price' && obj && obj.data && obj.name){
			var Price = new Array();
			Price = obj.data.split("-");
			if(Price!="undefined"){
				$("input[name='minPrice']").val(Price[0]);
				$("input[name='maxPrice']").val(Price[1]);
			}
		}
	});
	}
}

/*自定义重量查询*/
function customWeightCheck(){  
	var minWeight = $("input[name='minWeight']").val();
	var maxWeight = $("input[name='maxWeight']").val();
	if(minWeight == '' || minWeight == 'undefined' || minWeight<0){
		minWeight = 0;
		$('#txtWeightMin').html(minWeight);
	}
	if(maxWeight == '' || maxWeight == 'undefined' || maxWeight>500){
		maxWeight = 500;
		$('#txtWeightMax').html(maxWeight);
	}
	if(minWeight > maxWeight){
		alert('最小重量不能大于最大重量');
		return false;
	}
	setCookie('diamondsDataUrl',setUrlParam(getCookie('diamondsDataUrl'),'Weight',minWeight+'-'+maxWeight));
	$("#txtWeight").html(minWeight+'-'+maxWeight);
	$("#weightCustom").hide();
	submitData();
}

/*自定义价格查询*/
function customPriceCheck(){
	var minPrice = $("input[name='minPrice']").val();
	var maxPrice = $("input[name='maxPrice']").val();
	if(minPrice == '' || minPrice == 'undefined' || minPrice<0){
		minPrice = 0;
		$('#txtWeightMin').html(minPrice);
	}
	if(maxPrice == '' || maxPrice == 'undefined' || maxPrice>50000000){
		maxPrice = 50000000;
		$('#txtWeightMax').html(maxPrice);
	}
	if(txtPriceMin > txtPriceMax){
		alert('最小价格不能大于最大价格');
		return false;
	}
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


/* 清空条件 */
function clearCondition(){
	setCookie('diamondsDataUrl','/Home/Goods/getGoodsDiamondDiy.html?luozuan_type='+luozuan_type+'&Shape=&Color=&Clarity=&Cert=&Cut=&Fluor=&Polish=&Location=&Symmetry=&Weight=&Price=&goods_sn=&page=1&rp=&orderby=&orderway=&act=getGoodsDiamondDiy');	//初始化数据接口参数
	$("._dataSort").attr("data",'');
	//$(".filterContent ul li").removeClass("selectd");	//初始化按钮状态
	$(".filterContent ul").each(function(){
		if($(this).data('isclick') != 'no'){
			$(this).children('li').removeClass("selectd");
		}	

	});	//初始化按钮状态	

	$("._dataSort ._dataSortImg").css("background-position","-884px -587px");	//初始化排序状态
	$("input[name='minWeight']").val('');
	$("input[name='maxWeight']").val('');
	$("input[name='minPrice']").val('');
	$("input[name='maxPrice']").val('');
	setCookie("page",1);
}

 /*清空条件并提交*/
function clearParameters(){
	clearCondition();
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
	
	$.post('/Home/Goods/add2cart',{Action:'post',gid:data},function(data){
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
	$.post("/Home/Goods/export",{"type":type,"page":request('p',getCookie('diamondsDataUrl')),"orderby":request('orderby',getCookie('diamondsDataUrl')),"orderway":request('orderway',getCookie('diamondsDataUrl')),"goods_ids":thisIDsData},function(data){
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

