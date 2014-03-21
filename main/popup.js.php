<?php
	require 'sql_op.php';
	initialize();
	if (isset($_POST['login_submit'])){
		if (user_login($_POST['login_email'], $_POST['login_password']))
			print 'password accepted';
		else
			print 'password denied';
	}
?>

<!doctype html>
<html>
  <head>
    <title>CrowdRef Login</title>
    <style>
      body {
        min-width: 357px;
        overflow-x: hidden;
      }

      img {
        margin: 5px;
        border: 2px solid black;
        vertical-align: middle;
        width: 75px;
        height: 75px;
      }
    </style>

    <!--
      - JavaScript and HTML must be in separate files: see our Content Security
      - Policy documentation[1] for details and explanation.
      -
      - [1]: http://developer.chrome.com/extensions/contentSecurityPolicy.html
     -->
    <script src="popup.js"></script>
  </head>
  <body>
    <form method="post" action="http://crowdref.atwebpages.com/login.php">
      <table style="width:100%;">
        <tr>
          <td>Email</td>
          <td><input style="width:100%;" type="text" id="login_email" name="login_email" /></td>
        </tr>
        <tr>
          <td>Password</td>
          <td><input style="width:100%;" type="password" id="login_password" name="login_password" /></td>
        </tr>
        <tr>
          <td colspan="2"><input style="width:100%;" type="submit" id="login_submit" name="login_submit"></td>
        </tr>
      </table>
    </form>
  </body>
</html>

