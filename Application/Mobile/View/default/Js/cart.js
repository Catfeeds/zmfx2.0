function cartClear(){
	$.post("/Order/cartClear",refCartOperation);    	
}
function refCartOperation(data){ 
	location.reload(); 
}
function cartDelete(id){
	$.post("/Order/cartDelete",{id:id},refCartOperation);    
}
function changeGoodsNumber(obj,cartId,goods_type){

	var stock = $("input[type=hidden][name="+cartId+"_stock]").val().trim();
	var buy_quantity; 
		//散货
	stock = parseFloat(stock);
	buy_quantity = parseFloat(obj.value);		
			
	if(buy_quantity <= 0){
		alert('购买数量必须大于0');
		buy_quantity = $("input[type=hidden][cartId="+cartId+"]").attr("default_val");
	}else if(buy_quantity > stock){
		alert('购买数量大于库存！现库存为'+stock);
		buy_quantity = $("input[type=hidden][cartId="+cartId+"]").attr("default_val");
		obj.value = $("input[type=hidden][cartId="+cartId+"]").attr("default_val");
	}else{	
		obj.value = Math.round(obj.value*1000)/1000;    //重量保持在小数点后三位
		$.post("/Order/changeGoodsNumber",{goods_number:obj.value,cartId:cartId,goods_type:goods_type},refCartOperation);
	}

	// if(obj.value <= 0){
	// 	alert('散货重量不能小于或等于 0 CT');
	// 	//obj.value = Math.abs(obj.value);
	// }else{ 
	//     obj.value = Math.round(obj.value*100)/100;    //重量保持在小数点后两位
	// 	$.post("/Order/changeGoodsNumber",{goods_number:obj.value,cartId:cartId,goods_type:goods_type},refCartOperation);
	// }
}
function confirmOrder(){
	var num = 0;
	$("input[name='goods_number']").each(function(){
		if($(this).val() <= 0){			
			num = num + 1;
			return false;		
		}
	});
	if(num > 0){
		alert('散货重量不能小于或等于 0 CT'); 
		return false;
	}

	if($("#total").html() != 0) {   
		$.post("/User/checkUserLogin",function(data){   
		   if(data.status == 1){
				window.location.href="/Order/orderConfirm";
		   }else{
				alert("请先登录，再提交订单");
				window.location.href="/Public/login";
		   }    
		});   
	}else{
		alert("请先添加货品再提交订单");
		return false;
	}
	
}

function submitOrder(){
	if($("input[name='address_id']:checked").val() == null){
		alert("请选择收货地址");
		return ;
	}
	var border_first_two='';
	var DistributionId='';
	$(".date a").each(function(i){	
		border_first_two = $(this).hasClass('hasborder'); 
		if(border_first_two){
			 DistributionId=$(this).attr('attr_id');
		}
	});	

	$.post('/Order/orderSubmit',{address_id:$("input[name='address_id']:checked").val(),id:$('input[name="id"]').val(),DId:DistributionId},function(data){
		  if(data.status == 1){
  	   		alert(data.info);
  	   		window.location.href = "/Order/orderComplete";
  	   }else{
  	   		alert(data.info);
  	   }
	});                                               
		
}