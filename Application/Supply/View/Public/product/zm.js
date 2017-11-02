// 后台公共效果库
// 创建时间 2015/06/06 15:57
// Author adcbguo

//////////用来确保网页随着浏览器的宽度自动的调整
$(window).resize(function(){
		$('.menu,.content,.index-right,.index-left').height($(window).height()-80);
		Cwidth = $(window).width()-161;//主体框宽度		
		$('.content').width(Cwidth);
		$('.page-main').height($(window).height()-84);//主体框高度
		$('.traderIndex').height($(window).height()-84);//分销商首页
		//首页内容宽高
		$('.index-right').width(Cwidth*0.2-20);
		$('.index-left').width(Cwidth*0.8-4);
		$('.statistics,.ranking').height($(window).height()-270);
		$('.statistics').width(Cwidth*0.8*0.8-52);
		$('.ranking').width(Cwidth*0.8*0.25-4);
		$('#myChart').width(Cwidth*0.8*0.8-72);
		$('#myChart').height($(window).height()-400);		
	});


$(document).ready(function(){
	//刷新图片验证码
	$('#Refresh').click(function(){
		$(this).attr('src',$.VerifyUrl+'?time='+Math.random());
	})
	
	//初始化页面元素
	function init(){
		$('.menu,.content,.index-right,.index-left').height($(window).height()-80);
		Cwidth = $(window).width()-161;//主体框宽度
		$('.content').width(Cwidth);
		$('.page-main').height($(window).height()-84);//主体框高度
		$('.traderIndex').height($(window).height()-84);//分销商首页
		//首页内容宽高
		$('.index-right').width(Cwidth*0.2-20);
		$('.index-left').width(Cwidth*0.8-4);
		$('.statistics,.ranking').height($(window).height()-270);
		$('.statistics').width(Cwidth*0.8*0.8-52);
		$('.ranking').width(Cwidth*0.8*0.25-4);
		$('#myChart').width(Cwidth*0.8*0.8-72);
		$('#myChart').height($(window).height()-400);
	}
	init();
	
	//删除确认
	$('a#isDel').live('click',function(){
		 if(confirm("确定要删除吗？"))return true;
		 else return false;
	})
	//取消确认
	$('a#isQx').live('click',function(){
		 if(confirm("确定要取消吗？"))return true;
		 else return false;
	})
	//是否确认
	$('a#isConfirm').live('click',function(){
		 if(confirm("是否确认通过"))return true;
		 else return false;
	})
	
	//添加导航JS
	$('#nav_type').change(function(){
		id = $(this).val();
		if(id == 1){
			$('#category_id').show();$('#function_id').hide();$('#goods_series_id').hide();
			sendGetNavUrl(id,$('select[name=category_id]').val())
		}else if(id == 2){
			$('#function_id').show();$('#category_id').hide();$('#goods_series_id').hide();
			sendGetNavUrl(id,$('select[name=function_id]').val())
		}else if(id == 3){
			$('#category_id').hide();$('#function_id').hide();$('#goods_series_id').hide();
		}else if(id == 4){
			$('#category_id').show();$('#function_id').hide();$('#goods_series_id').hide();
			sendGetNavUrl(id,$('select[name=category_id]').val())
		}else if(id == 5){
			$('#category_id').hide();$('#function_id').hide();$('#goods_series_id').show();
		}
	})
	$('select[name=category_id],select[name=function_id]').change(function(){
		sendGetNavUrl($('#nav_type').val(),$(this).val())
	})
	$('select[name=goods_series_id]').change(function(){
		var _id  = $(this).val();
		var _url = '/Home/Goods/goodsSeries/gsid/'+_id+'.html';
		$('#nav_url input[name=nav_url]').val(_url);
	})
	function sendGetNavUrl(type,id){
		data = {type:type,id:id};
		$.get($.getNavUrl,data,function(data){
			$('#nav_url input[name=nav_url]').val(data);
		})
	}
	//添加产品到订单或采购订单
	$('a[addOrder]').click(function(){
		par = $(this).attr('addOrder');
		str = par.split(",");
		if(str[1] == 'sanhuo'){
			num = $(this).parents('tr').children('td').children('.goods_num').val();
			par += ','+num;
		}
		_this = this;
		$.get($.addOrder,{par:par},function(data){
			if(data.success == 'true'){
				alert(data.msg);
				$(_this).remove();
			}else{
				alert(data.msg);
			}
		})
		return false;
	})
	
	
	
	//树目录展开收起,依赖Jquery,Jquery.cookie
	$('a[data-expansion-id]').click(function(){
		id = $(this).attr('data-expansion-id');
		c = $(this).hasClass('active');
		if(!c){
			$(this).html('收起');
			//把A标签ID记录起来
			Aid = $(this).attr('data-id');
			productAttributeListA = $.cookie('productAttributeListA');
			if(productAttributeListA != null){
				$.cookie('productAttributeListA',productAttributeListA+Aid+',');
			}else{
				$.cookie('productAttributeListA',Aid+',');
			}
			//展开下级列表
			$(this).addClass('active');
			$('.expansion_'+id).show();
			trId = $.cookie('productAttributeList');
			//把展开的TR记录起来
			if(trId == null){trId = '';}
			$('.expansion_'+id).each(function(index, element) {
				trId +=$(this).attr('data-id')+',';
            });
			$.cookie('productAttributeList',trId);
		}else{
			$(this).html('展开');
			//把A标签ID删除
			Aid = $(this).attr('data-id');
			productAttributeListA = $.cookie('productAttributeListA');
			if(productAttributeListA != null){
				var reg=new RegExp(Aid+',',"g");
				productAttributeListA =productAttributeListA.replace(reg,"");
				$.cookie('productAttributeListA',productAttributeListA);
			}
			//收起下级列表
			$(this).removeClass('active');
			$('.expansion_'+id).hide();
			//收起后清除记录
			trId = $.cookie('productAttributeList');
			$('.expansion_'+id).each(function(index, element) {
				var reg=new RegExp($(this).attr('data-id')+',',"g");
				trId =trId.replace(reg,"");
            });
			$.cookie('productAttributeList',trId);
			//1级收起给二级也做收起
			$('.expansion_'+id+' a[data-expansion-id]').html('展开');
			$('.expansion_'+id+' a[data-expansion-id]').removeClass('active');
			//把二级A标签ID删除
			Aid = $('.expansion_'+id+' a[data-expansion-id]').attr('data-id');
			productAttributeListA = $.cookie('productAttributeListA');
			if(productAttributeListA != null){
				var reg=new RegExp(Aid+',',"g");
				productAttributeListA =productAttributeListA.replace(reg,"");
				$.cookie('productAttributeListA',productAttributeListA);
			}
			$('.expansion_p_'+id).hide();
			//收起后清除记录
			$('.expansion_p_'+id).each(function(index, element) {
                var reg=new RegExp($(this).attr('data-id')+',',"g");
				trId =trId.replace(reg,"");
            });
			$.cookie('productAttributeList',trId);
		}
		return false;
	})
	//页面刷新后展开Cookie选中的
	$.productAttributeListInit = function(){
		//Cookie记录的列表展开
		trId = $.cookie('productAttributeList');
		if(trId != null){
			trId = trId.split(',');
			for(i=0;i<trId.length;i++){
				$('tr[data-id="'+trId[i]+'"]').show();
			}
		}
		//Cookie记录的A标签选中
		pA = $.cookie('productAttributeListA');
		if(pA != null){
			pA = pA.split(',');
			for(i=0;i<pA.length;i++){
				$('a[data-id="'+pA[i]+'"]').html('收起');
				$('a[data-id="'+pA[i]+'"]').addClass('active');
			}
		}
	}
	
	
	//
//	$('*[showOrder]').mouseover(function(){
//		order_id = $(this).attr('showOrder');
//		$.get($.showOrder,{order_id:order_id},function(data){
//			
//		})
//	})
	
});

