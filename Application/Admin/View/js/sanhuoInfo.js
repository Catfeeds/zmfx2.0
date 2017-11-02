$(function(){
	//鼠标点击选中框
	$('ul li input[type="checkbox"]').live('click',function(){
		id = $(this).attr('id').split('_')[1];
		type = $(this).attr('id').split('_')[0];
		c = $(this).attr('checked');
		if(c == undefined){
			$('#i_'+type+'_'+id).hide();
			$('#b_'+type+'_'+id).html('');
			$('#i_'+type+'_'+id+' input').val('');
		}else{
			$('#i_'+type+'_'+id).show();
			$('#i_'+type+'_'+id+' input').focus();
		}
	});
	//弹出框确认事件
	$('button').live('click',function(){
		type = $(this).parents('div').attr('id').split('_')[1];
		id = $(this).parents('div').attr('id').split('_')[2];
		num = $('#i_'+type+'_'+id+' input').val();
		total = $('input[name="goods_weight"]').val();
		if(total > 0){
			bl = Percentage(num,total);
			if(num > 0){
				$('#b_'+type+'_'+id).html(bl);
				$('#i_'+type+'_'+id).hide();
			}else{
				alert('库存重量需要大于0');
			}
		}else{
			alert('请先填写总库存重量!');
		}
	});
	//鼠标点击比例
	$('ul li span.bl').live('click',function(){
		type = $(this).attr('id').split('_')[1];
		id = $(this).attr('id').split('_')[2];
		$('#i_'+type+'_'+id).show();
	});
	//把价格循环出来
	$('ul li div.input').each(function(index) {
		type = $(this).attr('id').split('_')[1];
		id = $(this).attr('id').split('_')[2];
		num = $(this).children('input[type="text"]').val();
		total = $('input[name="goods_weight"]').val();
		if(total > 0){
			bl = Percentage(num,total);
			if(num > 0){
				$('#b_'+type+'_'+id).html(bl);
				$('#'+type+'_'+id).attr('checked',true);
			}else{
				$('#'+type+'_'+id).attr('checked',false);
			}
		}
    });
	//获取散货或者筛号
	$('select[name="weights"]').change(function(){
		name = $(this).val();
		$.get($.getSanhuoWeights,{name:name},function(data){
			html = '';
			for($i=0;$i<data.length;$i++){
				html += '<li class="t4">'+
            		 '<div class="input" id="i_weights_'+data[$i]['weights_id']+'">'+
            		 '<input type="text" value="" name="c_weights['+data[$i]['weights_id']+'][bl]" placeholder="库存重量">'+
            		 '<button type="button">确定</button>'+
                     '</div>'+
                     '<input type="checkbox" id="weights_'+data[$i]['weights_id']+'" name="c_weights['+data[$i]['weights_id']+'][name]" value="'+data[$i]['weights_name']+'" >'+
                     '<span class="t_weights_'+data[$i]['weights_id']+'">'+data[$i]['weights_name']+'</span>'+
                     '<span id="b_weights_'+data[$i]['weights_id']+'" class="bl"></span>'+
                   '</li>';
			}
			html += '<div class="clear"></div>';
			$('#weights2').html(html);
		})
	});
})