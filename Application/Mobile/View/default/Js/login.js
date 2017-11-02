//验证初始化
$('#regist').validator({
	theme: 'simple_right',
	focusCleanup: true,
	stopOnError: false,
	//debug: true,
	timely: 2,
	//自定义规则（PS：建议尽量在全局配置中定义规则，统一管理）
	rules: {
		username: [/^[a-zA-Z0-9]+$/, '用户名无效! 仅支持字母与数字。'],
		phone: [/^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/, '手机号码不正确'],
		email: [/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/, '邮箱格式不正确'],
		limitusername:
        	function(element){               
        		if(element.value.length<3){
        			return false;
        		}else{
        			return true;
        		}
        	},
		remote: function(element) {
			return $.ajax({
				url: '/Home/Public/checkusername',
				type: 'post',
				data: element.name + '=' + element.value,
				dataType: 'json',
				success: function(d) {
					window.console && console.log(d);
				}
			});
		}
	},

	fields: {
		"username": {
			rule: "required;limitusername;",
            tip: "输入你的用户名",
            ok: "",
            msg: {required: "请输入用户名",limitusername:"用户名最小长度为3"}
		},
		"password": {
			rule: "required",
			tip: "",
			msg: {
				required: ""
			}
		}
	},
	//验证成功
	valid: function(form) {
		$.ajax({
			url: '/Public/login',
			type: 'POST',
			data: $(form).serialize(),
			success: function(d) {
				layer.open({
					content:d.msg,
				})
				if (d.success) {
					setTimeout("window.location.href = ' "+d.url+" ' ",1500);
				}
			}
		});
	},

});