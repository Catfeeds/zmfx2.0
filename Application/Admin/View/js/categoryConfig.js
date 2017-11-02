$(function(){
	//设置是否要显示分类
	$('.catConfigCheck').change(function(){
		var is_show    = ($(this).prop('checked'))?1:0;
		var is_validId = parseInt(($(this).attr('categoryConfigId')).trim());
		var id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
		var cid 	   = $(this).attr('cid').trim();
		var alias 	   = $('input[class=aliasInput][aliasId='+cid+']').prop('value');
		var pid 	   = parseInt($(this).attr('pid').trim());
		var sort_id    = 0;	//排序
		
		if(typeof alias != 'string' || alias.length==0){
			alert('别名不能为空！');
			return false;
		}
		
		changeCategoryShow(id,cid,alias,pid,sort_id,is_show);	//数据与zm_goods_category_config对应 
		
		if(pid){
			console.log("操作子级"+pid);
			checkSubCategory(cid,pid,is_show);	//如果是操作子级
		}else{
			console.log("操作顶级"+pid);
			checkTopCategory(cid,is_show);		//如果操作顶级
		}
		
		
		//判断是否全部选中
		var isCheckAll = true;
		$("input[type='checkbox'][class='catConfigCheck']").each(
			function(index,domEle){
				if(!($(this).prop("checked"))){	//如果没选中
					isCheckAll=false;
					return false;
				}
			}
		);
		var realCheckAll = $("#catConfigCheckAll").prop("checked");
		if(isCheckAll)
			realCheckAll ? "":$("#catConfigCheckAll").prop("checked",true);
		else
			realCheckAll ? $("#catConfigCheckAll").prop("checked",false):"";
		
	});
	
	//修改别名
	$('.aliasInput').change(function(){
		//console.log($(this).attr(""));
		var alias      = $(this).prop('value').trim();
		var is_validId = parseInt(($(this).attr('categoryConfigId')).trim());
		var id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
		var cid 	   = $(this).attr('cid').trim();
		var pid 	   = $(this).attr('pid').trim();
		var sort_id    = 0;	//排序
		if(typeof alias != 'string' || alias.length==0){
			alert('别名不能为空！');
			return false;
		}
		$.ajax({
			url: '/Admin/Public/changeCategoryAlias',
			data: {id:id, alias:alias, cid:cid, pid:pid, sort_id:sort_id}
		}).done(function(data){ if(data) console.log(data); })
		  .fail(function(data){ alert("修改别名失败"); });
	});
	
	$('#catConfigCheckAll').change(function(){
		//如果全选选中
		if($("#catConfigCheckAll").prop("checked")){
			$("input[type='checkbox'][class='catConfigCheck']").each(
				function(index,domEle){
					if(!($(this).prop("checked"))){	//如果没选中
						$(this).prop("checked",true);
						$(this).change();
					}
				}
			);
		}else{	//如果全选取消
			$("input[type='checkbox'][class='catConfigCheck']").prop("checked",false);
			$(".catConfigCheck").change();
		}
	});

	/* 更改首页展示 */
	$('.showInput').change(function(){
		var categoryConfigId = $(this).attr('name').replace('home_show_','');
		var nowValue		 = $(this).val();
		var arr = ['0','1'];
		if(arr.indexOf(nowValue) == -1){
			layer.msg('操作错误',{
				shift:6,
			});
			return false;
		}
		if(categoryConfigId && nowValue){
			//console.log(categoryConfigId);
			//console.log(nowValue);return false;
			updateCategory(categoryConfigId,nowValue,1);
		}else{
			layer.msg('操作错误',{
				shift:6,
			});
			return false;
		}
	})
	/* 更改分类排序 */
	$('.sortInput').change(function(){
		var categoryConfigId = $(this).attr('name').replace('sort_id_','');
		var nowValue		 = $(this).val();
		if(categoryConfigId && nowValue){
			updateCategory(categoryConfigId,nowValue,2);
		}
	})
})
/* ajax请求修改产品分类数据 type: 1:分类首页展示，2:分类排序*/
function updateCategory(categoryConfigId,nowValue,type){
	var arr = [1,2];
	if(arr.indexOf(type) == -1){
		layer.msg('操作错误',{
			shift:6,
		});
		return false;
	}
	$.ajax({
		type: "post",
		url : "/Admin/Public/updateCategory", 
		dataType:'json',
		data: {categoryConfigId:categoryConfigId,nowValue:nowValue,type:type}, 
		success: function(res){
			if(res.status != 100){
				layer.msg(res.info,{
					shift:6,
					icon: 5
				});
			}
		},
	});	
}

