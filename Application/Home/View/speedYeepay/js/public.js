
$(function(){

	/* 分类选择 */
	$(".delete").click(function(){
		$(this).parents("a").remove();
	});
	$(".groups .reset").click(function(){
		$(".checked a").remove();
		$(".groups a").removeClass("active");
	});

	/* help */
	$(".sidebar-list h6").click(function(){
		var H6 = $(this);
		$(this).next("ul").slideToggle(function(){
			if(H6.find("img").attr("data") == "true"){
				H6.find("img").attr("data","false");
				H6.find("img").attr("src", "images/help_10.png");
			}else{
				H6.find("img").attr("data","true");
				H6.find("img").attr("src", "images/help_07.png");
			}
		});
	});

	/* 挑选拖戒-拖戒选择 */
	$('.pps').bind('click', function(){
		$('.tiaoxuan-center img').attr('src', $(this).parents('.thumbnail').find('img').attr('src'));
		$('.tiaoxuan-center .rmb').text($(this).parents('.thumbnail').find('.rmbsum').text());
		$('.tiaoxuan-center .txname').text($(this).parents('.thumbnail').find('.list-name').text());
		location.href="#sellist";
		
	});

	/* 挑选拖戒-条件筛选 */
	$('.groups ul li').each(function(){
		if($(this).hasClass('checked') == false){
			$(this).find('a').bind('click', function(){
				if( $(this).attr('data') == "0" || $(this).attr('data') == undefined ){
					$(this).attr('data', "1"); //判断是否己选择条件
					$(this).addClass('active');
					var text = $(this).text();
					$('#conditions').append('<a href="javascript:;"><span class="mallhot-tag" data="'+text+'">'+text+'<i class="delete"> x</i></span></a>');
				}
			});
		}
	});

	/* 挑选拖戒-删除己选条件 */ 
	$(document).delegate('.mallhot-tag','click', function() {

		ThisText = $(this).attr('data');
		$('.groups ul li a').each(function(){
			if($(this).html() == ThisText){
				$(this).attr('data', '0');
				$(this).removeClass('active');
			}
		});
		$(this).parents("a").remove();
	});


	/* 祼钻定制->挑选祼钻->选择钻石 */
	$('.diamond-list a,.diamond-listbox a').bind('click', function(){

		if($(this).hasClass('active') == false){
			$(this).css({'background':'#f0bf4e', 'color': '#FFF'}).addClass('active');
		}else{
			$(this).css({'background':'#FFF', 'color': '#23527c'}).removeClass('active');
		}
		
	});

	/* 祼钻定制->挑选祼钻->全选全不选 */
	$('.all-checkbox').bind('click', function(){
		var AllCheckbox = $(this);
		$('#gmap-street-view table input[type="checkbox"]').each(function(){
			if(AllCheckbox.prop('checked') == false){
				$(this).prop('checked', false);
			}else{
				$(this).prop('checked', true);
			}
		});
	});

	/* 祼钻定制->挑选祼钻->全选全不选 */
	$('.user-message-checked').bind('click', function(){
		var AllCheckbox = $(this);
		$('.message-table input[type="checkbox"]').each(function(){
			if(AllCheckbox.prop('checked') == false){
				$(this).prop('checked', false);
			}else{
				$(this).prop('checked', true);
			}
		});
	});
});

