<?php
	require 'sql_op.php';
	require 'link_verification.php';
	initialize();
	if (verify_link($_POST['ref_text']) > 0){
		set_notification("bad_url");
	} else {
		$status = add_reference($_POST['ref_text']);
  		echo json_encode(array('status'=>$status)); 
  	}
?>