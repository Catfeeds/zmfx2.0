/***模拟百度贴吧的分页**/
var  langScriptArgs = document.getElementById('pageScript').getAttribute('data');

if(langScriptArgs == 'English'){
	var L_prev 	  = 'PREV';
	var L_Next 	  = 'NEXT';
	var L_First   = 'HOME';
	var L_Last 	  = 'LAST';
	var L_Total   = 'total: ';
	var L_Records = ' Records ';
	var L_Pages   = ' Pages ';
}else if(langScriptArgs == '繁體'){
	var L_prev 	  = '上壹個';
	var L_Next 	  = '下壹個';
	var L_First   = '首頁';
	var L_Last 	  = '尾頁';
	var L_Total   = '共';
	var L_Records = '條';
	var L_Pages   = '頁';
}else{
	var L_prev 	  = '上一个';
	var L_Next 	  = '下一个';
	var L_First   = '首页';
	var L_Last 	  = '尾页';
	var L_Total   = '共';
	var L_Records = '条';
	var L_Pages   = '页';
}

var page = {
	data:{},             //运行时的中间数据


    options:{
		PageID:1,        //当前第几页
        TotalNum:0,      //数据总数    
        PageSize:50,     //每页的行数
        //下面是常用的静态设置
        PageSum:1,       //总页数
        DisplayMark:10,  //页面数字最多显示多少列
        Prev:L_prev,
        Next:L_Next,
        First:L_First,
        Last:L_Last,
		//下面的设置用于post提交的场景
		Url:'',          //地址
		Format:'',
		PageArgsStr:'page_id',  //href连接的分页name，href会增加?p=
		PageSizeArgsStr:'page_size',//页面数据的pagesize参数
		SetPageSize:true,
	},
	create:function(options){
        for(var key in this.options){
            this.options[key] = options[key] || this.options[key];
        }
        options = this.options;
		var arr = new Array();
		if(options.TotalNum < 1 ){
			arr[1] = 1;
		}
		options.PageSum = Math.ceil(options.TotalNum/options.PageSize); //总页数
		if(options.PageSize >= options.TotalNum ){
            if (options.PageSum < options.PageID ){ //无数据
            	arr = null;
			}else{
                arr[1] = 1;       
            }
		}else{
			if (options.PageSum < options.PageID ){ //无数据
				arr = null;
			}else {
				if ( options.PageSum > options.DisplayMark ){
					if (options.PageID <= Math.ceil(options.DisplayMark/2)){
						var begin = 1;
						var total = options.DisplayMark;
					} else if (options.PageID>Math.ceil(options.DisplayMark/2) && options.PageID < options.PageSum - Math.floor(options.DisplayMark/2)){
						var begin = options.PageID - Math.ceil(options.DisplayMark/2)+1;
						var total = options.DisplayMark;
					} else if (options.PageID >= options.PageSum - Math.floor(options.DisplayMark/2)) {
						var begin = options.PageSum - options.DisplayMark+1;
						var total = options.DisplayMark;
					}
				}else {
					var begin = 1;
					var total = options.PageSum;
				}
				for( var i = 0;i < total; i++ ){
					arr[begin+i] = begin+i;
				}
			}
		}
		this.data = arr;
		return this;
	},
	getHtml:function(){
		var _PageArr = this.data;
		var html     = '';
		if(_PageArr){
			html  = '<ul class="pagination pagination-sm">';
			html += '<li><input name="page_size" value="'+this.options.PageSize+'" type="text" />' +  L_Records +'/'+ L_Pages +'</li>';


			html += (this.options.PageID==1?'':'<li><a data-page-id="1" data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.First+'</a></li><li><a data-page-id="'+(Number(this.options.PageID)-1)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Prev+'</a></li>');
			for(var value in _PageArr){
				if ( value == this.options.PageID){
					html += '<li class="active"><span>'+value+'</span></li>';
				}else {
					html += '<li><a data-page-id="'+value+'" data-page-size = "'+this.options.PageSize+'"  href="javascript:void(0);">'+value+'</a></li>';
				}
			}
			html += (this.options.PageID==this.options.PageSum?'':('<li><a data-page-id='+(Number(this.options.PageID)+1)+' data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.Next+'</a></li><li><a data-page-id="'+(this.options.PageSum)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Last+'</a></li>'));
			html += '<li class="page">' + L_Total +this.options['TotalNum']+ L_Records +'/'+this.options['PageSum']+ L_Pages +'</li>';
			html += '<li><input name="page_id" value="'+this.options.PageID+'" type="text" /><button class="go btn btn-sm btn-primary" >GO</button></li>';
			html += '</ul>';
		}else{
			html  = '';
		}
		return html;
	},
	getHrefHtml:function(){
		var _PageArr = this.data;
		var html     = '';
		if(_PageArr){
			html = (this.options.PageID==1?'':'<a href="'+this.options.Url+this.options.Format+this.options.PageArgsStr+'=1'+(this.options.SetPageSize?('&'+this.options.PageSizeArgsStr+'='+this.options.PageSize):'')+'">'+this.options.First+'</a><a href="'+this.options.Url+this.options.Format+this.options.PageArgsStr+'='+((this.options.PageID-1))+(this.options.SetPageSize?('&'+this.options.PageSizeArgsStr+'='+this.options.PageSize):'')+'">'+this.options.Prev+'</a>');
			for(var value in _PageArr){
				if ( value == this.options.PageID){
					if(value != 1 ){
						html += ',';
					}
					html += '<span>'+value+'</span>';
				}else {
					html += ',<a href="'+this.options.Url+this.options.Format+this.options.PageArgsStr+'='+(value)+(this.options.SetPageSize?('&'+this.options.PageSizeArgsStr+'='+this.options.PageSize):'')+'">'+value+'</a>';
				}
			}
			html += (this.options.PageID==this.options.PageSum?'':('<a href="'+this.options.Url+this.options.Format+this.options.PageArgsStr+'='+((this.options.PageID+1))+(this.options.SetPageSize?('&'+this.options.PageSizeArgsStr+'='+this.options.PageSize):'')+'">'+this.options.Next+'</a><a href="'+this.options.Url+this.options.Format+this.options.PageArgsStr+'='+(this.options.PageSum)+(this.options.SetPageSize?('&'+this.options.PageSizeArgsStr+'='+this.options.PageSize):'')+'">'+this.options.Last+'</a>'));
		}else{
			html  = '';
		}
		return html;
	},
}