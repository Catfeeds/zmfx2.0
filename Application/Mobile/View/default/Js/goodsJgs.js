function isExistOption(id,value) {  
    var isExist = false;  
	var count = $('#'+id).find('option').length;     
	for(var i=0;i<count;i++)     
	{     
		if($('#'+id).get(0).options[i].value == value)     
		{     
			isExist = true;     
			break;     
		}     
	}     
	return isExist;  
}  

//获取页面产品选中金工石数据
function getPageGoodsSqlJGS1(id_type){
	selMaterial = selDeputystone = selLuozuan = 0;
	$('.product-attr dd').each(function(){
		if($(this).hasClass('active')){
			if($(this).attr('name') == 'selMaterial') selMaterial = $(this).attr('data-value');
			if($(this).attr('name') == 'selDeputystone') selDeputystone = $(this).attr('data-value');
			if($(this).attr('name') == 'selLuozuan') selLuozuan = $(this).attr('data-value');
		}
	})
	goods_id   = $('input[name="goods_id"]').val();
	goods_type = $('input[name="goods_type"]').val();
	console.log($('#selHand[name="selHand"]'));
	if($('#selHand[name="selHand"]').val()){
		head       = $('#selHand[name="selHand"]').val();
	}else{
		head       ='';
	}
	word       = $('input[name="word"]').val();
	sd_id      = $("input[name='sd_id']").val();
	if(word.length >10 ){
		alert('刻字不能超过十个字符');return false;
	}
	if($('#selHand1[name="selHand1"]').val()){
		head1       = $('#selHand1[name="selHand1"]').val();
	}else{
		head1       ='';
	}
	word1           = $('input[name="word1"]').val();
	sd_id1          = $("input[name='sd_id1']").val();
	if(word1.length >10 ){
		alert('刻字不能超过十个字符');return false;
	}
	selString = selMaterial+','+selDeputystone+','+selLuozuan+','+head+','+word+','+sd_id+','+head1+','+word1+','+sd_id1;
	data = {goods_id:goods_id,type:goods_type,selString:selString,id_type:id_type};
	return data;
}


//重新获取金工石产品价格
function getGoodsJGS1(){
	var img_src      = $(".icon-head .fh img").attr('src');
	var img_val      = $("input[name='sd_id']").val();
	var selHand_val  = $('#selHand[name="selHand"]').val();
	var img_src1     = $(".icon-head1 .fh img").attr('src');
	var img_val1     = $("input[name='sd_id1']").val();
	var selHand_val1 = $('#selHand1[name="selHand1"]').val();
	data             = getPageGoodsSqlJGS1();
	$.get($.getGoodsPrice,data,function(data){
		$('#goods_type').html(data.html);
		$('.goods_price').html(data.price);
		if(img_src){
			$(".icon-head .fh").html("<img src="+img_src+">");					
			$("input[name='sd_id']").val(img_val);
		}
		if(selHand_val){
			if($('#selHand[name="selHand"]').attr('type')=='input' ){
				$('#selHand[name="selHand"]').val(selHand_val);
			}else{
				if(isExistOption('selHand',selHand_val) ){
					$('#selHand[name="selHand"]').val(selHand_val);
				}
			}
		}
		if(img_src1){
			$(".icon-head1 .fh").html("<img src="+img_src1+">");					
			$("input[name='sd_id1']").val(img_val1);
		}
		if(selHand_val1){
			if($('#selHand1[name="selHand1"]').attr('type')=='input' ){
				$('#selHand1[name="selHand1"]').val(selHand_val1);
			}else{
				if(isExistOption('selHand1',selHand_val1) ){
					$('#selHand1[name="selHand1"]').val(selHand_val1);
				}
			}
		}
	})
}


$(function(){
	//金工石选中事件
	$('.product-attr dd').on('click',function(){

		if($(this).hasClass('option')){
			$(this).parent('div').parent('dl').children('div').children('dd').removeClass('active').addClass('option');
			$(this).removeClass('option');
			$(this).addClass('active');
			getGoodsJGS1();
		}
	})


	$('.selHand').change(function(){
		getGoodsJGS1();
	});

	//立即定制
	$('#goodsDiy').click(function(){
		data = getPageGoodsSqlJGS1();
		par = '?goods_id='+data.goods_id+'&goods_type='+data.type+'&selString='+data.selString;
		window.location.href = $.goodsDiy+par;
	})

	/*	添加产品到购物车(效果和执行),立即购买
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	 *	id_type: 用来区分是加入购物车（'addTOCart'）还是立即购买（'rapidBuy'）
	*/
	$('#goodsBuy,#rapidBuy').click(function(){
		id_type = $(this).attr('id');
		data	= getPageGoodsSqlJGS1(id_type);
		$.get($.cartAddGoods,data,function(res){
			if(res.status == 100 && id_type == 'rapidBuy' ){
				var id = res.id;
				window.location.href="/index.php/Order/orderConfirm?id="+id;
			}else{
				alert(res.info);
			}
		})
	})

})