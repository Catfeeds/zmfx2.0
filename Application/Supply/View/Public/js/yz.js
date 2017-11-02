var  langScriptArgs = document.getElementById('yzScript').getAttribute('data');

if(langScriptArgs == 'English'){
	
    var L_email_geshi_error = 'The Email is error';  //邮箱格式错误
	var L_email_not_null    = 'Please enter your email ID '; //邮箱不能为空
    var L_tel_not_null      = 'Please enter your extension '; //座机号不能为空
    var L_phone_not_null    = 'Please enter your phone NO '; //手机号不能为空
    var L_fax_not_null      = 'Please enter your fax NO '; //传真号码不能为空
    var L_less				= 'It can’t be less than'; //不能少于
	var L_morethan          = 'It can’t be more than'; //不能多于
    var L_char				= 'characters';  //字符
	var L_not_null          = "It can't be blank"; //不能为空


}else if(langScriptArgs == '繁體'){
	var L_email_geshi_error = '郵箱格式錯誤';
	var L_email_not_null    = '郵箱不能為空';
    var L_tel_not_null      = '座機號不能為空';
    var L_phone_not_null    = '手機號不能為空';
    var L_fax_not_null      = '傳真號碼不能為空';
    var L_less				= '不能少於';
    var L_morethan          = '不能多於';
    var L_char				= '個字符';
    var L_not_null          = '不能為空';
}else{
	var L_email_geshi_error = '邮箱格式错误';
	var L_email_not_null    = '邮箱不能为空';
    var L_tel_not_null      = '座机号不能为空';
    var L_phone_not_null    = '手机号不能为空';
    var L_fax_not_null      = '传真号码不能为空';
    var L_less				= '不能少于';
    var L_morethan          = '不能多于';
    var L_char				= '个字符';
    var L_not_null          = '不能为空';
}


var checkargs = {
	checkemail:function(){
		return /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/; //邮箱验证
	},
	checktel   :function(){
		return /^([0-9]{3,4}-)?[0-9]{7,8}$/;									 //座机验证
	},
	checkphone :function(){
		return /^(13[0-9]|15[0-9]|17[678]|18[0-9]|14[57])[0-9]{8}$/;	    	//手机号码验证
	},
	checkfax :function(){
		return /^(\d{3,4}-)?\d{7,8}$/;	    									//传真号码验证
	},
	
};
/* input正则验证 */
function checkeinput(chekcname,name){
		var value = $('input[name="'+name+'"]').val();
		if(chekcname == 'email'){
			if($.trim(value)){
				if(checkargs.checkemail().test(value) == false){
					layer.msg(L_email_geshi_error ,{
						offset:['260px'],
						shift :6,
					});
					return false;
				}
			}else{
				layer.msg(L_email_not_null,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "tel"){
			if($.trim(value)){
				// if(checkargs.checktel().test(value) == false){
				// 	layer.msg('座机号格式错误',{
				// 		offset:['260px'],
				// 		shift :6,
				// 	});
				// 	return false;
				// }
			}else{
				layer.msg(L_tel_not_null,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "phone"){
			if($.trim(value)){
				// if(checkargs.checkphone().test(value) == false){
				// 	layer.msg('手机号格式错误',{
				// 		offset:['260px'],
				// 		shift :6,
				// 	});
				// 	return false;
				// }
			}else{
				layer.msg(L_phone_not_null,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "fax"){
			if($.trim(value)){
				if(checkargs.checkfax().test(value) == false){
					// layer.msg('传真号码格式错误',{
					// 	offset:['260px'],
					// 	shift :6,
					// });
					// return false;
				}
			}else{
				layer.msg(L_fax_not_null,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
	
}
/* input长度验证 
	name：input的name值
	minlength：最小的长度
	maxlength：最长的长度
	msg：错误提示信息
*/
function checkeinput_length(name,minlength,maxlength,msg){
	var value = $('input[name="'+name+'"]').val();
	if($.trim(value)){
		if(minlength){
			if(value.length<minlength){
				if(!msg){
					msg = L_less+minlength+L_char;
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(maxlength){
			if(value.length>maxlength){
				if(!msg){
					msg = L_morethan +minlength+L_char;
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
	}else{
		if(!msg){
			msg = L_not_null;
		}
		layer.msg(msg,{
			offset:['260px'],
			shift :6,
		});
		return false;
	}
}
/* textarea长度验证 */
function checketextarea_length(name,minlength,maxlength,msg){
	var value = $('textarea[name="'+name+'"]').val();
	if($.trim(value)){
		if(minlength){
			if(value.length<minlength){
				if(!msg){
					msg = L_less+minlength+L_char;
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(maxlength){
			if(value.length>maxlength){
				if(!msg){
					msg = L_less+minlength+L_char;
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
	}else{
		if(!msg){
			msg = L_not_null;
		}
		layer.msg(msg,{
			offset:['260px'],
			shift :6,
		});
		return false;
	}
}	

/* select值验证 */
function checkeselect(name,msg){
	var value = $('select[name="'+name+'"]').val();
	if(!$.trim(value)){
		if(!msg){
			msg = L_not_null;
		}
		layer.msg(msg,{
			offset:['260px'],
			shift :6,
		});
		return false;
	}
}

/* 选中验证 */
function checke_checked(name,msg){
	var chk_value =[];
	$('input[name="'+name+'"]:checked').each(function(){ 
		chk_value.push($(this).val()); 
	}); 
	if(chk_value.length == 0){
		if(!msg){
			msg = L_not_null;
		}
		layer.msg(msg,{
			offset:['260px'],
			shift :6,
		});
		return false;
	}
	
}
