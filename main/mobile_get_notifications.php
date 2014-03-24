<?php
require 'sql_op.php';
initialize();
if ($_SESSION['user_logged']){
	$notifications = get_notification_count($_SESSION['email']);
  echo json_encode(array('count'=>$notifications)); 
}
?>