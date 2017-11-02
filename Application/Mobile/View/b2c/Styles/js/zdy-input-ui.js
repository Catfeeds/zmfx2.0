/**
 * JavaScript Custom Input UI
 * @author: songjingwen;
 * @date: 2017-8-1;
**/

(function($){

    var zdy = {
        //checkbox 为多选
        change: function(obj){
            if(obj.prop('checked')) {
                obj.addClass('active');
            }else{
                obj.removeClass('active');
            }
        },
        //radio 为单选
        radio: function(obj){
            //获取同名的radio表单进行操作
            var name = obj.prop('name');
            if(name){
                $("input[name="+ name +"]").prop('checked', false).removeClass('active');
            }
            $(obj).prop('checked', true).addClass('active');
        }
    }

    /**
     * @自定义checkbox表单
     *
    **/
    //初始化渲染表单
    $('.zdy-checkbox').each(function(){
        zdy.change($(this));
    });
    //监听选择
    $(document).delegate('.zdy-checkbox', 'change', function(){
        zdy.change($(this));
    });

    //监听checkbox全选按钮
    $(document).delegate('#check-all', 'change', function(){
        if($(this).prop('checked')) {
            $('.zdy-checkbox').prop('checked', true).addClass('active');
        }else{
            $('.zdy-checkbox').prop('checked', false).removeClass('active');
        }
    });

    /**
     * @自定义radio表单
     *
    **/
    //初始化渲染表单
    $('.zdy-radio').each(function(){
        zdy.change($(this));
    });
    //监听选择
    $(document).delegate('.zdy-radio', 'change', function(){
        zdy.radio($(this));
    });

})(jQuery);

//重新表单渲染
var reset = {
    //重新渲染checkbox
    checkbox: function(obj){
        if($(obj).prop('checked')) {
            $(obj).addClass('active');
        }else{
            $(obj).removeClass('active');
        }
    },
    //设置全选
    checkboxAll: function(){
        $('.zdy-checkbox').prop('checked', true).addClass('active');
    },
    //设置反选
    checkboxAllFalse: function(){
        $('.zdy-checkbox').prop('checked', false).removeClass('active');
    }
}
