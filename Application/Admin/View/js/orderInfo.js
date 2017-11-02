// 订单信息页JS
// 创建时间 2015/06/06 15:57
// Author adcbguo
$(function(){
	// 裸钻分期
	function period(){
		val = $("input[name='luozuanPay']:checked").val();
		period_num = $("#luozuan_period_num").children('option:selected').val();
		html ='';
		luozuanTotal = $('#luozuanTotal').html();
		for(i=0;i<period_num;i++){
			if(i==0){price = luozuanTotal}else{price=''}
			html += '<ul>'
			+'<li><span>天数：</span><input type="text" class="time" value="" name="luozuan_period['+i+'][time]"></li>'
			+'<li><span>还款：</span><input type="text" class="price" value="'+price+'" name="luozuan_period['+i+'][price]"></li>'
			+'<div class="clear"></div>'
			+'</ul>';
		}
		if(val == 1){
			$('#luozuan_period_list').html(html);
		}else{
			$('#luozuan_period_list').html('');
		}
	}
	period();
	$("input[name='luozuanPay']").click(period);
	$("#luozuan_period_num").change(period);
	//裸钻自动填写分期价格
	$('#luozuan_period_list .price').live('change',function(){
		$.price = 0;
		luozuanTotal = $('#luozuanTotal').html();
		$('#luozuan_period_list .price').each(function(index){
			luozuanTotal = luozuanTotal - $(this).val();
			luozuanTotal = toDecimal(luozuanTotal);
			if(!$(this).val() || $(this).val() == '0.00'){
				$(this).val(luozuanTotal);
				$.price = $.price + parseFloat(luozuanTotal);
				luozuanTotal = '';
			}else{
				$.price = $.price + parseFloat($(this).val());
			}
		})
		if($.price != $('#luozuanTotal').html()){
			alert('分期金额和产品价格匹配不上');
		}
	})
	//修改裸钻单价和数量
	function countPrice(){
		$.price = parseFloat(0);
		$('.luozuan .list').each(function(index, element) {
			price = $('.luozuan .list:eq('+index+') .goods_price').val();
			num = parseFloat($('.luozuan .list:eq('+index+') .goods_num').val(),2);
			$(this).children('.goods_price').html(price*num);
			$.price += parseFloat(price*num,2);
		});
		$('#luozuanTotal').html(toDecimal($.price));
	}
	$('.luozuan .goods_price').on('input',function(e){countPrice()});
	$('.luozuan .goods_num').on('input',function(e){
		if($(this).val() > 1){alert('裸钻只能是一个!');$(this).val(1);}
		countPrice()
	});
	//修改裸钻折扣
	$('.luozuan tr td .discount').change(function(){
		discount = $(this).val();
		good_sn = $(this).attr('data-sn');
		id = $(this).attr('data-id');
		//获取折扣
		$.get($.getLuozuanDiscountPrice,{discount:discount,goods_sn:good_sn},function(data){
			$('input[data-id='+id+'].goods_price').val(data);
			countPrice()
		})
	})
	// 散货分期
	function period2(){
		val = $("input[name='sanhuoPay']:checked").val();
		period_num = $("#sanhuo_period_num").children('option:selected').val();
		html ='';
		sanhuoTotal = $('#sanhuoTotal').html();
		for(i=0;i<period_num;i++){
			if(i==0){price = sanhuoTotal}else{price=''}
			html += '<ul>'
			+'<li><span>天数：</span><input type="text" value="" name="sanhuo_period['+i+'][time]"></li>'
			+'<li><span>还款：</span><input type="text" class="price" value="'+price+'" name="sanhuo_period['+i+'][price]"></li>'
			+'<div class="clear"></div>'
			+'</ul>';
		}
		if(val == 1){
			$('#sanhuo_period_list').html(html);
		}else{
			$('#sanhuo_period_list').html('');
		}
	}
	period2();
	$("input[name='sanhuoPay']").click(period2);
	$("#sanhuo_period_num").change(period2);
	//散货自动填写分期价格
	$('#sanhuo_period_list .price').live('change',function(){
		$.price = 0;
		sanhuoTotal = $('#sanhuoTotal').html();
		$('#sanhuo_period_list .price').each(function(index){
			sanhuoTotal = sanhuoTotal - $(this).val();
			sanhuoTotal = toDecimal(sanhuoTotal);
			if(!$(this).val() || $(this).val() == '0.00'){
				$(this).val(sanhuoTotal);
				$.price = $.price + parseFloat(sanhuoTotal);
				sanhuoTotal = '';
			}else{
				$.price = $.price + parseFloat($(this).val());
			}
		})
		if($.price != $('#sanhuoTotal').html()){
			alert('分期金额和产品价格匹配不上');
		}
	})
	//修改散货单价
	function countPrice2(){
		$.price = 0.00;
		$('.sanhuo .list').each(function(index, element) {
			price = $('.sanhuo .list:eq('+index+') .goods_price').val();
			$.price += parseFloat(price);
		});
		$('#sanhuoTotal').html(toDecimal($.price));
	}
	$('.sanhuo .goods_price').on('input',function(e){countPrice2()});
	//修改散货数量
	function countPrice3(){
		$.price = 0.00;
		$('.sanhuo .list').each(function(index, element) {
			var price = $('.sanhuo .list:eq('+index+') .goods_price_on').html();
			var num = parseFloat($('.sanhuo .list:eq('+index+') .goods_num').val().trim());
			var stock = parseFloat($('.sanhuo .list:eq('+index+') .goods_number').html().trim());
			if(num > stock){
				alert("数量超过库存，现库存为"+stock);
				var default_val = $('.sanhuo .list:eq('+index+') .goods_num').attr('default_val');
				$('.sanhuo .list:eq('+index+') .goods_num').val(default_val);
				num = default_val;
			}else if(num < 0){
				alert('数量不能小于0');
				var default_val = $('.sanhuo .list:eq('+index+') .goods_num').attr('default_val');
				$('.sanhuo .list:eq('+index+') .goods_num').val(default_val);
				num = default_val;
			}
			
			prices = price*num;
			$('.sanhuo .list:eq('+index+') .goods_price').val(toDecimal(prices));
			$.price += parseFloat(prices);
		});
		$('#sanhuoTotal').html(toDecimal($.price));
	}
	$('.sanhuo .goods_num').on('input',function(e){countPrice3()});
	//珠宝产品分期
	function period3(){
		val = $("input[name='goodsPay']:checked").val();
		period_num = $("#goods_period_num").children('option:selected').val();
		html ='';
		goodsTotal = $('#goodsTotal').html();
		for(i=0;i<period_num;i++){
			if(i==0){price = goodsTotal}else{price=''}
			html += '<ul>'
			+'<li><span>天数：</span><input type="text" value="" name="goods_period['+i+'][time]"></li>'
			+'<li><span>还款：</span><input type="text" class="price" value="'+price+'" name="goods_period['+i+'][price]"></li>'
			+'<div class="clear"></div>'
			+'</ul>';
		}
		if(val == 1){
			$('#goods_period_list').html(html);
		}else{
			$('#goods_period_list').html('');
		}
	}
	period3();
	$("input[name='goodsPay']").click(period3);
	$("#goods_period_num").change(period3);
	//修改珠宝产品单价
	function countPrice4(type){
		$.price = 0.00;
		$('.goods .list').each(function(index, element) {
			if(type == 1){
				var price = $('.goods .list:eq('+index+') .unitPrice').val();
				var num = parseInt($('.goods .list:eq('+index+') .goods_num').val());
				var stock = parseInt($('.goods .list:eq('+index+') .stock').val());
				if(num < 0){
					alert('数量不能小于0');
					$('.goods .list:eq('+index+') .goods_num').val(1);
					num = 1;
				}else if(num > stock){
					//alert('__price' + price + '__num' + num + '__stock' + stock);
					alert("数量超过库存，现库存为"+stock);
					var default_val = $('.goods .list:eq('+index+') .goods_num').attr('default_val');
					$('.goods .list:eq('+index+') .goods_num').val(default_val);
					//alert($('.goods .list:eq('+index+') .goods_num').val());
					num = default_val;
					//alert( default_val);
				}
				price2 = toDecimal(price*num);
				$('.goods .list:eq('+index+') .goods_price').val(price2);
				$.price += parseFloat(price2);
			}else if(type == 2){
				price = $('.goods .list:eq('+index+') .goods_price').val();
				num = $('.goods .list:eq('+index+') .goods_num').val();
				price2 = parseFloat(price/num);
				$('.goods .list:eq('+index+') .unitPrice').val(price2);
				$('.goods .list:eq('+index+') .goods_price').val(price);
				$.price += parseFloat(price);
			}
		});
		$('#goodsTotal').html(toDecimal($.price));
	}
	$('.goods .goods_num').on('click',function(e){countPrice4(1)});
	$('.goods .goods_price').on('input',function(e){countPrice4(2)});
	//珠宝产品填写分期价格
	$('#goods_period_list .price').live('change',function(){
		$.price = 0;
		goodsTotal = $('#goodsTotal').html();
		$('#goods_period_list .price').each(function(index){
			goodsTotal = goodsTotal - $(this).val();
			goodsTotal = toDecimal(goodsTotal);
			if(!$(this).val() || $(this).val() == '0.00'){
				$(this).val(goodsTotal);
				$.price = $.price + parseFloat(goodsTotal);
				goodsTotal = '';
			}else{
				$.price = $.price + parseFloat($(this).val());
			}
		})
		if($.price != $('#goodsTotal').html()){
			alert('分期金额和产品价格匹配不上');
		}
	})
	//代销货分期
	function period4(){
		val = $("input[name='consignmentPay']:checked").val();
		period_num = $("#consignment_period_num").children('option:selected').val();
		html ='';
		consignmentTotal = $('#consignmentTotal').html();
		for(i=0;i<period_num;i++){
			if(i==0){price = consignmentTotal}else{price=''}
			html += '<ul>'
			+'<li><span>天数：</span><input type="text" value="" name="consignment_period['+i+'][time]"></li>'
			+'<li><span>还款：</span><input type="text" class="price" value="'+price+'" name="consignment_period['+i+'][price]"></li>'
			+'<div class="clear"></div>'
			+'</ul>';
		}
		if(val == 1){
			$('#consignment_period_list').html(html);
		}else{
			$('#consignment_period_list').html('');
		}
	}
	period4();
	$("#consignment_period_num").change(period4);
	$("input[name='consignmentPay']").click(period4);
	//代销货填写分期价格
	$('#consignment_period_list .price').live('change',function(){
		$.price = 0;
		consignmentTotal = $('#consignmentTotal').html();
		$('#consignment_period_list .price').each(function(index){
			consignmentTotal = consignmentTotal - $(this).val();
			consignmentTotal = toDecimal(consignmentTotal);
			if(!$(this).val() || $(this).val() == '0.00'){
				$(this).val(consignmentTotal);
				$.price = $.price + parseFloat(consignmentTotal);
				consignmentTotal = '';
			}else{
				$.price = $.price + parseFloat($(this).val());
			}
		})
		if($.price != $('#consignmentTotal').html()){
			alert('分期金额和产品价格匹配不上');
		}
	})
	//修改代销货单价
	function countPrice5(type){
		$.price = 0.00;
		$('.consignment .list').each(function(index, element) {
			if(type == 1){
				price = $('.consignment .list:eq('+index+') .unitPrice').val();
				num = $('.consignment .list:eq('+index+') .goods_num').val();
				if(num < 0){
					alert('数量不能小于0');
					$('.consignment .list:eq('+index+') .goods_num').val(1);
				}
				price2 = toDecimal(price*num);
				$('.consignment .list:eq('+index+') .goods_price').val(price2);
				$.price += parseFloat(price2);
			}else if(type == 2){
				price = $('.consignment .list:eq('+index+') .goods_price').val();
				num = $('.consignment .list:eq('+index+') .goods_num').val();
				price2 = parseFloat(price/num);
				$('.consignment .list:eq('+index+') .unitPrice').val(price2);
				$('.consignment .list:eq('+index+') .goods_price').val(price);
				$.price += parseFloat(price);
			}
		});
		$('#consignmentTotal').html(toDecimal($.price));
	}
	$('.consignment .goods_num').on('input',function(e){countPrice5(1)});
	$('.consignment .goods_price').on('input',function(e){countPrice5(2)});
//	//珠宝产品和代销货选择规格后价格改变
//	$('.sxgg select').live('change',function(){
//		goods_id = $(this).parents('tr').attr('goods-id');
//		$('.sxgg select').each(function(index) {
//			val = $(this).val();
//			if(index == 0){$.sel = val;}
//			else{$.sel += ','+val;}
//		})
//		//获取价格，库存
//		$.get($.getWebGoodsSpecification,{sel:$.sel,goods_id:goods_id},function(data){
//			$('tr[goods-id="'+data.goods_id+'"] .goods_price').html(data.goods_price);
//			$('tr[goods-id="'+data.goods_id+'"] .goods_number').html(data.goods_num);
//			$('tr[goods-id="'+data.goods_id+'"] .goods_price').val(data.goods_price);
//			countPrice4();
//			countPrice5();
//		})
//	})

	$('input[name="confirmAction"]').click(function(form){	
		var k = 0;
		var i = 0;	
		if($("input[name='luozuanPay']").length > 0){
			if($("input[name='luozuanPay']:checked").val() == 1){				
			    i = $("#luozuan_period_num").val();
				k = 0;			
				while(k<i) 
				{								
					if($("input[name='luozuan_period[" + k + "][time]']").val() == '' || $("input[name='luozuan_period[" + k + "][price]']").val() == ''){
						alert('证书货做期支付信息不完整！');
						return false;
					}
					k=k+1;
				} 
			}
		}

		if($("input[name='sanhuoPay']").length > 0){
			if($("input[name='sanhuoPay']:checked").val() == 1){				

				i = $("#sanhuo_period_num").val();
				k = 0;			
				while(k<i) 
				{						
					if($("input[name='sanhuo_period[" + k + "][time]']").val() == '' || $("input[name='sanhuo_period[" + k + "][price]']").val() == ''){
						alert('散货做期支付信息不完整！');
						return false;
					}
					k=k+1;
				} 
			}
		}

		if($("input[name='goodsPay']").length > 0){
			if($("input[name='goodsPay']:checked").val() == 1){				

				i = $("#goods_period_num").val();
				k = 0;			
				while(k<i) 
				{						
					if($("input[name='goods_period[" + k + "][time]']").val() == '' || $("input[name='goods_period[" + k + "][price]']").val() == ''){
						alert('珠宝产品做期支付信息不完整！');
						return false;
					}
					k=k+1;
				} 
			}
		}

		if($("input[name='consignmentPay']").length > 0){
			if($("input[name='consignmentPay']:checked").val() == 1){				

				i = $("#consignment_period_num").val();
				k = 0;			
				while(k<i) 
				{						
					if($("input[name='consignment_period[" + k + "][time]']").val() == '' || $("input[name='consignment_period[" + k + "][price]']").val() == ''){
						alert('代销货产品做期支付信息不完整！');
						return false;
					}
					k=k+1;
				} 
			}
		}
	});

	$("input[name='exportOrder']").click(function(){
		var _order_id = $(this).attr('data');
		window.location.href = '/Admin/Order/orderExport/order_id/'+_order_id;
	});
	
})