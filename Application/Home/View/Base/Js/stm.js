$(function(){
	/* 收起筛选条件 */
	$('.up-screening').bind('click', function(){
		if($('.screening').hasClass('open')){
			$(this).find('.up-test').html('展开筛选条件');
			var down = $(this).find('.zhankai').attr('data-down');
			$(this).find('.zhankai').attr('src', down);
			$('.screening').css('height', '260px').removeClass('open');
		}else{
			$(this).find('.up-test').html('收起筛选条件');
			var up = $(this).find('.zhankai').attr('data-up');
			$(this).find('.zhankai').attr('src', up);
			$('.screening').css('height', 'auto').addClass('open');
		}
	});
	/* 钻石选择 */
	/* $('.diamond-icon-box .diamond-icon').bind('click', function(){
		if($(this).hasClass('active')){
			$(this).removeClass('active');
		}else{
			$(this).addClass('active');
		}
	}); */


});