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
  <link rel="stylesheet" href="/resources/demos/style.css">
  <script>
  <?php
  	foreach ($references as $key => $reference) {
		print ' $(function() {
		    $( "#progressbar_'. $reference['id'] .'" ).progressbar({
		      value: '.$reference['status'].', max: '. ($status_cap+1) .'
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
		print $reference['url']. '<br><div id="progressbar_'.$reference['id'].'"></div>';
	}
  ?>
 
</body>
</html>