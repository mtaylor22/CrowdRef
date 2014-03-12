<?php
	session_start();
	require 'pass.php';
	$db_connected = false;
	function connect(){
		global $db_connected, $db_name, $db_user, $db_pass, $db_host, $dbc;
		try {
		    $dbc = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
		    return true;
		} catch (PDOException $e) {
		    echo 'Connection failed: ' . $e->getMessage();
		    error_log('Connection failed: ' . $e->getMessage());
		}
		return false;
	}

	function initialize(){
		global $db_connected;
		$db_connected = connect();
		//if ($db_connected)	print 'connected';
	}

	function user_add($user_email, $user_password){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$sql = "INSERT INTO Users (email,password) VALUES (:user_email,:user_password)";
			$q = $conn->prepare($sql);
			$q->execute(array(':user_email'=>$user_email, ':user_password'=>$user_password));
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return true;
	}

	function user_login($user_email, $user_password){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		if ($_SESSION['user_logged']) return true;
		try {
			$data = $dbc->query('SELECT * FROM Users WHERE email = ' . $dbc->quote($user_email) . ' AND password = ' . $dbc->quote($user_password));
		    foreach($data as $row) {
		        $_SESSION['user_logged'] = true;
		        return true;
		    }
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        $_SESSION['user_logged'] = false;
	        return false;
		}
		return false;
	}
	
?>