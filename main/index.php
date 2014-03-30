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
	<script type="text/javascript" src="crowdref.js"></script>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
	<div id="container">
		<div id="left">
			<h3 style="font-family:Georgia; font-size:22px; text-align:center; font-weight:bold; margin-top:7px; padding:7px; color:#BBB; border-bottom: 2px #bbb dashed;">
				What is CrowdRef?
			</h3>
			<div id="what_is_content" style="color:#AAA; font-family:arial; padding:12px;">
				CrowdRef is a Crowd-Powered System designed to harness the power of human crowd workers to create citations/references. 
			</div>
			<h3 style="font-family:Georgia; font-size:22px; text-align:center; font-weight:bold; margin-top:7px; padding:7px; color:#BBB; border-bottom: 2px #bbb dashed;">
				How Does it Work?
			</h3>
			<div id="what_is_content" style="color:#AAA; font-family:arial; padding:12px;">
				When you request a citation in CrowdRef, job(s) are posted on Amazon Mechanical Turk. These jobs pay workers for gathering reference-related information from the URL you provide. Once the job(s) are complete, a final job is posted asking a worker to choose the best solution. This worker's choice will trigger reference generation in our systems, and you will be notified upon completion.
			</div>
			<h3 style="font-family:Georgia; font-size:22px; text-align:center; font-weight:bold; margin-top:7px; padding:7px; color:#BBB; border-bottom: 2px #bbb dashed;">
				Who Can Use CrowdRef?
			</h3>
			<div id="what_is_content" style="color:#AAA; font-family:arial; padding:12px;">
				CrowdRef is currently in development, and not open to new users. 
			</div>
			<div id="login_container">
				<div id="login_control">
				<?php 
					if ($_SESSION['user_logged']){
						print '<a href="logout.php">Logout</a> <a href="account.php"> (Account)</a>';
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
		<div id="banner_holder" style="width:40%;">
			<img id="banner" style="width:100%" src="banner.png">
			<form method="post" id="ref_form" name="ref_form" action="submitref.php">
				<input type="text" id="ref_text" onblur="ref_text_unfocus()" onfocus="ref_text_focus()" name="ref_text" value="Enter URL" style="color:#444;background-color:#EEE; color:#333; width:80%; height:40px;line-height:20px; margin-top:10px; font-size:18px; border:2px solid #CCC; outline:none;text-indent:5px;">
				<input<?php 
					if ($_SESSION['user_logged']){
						print ' type="submit" onclick="$(\'ref_form\').trigger(\'submit\')" ';
					} else {
						print ' type="button" onclick="alert(\'Please log in first\');" ';
					}
				?>id="ref_submit" name="ref_submit" style="width:19%; height:40px; background-color:#EEE;font-weight:bold; font-family:arial; border:2px solid #CCC; outline:none;" value="Get Reference">
			</form>
		</div>
	</div>



</body>
</html>