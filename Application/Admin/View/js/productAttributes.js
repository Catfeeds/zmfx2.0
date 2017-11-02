$(function(){
	//添加属性
	$('.layer-attr').click(function(){
		href = $(this).attr('href');
		$.get(href,{},function(data){
			layer.open({
			    type: 1,
			    title:'添加属性',
			    area: ['780px', '450px'],
			    fix: false, //不固定
			    maxmin: false,
			    content: data
			});
		})
		return false;
	})
	//查看属性值列表
	$('.layer-attr-value').click(function(){
		href = $(this).attr('href');
		$.get(href,{},function(data){
			layer.open({
			    type: 1,
			    title:'属性值列表',
			    area: ['600px', '500px'],
			    fix: false, //不固定
			    maxmin: false,
			    content: data,
				btn: ['添加属性值'],
				yes: function(index, layero){
        			//添加属性值
        			attr_id = $('input[name="attr_id"]').val();
        			$.get($.productAttributeValueInfo,{attr_id:attr_id},function(data){
        				$.AttrValueKuang = layer.open({
        					type: 1,
        					content: data,
        					area: ['500px', '300px'],
        					fix: false,
			    			maxmin: false,
			    			title: '添加属性值'
        				})
        			});
			    },cancel: function(index){
			        window.location.reload();
			    }
			});
		})
		return false;
	})
	//添加属性值不跳转
	$('#productAttributeValueInfo').live('click',function(){
        attr_id = $('input[name="attr_id"]').val();
        attr_value_name = $('input[name="attr_value_name"]').val();
		$.post($.productAttributeValueInfo,{attr_id:attr_id,attr_value_name:attr_value_name},function(data){
			if(data.status == 0){
				alert(data.info);
			}else{
				layer.close($.AttrValueKuang);
				html = '<tr><td class="tl pl20">'+data.attr_value_id+'</td><td>'+data.attr_value_name+'</td><td>'+data.attr_code+'</td></tr>';
				$('.attr-list').before(html);
			}
		})
		return false;
	})
	//是否是手填值
	$('input[name="input_type"]').live('click',function(){
		val = $(this).val();
		if(val == 3){
			$('#input_mode_div').show();
		}else{
			$('#input_mode_div').hide();
		}
	})
	//切换是否筛选
	$('tr>td>span.whether').click(function(){
		id = $(this).attr('data-id');
		is_filter = $(this).hasClass('yes');
		if(is_filter)is_filter = 0;
		else is_filter = 1;
		_self = this;
		$.get($.setAttributeIsFilter,{attr_id:id,is_filter:is_filter},function(data){
			if(data){
				$(_self).toggleClass('yes');
			}else{
				alert('修改失败');
			}
		})
	})
})