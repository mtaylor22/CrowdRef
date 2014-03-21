<?php
	require 'sql_op.php';
	initialize();
	$status = add_reference($_POST['ref_text']);
  	echo json_encode(array('status'=>$status)); 
?>