<?php
require 'sql_op.php';
initialize();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>CrowdRef</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.9.1.js"></script>
  <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
	<link rel="stylesheet" type="text/css" href="crowdref.css">
	<style type="text/css">
	.ellipsis {
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;

	}
	.reference_input{
		width:100%;
		height:24px;
		font-family: arial; 
		font-size:18px;
		margin-top:5px;
		background-color: #DDD;
 	}
	.citation {
	text-indent:-20px;
	margin-left:20px;
	}
	body{
		background-color:#BBB;
	}
	.unit_block{
		width:60%; position: relative;left:15%; margin-top:15px; background-color: #EEE; padding: 20px;
		margin:10px;
	}
	.unit_block_title{
		color:#333; font-family:georgia; font-size:32px;border-bottom: 2px #333 dashed; font-weight: bold;
	}
	.unit_block_subtitle{
		margin:0;font-family:georgia; color:#333;
	}
	.notification_url{
		color:#CCC;
	}
	.logout_url{
		color:#333;
		text-decoration: none;
		font-weight: bold;
	}
	</style>
	<script type="text/javascript" src="crowdref.js"></script>
	  <script type="text/javascript">
    var references = new Array();
    <?php
	$references = get_references($_SESSION['email']);
      foreach ($references as $key => $reference) {
        if ($reference['status'] > $status_cap){
          $correct_reference = get_correct_references_by_id($reference['id']);
          print 'references['.$reference['id'].'] = {';

          print '"title": "'. 			urldecode($correct_reference[0]['title']) .			'", ';
          print '"i": "'. 				$reference['id'] .									'", ';
          print '"author": "'. 			urldecode($correct_reference[0]['author']) .		'", ';
          print '"website_title": "'. 	urldecode($correct_reference[0]['website_title']) .	'", ';
          print '"publisher": "'. 		urldecode($correct_reference[0]['publisher']) .		'", ';
          print '"date_published": "'. 	urldecode($correct_reference[0]['date_published']) .'", ';
          print '"date_accessed": "'. 	urldecode($correct_reference[0]['date_accessed']) .	'", ';
          print '"medium": "'. 			urldecode($correct_reference[0]['medium']) .		'" };';
        }
      }
    ?>
    function set_ref(id){
    	var template_mla = '<div class="citation">"{{title}}." <i>{{website_title}}</i>. {{publisher}}, {{date_published}}. {{medium}}. {{date_accessed}}</div>';
      	$('#references_'+id).html(Mustache.to_html(template_mla, references[id]));
    }

    $(document).ready(function(){
      references.forEach(function(reference){
        set_ref(reference['i']);
      });
    });
  </script>
  <script type="text/javascript">
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
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
	<div id="container" >
		<div id="left" style="position:fixed;">
			<div id="what_is_content" style=" color:#AAA; font-family:arial; padding-left:7px; padding-right:7px; margin:0; position:relative; height:100%; overflow-y:auto;">
				<h3 style="font-family:Georgia; font-size:22px; text-align:center; font-weight:bold; margin-top:7px; padding:7px; color:#BBB; border-bottom: 2px #bbb dashed;">
					Notifications
				</h3>
				<?php
				$count = get_notification_count_all($_SESSION['email']);
				if ($count>0){
					$notifications = get_notifications($_SESSION['email']);
					mark_notifications_read($_SESSION['email']);
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
							print '<div class="'. (($reference['viewed'] == 1)? 'unviewed' : 'viewed') .'"><p><div class="ellipsis"> <b>Reference</b></a>: <a class="notification_url" href="'. urldecode($reference['url']). '">'. urldecode($reference['url']) . '</a></div><b>Status:</b> '. $status .'<br><a class="notification_url" href="reference.php?ref_id='. $reference['id'].'">View Reference</a></p></div>';
						}
					}
					print '<div style="width:100%; padding-bottom:50px; text-align:center"><a href="reference.php" style="text-decoration:none; font-family:Georgia; font-size:22px; margin:0 auto; font-weight:bold; margin-top:7px; padding:7px; color:#BBB;">
						View All
					</a></div>';
				} else {
					//no notifications
					print '<h3 style="font-weight:normal; color:#DDD">There are no notifications, get started creating some references!</h3>';
				}
			?>
			</div>
			
			
			<div id="login_container">
				<div id="login_control">
				<?php 
					if ($_SESSION['user_logged']){
						print '<a class="logout_url" href="logout.php">Logout</a> | <a class="logout_url" href="account.php"> (Account)</a>';
					} else {
						print '<h3 style="cursor:pointer" onclick="login_slide_toggle()">Click Here to Login</h3>';
					}
				?>
				</div>
				<div id="login_handler">
					<form method="POST" action="login.php">
					<table style="margin-top:10px;">
						<tr><td style="text-align:right; color:#333; font-weight:bold;">Username/Email</td><td><input type="text" id="login_email" name="login_email" class="login_text"></td></tr>
						<tr><td style="text-align:right; color:#333; font-weight:bold;">Password</td><td><input type="password" id="login_password" name="login_password" class="login_text"></td></tr>
						<tr><td style="text-align:center;" colspan="2"><input type="submit" id="login_submit" name="login_submit" class="login_submit"></td></tr>
					</table>
					</form>
				</div>
			</div>
		</div>

		<div id="right" style="position:absolute; width:80%;left:20%;">
			<?php 
			$count = count(get_references($_SESSION['email']));
			if ($count>0){
				if (isset($_GET['ref_id'])){
					$reference = get_references_by_id($_GET['ref_id'])[0];
					print '
					<div id="Account" class="unit_block">
						<div class="unit_block_title"  style="width:100%; position:relative;">
							<div class="ellipsis">
							' . urldecode($reference['url']) . '
							</div>
						</div>
						<p>';
					$status = $reference['status'];
			  		if ($status < $status_cap)
						$status_d = "Waiting for worker results";
					else if ($status == $status_cap)
						$status_d = "Waiting for final result";
					else if ($status > $status_cap)
						$status_d = "complete";
					if ($reference['status'] < $status_cap)
						print '
							<h2 class="unit_block_subtitle">Unfinished</h2>
							Your reference is unfinished.
							<br>
							<h2 class="unit_block_subtitle">Status: '. $status_d .'</h2>						
							<div id="progressbar_'.$reference['id'].'"></div>
							<input type="input" readonly class="reference_input" value="'. urldecode($reference['url']).'"/>';
					else {
						print '
							<h2 class="unit_block_subtitle">Reference</h2>
							<div id="references_'. $reference['id'] .'"></div> <br>
							<h2 class="unit_block_subtitle">Status: '. $status_d .'</h2>
							<div id="progressbar_'.$reference['id'].'"></div>
							<input type="input" readonly class="reference_input" value="'. urldecode($reference['url']).'"/>';
					}
					print '</p>
					</div>';
				} else {
					foreach ($references as $key => $reference) {
					print '
					<div id="Account" class="unit_block">
						<div class="unit_block_title"  style="width:100%; position:relative;">
							<div class="ellipsis">
							' . urldecode($reference['url']) . '
							</div>
						</div>
						<p>';

					if ($reference['status'] < $status_cap)
						print '
							<h2 class="unit_block_subtitle">Unfinished</h2>
							Your reference is unfinished.
							<br>
							<div id="progressbar_'.$reference['id'].'"></div>
							<input type="input" readonly class="reference_input" value="'. urldecode($reference['url']).'"/>
							';
					else {
				  		$status = $reference['status'];
				  		if ($status < $status_cap)
							$status_d = "Waiting for worker results";
						else if ($status == $status_cap)
							$status_d = "Waiting for final result";
						else if ($status > $status_cap)
							$status_d = "complete";
						print '
							<h2 class="unit_block_subtitle">Reference</h2>
							<div id="references_'. $reference['id'] .'"></div> <br>
							<h2 class="unit_block_subtitle">Status: '. $status_d .'</h2>
							<div id="progressbar_'.$reference['id'].'"></div>
							<input type="input" readonly class="reference_input" value="'. urldecode($reference['url']).'"/>
							';
					}
					print '</p>
					</div>';
					}
				}
			} else {
				print '<div id="Account" class="unit_block">
							<div class="unit_block_title" style="width:100%; position:relative;">
								There isn\'t anything here, yet!
							</div>
							<p>
								<h2 class="unit_block_subtitle">What Now?</h2>
								Go <a href="/" style="color:#333">here</a> to start creating references, or download the Chrome Extension.</a>
								<br>
							</p>
						</div>';
			}
			?>

		</div>
	</div>


	<script src = "//cdnjs.cloudflare.com/ajax/libs/mustache.js/0.8.1/mustache.min.js"></script>
</body>
</html>