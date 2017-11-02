$(document).ready(function(){
	setCookie("page",1);
	diamondsInit();
	$(".condition_item").click(function(){
		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));
		var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
		var url = '/Goods/getGoodsByParam';
		if(diamondsSanhuoUrl == null){
			$(".condition_item").removeClass("actives");	//初始化按钮状态
			setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1&orderby=&orderway=');	//初始化数据接口参数
		}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
			$(".condition_item").removeClass("actives");	//初始化按钮状态
			setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1&orderby=&orderway=');	//初始化数据接口参数
		}else{
			setBtnStatus();
		}

		var thisName = $(this).attr("name");
		var thisRef = $(this).attr("ref");
		if($(this).hasClass("condition_item")){
			var thisData = request(thisName,getCookie('diamondsSanhuoUrl'));
		}

		//鼠标单击时候改变当前元素的状态
		if($(this).hasClass("actives")){
			$(this).removeClass("actives");
			if($(this).hasClass("condition_item")){
				var tmpdata = thisData.replace(',' + thisRef, '');
				if(tmpdata == thisData){
					tmpdata = thisData.replace(thisRef + ',', '');
				}
				if(tmpdata == thisData){
					tmpdata = thisData.replace(thisRef, '');
				}
				thisData = tmpdata;
				thisUrl = setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisData);
				setCookie('diamondsSanhuoUrl',thisUrl);
				submitData();
			}
		}else{

			$(".condition_item[name='"+thisName+"'][ref='"+thisRef+"']").removeClass("actives");
			$(this).addClass("actives");
			if($(this).hasClass("condition_item")){

				if($(this).hasClass("radio")){
					$(".condition_item[name='"+thisName+"']").removeClass("actives");
					$(this).addClass("actives");
					setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisRef));
				}else{
					if (thisData) {
						thisData = thisData + ',' + thisRef
					} else {
						thisData = thisRef
					};
					thisData = $.trim(thisData);
					if (thisData.charAt(0) == ',') {
						thisData = thisData.substr(1)
					};
					thisUrl = setUrlParam(getCookie('diamondsSanhuoUrl'),thisName,thisData);
					setCookie('diamondsSanhuoUrl',thisUrl);
				}
				submitData();
			}
		}
	});


    $('.orderby').click(function() {
		setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));
        if ($(this).find('i').hasClass('glyphicon-arrow-down') || $(this).find('i').hasClass('glyphicon-arrow-up')) {
            if ($(this).find('i').hasClass('glyphicon-arrow-down')) {
                $('.orderby').find('i').removeClass('glyphicon-arrow-down');
                $(this).find('i').addClass('glyphicon-arrow-up');
                var order = $(this).find('i').attr('data-value');
                setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderway','asc'));
                setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderby',order));
            } else {
                $('.orderby').find('i').removeClass('glyphicon-arrow-up');
                $(this).find('i').addClass('glyphicon-arrow-down');
                var order = $(this).find('i').attr('data-value');
				setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderway','desc'));
                setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderby',order));
            }
        } else {
            $('.orderby').find('i').removeClass('glyphicon-arrow-down');
            $('.orderby').find('i').removeClass('glyphicon-arrow-up');
            $(this).find('i').addClass('glyphicon-arrow-down');
            var order = $(this).find('i').attr('data-value');
			setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderway','desc'));
            setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'orderby',order));
        }
           submitData();
    });


    $('.huoping-text').focus(function(){
        $('.hpul').show();
    });

    $('.huoping-text').blur(function(){
        getGoodsSnList($(this).val());
	});

    $('.huoping').mouseleave(function(){
        $('.hpul').hide();
        $('.slhp').blur();
    });

    $(document).on("click",".hpul li",function(){
        $('.huoping-text').val($(this).text());
        setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',1));
        setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',$(this).attr('data')));
        $('.hpul').hide();
        submitData();
    });

	/*清空条件*/
    $('.deselect').bind('click', function() {
        $('.panel-body a').removeClass('actives');
        $('.orderby').find('i').removeClass('glyphicon-arrow-down').removeClass('glyphicon-arrow-up');
        $('.condition_item_mobile').prop('checked', false);
        setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1&orderby=&orderway=');	//初始化数据接口参数
        $(".condition_item").removeClass("actives");
        setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',''));
        $("select[name='goodsType']").val('');
        $("input[name='choose_goods_sn']").val('');
        $('.hpul').hide();
        submitData();
    });

    $('.all-checkbox').bind('click', function() {
        selectThisPage();
    });

    $('#addCartAll').bind('click', function() {
        addThisPageToCart();
    });
});

