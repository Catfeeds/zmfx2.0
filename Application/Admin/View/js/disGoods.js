$(function(){
	layer.config({
	    extend: 'extend/layer.ext.js'
	});
	$('td[data-photos] img').click(function(){
		var json = {
		    "title": "产品图片",
		    "id": 0, //相册id
		    "start": $(this).attr('data-key'), //初始显示的图片序号，默认0
		    "data": []   //相册包含的图片，数组格式
		};
		$(this).parents('td[data-photos]').children('img').each(function(index){
			json.data[index] = {
				"alt": "图片名",
	            "pid": index, //图片id
	            "src": $(this).attr('Big'), //原图地址
	            "thumb": $(this).attr('src') //缩略图地址
			}
		});
	    layer.photos({
	        photos: json
	    });
	})
})