<?php
require 'sql_op.php';
initialize();
if ($_SESSION['user_logged']){
	$references = get_references($_SESSION['email']);
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>jQuery UI Progressbar - Default functionality</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.9.1.js"></script>
  <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
  <style type="text/css">
  	.ellipsis {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    width:300px;
	}
	</style>
  <script>
  <?php
  	foreach ($references as $key => $reference) {
		print ' $(function() {
		    $( "#progressbar_' . $reference['id'] . '" ).progressbar({
		      value: ' . ($reference['status']+1) . ', max: ' . ($status_cap+2) . '
		    });
		  });';
	}
  ?>
  </script>
</head>
<body>
 
<div id="progressbar"></div>
   <?php
  	foreach ($references as $key => $reference) {
  		$status = $reference['status'];
  		if ($status < $status_cap)
			$status_d = "Waiting for worker results";
		else if ($status == $status_cap)
			$status_d = "Waiting for final result";
		else if ($status > $status_cap)
			$status_d = "complete";
		print '<p><div class="ellipsis"> Reference: <a href="'.$reference['url']. '">'. $reference['url'] . '</a></div>Status: '. $status_d  .'<br><div id="progressbar_'.$reference['id'].'"></div></p>';
	}
  ?>
 
</body>
</html>