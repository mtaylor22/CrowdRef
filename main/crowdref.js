var slide_open = false; 
function login_slide_toggle(){
	if (slide_open){
		login_slide_closed();
		slide_open=false;
	} else {
		login_slide_open();
		slide_open=true;
	}
}
function login_slide_open(){
	$('#login_container').animate({height: "130px"}, 500);
}
function login_slide_closed(){
	$('#login_container').animate({height: "30px"}, 500);
}
function ref_text_focus(){
	if ($('#ref_text').val() == 'Enter URL'){
		$('#ref_text').css('color', '#000');
		$('#ref_text').val('');
	}
}
function ref_text_unfocus(){
	if ($('#ref_text').val() == ''){
		$('#ref_text').css('color', '#444');
		$('#ref_text').val('Enter URL');
	}
}