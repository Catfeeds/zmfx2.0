// 订单添加页JS
// 创建时间 2015/06/06 15:57
// Author adcbguo
$(function(){
	//根据业务员获取客户列表
	$('select[name="business_id"]').change(function(){
		business_id = $(this).val();
		if(business_id > 0){
			$.get($.getBusinessUserList,{id:business_id},function(data){
				html = '<option value="0">请选择客户</option>';
				for(i=0;i<data.length;i++){
					html += '<option value="'+data[i].uid+'">'+data[i].username+'</option>';
				}
				$('select[name="uid"]').html(html);
			})
		}
	})
	//根据客户id获取收货地址和方式
	$('select[name="uid"]').change(function(){
		uid = $(this).val();
		if(uid > 0){
			$.get($.getUserAddress,{uid:uid},function(data){
				html = '';
				for(i=0;i<data.length;i++){
					if(data[i].is_default == 1){checked = 'checked';}else{checked = '';}
					html += '<li><label><input type="radio" name="address_id" value="'+data[i].address_id+'"'+checked+'><span>'+data[i].country_name+data[i].province_name+data[i].city_name+data[i].district_name+data[i].address+'</span><span>'+data[i].name+'</span><span>'+data[i].phone+'</span><div class="clear"></div></label></li>';
				}
				$('.addressList').html(html);
			})
		}
		$(".luozuan tr").each(function(){
			var id = $(this).data('id');
			var certificate_number = $('tr[data-id="'+id+'"] .certificate_number').val();
			
			if(certificate_number!=undefined && certificate_number!= '' && id>0){
			 	$.ajax({
			 	url: $.orderAddGoodsLuozuan,
					data: {certificate_number:certificate_number,  uid:uid},
					success: function(data){					
						$('tr[data-id="'+id+'"] .goods_price').val(data.price);
						c4  = data.weight+'/'+data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_number').html(data.goods_number);
						countPrice('.luozuan');					
					}
				});

			}
		});

		$(".sanhuo tr").each(function(){
			var id = $(this).data('id');			
			var goods_sn = $("input[name='sanhuo["+id+"][goods_sn]']").val();
			var goods_id = $("input[name='sanhuo["+id+"][goods_id]']").val();
			if(goods_sn!=undefined && goods_sn!= '' && id>0){
				$.ajax({
					url: $.orderAddGoodsSanhuo,
					data: {goods_sn:goods_sn, goods_id:goods_id, uid:uid},
					success: function(data){
						
						$('tr[data-id="'+id+'"] .goods_sn').val(data.goods_sn);
						$('tr[data-id="'+id+'"] .goods_id').val(data.goods_id);
						$('tr[data-id="'+id+'"] .goods_price').val(data.xiaoshou_price);
						c4  = data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .goods_price').html(data.xiaoshou_price);
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_weight').html(data.goods_weight);
						countPrice3('.sanhuo');						
					},
					
				});
			}
		});
	})
	/*裸钻计算小计价格和总价*/
	function countPrice(type){
		$.price = 0.00;
		$(type+' .list').each(function(index, element) {
			price = parseFloat($(type+' .list:eq('+index+') .goods_price').val(),2);
			$(this).children('.goods_price').html(price);
			$.price += price;
		});
		
		
		$(type+'Total').html(toDecimal($.price));
	}
	$('.luozuan .goods_price').live('change',function(){
		countPrice('.luozuan');
	})
	/*数量变化散货计算小计价格和总价*/
	function countPrice2(type){
		$.price = 0.00;
		$(type+' .list').each(function(index, element) {
			price = $(type+' .list:eq('+index+') .goods_price').html();
			num = $(type+' .list:eq('+index+') .goods_num').val();
			prices = price*num;
			$(type+' .list:eq('+index+') .goods_price').val(prices);
			$.price += prices;
		});
		$(type+'Total').html(toDecimal($.price));
	}
	$('.sanhuo .goods_num').live('change',function(){
		countPrice2('.sanhuo');
	})
	/*小计价格变化散货计算总价*/
	function countPrice3(type){
		$.price = 0.00;
		$(type+' .list').each(function(index, element) {
			$.price += parseFloat($(type+' .list:eq('+index+') .goods_price').val());
		});
		$(type+'Total').html(toDecimal($.price));
	}
	$('.sanhuo .goods_price').live('change',function(){
		countPrice3('.sanhuo');
	})
	/*成品货计算价格*/
	function countPrice4(){
		$.price = 0.00;
		$('.product .list').each(function(index, element) {
			price = parseFloat($('.product .list:eq('+index+') .goods_price').html());
			num = $('.product .list:eq('+index+') .goods_num').val();
			price2 = price*num;
			$('.product .list:eq('+index+') .goods_price').val(price2);
			$.price += price*num;
		});
		$('.productTotal').html(toDecimal($.price));
	}
	$('.product .goods_price,.product .goods_num').live('change',function(){
		countPrice4();
	})
	/*添加订单裸钻*/
	$('#orderAddGoodsLuozuan').click(function(){
		$.get($.orderAddGoodsLuozuan,'',function(data){
			$('.goodsLuozuanList').after(data);
		})
	})
	
	/*
	$('.certificate_number').live('change',function(){
		id = $(this).parents('tr').attr('data-id');		
		certificate_number = $(this).val();
		if(certificate_number != ' '){
			$.ajax({
				url: $.orderAddGoodsLuozuan,
				data: {certificate_number:certificate_number},
				success: function(data){
					if(data == null){
						alert('证书号填写错误或者没有这个裸钻');
					}else{
						$('tr[data-id="'+id+'"] .goods_price').val(data.price);
						c4  = data.weight+'/'+data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_number').html(data.goods_number);
						countPrice('.luozuan');
					}
				},
				error:function(XMLHttpRequest,info){
					alert('证书号填写错误或者没有这个裸钻');
				}
			});
		}
	})
	*/
	
	$('.dropdown_luozuan span').live('click',function(){
		var id = $(this).parents('tr').attr('data-id');
		var sub_id;			
		var certificate_number = $("input[name='luozuan["+id+"][certificate_number]']").val();
		var uid      = $("select[name='uid']").val();

		var sub_certificate_number;
		if(certificate_number != ''){	
			$('.goodsLuozuanList ~ tr').each(function(index, element) {			 
			 	sub_id = $('.goodsLuozuanList ~ tr:eq('+index+')').attr('data-id');						 		
				sub_certificate_number = $("input[name='luozuan["+sub_id+"][certificate_number]']").val();	
			 	 if((certificate_number == sub_certificate_number) && (sub_id != id) ){
			 	 	alert('证书号重复，请重新输入！');
			 	 	
			 	 	$("input[name='luozuan["+id+"][certificate_number]']").focus();				 	 	
			 	 	$.stop();
			 	 }				
			});

			$.ajax({
				url: $.orderAddGoodsLuozuan,
				data: {certificate_number:certificate_number, order_leixing:$.leixing, uid:uid},//$.leixing在order_jiehuodan_common.js中
				success: function(data){
					if(data == null){
						alert('证书号填写错误或者没有这个裸钻');
					}else{
						$('tr[data-id="'+id+'"] .goods_price').val(data.xiaoshou_price);
						c4  = data.weight+'/'+data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_number').html(data.goods_number);
						countPrice('.luozuan');
					}
				},
				error:function(XMLHttpRequest,info){
					alert('证书号填写错误或者没有这个裸钻');
				}
			});
		}
	})
	$('.orderDelGoodsLuozuan').live('click',function(){
		id = $(this).attr('data-id');
		$('tr[data-id="'+id+'"]').remove();
		countPrice('.luozuan');
	})
	/*添加订单散货*/
	$('#orderAddGoodsSanhuo').click(function(){
		$.get($.orderAddGoodsSanhuo,'',function(data){
			if(data.info != undefined){
				layer.open({ content: data.info});
			}else{
				$('.goodsSanhuoList').after(data.html);
			}
		})
	})
	/*
	$('.goods_sn').live('change',function(){
		id = $(this).parents('tr').attr('data-id');
		goods_sn = $(this).val();
		goods_id = $("input[name='sanhuo["+id+"][goods_id]']").val();
		if(goods_sn != ''){
			$.ajax({
				url: $.orderAddGoodsSanhuo,
				data: {goods_sn:goods_sn, goods_id:goods_id},
				success: function(data){
					if(data == null){
						alert('散货产品编号填写错误或者没有这个编号');
					}else{
						$('tr[data-id="'+id+'"] .goods_sn').val(data.goods_sn);
						$('tr[data-id="'+id+'"] .goods_id').val(data.goods_id);
						$('tr[data-id="'+id+'"] .goods_price').val(data.xiaoshuo_price);
						c4  = data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .goods_price').html(data.xiaoshuo_price);
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_weight').html(data.goods_weight);
						countPrice3('.sanhuo');
					}
				},
				error:function(XMLHttpRequest,info){
					alert('散货产品编号填写错误或者没有这个编号');
				}
			});
		}
	})
	*/
	
	$('.dropdown_sanhuo span').live('click',function(){		
		id = $(this).parents('tr').attr('data-id');
		var goods_sn = $("input[name='sanhuo["+id+"][goods_sn]']").val();
		var goods_id = $("input[name='sanhuo["+id+"][goods_id]']").val();
		var uid      = $("select[name='uid']").val();
		if(goods_sn != ''){
			$.ajax({
				url: $.orderAddGoodsSanhuo,
				data: {goods_sn:goods_sn, goods_id:goods_id, uid:uid},
				success: function(data){
					if(data == null){
						alert('散货产品编号填写错误或者没有这个编号');
					}else{
						$('tr[data-id="'+id+'"] .goods_sn').val(data.goods_sn);
						$('tr[data-id="'+id+'"] .goods_id').val(data.goods_id);
						$('tr[data-id="'+id+'"] .goods_price').val(data.xiaoshou_price);
						c4  = data.color+'/'+data.clarity+'/'+data.cut;
						$('tr[data-id="'+id+'"] .goods_price').html(data.xiaoshou_price);
						$('tr[data-id="'+id+'"] .c4').html(c4);
						$('tr[data-id="'+id+'"] .goods_weight').html(data.goods_weight);
						countPrice3('.sanhuo');
					}
				},
				error:function(XMLHttpRequest,info){
					alert('散货产品编号填写错误或者没有这个编号');
				}
			});
		}
	})
	$('.orderDelGoodsSanhuo').live('click',function(){
		id = $(this).attr('data-id');
		$('tr[data-id="'+id+'"]').remove();
		countPrice2('.sanhuo');
	})
	
	/*添加订单成品产品*/
	$('#orderAddProduct').click(function(){
		$.get($.orderAddProduct,'',function(data){
			$('.productList').after(data);
		})
	})
	//删除
	$('.orderDelProduct').live('click',function(){
		id = $(this).attr('data-id');
		$('tr[data-id="'+id+'"]').remove();
	})
	//填写产品编号后
	$('.goods_sn2').live('change',function(){
		id = $(this).parents('tr').attr('data-id');
		goods_sn = $(this).val();
		if(goods_sn != ' '){
			$.ajax({
				url: $.orderAddProduct,
				data: {goods_sn:goods_sn,SJID:id},
				success: function(data){
					if(data == null){
						leyer.open({content:'成品货编号错误！'});
					}else{
						$('tr[data-id="'+id+'"] .goods_price').val(data.goods_price);
						$('tr[data-id="'+id+'"] .goods_price').html(data.goods_price);
						$('tr[data-id="'+id+'"] .goods_number').html(data.goods_number);
						$('tr[data-id="'+id+'"] .sxgg').html(data.specification);
						$('tr[data-id="'+id+'"]').attr('goods-id',data.goods_id);
						countPrice4();
					}
				},
				error:function(XMLHttpRequest,info){
					alert('成品货产品编号填写错误或者没有这个编号');
				}
			});
		}
	})
	//选择规格后
	$('.sxgg select').live('change',function(){
		goods_id = $(this).parents('tr').attr('goods-id');
		$('.sxgg select').each(function(index) {
			val = $(this).val();
			if(index == 0){$.sel = val;}
			else{$.sel += ','+val;}
		})
		//获取价格，库存
		$.get($.getWebGoodsSpecification,{sel:$.sel,goods_id:goods_id},function(data){
			$('tr[goods-id="'+data.goods_id+'"] .goods_price').html(data.goods_price);
			$('tr[goods-id="'+data.goods_id+'"] .goods_number').html(data.goods_num);
			$('tr[goods-id="'+data.goods_id+'"] .goods_price').val(data.goods_price);
			countPrice4();
		})
	})
	
	//点击添加代销货
	$('#orderAddConsignment').click(function(){
		$.get($.orderAddConsignment,function(data){
			$('.consignmentList').after(data);
		})
		return false;
	})
	//删除代销货
	$('.orderDelGoodsConsignment').live('click',function(){
		id = $(this).attr('data-id');
		$('tr[data-id="'+id+'"]').remove();
	})
	//填写产品编号后
	$('.goods_sn3').live('change',function(){
		goods_sn = $(this).val();
		var id = $(this).parents('tr').attr('data-id');
		$.post($.orderAddConsignment,{goods_sn:goods_sn},function(data){
			if(data.status){
				$('tr[data-id="'+id+'"]').before(data.info);
				$('tr[data-id="'+id+'"]').remove();
			}else{
				alert(data.info);
			}
		})
	})
	$('select[name=material],select[name=deputystone]').live('change',function(){
		data = {};
		data.goods_sn = $('.goods_sn3').val();
		data.material = $('select[name=material]').val();
		data.deputystone = $('select[name=deputystone]').val();
		data.luozuan = $('select[name=luozuan]').val();
		//选中不同的
		$.get($.orderAddConsignment,data,function(data){
			alert(data);
		})
	})
	
	//提交表单，判断收货地址
	$('.order').submit(function(form){
		address_id = $('input[name="address_id"]').val();
		if(!address_id){
			alert('请选择客户收货地址');
			return false;
		}
		return true;
	})
	
	
});

	function showdiv(mingchen, sjid)
    {	    	
		$(".dropdownBorder").parent().css("position", "relative");	
        $('#' + mingchen + '_' + sjid).css({display:'block'});
    }
    function hiddendiv(mingchen, sjid)
    {
        $('#' + mingchen + '_' + sjid).css({display:'none'});
    }
    function xuanzhong(mingchen, sjid , title)
    {
        if( mingchen == 'luozuan')
        {
            $("input[name='" + mingchen + "["+ sjid + "][certificate_number]']").attr("value", title);
        }
        else if(mingchen == 'sanhuo')
        {
			var sanhuo_info = title.split(':');
            $("input[name='" + mingchen + "["+ sjid + "][goods_sn]']").attr("value", sanhuo_info[0]);
			$("input[name='" + mingchen + "["+ sjid + "][goods_id]']").attr("value", sanhuo_info[1]);
        }		
        $('#' + mingchen + '_' + sjid).css({display:'none'});
        
    }
	function keyup(key, sjid, mingchen)
    {
    	
        $.key = key;
        if(mingchen == 'luozuan')
        {
            $.luozhuan_xiala_ajax_url = "/Admin/Public/orderAddGoodsLuozuanXiala.html";//添加订单页面增加证书 
            $.get($.luozhuan_xiala_ajax_url,{certificate_number:$.key},function(data){
                html = '<div class="dropdown dropdown_luozuan" style="overflow: hidden; height: 100%; z-index: 999; display: block;">';
                if(data == ''){
                    html += '<span>没有该证书货</span>';  
                }else{
					
					for(i=0;i<data.length;i++){     
						html += '<span  onclick="xuanzhong(\'luozuan\', ' + sjid + ', \'' + data[i].certificate_number+'\')">' + data[i].certificate_number +   '</span>';  
					}
					
                }
                html += '</div>';
                $('#' + mingchen + '_' + sjid ).html(html);
            })

        }else if(mingchen == 'sanhuo'){			
            $.luozhuan_xiala_ajax_url = "/Admin/Public/orderAddGoodsSanhuoXiala.html";//添加订单页面增加散货  //public 可以跳过权限验证
            $.get($.luozhuan_xiala_ajax_url,{goods_sn:$.key},function(data){																	  
                html = '<div class="dropdown dropdown_sanhuo" style="overflow: hidden; height: 100%; z-index: 999; display: block; text-align:left;">';
                if(data == ''){
                    html += '<span>没有该散货</span>';   
                }else{							
					for(i=0;i<data.length;i++){     
						html += '<span  onclick="xuanzhong(\'sanhuo\', ' + sjid + ', \'' + data[i].goods_sn+ ':' + data[i].goods_id+'\')">' + data[i].goods_sn + '-'+data[i].goods_weight+  '</span>';  
					}					
                }
                html += '</div>';
                $('#' + mingchen + '_' + sjid ).html(html);
				
            })
        }                     
    }
