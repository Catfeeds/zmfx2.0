/**
 * 成品和产品定制筛选
 */
$(document).ready(function(){
    $(".pageNum li").click(function(){
        $(".pageNum").find("li").each(function(){
            $(this).removeClass("active");
        });
        setCookie('n',$(this).html());
        $(this).addClass("active");
        submitData();
    });

    $(".filterTo li").click(function(){
        var type = $(this).attr('ref');
        setCookie('type',type);
        if(getCookie(type)=="DESC"){
            setCookie(type,"ASC");
            $(this).html($(this).attr('txt')+"&uarr;");
        }else{
            setCookie(type,"DESC");
            $(this).html($(this).attr('txt')+"&darr;");
        }
        setCookie('page',1);
        submitData();
    });

    $(".pageNum").find("li").each(function(index,e){
        if($(this).html() == getCookie('n')){
            $(this).addClass("active");
        }
    });
    setCookie("page",1);
    setCookie('attribute_id','');
    //submitData();
    initFilter();
    initData();
});

function setPage(page){
    setCookie("page",page);
    submitData();
}

/**
 * @param obj 被点击的属性值
 * @param attribute_id 被点击的属性id
 */
function setAttributeId(obj,traderId,categoryId,goodsType,attributeId,tid){
    $(obj).toggleClass("on");	//修改样式
    var combo_attr_code=0;
    $(obj).parent().find("li[attr_id="+attributeId+"]").each(function(){
        if($(this).hasClass("on"))	//如查此属性值有选中，则加上
            combo_attr_code += parseInt($(this).attr("attr_code").trim());
    });
    setCookie(traderId+'_'+categoryId+'_'+goodsType+'_'+attributeId,combo_attr_code);	//以"category_id+'_'+attribute_id"作为cookie的key,以防冲突
    submitData();
}

function initFilter(){
    //遍历所有筛选属性
    var containValue = false;
    var cookieKey;
    var cookieVal;
    var combo_attr_code;
    var this_attr_code;
    var tid;
    var cid;
    var attr_id;
    var goodsType;
    $("div[class=attrs] ul[class=attr]").each(function(){
        //遍历属性值
        containValue = false;
        $(this).children("li").each(function(){
            containValue = false;
            tid = $(this).attr("tid").trim();
            cid = $(this).attr("cid").trim();
            attr_id = $(this).attr("attr_id").trim();
            goodsType = getVal('goodsType');
            cookieKey = tid+'_'+cid+'_'+goodsType+'_'+attr_id;
            if(document.cookie.indexOf(cookieKey)>=0){
                cookieVal = getCookie(cookieKey);
                if(isValid(cookieVal)){
                    combo_attr_code = parseInt(cookieVal.trim());
                    this_attr_code =parseInt($(this).attr("attr_code").trim());
                    if(combo_attr_code & this_attr_code)
                        containValue = true;
                }
            }
            if(containValue)	//如当前项的attr_code与上cookie的值为真，则加上class="on",
                if(!$(this).hasClass("on"))
                    $(this).addClass("on");
        });
    });

    //以下为产品系列的逻辑
    var series_id;
    series_id = getCookie('series_id');
    $('#series_id_'+series_id).css({ "color": "#fff", "background": "#737080" });
    var display_type = $('#series_id_'+series_id).closest("ul").css('display');
    if(display_type == 'none'){
        openShutManager($('.more'),'series_info','收起-','更多+');
    }
}

function openShutManager(oSourceObj,oTargetObj,oOpenTip,oShutTip){
    var str = $(oSourceObj).html();
    if( str == oShutTip ){
        $(oSourceObj).html(oOpenTip);
        $('.'+oTargetObj).css('display','');
    }else{
        $(oSourceObj).html(oShutTip);
        $('.'+oTargetObj).css('display','none');
        $('#info_1').css('display','');
    }
}

