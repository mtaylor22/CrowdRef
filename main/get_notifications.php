<?php
require 'sql_op.php';
initialize();
if ($_SESSION['user_logged']){
	$notifications = get_notifications($_SESSION['email']);
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CrowdRef - Notifications</title>
  <style type="text/css">
  	.ellipsis {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    width:300px;
	}
	</style>
</head>
<body>
 
<div id="progressbar"></div>
   <?php
  	foreach ($notifications as $key => $notification) {
    $reference = get_references_by_id($notification['ref'])[0];
    switch ($notification['action']){
      case 'ref_finished':
        $status="Reference Finished";
        break;
      default:
        $status = '?';
    }
		print '<p><div class="ellipsis"> Reference: <a href="'.$reference['url']. '">'. $reference['url'] . '</a></div>Status: '. $status  .'</p>';
	}
  ?>
 
</body>
</html>