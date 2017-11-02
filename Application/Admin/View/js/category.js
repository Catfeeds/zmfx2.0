$(function(){
	//打开添加分类属性
	$('.addCatAttr').click(function(){
		//type 1为成品属性;2为成品规格;3为金工石属性   
		type = parseInt($(this).attr('data-type'));
		href = $(this).attr('href');
		//遍历出对应类型的已选中的属性
		attr_selected_arr = new Array();
		$(".specDivContent_"+type+" input[type=hidden]").each(function(){
			if ($(this).attr("value"))
				attr_selected_arr.push($(this).attr("value"));
		});
		
		$.get(href,{type:type},function(data){
			layer.open({
			    type: 1,
			    title:'添加分类属性',
			    area: ['600px', '322px'],
			    fix: false, //不固定
			    maxmin: false,
			    content: data,
			    btn: ['确定添加'],
			    yes: function(index, layero){
					layer.close(index);
    			},cancel: function(index){
    				layer.close(index);
				}
			});
		})
		return false;
	})
	
	//选中属性
	$('#specDivContent label input[type=checkbox]').live('click',function(){
		var is_checked = $(this).prop('checked');
		//如果是选中
		if(is_checked){
			var attr_value_id = $(this).attr('data-value');
			var attr_value_name = $(this).attr('data-name');
			//html = '<div data-id="'+attr_value_id+'" style="float:left;padding-right:3px;"><input type="hidden" name="attr['+type+'][]" value="'+attr_value_id+'" /><button onclick="return false">'+attr_value_name+'</button></div>';
			html = '<li data-id="'+attr_value_id+'">'+attr_value_name+'<input type="hidden" name="attr['+type+'][]" value="'+attr_value_id+'" /></li>';
			switch(type){
				case 1:
					$(".specDivContent_1 a").before(html);
					break;
				case 2:
					//规格属性最多只能选择3个
					if($('#specDivContent label input[type=checkbox]:checked').length>=3){
						$('#specDivContent label input[type=checkbox]').each(function(){
							if(!($(this).prop('checked'))) $(this).attr('disabled','disabled');
						});
					}
					$(".specDivContent_2 a").before(html);
					break;
				case 3:
					$(".specDivContent_3 a").before(html);
					break;
			}
			$('.gbin1-list').sortable('destroy');
			$('.gbin1-list').sortable();
			
		}else{
			//如果不是选中
			attr_value_id = $(this).attr('data-value');
			switch(type){
			case 1:
				$(".specDivContent_1 li[data-id="+attr_value_id+"]").remove();
				break;
			case 2:
				$(".specDivContent_2 li[data-id="+attr_value_id+"]").remove();
				break;
			case 3:
				$(".specDivContent_3 li[data-id="+attr_value_id+"]").remove();
				break;
			}
			if($('#specDivContent label input[type=checkbox]:checked').length<3)
				$('#specDivContent label input[type=checkbox]').each(function(){$(this).removeAttr('disabled');});
		}
	})
	
	//只有添加非顶级分类时才显示属性选择
	$('#selectCat').live('change',function(){
		if(parseInt(this.value) <=0){
			$('.specDiv').css('display','none');
		}else{
			$('.specDiv').css('display','block');
		}
	}).change();
})