
//输入框删除事件
  $(document).ready(function(){
    $('.form-control').keyup(function(){
        if($('.form-control').val() == ''){
           $('#clear').css('display','none'); 
        }else{
           $('#clear').css('display', 'block'); 
        }
      });  

   $(function(){  
     $('#clear').click(function(){
       $('#clear').css('display','none'); 
       $('.form-control').val("");  
      });  
    });  
  });  

    /* document.getElementById("control").addEventListener("keyup",function(){
        if(this.value.length>0)
        {
            document.getElementById("clear").style.visibility="visible";
            document.getElementById("clear").onclick=function()
            {
                document.getElementById("control").value="";
            }
        }else
        {
            document.getElementById("clear").style.visibility="hidden";
        }
    });*/