<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>跳转提示</title>
<style type="text/css">
a, input, select, textarea { outline: none; }
body { margin: 0px; padding: 0px; text-align: center; font-family: Tahoma, Geneva, sans-serif; font-size: 13px; }
.box { margin-top: 80px; width: 550px; margin-right: auto; margin-left: auto; padding: 1px; background-color: #e0e0e0; }
.box #title { background-color: #f0f0f0; height: 30px; text-align: left; line-height: 30px; font-size: 13px; margin: 0px; padding-left: 10px; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #e0e0e0; }
.box #content { background-color: #FFF; padding: 50px 0; }
.box #content p { font-size: 16px; margin: 0; padding: 0; }
.errno { color:#e00; }
.box #content p a { display: block; font-size: 14px; margin: 15px; color: #555; text-decoration: none; }
.box #content p a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="box">
  <div id="title"><?php if(LANG_SET !='zh-cn'){ echo L('Jump').L('notice'); }else{	echo '跳转提示';}?></div>
  <div id="content">
	<p>
 
		<?php if(isset($message)) {?>
		<?php echo($message); ?>
		<?php }else{?>
		<?php echo($error); ?>
		<?php }?>
		<br>
		<a id="href" href="<?php echo($jumpUrl); ?>">
		<?php if(LANG_SET !='zh-cn'){ ?>
		<?php	 echo L('Jump'); ?>
		<?php }else{	?>
		<?php	 echo '跳转';?>
		<?php }?>
		</a> 
		<?php if(LANG_SET !='zh-cn'){ ?>
		<?php	 echo L('wait_time'); ?>
		<?php }else{	?>
		<?php	 echo '等待时间';?>
		<?php }?>
		 ： <b id="wait"><?php echo($waitSecond); ?>
	</p> 
</div>
</div>

</body>
<script type="text/javascript">
    if (!!window.ActiveXObject || "ActiveXObject" in window){
		 		var interval = setInterval(function(){
					location.href = '<?php echo 'http://'.$_SERVER['HTTP_HOST']; ?>';
				}, 3000);
		 document.getElementById('wait').innerHTML='3秒之后跳转...';
	}else{
		(function(){
		var wait = document.getElementById('wait'),href = document.getElementById('href').href;
		var interval = setInterval(function(){
			var time = --wait.innerHTML;
			if(time <= 0) {
				location.href = href;
				clearInterval(interval);
			};
		}, 1000);
		})();
	}
</script>
</html>