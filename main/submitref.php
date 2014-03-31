<?php
	require 'sql_op.php';
	require 'link_verification.php';
	initialize();
	if (isset($_POST['ref_submit'])){
		if (verify_link($_POST['ref_text']) > 0){
			set_notification("bad_url", -1, $_SESSION['email']);
		} else {
			switch (add_reference(urlencode($_POST['ref_text']))){
				case 0:
					// print 'ref added';
					header('location: message.php?message=submit');
					break;
				case 1:
					// print 'database is not connected';
					header('location: message.php?message=error');
					break;
				case 2:
					// print 'ref failed, you need to log in.';
					header('location: message.php?message=error');
					break;
				case 3:
					// print 'ref failed, mysql error:';
					header('location: message.php?message=error');
					break;
				default:
					// print 'unknown status';
					header('location: message.php?message=error');
					break;
				}
		}
	} else {
		header('location: message.php?message=error');
	}
?>