<?php
	require 'sql_op.php';
	initialize();
	if (isset($_POST['ref_submit'])){
		switch (add_reference($_POST['ref_text'])){
			case 0:
				print 'ref added';
				break;
			case 1:
				print 'database is not connected';
				break;
			case 2:
				print 'ref failed, you need to log in.';
				break;
			case 3:
				print 'ref failed, mysql error:';
				break;
			default:
				print 'unknown status';
				break;
		}
	} else {
		print 'How did you get here?';
	}
?>