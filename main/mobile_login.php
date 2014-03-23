<?php
	require 'sql_op.php';
	initialize();
  if ($_SESSION['user_logged']){
    $status=1;
  } else {
  	if (isset($_POST['login_submit'])){
		if (user_login($_POST['login_email'], $_POST['login_password'])){
			$status=1;
		} else
			$status=0;
	} else {
    	$status=0;
	}
  }
  echo json_encode(array('status'=>$status)); 
?>