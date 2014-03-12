<?php
	require 'sql_op.php';
	initialize();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Your Website</title>
	<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
	<style type="text/css">
		#container{
			width: 100%;
			height: 100%;
			position: relative;
			background-color: red;
		}
		body{
			height:100%;
			width:100%;
			padding:0px;
			margin:0px;
			font-family: Helvetica, Arial;
		}
		#left{
			width: 25%;
			background-color: #333;
			height: 100%;
			z-index: 0;
			float: left;
			position: relative;
		}
		#right{
			float: left;
			width: 75%;
			height: 100%;
			background-color: #BBB;
			z-index: 0;
		}
		#banner{
			z-index: 2;
			position: absolute;
			top: 13%;
			left: 25%;
			margin-left: -309;
			background-color: #EEE;
			opacity: 1;
			padding: 20px;
		}
		h1,h2,h3,h4,h5,h6{
			margin:0;
			padding:0;
		}
		#login_container{
			width:100%;
			height:30px;
			line-height:30px;
			font-size:18px;
			background-color:#AAA;
			color:#333;
			position:absolute;
			bottom:0px;
			text-align: center;
			overflow:hidden;
		}
		#logout_link{
			/* text-decoration:none; */
			color:#333;
		}
		#login_handler{
			height:100px;
			background-color:#888;
			width:100%;
		}
		table{
			width:100%;
		}
		.login_text{
			color:#333;
			width:100%;
			border:none;
			outline:none;
			background-color:#EEE;
		}
		.login_submit{
			color:#333;
			border:none;
			outline:none;
			background-color:#EEE;
		}
	</style>
	<script type="text/javascript">
		var slide_open = false; 
		function login_slide_toggle(){
			if (slide_open){
				login_slide_closed();
				slide_open=false;
			} else {
				login_slide_open();
				slide_open=true;
			}
		}
		function login_slide_open(){
			$('#login_container').animate({height: "130px"}, 500);
		}
		function login_slide_closed(){
			$('#login_container').animate({height: "30px"}, 500);
		}
	</script>
</head>
<body>

	<div id="container">
		<div id="left">
			<div id="login_container">
				<div id="login_control">
					<?php 
						if (isset($_SESSION['user_logged']))
							print '<a id="logout_link" href="logout.php">Logout</a>'; 
						else 
							print '<h3 onclick="login_slide_toggle()">Click Here to Login</h3>'
					?>
				</div>
				<div id="login_handler">
					<form method="POST" action="login.php">
					<table>
						<tr><td style="text-align:right; color:#333; font-weight:bold;">Username/Email</td><td><input type="text" id="login_email" name="login_email" class="login_text"></td></tr>
						<tr><td style="text-align:right; color:#333; font-weight:bold;">Password</td><td><input type="password" id="login_password" name="login_password" class="login_text"></td></tr>
						<tr><td style="text-align:center;" colspan="2"><input type="submit" id="login_submit" name="login_submit" class="login_submit"></td></tr>
					</table>
					
				</div>
			</div>
		</div>
		<div id="right">
			
		</div>
		<img id="banner" src="banner.png">
	</div>


</body>
</html>