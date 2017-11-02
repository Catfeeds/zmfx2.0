$(document).ready(function(){   
	$(".pageNum li").click(function(){
	  $(".pageNum").find("li").each(function(){
	     $(this).removeClass("active");
	  });
	  setCookie('n',$(this).html());
	  $(this).addClass("active"); 
	  submitData();
	});
	
	$(".filterTo li").click(function(){
		var type = $(this).attr('ref');
	   	setCookie('type',type);
	   	if(getCookie(type)=="DESC"){
	   		setCookie(type,"ASC");
	   		$(this).html($(this).attr('txt')+"&uarr;");  
	   	}else{
	   		setCookie(type,"DESC");                      
	   		$(this).html($(this).attr('txt')+"&darr;");         
	   	}
	   	setCookie('page',1); 
	   	submitData();
	});   
	
	$(".pageNum").find("li").each(function(index,e){
		if($(this).html() == getCookie('n')){
			$(this).addClass("active");
		}
		
	});
	setCookie("page",1);  
	setCookie('attribute_id','');
	submitData();
	

});

function setPage(page){
	setCookie("page",page);
	submitData();
}

function setAttributeId(obj,attribute_id){
	var attributes = [];
	$(obj).parent().find("li").each(function(){
		$(this).removeClass("on"); 
	}); 
	$(obj).addClass("on");
	$(".attrs").find("li").each(function(){
		if($(this).hasClass("on")){
			attributes.push($(this).attr('ref'));	
		}
	});
	setCookie("attribute_id",attributes);
	submitData();
}

function submitData(){ 
	$.post("/Home/Goods/getAjaxProducts",{Action:"post",page:getCookie('page'),sortType:getCookie('type'),sortValue:getCookie(getCookie('type')),n:getCookie('n'),categroy_id:getCookie('trader_category'),goods_type:getCookie('goods_type'),attribute_id:getCookie('attribute_id')},refData);
}

function refData(data){   
	if(data.data && data.data != ''){
		var html = "";
		data.data.forEach(function(e){
			html += "<li>";
			html += "<div class='data_img'><a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'><img src='"+e.thumb+"'/></a></div>";
			html += "<div class='data_text'><p><a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'>"+e.goods_name_txt+"</a></p>";
			html += "<p class='price'>&yen;"+e.goods_price+"</p></div>";
			html += "</li>";
		}); 
		$("#dataList").html(html); 
	}else{
		$("#dataList").html("");
	}
	$("#page").html(data.page); 
	$("#count").html(data.count);
}