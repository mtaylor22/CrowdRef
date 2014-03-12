<?php
	require 'sql_op.php';
	initialize();
	if (isset($_POST['login_submit'])){
		if (user_login($_POST['login_email'], $_POST['login_password']))
			print 'password accepted';
		else
			print 'password denied';
	}
?>