function func_series_set(series_id){
    var cur_series_id = getCookie('series_id');
    if(getCookie('series_id') != series_id){
        setCookie("series_id",series_id);
        $('#series_id_'+cur_series_id).css({ "color": "rgb(0, 0, 0)", "background": "" });
        $('#series_id_'+series_id).css({ "color": "#fff", "background": "#737080" });
    }else{
        setCookie("series_id",0);
        $('#series_id_'+series_id).css({ "color": "rgb(0, 0, 0)", "background": "" });
    }
    submitData();
}

/*function initFilter(obj,traderId,category_id,attribute_id,tid){
 var containValue = false;
 var cookieKey;
 var cookieVal;
 var combo_attr_code;
 var this_attr_code;

 $(obj).parent().find("li[attr_id="+attribute_id+"]").each(function(){
 containValue = false;
 cookieKey = tid+'_'+category_id+'_'+attribute_id;
 if(document.cookie.indexOf(cookieKey)>=0){
 cookieVal = getCookie(cookieKey);
 if(isValid(cookieVal)){
 combo_attr_code = parseInt(cookieVal.trim());
 this_attr_code =parseInt($(this).attr("attr_code").trim());
 if(combo_attr_code & this_attr_code)
 containValue = true;
 }
 }
 if(containValue)	//如当前项的attr_code与上cookie的值为真，则加上class="on",
 if(!$(this).hasClass("on"))
 $(this).addClass("on");
 });
 }*/

function submitData(){
    $.post("/Home/Goods/getFilterProducts",
        {
            Action:"post",
            page:getCookie('page'),
            sortType:getCookie('type'),
            sortValue:getCookie(getCookie('type')),
            n:getCookie('n'),
            categroyId:getVal('categroyId'),
            goodsType:getVal('goodsType'),
            series_id:getCookie('series_id')
            //attribute_id:getCookie('attribute_id'),
        },
        refData
    );

}

function initData(){
    //一开始或刷新都会访问这里，
    $.post("/Home/Goods/initProducts",
        {
            Action:"post",
            page:getCookie('page'),
            sortType:getCookie('type'),
            sortValue:getCookie(getCookie('type')),
            n:getCookie('n'),
            categroyId:getVal('categroyId'),
            //goods_type:getCookie('goods_type'),
            goodsType:getVal('goodsType'),
            attribute_id:getCookie('attribute_id'),
            series_id:getCookie('series_id')
        },
        refData
    );
}

function getVal(name){
    return $("input[type=hidden][name="+name+"]").val().trim();
}

function refData(data){
    var pagetip="";
    var pagetipcount="";
    if(data.data && data.data != ''){
        var html = "";
        data.data.forEach(function(e){
            html += "<li>";
            html += "<div class='data_img'><a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'><img src='"+e.thumb+"'/></a></div>";
            html += "<div class='data_text'><p><a title='"+e.goods_name+"' href='/Home/Goods/goodsInfo/goods_type/"+e.goods_type+"/gid/"+e.goods_id+"'>"+e.goods_name_txt+"</a></p>";
            if(e.activity_status == '0' || e.activity_status == '1'){
                html += "<b>&yen;"+e.activity_price+"</b> <img src='../../../../Public/images/cart.png' class='join-cart' onclick='addToCart(2, "+e.goods_id+", "+e.goods_type+")' style='width: 32px; height: 18px; margin: 4px; vertical-align: middle;'></div>";
            }else{
                html += "<b>&yen;"+e.goods_price+"</b> <img src='../../../../Public/images/cart.png' class='join-cart' onclick='addToCart(2, "+e.goods_id+", "+e.goods_type+")' style='width: 32px; height: 18px; margin: 4px; vertical-align: middle;'></div>";
            }
            html += "</li>";
        });
        pagetip=data.page;
        pagetipcount=data.count;
        $("#dataList").html(html);
    }else{
        $("#dataList").html("");
        pagetip="";
        pagetipcount="0";
    }
    $("#page").html(pagetip);
    $("#count").html(pagetipcount);
}

