<?php
	require 'sql_op.php';
	require 'link_verification.php';
	initialize();
	$url = $_GET['ref_text'];
	$url = urldecode(stripslashes(nl2br($url)));
	if (verify_link($url) > 0){
		trigger('badurl');
		set_notification("bad_url", NULL);
		$status=1;
	} else {
		$status = add_reference($_GET['ref_text']);
		trigger('goodurlurl: '.$status);
  	}
  		echo json_encode(array('status'=>$status)); 
?>