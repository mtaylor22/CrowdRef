<?php
	session_start();
	if (!isset($_SESSION['user_logged'])){
		print 'you need to be logged in';
		exit(1);
	}
	require 'amtapi/amt_rest_api.php';

	try {
		$balance = amt\balance_request::execute();
		echo "Balance is $balance\n";
	} catch (amt\Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
?>
work 