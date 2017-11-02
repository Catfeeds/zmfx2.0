/*!
 *Author:
 *Website:
 *Date:
 */
/*
 *options:参见代码中setOptions中的注释
 */
var imageZoom = function(element,options) {
	this.element=element;
	this.img=element.children[0];
	this.setOptions(options);
	this.options.bigImg=element.href;
	this.init();
}
imageZoom.prototype={
	setOptions:function(options) {
		this.options={
			viewerID:"MogearViewer1",//放大框的ID
			viewerPos:{h:5,v:0},//放大框显示位置,h水平文向，v垂直方向，可使用正负数，正数表示右边，负数表示左边，默认水平偏右5px
			viewerScale:1,//放大框的放大倍数，默认为原图大小
			viewerBorderStyle:"solid 1px #d8d8d8",//放大框的边框宽度
			largeImageScale:0//图片放大倍数，默认为不放大（显示图片的原来大小）
		};
		for(var o in options) {this.options[o]=options[o];}
	},
	attachEvent:function(el,type,call){//DOM事件绑定
		if(el.addEventListener){
			el.addEventListener(type,call,false);
		}else{
			el.attachEvent("on"+type,call);
		}
	},
	getPos:function(o){//取元素XY坐标
		var x = 0, y = 0;
		do{x += o.offsetLeft; y += o.offsetTop;}
		while(o=o.offsetParent);
		return {'x':x,'y':y};
	},
	getSize:function(o) {//取元素宽高
		return {w:o.offsetWidth,h:o.offsetHeight};
	},
	init:function(){
		//创建一个放大框，并设置边框样式
		this.viewer=document.createElement("div");
		
		var _is=this.getSize(this.img);
		var pos=this.getPos(this.element);

		//放大框的top坐标
		var t=pos.y+this.options.viewerPos.v;
		if(this.options.viewerPos.v<0){
			t-=_is.h*this.options.viewerScale;
		}else if(this.options.viewerPos.v>0){
			t+=_is.h;
		}else t=pos.y;		
		//放大框的left坐标
		var l=pos.x+this.options.viewerPos.h;
		if(this.options.viewerPos.h<0){
			l-=_is.w*this.options.viewerScale;
		}else if(this.options.viewerPos.h>0){
			l+=_is.w;
		}else l=pos.x+_is.w;
		t = "240";
		l = "789";		
		//设置放大框的位置和宽高样式，最终加入DOM
		this.viewer.style.cssText="display:block;overflow:hidden;position:absolute;top:"+(t)+"px;left:"+(l)+"px;height:"+_is.h*this.options.viewerScale+"px;width:"+_is.w*this.options.viewerScale+"px;border:"+this.options.viewerBorderStyle+";";
		this.viewer.innerHTML="<iframe style='position:absolute;z-index:1;background:#ffffff;' width=\""+_is.w*this.options.viewerScale+"\" height=\""+_is.h*this.options.viewerScale+"\" marginwidth=\"0\" marginheight=\"0\" frameBorder=\"0\" border=\"0\" scrolling=\"no\"></iframe>";
		this.viewer.id=this.options.viewerID;

		//往放大框中置入大图片
		this.viewimg=document.createElement("img");
		this.viewimg.style.cssText="position:relative;z-index:9988;left:-33%;top:-33%;";
		if(this.options.largeImageScale) {//如果需要放大图片
			this.viewimg.style.width=_is.w*this.options.largeImageScale +"px";
			this.viewimg.style.height=_is.h*this.options.largeImageScale +"px";
		}
		
		//侦听鼠标移出事件
		var o=this;		
		this.attachEvent(this.element,"mouseout",function(){
			var ele=document.getElementById(o.options.viewerID);
			if (ele){
				document.body.removeChild(ele);
			}
		});
		//图片加载完成后监听mousemove事件	
		this.viewimg.onload=function(){
			o.attachEvent(o.img,"mousemove",function(event){o.move.call(o,event);});
		}
		
		//加入DOM
		this.viewer.appendChild(this.viewimg);
		document.body.appendChild(this.viewer);
		
		//开始加载大图
		this.viewimg.src=this.options.bigImg;
		
	},	
	move:function(e) {
		if(!this.options.largeImageScale){
			this.options.largeImageScale=this.viewimg.offsetHeight/this.img.offsetHeight;
		}
		var pos=this.getPos(this.img);
		var l=e.clientX-pos.x+(document.documentElement.scrollLeft || document.body.scrollLeft);//鼠标位置相对于图片左上角的偏移
		var t=e.clientY-pos.y+(document.documentElement.scrollTop || document.body.scrollTop);
		var zs=this.getSize(this.viewer);
		var pl=-l*this.options.largeImageScale+zs.w/2;
		var pt=-t*this.options.largeImageScale+zs.h/2;
		pl=pl>0?0:pl;
		pt=pt>0?0:pt;

		var vs=this.getSize(this.viewimg);
		pl=Math.max(pl,zs.w-vs.w);
		pt=Math.max(pt,zs.h-vs.h);

		this.viewimg.style.left=pl+"px";
		this.viewimg.style.top=pt+"px";
	}
};