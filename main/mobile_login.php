<?php
	require 'sql_op.php';
	initialize();
  if ($_SESSION['user_logged']){
    $status=1;
  } else {
    $status=0;
  }
  echo json_encode(array('status'=>$status)); 
?>