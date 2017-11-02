$(function(){
	//规格选中事件
	$('.product-info dd').on('click',function(){
		if($(this).hasClass('option')){
			$(this).parent('dl').children('dd').removeClass('active').addClass('option');
			$(this).removeClass('option');
			$(this).addClass('active');			
			getGoodsSpec();
		}
	})
	
	//根据规格重新获取成品产品价格
	function getGoodsSpec(){
		data = getPageGoodsSelSpec();
		$.get('/index.php'+$.getGoodsPrice,data,function(data){
			$('#goods_type').html(data.html);
			$('.goods_price').html(data.price);
		})
	}

	//获取页面产品选中规格
	function getPageGoodsSelSpec(id_type){
		var selString = '';
		$('#spec dd').each(function(index){
			if($(this).hasClass('active')){selString += ','+$(this).attr('data-id');}
		})
		selString=selString.substr(1);
		goods_id = $('input[name="goods_id"]').val();
		goods_type = $('input[name="goods_type"]').val();
		data = {goods_id:goods_id,type:goods_type,selString:selString,id_type:id_type};		
		return data;
	}
	
	/*	添加产品到购物车(效果和执行),立即购买
	 *	id：购物车表中ID，如果存在该值，则是立即购买条转过来的，确认页面直接取该ID的商品
	 *	id_type: 用来区分是加入购物车（'addTOCart'）还是立即购买（'rapidBuy'）
	*/
	$('#addTOCart,#rapidBuy').click(function(){
		id_type = $(this).attr('id');
		data = getPageGoodsSelSpec(id_type);
		
		$.get('/index.php'+$.cartAddGoods,data,function(res){
			if(res.status == 100 && id_type == 'rapidBuy'){
				var id = res.id;
				window.location.href="/index.php/Order/orderConfirm?id="+id;
				return false;
			}else{
				alert(res.info);
				return false;
			}
		});
		if(id_type != 'rapidBuy'){
			img = $('.am-active-slide img').attr('src');
			$('#imgCart img').attr('src',img);
			SX = $('#imgCart').parent('div').position().top + 10;
			$('#imgCart').css('top','10px').css('left','50px');
			$('#imgCart').show().animate({'left':'320px','top':'-'+SX+'px'},1500,function(){
				$('#imgCart').hide();
			})
		}
	})
})
