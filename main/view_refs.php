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
  <title>CrowdRef - References</title>
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
  <script type="text/javascript">
    var references = new Array();
    <?php
      foreach ($references as $key => $reference) {
        if ($reference['status'] > $status_cap){
          $correct_reference = get_correct_references_by_id($reference['id']);
          print 'references['.$reference['id'].'] = new Array();';
          print 'references['.$reference['id'].']["title"] = "'. $correct_reference[0]['title'] .'";';
          print 'references['.$reference['id'].']["author"] = "'. $correct_reference[0]['author'] .'";';
          print 'references['.$reference['id'].']["website_title"] = "'. $correct_reference[0]['website_title'] .'";';
          print 'references['.$reference['id'].']["publisher"] = "'. $correct_reference[0]['publisher'] .'";';
          print 'references['.$reference['id'].']["date_published"] = "'. $correct_reference[0]['date_published'] .'";';
          print 'references['.$reference['id'].']["date_accessed"] = "'. $correct_reference[0]['date_accessed'] .'";';
          print 'references['.$reference['id'].']["medium"] = "'. $correct_reference[0]['medium'] .'";';
        }
      }
    ?>
    function set_ref(id){
      $('#references_'+id).html('"'+references[id]['title']+'." <i>'+references[id]['website_title']+'</i>. '+references[id]['publisher']+', '+ references[id]['date_published'] + '. '+references[id]['medium']+'. '+references[id]['date_accessed']);
    }
  </script>
</head>
<body>
 
<div id="progressbar"></div>
   <?php
  	foreach ($references as $key => $reference) {
  		if ($reference['status'] > $status_cap)
        $correct_reference = get_correct_references_by_id($reference['id']);
    		print '<p><div class="ellipsis"> Reference: <a href="'.$reference['url']. '">'. $reference['url'] . '</a></div><div id="references_'. $reference['id'] .'"></div>';
        // print $correct_reference[0]['title'];
	}
  ?>
 
</body>
</html>