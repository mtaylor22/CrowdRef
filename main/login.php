<?php
	require 'sql_op.php';
	initialize();
	if (isset($_POST['login_submit'])){
		if (user_login($_POST['login_email'], $_POST['login_password']))
			header('location: message.php?message=login');
		else
			header('location: message.php?message=error');
	}
?>