/*裸钻数据列表初始化*/
function diamondsInit(){
	// var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');
    var diamondsSanhuoUrl = '';
	$(".condition_item").removeClass("actives");	//初始化按钮状态

	var url = '/Goods/getGoodsByParam';
	if(diamondsSanhuoUrl == null){
		setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1&orderby=&orderway=');	//初始化数据接口参数
	}else if(diamondsSanhuoUrl.indexOf(url) == -1 ){
		setCookie('diamondsSanhuoUrl','/Home/Goods/getGoodsByParam.html?GoodsType=&GoodsSn=&Color=&Clarity=&Cert=&Cut=&Weight=&page=1&orderby=&orderway=');	//初始化数据接口参数
	}else{
		setBtnStatus();
	}

	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',''));
	submitData();
}

/*根据cookie设置按钮状态*/
function setBtnStatus(){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page','1'));	//将页数设置为1
	var diamondsSanhuoUrl = getCookie('diamondsSanhuoUrl');	//从cookie获取URL
	if(null != diamondsSanhuoUrl && typeof(diamondsSanhuoUrl) != "undefined"){
		var urlParameters = [
			{'name':'GoodsType','data':request('GoodsType',diamondsSanhuoUrl)},
			{'name':'Weight','data':request('Weight',diamondsSanhuoUrl)},
			{'name':'Color','data':request('Color',diamondsSanhuoUrl)},
			{'name':'Clarity','data':request('Clarity',diamondsSanhuoUrl)},
			{'name':'Cut','data':request('Cut',diamondsSanhuoUrl)},
			{'name':'Location','data':request('Location',diamondsSanhuoUrl)}
		];
		$.each(urlParameters, function(i, obj) {
			if(obj.name != 'Price' && obj && obj.data && obj.name){
				var temp = new Array();
				temp = obj.data.split(",");
				for(m=0;m<temp.length;m++){
					$(".condition_item[name='"+obj.name+"'][ref='"+temp[m]+"']").removeClass("actives").addClass("actives");
				}
			} else if(obj.name == 'Price' && obj && obj.data && obj.name){
				var Price = new Array();
				Price = obj.data.split("-");
				$("input[name='minPrice']").val(Price[0]);
				$("input[name='maxPrice']").val(Price[1]);
			}
		});
		//初始化排序按钮
		var orderby = request('orderby',diamondsSanhuoUrl);
		var orderway = request('orderway',diamondsSanhuoUrl);
		if(orderway == 'desc'){
            $(".orderby i[data-value='"+orderby+"']").removeClass().addClass("glyphicon glyphicon-arrow-down");
		}else if(orderway == 'asc'){
            $(".orderby i[data-value='"+orderby+"']").removeClass().addClass("glyphicon glyphicon-arrow-up");
		}

	}
}

/*提交数据执行查询*/
function submitData(){
	_ajax(getCookie('diamondsSanhuoUrl'), 't='+Math.random()+'&type='+request('type'), 'GET', diamondsData_callback, true);
}

