<?php
require 'sql_op.php';
initialize();
if ($_SESSION['user_logged']){
	$notifications = get_notifications($_SESSION['email']);
  mark_notifications_read($_SESSION['email']);
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
    width:100%;
	}
  p{
    margin:0;
    padding: 0;
  }
  .viewed:nth-child(even){
    background-color:#BBB;
  }
  .viewed:nth-child(odd){
    background-color:#AAA;
  }
  .unviewed:nth-child(even){
    background-color:#BBE;
  }
  .unviewed:nth-child(odd){
    background-color:#AAD;
  }
	</style>
</head>
<body>

<?php
foreach ($notifications as $key => $notification) {
if ($notification['ref'] == -1){
  switch (urldecode($notification['action'])){
    case 'bad_url':
      $status="A URL you submitted was denied by te system because it is inaccessible or appears malicious.";
      break;
    default:
      $status = '?';
    }
    print '<div class="'. (($reference['viewed'] == 1)? 'unviewed' : 'viewed') .'"><p>'. $status  .'</p></div>';
} else {
  $reference = get_references_by_id($notification['ref'])[0];
  switch (urldecode($notification['action'])){
    case 'ref_finished':
      $status="Reference Finished";
      break;
    default:
      $status = '?';
  }
	print '<div class="'. (($reference['viewed'] == 1)? 'unviewed' : 'viewed') .'"><p><div class="ellipsis"> Reference: <a href="'. urldecode($reference['url']). '">'. urldecode($reference['url']) . '</a></div>Status: '. $status  .'</p></div>';
}
}
?>
 
</body>
</html>