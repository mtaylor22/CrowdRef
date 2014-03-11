<?php
	require 'pass.php';
	$db_connected = false;
	$db_pass = "csce438";
	$db_host = "mysql15.000webhost.com";
	function connect(){
		global $db_connected, $db_name, $db_user, $db_pass, $db_host;
		try {
		    $dbh = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
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
	
?>