function changeCategoryShow(id,cid,alias,pid,sort_id,is_show){
	$.ajax({
		url:'/Admin/Public/changeCategoryShow',
		data:{
			id : id,
			cid : cid,
			alias : alias,
			pid : pid,
			sort_id : sort_id,
			is_show : is_show
		}
	}).done(function(data) { console.log('修改显示成功'+data); })
    .fail(function(data) { alert("修改分类是否显示错误"); });
}

function checkSubCategory(cid,pid,is_show){
	//1.如果是选中
	if(is_show){
		//检查上级否有选中,没选中则自动选中上级
		var isTopChecked = $("input[type=checkbox][cid="+pid+"]").prop("checked");
		if(!isTopChecked){
			$("input[type=checkbox][cid="+pid+"]").prop("checked",true);
			//$("input[type=checkbox][cid="+pid+"]").trigger("click");
			var is_show    = 1;
			var is_validId = parseInt(($("input[type=checkbox][cid="+pid+"]").attr('categoryConfigId')).trim());
			var id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
			var cid 	   = pid;
			var alias 	   = $('input[class=aliasInput][aliasId='+pid+']').prop('value');
			var pid 	   = 0;
			var sort_id    = 0;	//排序
			//$("input[type=checkbox][cid="+pid+"]").prop("checked",true);
			changeCategoryShow(id,cid,alias,pid,sort_id,is_show);
		}
	}else{//2.如果是取消
		//检查是否还有其他同级项，没有则也联动取消上级
		var isAllUnChecked = true;
		$("input[type=checkbox][pid="+pid+"]").each(
			function(index,domEle){
				//console.log($(this).attr("cid"));
				if(($(this).prop("checked"))){	//如果有一个选中，立即跳出each
					isAllUnChecked=false;
					return false;				//false时相当于break, 如果return true 就相当于continure。
				}
			}
		);
		if(isAllUnChecked){
			//$("input[type=checkbox][cid="+pid+"]").trigger("click");
			$("input[type=checkbox][cid="+pid+"]").prop("checked",false);
			var is_show    = 0;
			var is_validId = parseInt(($("input[type=checkbox][cid="+pid+"]").attr('categoryConfigId')).trim());
			var id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
			var cid 	   = pid;
			var alias 	   = $('input[class=aliasInput][aliasId='+pid+']').prop('value');
			var pid 	   = 0;
			var sort_id    = 0;	//排序
			changeCategoryShow(id,cid,alias,pid,sort_id,is_show);
		}
	}
}

function checkTopCategory(cid,is_show){
	var is_show    ;
	var is_validId ;
	var id		   ;
	var cid 	   ;
	var alias 	   ;
	var pid 	   ;
	var sort_id    ;	//排序
	if(is_show){
		//把子分类下未选中的选中
		$("input[type=checkbox][pid="+cid+"]").each(
			function(index,domEle){
				if((!$(this).prop("checked"))){
					is_show    = 1;
					is_validId = parseInt(($(this).attr('categoryConfigId')).trim());
					id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
					cid 	   = $(this).attr('cid').trim();
					alias 	   = $('input[class=aliasInput][aliasId='+cid+']').prop('value');
					pid 	   = parseInt($(this).attr('pid').trim());
					sort_id    = 0;	//排序
					$(this).prop("checked",true);
					changeCategoryShow(id,cid,alias,pid,sort_id,is_show);
				}
			}
		);
	}else{
		//把子分类下选中的取消选中
		$("input[type=checkbox][pid="+cid+"]").each(
			function(index,domEle){
				is_show    = 0;
				is_validId = parseInt(($(this).attr('categoryConfigId')).trim());
				id		   = is_validId ? is_validId:-1;	//如果该属性还未曾设置，则id用-1标记
				cid 	   = $(this).attr('cid').trim();
				alias 	   = $('input[class=aliasInput][aliasId='+cid+']').prop('value');
				pid 	   = parseInt($(this).attr('pid').trim());
				sort_id    = 0;	//排序
				$(this).prop("checked",false);
				changeCategoryShow(id,cid,alias,pid,sort_id,is_show);
			}
		);
	}
	
}

//function toggleCategoryShow(id,is_show){
//	//发ajax请求修改zm_goods_category_config表的is_show
//	$.ajax({
//	  url: '/Admin/Public/toggleCategoryShow',
//	  data: { 
//		  id : id , 
//		  is_show : is_show,
//	  }
//	}).done(function(data) { console.log(data); })
//      .fail(function(data) { alert("修改分类是否显示错误"); });
//}
//
//function insertCategoryShow(cid,alias,pid,sort_id,is_show){
//	$.ajax({
//		url: '/Admin/Public/insertCategoryShow',
//		data:{
//			cid : cid,
//			alias : alias,
//			pid : pid,
//			sort_id : sort_id,
//			is_show : is_show
//		}
//	}).done(function(data) {console.log(￥);})
//	;
//}

