/*
﻿$(function() {
	$.val = {'Shape':'','Weight':'','Cut':'','Price':'','Polish':'','Color':'','SM':'','Clarity':'','Cert':'','Location':'','p':''};
	//打开二级菜单
	$('.openNav').mouseover(function(){
		$(this).children('ul.nav_zi').show();
	}).mouseout(function(){
		$(this).children('ul.nav_zi').hide();
	})

	function in_array(stringToSearch, arrayToSearch) {
		for (s = 0; s < arrayToSearch.length; s++) {
			thisEntry = arrayToSearch[s].toString();
			if (thisEntry == stringToSearch) {
				return true;
			}
		}
		return false;
	}



	//初始化裸钻查询页面
	//cookie里的裸钻条件返回到页面
	function luozuanInit(){
		val = ['Shape','Weight','Cut','Price','Polish','Color','SM','Clarity','Cert','Location'];
		for (var i = 0; i < val.length; i++) {
			arr = $.cookie('luozuan.'+val[i]);
			if(arr){
				arr = arr.split(",");
				$('.filterContent').find('ul#'+val[i]).find('li span').each(function(index,Element){
					if(in_array($(this).html(),arr)){$(this).parent('li').addClass('active');}
				})
			}
		}
	}


	// submitData();
	luozuanInit();
	// 裸钻查询组装查询条件
	$('li').click(function(){
		
		var liId = $(this);

		var ulId = $(this).parents('.kuang').attr("id");
<<<<<<< .mine
		
		if(ulId == "Weight" || ulId == "Price" || ulId == "Location"){    
=======
		if(ulId == "Weight" || ulId == "Price" || ulId == "Location"){
>>>>>>> .r839
			$(this).parents('.kuang').find('li').each(function(index,Element){
				if(liId != $(this)){
					$(this).removeClass("active");
				}
			})
		}
<<<<<<< .mine
		$(this).toggleClass('active');  
		$(this).parents('.filterContent').find('ul').each(function(index,Element){  
			$.id = $(this).attr('id');          
			$(this).find('li.active span').each(function(index,element){ 
=======

		$(this).parents('.filterContent').find('ul').each(function(index,Element){
			$.id = $(this).attr('id');
			$(this).find('li.active span').each(function(index,element){
>>>>>>> .r839
				if(index){
					if($.id == 'Weight' || $.id == 'Price' || $.id == "Location"){
						$.val[$.id ] = $(this).attr("ref");
					}else{
						$.val[$.id ] += ','+$(this).attr("ref");
					}
				}else{
					$.val[$.id ] = $(this).attr("ref");
				}
			})
			$.cookie('luozuan.'+$.id,$.val[$.id ]);
		})
		$.cookie('luozuan.p',1);
		$.val.p = 1;
		// submitData();
	});



});


function setPage(id){
	$.cookie('luozuan.p',id);
	$.val.p = id;
	// submitData();

}
function submitData(){
	url ="/Home/Goods/getGoodsDiamondDiy"; 
	$.get(url,$.val,function(data){
		$("#count").html(data.count);
		if(data.data){
			html = "";
			data.data.forEach(function(e){
				html += "<li>";
				html += "<div style='width:50px'>&nbsp;"+e.location+"&nbsp;</div>";
				html += "<div style='width:50px'>&nbsp;"+e.shape+"&nbsp;</div>";
				html += "<div style='width:150px'>&nbsp;"+e.certificate_number+"&nbsp;</div>";
				html += "<div style='width:120px'>&nbsp;"+e.goods_name+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.weight+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.color+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.clarity+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.cut+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.polish+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.symmetry+"&nbsp;</div>";
				html += "<div style='width:60px'>&nbsp;"+e.fluor+"&nbsp;</div>";
				html += "<div style='width:80px'>&nbsp;"+e.dia_table+"&nbsp;</div>";
				html += "<div style='width:80px'>&nbsp;"+e.dia_depth+"&nbsp;</div>";
				html += "<div style='width:133px'><font style='color:red'>&yen;"+e.price+"&nbsp;</font></div>"; 
				html += "<div style='width:50px'><input type='checkbox' name='selectedId' onclick='tbSelectId("+e.id+")' /></div>";
				html += "<div style='width:50px'><a href='javascript:void(0)'>购买</a></div>";
				html += "<div class='clear'></div>";
				html += "</li>";
			});
			$("#diamondData").html(html);
		}
		$("#page").html(data.page);
	})
}

*/








// 以上是关于裸钻查询相关 

// 空值检查 --> ken
function isEmpty(val) {
    var result = true;
    switch (typeof val) {
    case 'string':
        if (val === '0') { result = true; } else { result = val.trim().length === 0 ? true : false; }
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
