<?php
	session_start();
	require 'pass.php';
	require 'amt_op.php';
	$db_connected = false;
	$status_cap = 1;
	function connect(){
		global $db_connected, $db_name, $db_user, $db_pass, $db_host, $dbc;
		try {
		    $dbc = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
		    $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
			$q = $dbc->prepare($sql);
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
		        $_SESSION['email'] = $user_email;
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
	function increment_status($id){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$sql = "UPDATE Ref SET status = status+1 WHERE id=:aid";
			$q = $dbc->prepare($sql);
			$q->bindParam(':aid', $id);
			$q->execute();
			return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
		    trigger('error updating pdo');
	        return 1;
		}
		return 1;
	}
	function get_status($id){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$data = $dbc->query('SELECT * FROM Ref WHERE id:'. $id );
		    foreach($data as $row) {
		    	$status = $row['status'];
		    }
		    return $status;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function get_ref_url($id){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$data = $dbc->query('SELECT * FROM Ref WHERE id:'. $id );
		    foreach($data as $row) {
		    	$url = $row['url'];
		    }
		    return $url;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function add_reference($ref_url){
		// add reference url
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		if (!$_SESSION['user_logged']) return 2;
		try {
			$sql = "INSERT INTO Ref (url, user) VALUES (:ref_url, :user)";
			$q = $dbc->prepare($sql);
			$q->bindParam(':ref_url', $ref_url);
			$q->bindParam(':user', $_SESSION['email']);
			$q->execute();
			execute_job($ref_url, $dbc->lastInsertId());
			return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return 3;
		}
		// call hit creation for job 1
		return 4;
	}

	function add_reference_result($title, $author, $website_title, $publisher, $date_published, $date_accessed, $medium, $ref, $workerid){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		try {
			$sql = "INSERT INTO Refdata (title, author, website_title, publisher, date_published, date_accessed, medium, ref, workerid) VALUES (:title, :author, :website_title, :publisher, :date_published, :date_accessed, :medium, :ref, :workerid)";
			$q = $dbc->prepare($sql);
			$q->bindParam(':title', $title);
			$q->bindParam(':author', $author);
			$q->bindParam(':website_title', $website_title);
			$q->bindParam(':publisher', $publisher);
			$q->bindParam(':date_published', $date_published);
			$q->bindParam(':date_accessed', $date_accessed);
			$q->bindParam(':medium', $medium);
			$q->bindParam(':ref', $ref);
			$q->bindParam(':workerid', $workerid);
			$q->execute();
		    trigger("Should have worked");
			return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
		    trigger("PDOException in add_ref_res: " . $e->getMessage());
	        return 3;
		}
		// call hit creation for job 1
	    trigger("???");
		return 4;
	}
	function trigger($value="hi"){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		try {
			$sql = "INSERT INTO trig (tgval) VALUES ('".$value."')";
			$q = $dbc->prepare($sql);
			$q->execute();
			print 'inserted';
			return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return 3;
		}
		return 1;
	}
	function resetdb(){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		try {
			$q1 = "TRUNCATE Ref";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q2 = "TRUNCATE Refdata";
			$q = $dbc->prepare($q2);
			$q->execute();
			$q3 = "TRUNCATE trig";
			$q = $dbc->prepare($q3);
			$q->execute();
			$q4 = "ALTER TABLE Ref AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q4);
			$q->execute();
			$q5 = "ALTER TABLE Refdata AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q5);
			$q->execute();
			$q6 = "ALTER TABLE trig AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q6);
			$q->execute();
			return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return 3;
		}
		return 1;
	}
	function generate_comparison_table($ref_id){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$output_str = '';
			$data = $dbc->query('SELECT * FROM Refdata WHERE ref:'. $ref_id );
		    foreach($data as $row) {
				$table = '<table><tbody><tr><td colspan=2>Table #'. $row['id']. '</td></tr>';
				$table.= '<tr><td>Title of Document</td><td>'.$row['title'].'</td></tr>';
				$table.= '<tr><td>Author(s)/Editor(s)</td><td>'.$row['author '].'</td></tr>';
				$table.= '<tr><td>Title</td><td>'.$row['website_title'].'</td></tr>';
				$table.= '<tr><td>Title</td><td>'.$row['publisher'].'</td></tr>';
				$table.= '<tr><td>Title</td><td>'.$row['date_published'].'</td></tr>';
				$table.= '<tr><td>Title</td><td>'.$row['date_accessed'].'</td></tr>';
				$table.= '<tr><td>Title</td><td>'.$row['medium'].'</td></tr>';
				$table.= '</tbody></table>';
				$output_str.=$table;
		    }
		    return $output_str;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
?>