    /**
     * 自用AJAX 3.0
     * zhy	find404@foxmail.com
     * 2017年8月11日 11:58:53
     */
	var ThreeAjax = {
			Interactive : [],
			Send	: 	(function(){
						var Url		  = (this.Interactive[0].Url==undefined) 		? window.location.pathname : this.Interactive[0].Url;
						var Operation = (this.Interactive[0].Operation==undefined)  ? 'post'				   : this.Interactive[0].Operation;
						$[Operation](Url, this.Interactive[0],function(data) {
							if(data){
								if(data.ret==100 || data.status==100){
									(ThreeAjax.Interactive[1] == undefined)			?	'' : ThreeAjax.Interactive[1](data);
								}else{
									if(ThreeAjax.Interactive[2]!=undefined){
										if(typeof ThreeAjax.Interactive[2]=='string'){
											ThreeAjax.tan(data[ThreeAjax.Interactive[2]]);
										}else{
											ThreeAjax.Interactive[2](data);
										}
									}else{
										ThreeAjax.tan('数据有误！');
									}
								}
							}else{
								if(ThreeAjax.Interactive[3]==undefined){
										ThreeAjax.tan('网络错误！');
									}else{
										ThreeAjax.Interactive[3];
								}
							}
						});
				}),
				
			tan  : function(msg){
					layer.open({
					   content: msg,
					   time: 2
					});
					return false;
			},

			JsB64 :function(str){
				var c1, c2, c3;
				var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";        
				var i = 0, len= str.length, JsB64String = '';
				while (i < len){
					c1 = str.charCodeAt(i++) & 0xff;
					if (i == len){
						JsB64String += base64EncodeChars.charAt(c1 >> 2);
						JsB64String += base64EncodeChars.charAt((c1 & 0x3) << 4);
						JsB64String += "==";
						break;
					}
					c2 = str.charCodeAt(i++);
					if (i == len){
						JsB64String += base64EncodeChars.charAt(c1 >> 2);
						JsB64String += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
						JsB64String += base64EncodeChars.charAt((c2 & 0xF) << 2);
						JsB64String += "=";
						break;
					}
					c3 = str.charCodeAt(i++);
					JsB64String += base64EncodeChars.charAt(c1 >> 2);
					JsB64String += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
					JsB64String += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
					JsB64String += base64EncodeChars.charAt(c3 & 0x3F);
				}
				return JsB64String;
			},
	};
 
 
	
	//中间层
	var MiddleLayer = function(RedayData,SuccessAction,NullAction,ErrorAction) {
		ThreeAjax.Interactive = [];
		ThreeAjax.Interactive.push(RedayData);	
		ThreeAjax.Interactive.push(SuccessAction);
		ThreeAjax.Interactive.push(NullAction);	
		ThreeAjax.Interactive.push(ErrorAction);
		ThreeAjax.Send();
	}

	//字符串处理
	var StringManage = {
		LPosition		: function(OriginalString){													//最后一位				
			return	OriginalString.charAt(OriginalString.length-1);
		},
		LReplace		: function(OriginalString,factString){										//替换字符串操作
			return	OriginalString.substring(0,OriginalString.length-1)+factString;
		},
		LTrim			: function(OriginalString){													//去除最后一位操作
			return	OriginalString.substring(0,OriginalString.length-1);
		},
		TimeStamp		: function(Ttime){															//时间戳转换					
				var date = new Date(Ttime * 1000);
				var M = (date.getMonth()+1 		< 10)   ? 	'0'+(date.getMonth()+1)		: date.getMonth()+1;
				var H = (date.getHours()+1		< 10)   ? 	'0'+(date.getHours())   	: date.getHours();
				var S = (date.getMinutes()+1	< 10)	? 	'0'+(date.getMinutes()) 	: date.getMinutes();
				var D = (date.getDate()			< 10)   ?	'0'+date.getDate()			: date.getDate()+'';
			return (M+'-'+D+' '+H+':'+S);   
		},
		VerifDateYmd		: function(DateString) {												//判断Y-M-D时间
				var result = DateString.match(/^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2})$/);
				var d 		= new Date(result[1], result[3] - 1, result[4]);
            return (d.getFullYear() == parseInt(result[1]) && (d.getMonth() + 1) == parseInt(result[3]) && (d.getDate()) == parseInt(result[4]));
        },
		VerifDateHms		: function(DateString) {												//判断H:M:S时间
				var Hms = DateString.match(/^(\d{1,2})(:)?(\d{1,2})\2(\d{1,2})$/);
			return (Hms[1]>24 || Hms[3]>60 || Hms[4]>60);
        },
		VerifDateYmdHms		: function(DateString) {												//判断Y-M-D  H:M:S时间
				var reg = /^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})$/; 
				var result = str.match(reg); 
				var d= new Date(result[1], result[3]-1,result[4],result[5],result[6],result[7]); 
			return (d.getFullYear()==r[1]&&(d.getMonth()+1)==r[3]&&d.getDate()==r[4]&&d.getHours()==r[5]&&d.getMinutes()==r[6]&&d.getSeconds()==r[7]);
        },
		VerifPhone			: function(phone){														//验证手机号码
			return (/^1[3|4|5|8][0-9]\d{4,8}$/.test(phone));
		},
		VerifEmail			: function(Email){														//验证手机号码
			return (/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/.test(Email));
		},
	}
	

    /**
     * 简易版本验证类
     * zhy	find404@foxmail.com	
     * 2017年8月19日 16:30:48
     */
	var VerifData ={										
			InitCondition	 : {												//初始化条件
								Func		: '',								//方法名
								Div			: '',								//那一个DIV下的
								Label		: ' input',							//默认标签
								Status		: 0,
							   },
			Condition		: {},												//发送数据层
			Main		 	: function(){										//验证主体
								var _this = this;
								$(this.InitCondition.Div+this.InitCondition.Label).each(function(k,v){
									if($(this)[0].type != 'checkbox'){
										if($(this).val()== ''){
											CommonFunction.Prompt.Tan($(this)[0].placeholder);
											_this.InitCondition.Status = 0;
											return false;
										}
										if($(this).attr('Func')){
											var FunArray = CommonFunction.String.ConvertsKeyword($(this).attr('Func'));
											if(CommonFunction[FunArray[0]][FunArray[1]]($(this).val())){
												CommonFunction.Prompt.Tan(CommonFunction.String.CutKeyword($(this)[0].placeholder,'的')+'输入的格式有误，请更正后在填写！');
												_this.InitCondition.Status = 0;
												return false;
											}
										}
										_this.Condition[$(this)[0].name] =$(this).val();
										_this.InitCondition.Status = 1;
									}
								})
							   },
	}

    /**
     * 最终版本AJAX 4.0
     * zhy	find404@foxmail.com	
     * 2017年8月17日 18:08:47
     */
	var BringAjax = {
			IntendData	 : {													
							StopgapPage		: 0,							//页面翻页
							Obj				: 'data.data',					//回调回来的数据产生点
							SwitchMain		: '',							//默认用既定AJAX模式
							ChangeHtml		: '',							//改变的页面class
							NullHtml		: '',							//当无数据时候的class
							Message			: 'msg',						//默认提示消息值
							Url				: window.location.pathname,		//置换的产生HTML代码层
							Operation		: 'post',						//通过请求回来的数据
							CacheHtmlKey	: 'Stopgap',					//设置默认读取html缓存KEY，如果不用缓存的情况下，只是单纯的存储
							CacheDataKey	: 'Stopgap',					//设置默认读取ajax数据缓存KEY，如果不用缓存的情况下，只是单纯的存储
							ClickPosition	: '.ClickEvent',				//默认点击事件发起点
							SuccessEvet		: '',							//后置事件，配合点击事件用
							SubstituteMode  : '',
							ModeOne 		: 'html',
							ModeTwo 		: 'append',
							},
			CacheHtml	 : {												//Html缓存层
							Stopgap	: '',
							},												
			CacheData	 : {												//数据缓存层
							Stopgap	: '',
							},												//接受POST数据缓存层
			Interactive	 : [],
			Send		 : 	function(){
								var _this = this ;
								$[this.IntendData.Operation](this.IntendData.Url, this.Interactive[0],function(data) {
									if(data){
										_this.CacheData[_this.IntendData.CacheHtmlKey] 		= 	data;
										if(_this.CacheData[_this.IntendData.CacheHtmlKey].iden==100){
											if(_this.IntendData.SwitchMain){
												(_this.Interactive[1] == undefined)			?	CommonFunction.Prompt.Tan(BringAjax.CacheData[BringAjax.IntendData.CacheHtmlKey].msg) : _this.Interactive[1](_this.CacheData[_this.IntendData.CacheHtmlKey]);
												(_this.IntendData.SuccessEvet) &&	_this.IntendData.SuccessEvet();	
											}else{
												//默认用既定AJAX模式，
												if($.isEmptyObject(_this.CacheData[_this.IntendData.CacheHtmlKey].data) == false){
													_this.Interactive[1]();
													_this.IntendData.SubstituteMode = (_this.IntendData.StopgapPage==0)  ? _this.IntendData.ModeOne  : _this.IntendData.ModeTwo;
													$(_this.IntendData.ChangeHtml)[_this.IntendData.SubstituteMode](_this.CacheHtml[_this.IntendData.CacheHtmlKey]);
												}else{
													(_this.IntendData.StopgapPage==0) ?	$(_this.IntendData.NullHtml).show() : $('.loading').html('没有了~~');
												}
											}
										}else{
											if(_this.Interactive[2]==undefined){
												CommonFunction.Prompt.Tan(_this.CacheData[_this.IntendData.CacheHtmlKey][_this.IntendData.Message]);
											}else{
												_this.Interactive[2](_this.CacheData[_this.IntendData.CacheHtmlKey]);
											}
										}
									}else{
										if(_this.Interactive[3]==undefined){
												CommonFunction.Prompt.Tan('网络错误！');
										}else{
												_this.Interactive[3];
										}
									}
								});
							},
	};
 	
	

	
	//简易过渡层
	var EasyTransition = function(RedayData,SuccessAction,NullAction,ErrorAction) {
		BringAjax.Interactive = [];
		BringAjax.Interactive.push(RedayData);	
		BringAjax.Interactive.push(SuccessAction);
		BringAjax.Interactive.push(NullAction);	
		BringAjax.Interactive.push(ErrorAction);
		BringAjax.Send();
	}
	 
	
	//简易点击事件发起
	var EasyClick = function() {
		$(BringAjax.IntendData.ClickPosition).click(function(){
			BringAjax.Send();
		});
	}	

    /**
     * 策略模式的多用版，公共方法类
     * zhy	find404@foxmail.com	
     * 2017年8月19日 16:30:48		
     */	
	var CommonFunction ={
		String : {
			Position		: function(OriginalString){													//最后一位				
				return	OriginalString.charAt(OriginalString.length-1);
			},
			Replace		: function(OriginalString,factString){											//替换字符串操作
				return	OriginalString.substring(0,OriginalString.length-1)+factString;
			},
			Trim		: function(OriginalString){														//去除最后一位操作
				return	OriginalString.substring(0,OriginalString.length-1);
			},
			Tength		: function(OriginalString){														//判断长度
				return (!(/^\S{2,10}$/.test(OriginalString)));  
			},																							
			Tength100	: function(OriginalString){														//判断长度
				return (!(/^\S{2,100}$/.test(OriginalString)));  
			},				
			CutKeyword  : function(OriginalString,Keyword){												//根据关键字从左至右截取后面的值。
				return OriginalString.substring(OriginalString.indexOf(Keyword)+1);  
			},
			CutLastKeyword  : function(OriginalString,Keyword){											//根据关键字从右至左截取后面的值。
				return OriginalString.substring(OriginalString.lastIndexOf(Keyword)+1);  
			},
			ConvertsKeyword : function(OriginalString,Keyword){											//字符串通过关键字转换数组
				return OriginalString.split(/\,/);  
			},
		},
		Time :{
			TimeStamp		: function(Ttime){															//时间戳转换					
					var date = new Date(Ttime * 1000);
					var M = (date.getMonth()+1 		< 10)   ? 	'0'+(date.getMonth()+1)		: date.getMonth()+1;
					var H = (date.getHours()+1		< 10)   ? 	'0'+(date.getHours())   	: date.getHours();
					var S = (date.getMinutes()+1	< 10)	? 	'0'+(date.getMinutes()) 	: date.getMinutes();
					var D = (date.getDate()			< 10)   ?	'0'+date.getDate()			: date.getDate()+'';
				return (M+'-'+D+' '+H+':'+S);   
			},
			TimeStamp2		: function(Ttime){															//时间戳转换					
					var date = new Date(Ttime * 1000);
					var M = (date.getMonth()+1 		< 10)   ? 	'0'+(date.getMonth()+1)		: date.getMonth()+1;
					var D = (date.getDate()			< 10)   ?	'0'+date.getDate()			: date.getDate()+'';
				return (M+'-'+D);   
			},
			
		},
		Verif :{
			DateYmd		: function(DateString) {														//判断Y-M-D时间
					var result = DateString.match(/^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2})$/);
					var d 		= new Date(result[1], result[3] - 1, result[4]);
				return (d.getFullYear() == parseInt(result[1]) && (d.getMonth() + 1) == parseInt(result[3]) && (d.getDate()) == parseInt(result[4]));
			},
			DateHms		: function(DateString) {														//判断H:M:S时间
					var Hms = DateString.match(/^(\d{1,2})(:)?(\d{1,2})\2(\d{1,2})$/);
				return (Hms[1]>24 || Hms[3]>60 || Hms[4]>60);
			},
			DateYmdHms		: function(DateString) {													//判断Y-M-D  H:M:S时间
					var reg = /^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})$/; 
					var result = str.match(reg); 
					var d= new Date(result[1], result[3]-1,result[4],result[5],result[6],result[7]); 
				return (d.getFullYear()==r[1]&&(d.getMonth()+1)==r[3]&&d.getDate()==r[4]&&d.getHours()==r[5]&&d.getMinutes()==r[6]&&d.getSeconds()==r[7]);
			},
			Phone			: function(phone){															//验证手机号码
				return !(/^1[3|4|5|8][0-9]\d{4,8}$/.test(phone));
			},
			Email			: function(Email){															//验证邮箱
				return !(/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/.test(Email));
			},
			Password		: function(Password){														//验证密码，6-16位数字字母
				return !(/^[A-Za-z0-9]{6,20}$/.test(Password));
			},
		},
		Prompt :{																						//提示
			Tan  		: function(msg){
				layer.open({
				   content: msg,
				   time: 2
				});
				return false;
			},
		},
		Link   :{
			Href		: function(url,second){					
					setTimeout("window.location.href='"+url+"'",(second) ? second : 3000);
			},
		},
		SecretKey :{																					//加密解密
			JsB64 		: function(str){
								var c1, c2, c3;
								var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";        
								var i = 0, len= str.length, JsB64String = '';
								while (i < len){
									c1 = str.charCodeAt(i++) & 0xff;
									if (i == len){
										JsB64String += base64EncodeChars.charAt(c1 >> 2);
										JsB64String += base64EncodeChars.charAt((c1 & 0x3) << 4);
										JsB64String += "==";
										break;
									}
									c2 = str.charCodeAt(i++);
									if (i == len){
										JsB64String += base64EncodeChars.charAt(c1 >> 2);
										JsB64String += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
										JsB64String += base64EncodeChars.charAt((c2 & 0xF) << 2);
										JsB64String += "=";
										break;
									}
									c3 = str.charCodeAt(i++);
									JsB64String += base64EncodeChars.charAt(c1 >> 2);
									JsB64String += base64EncodeChars.charAt(((c1 & 0x3) << 4) | ((c2 & 0xF0) >> 4));
									JsB64String += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >> 6));
									JsB64String += base64EncodeChars.charAt(c3 & 0x3F);
								}
								return JsB64String;
						},
			
		}

	} 
 