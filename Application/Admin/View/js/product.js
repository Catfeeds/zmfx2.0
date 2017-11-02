$(function(){

	// 记录图片上传的次数
	var imgTotal = 1;
	// 产品图片上传
	function ajaxFileUpload() {
		if ($('.thumbImg').length > 5) {alert('一个产品只能有5张图片！');return false;}
		$.ajaxFileUpload({
			url: $.productByOne,
			secureuri: false,
			fileElementId: 'firstImg',
			dataType: 'json',
			success: function (data, status) {
				var imgHidden;
				if (data.success) {
					html = '<div class="thumbImg">'
							  +'<a value="'+data.data.images_id+'" id="deleteImg"><img src="/Application/Admin/View/img/mail_delete.png"/ width="25"></a>'
							  +'<input type="hidden" value="'+data.data.images_id+'" name="images[]">'
							  +'<img src="/Public'+data.data.small_path.substr(1)+'" width="100%" />'
						  +'</div>';
					$('#firstImg').before(html);
					imgTotal++;
				} else {
					if(data.msg){
						alert(data.msg);
					}else{
						alert('上传失败，请换过一张图片上传!');
					}
				}
				$('#firstImg input').change(function () {ajaxFileUpload();});
			},error: function (data, status, e) {alert('图片上传出错！');}
		});
	}
	// 文件上传按钮绑定事件
	$('#firstImg input').change(function () {ajaxFileUpload();});
	// 为缩略图绑定删除事件
	$('a#deleteImg').live('click', function (e) {
		var imgDIv = $(this), images_id = imgDIv.attr('value');
		$.ajax({
			url: $.productByOne,
			type: 'POST',
			data: {'images_id': images_id},
			dataType: 'json',
			success: function (data) {
				if (data.success) {imgDIv.closest('.thumbImg').remove();imgTotal--;}
				else {alert(data.msg);}
			},error: function (data) {alert('异常！');}
		});
	});

	function func_display_sd(){
		$.ajax({
			url: '/Admin/Goods/symbolDesign',
			type: 'POST',
			dataType: 'json',
			success: function (data) {	
				if (data.code == '1') {
					data = data.data;
					var html = '',html2='';
					for(var i in data){
						html += '<li><img src="' + data[i].images_path + '" value="' + data[i].sd_id + '" /></li>';
						if( i < 2 ){
							html2 += '<img src="'+data[i].images_path+'" />';
						}
					}
					html += '<div class="clear"></div>';
					$('#js_sd_list').html(html);
					$('.iconlist').html(html2);
					$('#js_sd_list li').bind('click', function(){
            			$('#js_sd_list li img').removeClass('imgb-border');
            			$(this).find('img').addClass('imgb-border');
            		});
				}else{
					html = '<div class="clear"></div>';
					$('#js_sd_list').html(html);
					$('.iconlist').html('');
					$('#js_sd_list li').bind('click', function(){
            			$('#js_sd_list li img').removeClass('imgb-border');
            			$(this).find('img').addClass('imgb-border');
            		});
				}
			},error: function (data) {alert('异常!');}
		});
	}

	//个性字符上传
	function SDUpload() {
		$.ajaxFileUpload({
			url: '/Admin/Goods/symbolDesignAdd',
			secureuri: false,
			fileElementId: 'sd_setting',
			dataType: 'json',
			success: function (data, status) {
				if (data.code == '1') {
					func_display_sd();
				} else {
					if(data.msg){
						alert(data.msg);
					}else{
						alert('上传失败，请换过一张图片上传!');
					}
				}
				$('#sd_setting').change(function () { SDUpload(); });
			},error: function (data, status, e) {alert('个性字符上传出错！');$('#sd_setting').change(function () { SDUpload(); });}
		});
	}
	// 文件上传按钮绑定事件
	$('#sd_setting').change(function () { SDUpload(); });
	// 为缩略图绑定删除事件
	$('#js_sd_delete').live('click', function (e) {
		var imgDIv = $('#js_sd_list .imgb-border'), sd_id = imgDIv.attr('value');
		if( sd_id ){
			$.ajax({
				url: '/Admin/Goods/symbolDesignDel',
				type: 'POST',
				data: {'sd_id': sd_id},
				dataType: 'json',
				success: function (data) {	
					if ( data.code == '1' ) {
						func_display_sd();
					}
				},error: function (data) { alert('异常！'); }
			});
		}
	});
	func_display_sd();
	// UE编辑器初始化
	window.UEDITOR_CONFIG.initialFrameHeight = '700';//编辑器的高度
	window.UEDITOR_CONFIG.initialFrameWidth = '100%';//编辑器的高度
	UE.getEditor('content');
	//添加属性页面JS
	$('select[name="attribute_type"]').change(function(){
		val  = $(this).val();
		if(val == 2){
			$.get($.getAttributeList,function(data){
				if(data.status == 0){
					alert(data.info);
				}else{
					html = '<option value="0">选择上级属性分类</option>';
					for (var i = 0; i < data.length; i++) {
						html += '<option value="'+data[i].attribute_id+'">'+data[i].attribute_name+'</option>';
					}
					$('#attribute_type_1 select').html(html);
					$('#attribute_type_1').show();
					$('#attribute_mode').show();
				}
			})
			$('#attribute_type_2').hide();
			$('#attribute_type_1 select').unbind('change');
		}else if(val == 3){
			$.get($.getAttributeList,function(data){
				if(data.status == 0){
					alert(data.info);
				}else{
					html = '<option value="0">选择上级属性分类</option>';
					for (var i = 0; i < data.length; i++) {
						html += '<option value="'+data[i].attribute_id+'">'+data[i].attribute_name+'</option>';
					}
					$('#attribute_type_1 select').html(html);
					$('#attribute_type_1').show();
				}
			})
			$('#attribute_type_1 select').change(function(){
				id = $('#attribute_type_1 select').val();
				$.get($.getAttributeList,{pid:id},function(data){
					if(data.status == 0){
						alert(data.info);
					}else{
						html = '<option value="0">选择上级属性</option>';
						for (var i = 0; i < data.length; i++) {
							html += '<option value="'+data[i].attribute_id+'">'+data[i].attribute_name+'</option>';
						}
						$('#attribute_type_2 select').html(html);
						$('#attribute_type_2').show();
					}
				})
			})
			$('#attribute_mode').hide();
		}else{
			$('#attribute_type_1').hide();
			$('#attribute_type_2').hide();
			$('#attribute_mode').hide();
		}
	})

	//设置属性规格
	SetAttrSpec = function(my_html){
		//选择分类
		category_id = $('select[name="category_id"]').val();

		if(category_id){
			if($('select[name="category_id"]').find("option:selected").attr('data-pid') == 0){
				alert('产品只能添加到二级分类');
				$('select[name="category_id"]').val(0);
				return false;
			}
		}else{
			category_id = $('#category_id').val();
		}
		if(category_id == 0) return false;
		//选择类别
		goods_type = $('select[name="goods_type"]').val();
		if(!goods_type){
			goods_type = $('#goods_type').val();
		}
		goods_id = $('#goods_id').val();
		data     = {category_id:category_id,goods_type:goods_type,goods_id:goods_id};
		$.getJSON($.loadAttSpe,data,function(data){
			if(goods_type == 3){
				$('#goods_price_div').show();
				$('#attrDivContent').html(data.attrHtml);
				$('#specDivContent').html(data.spacHtml);
				$('#attrDiv').show();
				$('#specDiv').show();
				$('.dingzhi_div').hide();
				//成品产品启用插件对Sku进行操作
				$('.specDiv').SkuAction();
			}else if(goods_type == 4){
				$('#goods_price_div').hide();
				if(my_html){
					//$('#attrDivContent').html( my_html );
				} else {
					$('#attrDivContent').html( data.attrHtml );
				}
				$('#luozuanMatchBox').html(data.matchHtml);
				autocheckbox();
				$('.dingzhi_div').show();
				$('#attrDiv').show();
				$('#specDiv').hide();
			}
			$('#public_goods_desc_down').remove();;
			$('#productMain').append(data.pubulicGoodsDescHtml);
		})
	};
	SetAttrSpec();

	//选择分类或者产品类型自动获取属性/规格/金工石数据
	$('select[name="category_id"],select[name="goods_type"]').change(function(){
		SetAttrSpec();
		var id = $('select[name="goods_type"]').val();
		if(id==3){
			$("#goods_series").show();
			$(".chengpinfangwei").show();
		}else{
			$(".chengpinfangwei").hide();
			$("#goods_series").hide();
		}
	})

	//属性添加删除一条属性
	$('a#addTiao').live('click',function(){
		id = parseInt($(this).attr('data-id'));
		var unitTip = "";
		switch(id){
		case 18:	//材质金重
			unitTip = "(单位：G)";break;
		case 22:	//主石大小
			unitTip = "(单位：CT)";break;
		case 27:	//副石数量
			unitTip = "(单位：粒)";break;
		case 28:	//副石大小
			unitTip = "(单位：CT)";break;
		}
		html = '<div class="atiao">'+
			   '<input type="text" name="attribute['+id+'][]" value="" class="fl" placeholder="'+unitTip+'"/>'+
			   '<a class="btn_common ml10" data-id = "'+id+'" id="delTiao">删除</a>'+
			   '<div class="clear"></div></div>';
		$(this).parents('.acontent').append(html);
	})
	$('a#delTiao').live('click',function(){
		$(this).parent('.atiao').remove();
	})

	//添加裸钻匹配
	$('.luozuan_button.add').live('click',function(){
		_self = this;
		material_id = $(this).attr('data-id');
		$.get($.addLuozuanMatch,{material_id:material_id},function(data){
			$(_self).parent('.materialBottom').children('.luozuan_match').append(data);
		})
	})
	//删除裸钻匹配
	$('.luozuan_button.del').live('click',function(){
		$(this).parent('.tiao').remove();
	})
	//添加匹配副石
	$('.luozuan_button.add2').live('click',function(){
		$.get($.addLuozuanMatch2,function(data){
			$('.luozuan_match2').append(data);
		})
	})
	//删除匹配副石
	$('.luozuan_button.del2').live('click',function(){
		$(this).parent('.tiao').remove();
	})

	//添加取消材质
	$('.luozuanMatchBox input[type="checkbox"]').live('click',function(){
		checkbox = $(this).attr('checked');
		id              = $(this).val();
		var category_id = $(this).attr('data-category-id');
		_self = this;
		if(checkbox == 'checked'){
			$.get($.addMaterial,{'material_id':id,'category_id':category_id},function(data){
				$('#material').append(data);
				$(_self).attr('checked',true);
			})
		}else{
			$('#material_'+id).remove();
			$(_self).attr('checked',false);
		}
	});

	//材质里有匹配金重损耗裸钻的自动选中
	autocheckbox = function(){
		$('.luozuanMatchBox input[type="checkbox"]').each(function(index){
			id = $(this).val();
			length = $('#material_'+id).length;
			if(!length){
				$(this).attr('checked',false);
			}else{
				$(this).attr('checked',true);
			}
		})
	}

	//
	$('.luozuan_button.add3').live('click',function(){
		var _self       = this;
		var material_id = $(this).attr('data-id');
		var category_id = $(this).attr('data-category-id');
		$.get($.addGoodsSize,{material_id:material_id,category_id:category_id},function(data){
			$(_self).parent('.materialBottom').children('.luozuan_match3').append(data);
		});
	})
	//
	$('.luozuan_button.del3').live('click',function(){
		$(this).parent('.tiao').remove();
	})
})
