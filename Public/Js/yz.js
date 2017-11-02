var checkargs = {
	checkemail:function(){
		return /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/; //邮箱验证
	},
	checktel   :function(){
		return /^([0-9]{3,4}-)?[0-9]{7,8}$/;									 //座机验证
	},
	checkphone :function(){
		return /^(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[0-9])[0-9]{8}$/;	    	//手机号码验证
	},
	checkfax :function(){
		return /^(\d{3,4}-)?\d{7,8}$/;	    									//传真号码验证
	},
	
};
/* input正则验证 */
function checkeinput(chekcname,name,msg){
		var value = $('input[name="'+name+'"]').val();
		if(chekcname == 'email'){
			if($.trim(value)){
				if(checkargs.checkemail().test(value) == false){
					if(!msg){
						msg = '邮箱格式错误';
					}
					layer.msg(msg,{
						offset:['260px'],
						shift :6,
					});
					return false;
				}
			}else{
				if(!msg){
					msg = '邮箱不能为空';
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "tel"){
			if($.trim(value)){
				if(checkargs.checktel().test(value) == false){
					if(!msg){
						msg = '座机号格式错误';
					}
					layer.msg(msg,{
						offset:['260px'],
						shift :6,
					});
					return false;
				}
			}else{
				if(!msg){
					msg = '座机号格式错误';
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "phone"){
			if($.trim(value)){
				if(checkargs.checkphone().test(value) == false){
					if(!msg){
						msg = '手机号格式错误';
					}
					layer.msg(msg,{
						offset:['260px'],
						shift :6,
					});
					return false;
				}
			}else{
				if(!msg){
					msg = '手机号不能为空';
				}
				layer.msg(msg,{
					offset:['260px'],
					shift :6,
				});
				return false;
			}
		}
		if(chekcname == "fax"){
			if($.trim(value)){
				if(checkargs.checkfax().test(value) == false){
					if(!msg){
						msg = '传真号码格式错误';
					}
					layer.msg(msg,{
						offset:['260px'],
						shift :6,
					});
					return false;
				}
			}else{
				if(!msg){
					msg = '传真号码不能为空';
				}
				layer.msg(msg,{
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
					msg = '不能少于'+minlength+'个字符';
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
					msg = '不能超过'+minlength+'个字符';
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
			msg = '不能为空';
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
					msg = '不能少于'+minlength+'个字符';
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
					msg = '不能超过'+minlength+'个字符';
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
			msg = '不能为空';
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
			msg = '不能为空';
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
			msg = '不能为空';
		}
		layer.msg(msg,{
			offset:['260px'],
			shift :6,
		});
		return false;
	}
	
}