function diamondsData_callback(data){
	if(data.data != ''){
		html = "";
		detailDialog = '';
        html_goods_sn = "<li data='-1'>请选择货号</li>";
		i = 1;
		data.data.forEach(function(e){
            html += "<tr>";
            html += "<th class='oper lb-hide'><button type='button' gid='"+e.goods_id+"' class='diamond-btnimg' data-toggle='modal' data-target='.model-dialog-"+e.goods_id+"'></button></th>";
		 	html += "<td  class='nf-hide'>"+e.location+"</td>";
		 	html += "<td>"+e.type_name+"</td>";
		 	html += "<td>"+e.goods_sn+"</td>";
            html_goods_sn += "<li data='"+e.goods_id+"'>"+e.goods_sn+"</li>";
		 	html += "<td>"+e.goods_weight+"</td>";
		 	html += "<td>"+e.color+"</td>";
		 	html += "<td>"+e.clarity+"</td>";
		 	html += "<td>"+e.cut+"</td>";
		 	html += "<td>"+e.goods_price+"</td>";
		 	html += "<td><input type='checkbox' onclick='tbSelectedId("+e.goods_id+")' id='sanhuoCHK_"+e.goods_id+"' name='tbSelectedId' value="+e.goods_id+" /></td>";
			html += "<td>";
		 	if(e.collection_id>0){
				html += "<button class='btn btn-default btn-sm btn-warning addDingzhi' onclick='addSanhuoToCollect($(this),"+e.goods_id+",1)' collection_id='"+e.collection_id+"'>已收藏</button> ";
			}else{
				html += "<button class='btn btn-default btn-sm btn-warning addDingzhi' onclick='addSanhuoToCollect($(this),"+e.goods_id+",1)'>收藏</button> ";
			}
            html += "<button onclick='addDiamondsToCart("+e.goods_id+")' class='btn btn-default btn-sm btn-warning addCart'>购买</button>";
            html += "</td>";
		 	html += "</tr>";

            html += "<tr id='tr_temp_"+e.goods_id+"' style='display:none;'><td colspan='11' class='tdLeft'>"+e.goods_4c+"</td></tr>";

            detailDialog += "<div class='modal fade bs-example-modal-lg model-dialog-"+e.goods_id+"' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel'><div class='modal-dialog modal-md'><div class='modal-content'>";

            detailDialog += "<div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button> <h4 class='modal-title'>规格明细</h4></div>";

            detailDialog += "<div class='luozuanDetail'><div class='row'><div class='col-md-12'>";

            detailDialog += "<ul id=''>";

            detailDialog += "<li class='col-md-6'><span>来源：</span>"+e.location+"</li>";
            detailDialog += "<li class='col-md-6'><span>分类：</span>"+e.type_name+"</li>";
            detailDialog += "<li class='col-md-6'><span>货号：</span>"+e.goods_sn+"</li>";
            detailDialog += "<li class='col-md-6'><span>净度：</span>"+e.clarity+"</li>";
            detailDialog += "<li class='col-md-6'><span>颜色：</span>"+e.color+"</li>";
            detailDialog += "<li class='col-md-6'><span>库存重量 · CT：</span>"+e.goods_weight+"</li>";
            detailDialog += "<li class='col-md-6'><span>切工：</span>"+e.cut+"</li>";
            detailDialog += "<li class='col-md-6'><span>统走定价 ·元/ CT：</span>"+e.goods_price+"</li>";
            detailDialog += "</ul>";
            detailDialog += "<div class='clearfix'></div>";
            if(e.goods_4c){
                detailDialog += "<ul>";
                detailDialog += "<p class='luozuan-ms'>"+e.goods_4c+"</p>";
                detailDialog += "</ul>";
			}
            detailDialog += "</div> </div> </div> </div> </div> </div>";

		 	i++;
		});

		$("#_dataRows").html(html);
		$("#modelDialog").html(detailDialog);
		$("#page").html(data.page);
		$("#goods_sn").html(html_goods_sn);
		$('#cart').html(data.cartCount);
        $('#num').html(data.total);
	 }else{
        $('#num').html(0);
	 	$("#_dataRows").html("<tr><td colspan='11'>暂无数据</td></tr>");
	 	$("#page").html(data.page);
	 }
	var goods_sn = data.goodsSn;

	if(goods_sn){
		$('#goods_sn').val(goods_sn);
	}
}

function getGoodsSnList(value){
    _ajax('/Home/Goods/getListByGoodsSn', 'goodsSn='+value+'&t='+Math.random()+'&type='+request('type'), 'GET', function(data){
		if(data.data){
            var html_goods_sn = '';
            data.data.forEach(function(e){
                html_goods_sn += "<li data='"+e.goods_id+"'>"+e.goods_sn+"</li>";
			});
            $("#goods_sn").html(html_goods_sn);
		}else{
            setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',-1));
            submitData();
		}
	}, true);
}

