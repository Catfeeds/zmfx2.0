/* user-message */
$(function(){
	$(document).delegate('.message-list h5', 'click', function(){
		var del = $(this).next(".message-delete").width();
		if(del != "0"){
			$(this).animate({width: "100%"});
			$(this).next(".message-delete").animate({width: "0%"}).hide();
		}else{
			/*$(".message-list h5").animate({width: "100%"});
			$(".message-delete").animate({width: "0%"}).hide();*/
			$(this).animate({width: "80%"});
			$(this).next(".message-delete").animate({width: "16%"}).show();
		}		
	});
/*
	$(document).delegate('.message-delete', 'click', function(){
		$(this).parents(".panel").remove();
	});
*/
});

