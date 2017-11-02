/**
 * Title:   生成SKU表格
 * Detail:  根据最多3级属性生成规格，可以是多选的input:checkbox和手填的input:text
 * Author:  郭冠常
 * Time:    2015年11月14日制作
 */
(function($){
	$.fn.SkuAction = function(options){
		var defaults = {
			//标签定义
			'specDiv':'.specDiv',//定义所有规格大标签
			'specOneDiv':'.specOneDiv',//定义单级规格标签,最多3个规格属性（必须要有3个属性“data-id”，“data-name”，“data-type”）例：data-id="56" data-name="生产工艺" data-type="2"
			'specOneDivinput':'.specOneDivinput',//定义单个规格标签
			'specDivTable':'.specDivTable',//定义生成Table到那个标签
			'goods_price':'#goods_price',//获取一口价
			//运行时变量
			'level':'0',//规格的深度
			'tableTop':[],//多级属性的表格头
			'attrs':[],//选中的属性数组
			'spec':[],//根据选中的属性生成多级规格
			'sku':[],//记录sku的价格库存编号
		};
		//初始化参数
		var options = $.extend(defaults,options);
		//绑定点击多选属性事件
		$(options.specOneDivinput+'[type="checkbox"]').live('click',function(){
			getAttrArray();
			generateSpec();
			generateSpecTable();
		})
		
		//绑定手填输入框生成规格事件
		$(options.specOneDivinput+'[type="text"]').live('input propertychange',function(){
			//生成一个新的input:text或删除一个input:text
			val = $(this).val();
			lastVal = $(this).parents(options.specOneDiv).find(options.specOneDivinput+'[type="text"]:last').val();
			if(val && lastVal){
				html = '<div class="atiao"><input type="text" class="fl specOneDivinput" /><div class="clear"></div></div>'
				$(this).parents(options.specOneDiv).append(html);
			}else if(!val){
				$(this).remove();
			}
			getAttrArray();
			generateSpec();
			generateSpecTable();
		})

		//获取选中的属性生成数组
		getAttrArray = function(){
			options.tableTop = [];
			options.attrs = [];
			options.level = 0;
			$(options.specDiv+' '+options.specOneDiv).each(function(index){
				attrOne = [];
				attrOneId = $(this).attr('data-id');
				attrOneName = $(this).attr('data-name');
				type = $(this).attr('data-type');
				if(type == 2){//检查当前级多选数组选中
					$(this).find(options.specOneDivinput+'[type="checkbox"]').each(function(){
						checked = $(this).attr('checked');
						if(checked){
							attr_name = $(this).attr('data-name');
							attr_value = $(this).val();
							attrOne.push({
								'attr_name':attr_name,
								'attr_value':attrOneId+':'+attr_value,
							})
						}
					})
				}else if(type == 3){//检查当前级手填数组选中
					$(this).find(options.specOneDivinput+'[type="text"]').each(function(){
						val = $(this).val();
						if(val){
							attr_name = $(this).val();
							attr_value = $(this).val();
							attrOne.push({
								'attr_name':attr_name,
								'attr_value':attrOneId+':'+attr_value,
							})
						}
					})
				}
				//当前级有选中，级别就加一
				if(attrOne.length){
					options.level = options.level+1;
					options.tableTop.push({'name':attrOneName})
					options.attrs[options.level] = attrOne;
				}
			})
		}
		
		//根据属性数组生成规格数组
		generateSpec = function(){
			options.spec = [];
			if(options.level == 1){
				//循环1级属性
				for (i=0;i<options.attrs[1].length;i++) {
					//生成1级属性值
					var attr_name1 = [];
					attr_name1.push({'attr_name':options.attrs[1][i].attr_name});
					sku = options.attrs[1][i].attr_value;
					if(typeof(options.sku[sku]) != 'undefined'){
						goods_price = options.sku[sku].goods_price;
						goods_number = options.sku[sku].goods_number;
						sku_sn = options.sku[sku].sku_sn;
						unforgecode = options.sku[sku].unforgecode;						
					}else{
						goods_price = 0;
						goods_number = 0;
						sku_sn = 0;
						unforgecode = 0;
					}
					options.spec.push({
						'attr_name'      : attr_name1,
						'goods_sku_attr' : sku,
						'goods_price'    : goods_price,
						'goods_number'   : goods_number,
						'sku_sn'       	 : sku_sn,
						'unforgecode'    : unforgecode,
					});
				}
			}else if(options.level == 2){
				//循环1级属性
				for (i=0;i<options.attrs[1].length;i++) {
					//循环2级属性
					for (ii=0;ii<options.attrs[2].length;ii++) {
						//生成1级属性值
						if(ii == 0) rowspan = options.attrs[2].length;else rowspan = 0;
						attr_name1 = {'attr_name':options.attrs[1][i].attr_name,'rowspan':rowspan}
						//生成2级属性
						var attr_name2 = [];
						attr_name2.push(attr_name1,{'attr_name':options.attrs[2][ii].attr_name});
						sku = options.attrs[1][i].attr_value+'^'+options.attrs[2][ii].attr_value;
						if(typeof(options.sku[sku]) != 'undefined'){
							goods_price = options.sku[sku].goods_price;
							goods_number = options.sku[sku].goods_number;
							sku_sn = options.sku[sku].sku_sn;
							unforgecode = options.sku[sku].unforgecode;							
								
						}else{
							goods_price = 0;
							goods_number = 0;
							sku_sn = 0;
							unforgecode = 0;
						}
						options.spec.push({
							'attr_name'      : attr_name2,
							'goods_sku_attr' : sku,
							'goods_price'    : goods_price,
							'goods_number'   : goods_number,
							'sku_sn'         : sku_sn,
							'unforgecode'    : unforgecode,
							
						});
					}
				}
			}else if(options.level == 3){
				//循环1级属性
				for (i=0;i<options.attrs[1].length;i++) {
					//循环2级属性
					for (ii=0;ii<options.attrs[2].length;ii++) {
						//循环3级属性
						for (iii=0;iii<options.attrs[3].length;iii++) {
							//生成1级属性值
							if(ii == 0 && iii == 0) rowspan = options.attrs[2].length*options.attrs[3].length;
							else rowspan = 0;
							attr_name1 = {'attr_name':options.attrs[1][i].attr_name,'rowspan':rowspan}
							//生成2级属性
							if(iii == 0) rowspan = options.attrs[3].length;
							else rowspan = 0;
							attr_name2 = {'attr_name':options.attrs[2][ii].attr_name,'rowspan':rowspan};
							//生成3级属性
							var attr_name3 = [];
							attr_name3.push(attr_name1,attr_name2,{'attr_name':options.attrs[3][iii].attr_name});
							sku = options.attrs[1][i].attr_value+'^'+options.attrs[2][ii].attr_value+'^'+options.attrs[3][iii].attr_value;
							if(typeof(options.sku[sku]) != 'undefined'){
								goods_price = options.sku[sku].goods_price;
								goods_number = options.sku[sku].goods_number;
								sku_sn = options.sku[sku].sku_sn;
								unforgecode = options.sku[sku].unforgecode;								
							}else{
								goods_price = 0;
								goods_number = 0;
								goods_sn = 0;
								unforgecode =0;
							}
							options.spec.push({
								'attr_name'      : attr_name3,
								'goods_sku_attr' : sku,
								'goods_price'    : goods_price,
								'goods_number'   : goods_number,
								'sku_sn'         : sku_sn,
								'unforgecode'    : unforgecode,
							});
						}
					}
				}
			}else{
				if(options.level > 0){
					alert('属性规格不能超过3级!');
				}
			}
		}
		
		//获取SKU的价格库存编码，后面生成规格的时候使用，作为数据记忆功能
		getSkuPriceNumberSn = function(){
			$(options.specDivTable+' [data-sku]').each(function(){
				sku = $(this).attr('data-sku');
				options.sku[sku] = {
					'goods_price':$(this).find('.goods_price').val(),
					'goods_number':$(this).find('.goods_number').val(),
					'sku_sn':$(this).find('.sku_sn').val(),
					'unforgecode':$(this).find('.unforgecode').val(),					
				};
			})
		}
		
		//SKU修改编辑后
		$(options.specDivTable+' [data-sku] input').live('input propertychange',function(){
			getSkuPriceNumberSn();
		})

		//根据规格数组生成SKU表格
		generateSpecTable = function(){
			if(options.spec.length){
				goods_price = $(options.goods_price).val();
				html = '<table class="listTbl" border="1" bordercolor="#000" cellspacing="0px" style="border-collapse:collapse">';
				html += '<tr>';
				//循环多级属性显示到表单
				for (ii=0;ii<options.tableTop.length;ii++) {
					html += '<th class="tableTop">'+options.tableTop[ii].name+'</th>';
				}
				html += '<th width="20">价格</th>';
				html += '<th width="20">库存</th>';
				html += '<th width="20">产品编码</th>';
				if(getCookie('unforgecode')=='1'){
					html += '<th width="20">防伪码</th>';
				}
				html += '</tr>';
				for (i=0;i<options.spec.length;i++) {
					var vo = options.spec[i];
					html += '<tr data-sku="'+vo.goods_sku_attr+'">';
					for(ii=0;ii<vo.attr_name.length;ii++){
						if(vo.attr_name[ii].rowspan != 0){
							if(vo.attr_name[ii].rowspan){
								html += '<td rowspan="'+vo.attr_name[ii].rowspan+'">'+vo.attr_name[ii].attr_name+'</td>';
							}else{
								html += '<td>'+vo.attr_name[ii].attr_name+'</td>';
							}
						}
					}
					price 			= vo.goods_price ?vo.goods_price:goods_price;
					goods_number  	= vo.goods_number?vo.goods_number:'';
					sku_sn 			= vo.sku_sn		 ?vo.sku_sn:'';
					unforgecode 	= vo.unforgecode ?vo.unforgecode:'';					
					html += '<td><input type="text" class="goods_price" value="'+price+'" name="sku['+vo.goods_sku_attr+'][goods_price]" placeholder="默认为一口价"></td>';
					html += '<td><input type="text" class="goods_number" value="'+goods_number+'" name="sku['+vo.goods_sku_attr+'][goods_number]" placeholder="默认为1件"></td>';
					html += '<td><input type="text" class="sku_sn" value="'+sku_sn+'" name="sku['+vo.goods_sku_attr+'][sku_sn]" placeholder="默认自动生成"></td>';
					if(getCookie('unforgecode')=='1'){
						html += '<td><input type="text" class="unforgecode" value="'+unforgecode+'" name="sku['+vo.goods_sku_attr+'][unforgecode]" placeholder="防伪码"></td>';
					}
					html += '</tr>';
				}
				html += '</table>';
				$(options.specDivTable).html(html);
			}else{
				$(options.specDivTable).html('');
			}
		}
		
		//初始化SKU插件
		this.each(function(){
			getAttrArray();
			getSkuPriceNumberSn();
			generateSpec();
			generateSpecTable();
		})
	}
})(jQuery);




/*cookie读取*/
function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) return unescape(arr[2]);
    else return null
}