if (!Array.prototype.forEach) {
    Array.prototype.forEach = function(callback, thisArg) {
        var T, k;
        if (this == null) {
            throw new TypeError(" this is null or not defined");
        }
        var O = Object(this);
        var len = O.length >>> 0; // Hack to convert O.length to a UInt32
        if ({}.toString.call(callback) != "[object Function]") {
            throw new TypeError(callback + " is not a function");
        }
        if (thisArg) {
            T = thisArg;
        }
        k = 0;
        while (k < len) {
            var kValue;
            if (k in O) {
                kValue = O[k];
                callback.call(T, kValue, k, O);
            }
            k++;
        }
    };
}

function setPage(page){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',page));
	setCookie("page",page);
	submitData();
}

function setGoodsType(obj){
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'page',1));	//将页数设置为1
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsType',obj.value));
	setCookie('diamondsSanhuoUrl',setUrlParam(getCookie('diamondsSanhuoUrl'),'GoodsSn',-1));
	$("input[name='choose_goods_sn']").val('');
	$('.hpul').hide();
	submitData();
}

var thisIDsData = '';	//用来存储用户选定的数据
thisIDs = {
	add:function(productID){
		if (thisIDsData!='') {
			thisIDsData = thisIDsData + ',' + productID
		} else {
			thisIDsData = productID
		};
		thisIDsData = $.trim(thisIDsData);
		if (thisIDsData.charAt(0) == ',') {
			thisIDsData = thisIDsData.substr(1)
		};
	},
	del:function(productID){
		thisIDsData = thisIDsData.replace(',' + productID, '');
		thisIDsData = thisIDsData.replace(productID + ',', '');
		thisIDsData = thisIDsData.replace(productID, '');
	},
	init:function(){
		return true;
	}
}

/*选定当前页数据*/
function selectThisPage(){
	var status = $("#selectThisPage").is(":checked");
	$("input[name = tbSelectedId]:checkbox").prop("checked", status);
	$("input[name = tbSelectedId]:checkbox").each(function(){
		var thisID = $(this).attr('id').replace('sanhuoCHK_','');
		tbSelectedId(thisID);
	});
}
function tbSelectedId(productID){//用户选定数据操作
	if($("#sanhuoCHK_"+productID).is(":checked")){
		thisIDs.add(productID);
	}else{
		thisIDs.del(productID);
	}
}
function addThisPageToCart(){
	if(thisIDsData==''){
		return false;
	}
	var data = thisIDsData.split(',');
	$.post('/Home/Goods/addSanhuo2cart',{Action:'post',goods_id:data},function(data){
		if(data.status == 1){
            layer.msg(data.info, {shift: 6});
            $('.cat-c').html(data.count+'件');
  	   		$('#cart').html(data.count);
  	    }else{
            layer.msg(data.info, {shift: 6});
  	    }
 	});
}

/*添加散货到购物车 */
function addDiamondsToCart(goodsId)
{
  var gids = new Array();
  gids[0]  = goodsId;
  $.post('/Home/Goods/addSanhuo2cart',{Action:'post',goods_id:gids},function(data){
  	   if(data.status == 1){
           	layer.msg(data.info, {shift: 6});
            $('.cat-c').html(data.count+'件');
		    $('#cart').html(data.count);
  	   }else{
           layer.msg(data.info, {shift: 6});
  	   }

  });
}
/* 导出选中选中项 */
function exportSelected(){
    if(thisIDsData){
        location.href = '/Home/Goods/exportSanhuoData?etd_id='+thisIDsData;
	}else{
        layer.msg('请先选择要导出的数据！',{ shift:6, });
	}
}

/* 导出当前页 */
function exportThisPage(){
	var thePageIds = '';
    $("input[name = tbSelectedId]:checkbox").each(function(){
        var thisID = $(this).val();
        thePageIds += thisID+',';
    });
    thePageIds = thePageIds.substring(0, thePageIds.length-1);
    if(thePageIds){
        location.href = '/Home/Goods/exportSanhuoData?etd_id='+thePageIds;
    }else{
        layer.msg('请先选择要导出的数据！',{ shift:6, });
    }
}