<?php
require 'sql_op.php';
initialize();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>CrowdRef</title>
	<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
	<link rel="stylesheet" type="text/css" href="crowdref.css">
	<style type="text/css">
	.logout_url{
	color:#333;
	text-decoration: none;
	font-weight: bold;
	}
	.ellipsis {
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;

	}
	.citation {
	text-indent:-20px;
	margin-left:20px;
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
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
	<div id="container">
		<div id="left">
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
						print '<a class="logout_url" href="logout.php">Logout</a> | <a class="logout_url" href="/"> (Main)</a>';
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
		<div id="right"></div>
		<? if (!isset($_SESSION['user_logged']))
			print 	'<div id="log_in" style="width:40%; position: absolute; top: 40%; margin-top:-50px; left:30%; background-color: #EEE; padding: 20px;">
						<h1 style="color:#333; font-family:georgia; font-size:32px;border-bottom: 2px #333 dashed;"> You Need to Log In</h1>
						<p>
							To View accont information, you should log in or create an account. <br>
							<h2 style="margin:0;font-family:georgia; color:#333;">Benefits of Creating an Account</h2>
							<ul style="margin-top:5px;">
								<li>You can submit reference urls</li>
								<li>We can generate and store your reference information</li>
								<li>You can return at any time to collect your information</li>
							</ul>
						</p>
					</div>';
			else
				print '<div id="Account" style="width:40%; position: absolute; top: 20%; margin-top:-50px; left:30%; background-color: #EEE; padding: 20px;">
							<h1 style="color:#333; font-family:georgia; font-size:32px;border-bottom: 2px #333 dashed;">Hello, '. $_SESSION['email']. '</h1>
							<p>
								Welcome to your account page. <br>
								<h2 style="margin:0;font-family:georgia; color:#333;">Your Account</h2>
								<ul style="margin-top:5px;">
									<li>Notifications show up in the left column</li>
								</ul>
							</p>
						</div>';
			?>
	</div>



</body>
</html>