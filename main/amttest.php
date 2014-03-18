<?php
	session_start();
	if (!isset($_SESSION['user_logged'])){
		print 'you need to be logged in';
		exit(1);
	}
	require 'amt_op.php';

	// print amt_get_balance
	// create_request();

	execute_job('http://www.ebay.com/');
?> hit?