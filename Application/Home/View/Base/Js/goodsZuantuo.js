$(document).ready(function(){

	$(".filter_order_way li").click(function(){
		var type = $(this).attr('ref');
	   	setCookie('type',type);
	   	if(getCookie(type)=="DESC"){
	   		setCookie(type,"ASC");
	   		$(this).html($(this).attr('txt')+"&uarr;");  
	   	}else{
	   		setCookie(type,"DESC");                      
	   		$(this).html($(this).attr('txt')+"&darr;");         
	   	}
	   	setCookie('p',1); 
	   	submitData();
	}); 
	$('.filter_row').find('a').each(function(){
		$(this).removeClass("on");
	   	if($(this).attr('type') == "Type"){
	    	if($(this).attr('ref') == getCookie('Type')){
	    		$(this).toggleClass("on");
	    	}                             
	   	}
	   	if($(this).attr('type') == "Material"){
	   		if($(this).attr('ref') == getCookie('Material')){
	   			$(this).toggleClass("on");
	   		}
	   	}
	   	if($(this).attr('type') == "Price"){
	   		if($(this).attr('ref') == getCookie('Price')){
	   			$(this).toggleClass("on");
	   		}
	   	}
	   	if($(this).attr('type') == "main_stone"){
	   		if($(this).attr('ref') == getCookie('main_stone')){
	   			$(this).toggleClass("on");
	   		}
	   	}
	});
	$(".filter_row a").click(function(){
		$(this).parent().find("a").each(function(){ 
			$(this).removeClass("on");
		});
		$(this).toggleClass("on");
		setCookie($(this).attr('type'),$(this).attr('ref'));
        setCookie('p',1);
        submitData();
	}); 
	
	submitData();
});
function setPage(p){
	setCookie("p",p);
	submitData();	
}

function submitData(){
	$.post("/Home/Goods/getAjaxZuantuo",{Action:"post",page:getCookie('p'),sortType:getCookie('type'),sortValue:getCookie(getCookie('type')),Type:getCookie('Type'),Material:getCookie('Material'),Price:getCookie('Price'),main_stone:getCookie('main_stone')},refData);  
}


function refData(data){   
	if(data.data && data.data != ''){
		var html = "<ul>";
		data.data.forEach(function(e){
			html += "<li><div class='data_img'><a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'><img src="+e.thumb+" /></a></div>";
			html += "<div class='data_text'><div class='data_text1'>";
			html += "<div class='data_text_name'>"+e.goods_name_txt+"</div>";
			html += "<div class='data_text_price'>￥"+e.goods_price+"</div></div>";
			html += "<div class='data_text2'><div class='data_detail'>";
			html += "<a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'>查看详情</a></div>";
			html += "</div></div></li>";
		}); 
		html += "</ul>";
		$("#diy_zuantuo_data").html(html); 
	}else{
		$("#diy_zuantuo_data").html("");
	}
	$("#total").html(data.total);
	$("#page").html(data.page);  
}