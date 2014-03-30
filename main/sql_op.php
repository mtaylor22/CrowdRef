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
			$data = $dbc->query('SELECT * FROM Ref WHERE id='. $id );
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
	function reference_exists($workerid, $ref_id){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$data = $dbc->query('SELECT * FROM Refdata WHERE ref='. $ref_id . ' AND workerid="'. $workerid . '"');
		    foreach($data as $row) {
		    	return true;
		    }
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function add_reference_result($title, $author, $website_title, $publisher, $date_published, $date_accessed, $medium, $ref, $workerid){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		if (reference_exists($workerid, $ref)) return 1; //prevent workerdata from being double-counted
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
	function handle_correct_reference($ref_id, $instance_id){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		try {
			$sql = "INSERT INTO Refdatacorrect (title, author, website_title, publisher, date_published, date_accessed, medium, ref, workerid)
					SELECT title, author, website_title, publisher, date_published, date_accessed, medium, ref, workerid
					FROM Refdata
					WHERE ref='". $ref_id ."' AND id='". $instance_id ."'";
			$q = $dbc->prepare($sql);
			$q->execute();
		    return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
		    trigger("PDOException in add_ref_res: " . $e->getMessage());
	        return 3;
		}
	    trigger("???");
		return 4;

	}
	function handle_correct_reference_specified($ref_id, $title, $author, $website_title, $publisher, $date_published, $date_accessed, $medium, $workerid){
		global $dbc, $db_connected;
		if (!$db_connected) return 1;
		try {
			$sql = "INSERT INTO Refdatacorrect (title, author, website_title, publisher, date_published, date_accessed, medium, ref, workerid)
					VALUES ('". $title . "', '". $author . "', '". $website_title . "', '". $publisher . "', '". $date_published . "', '". $date_accessed . "', '". $medium . "', '". $ref_id . "', '". $workerid . "')";
			$q = $dbc->prepare($sql);
			$q->execute();
		    return 0;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
		    trigger("PDOException in add_ref_res: " . $e->getMessage());
	        return 3;
		}
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
			$q1 = "TRUNCATE Refdata";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "TRUNCATE trig";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "TRUNCATE Refdatacorrect";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "TRUNCATE Notification";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "ALTER TABLE Ref AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "ALTER TABLE Refdata AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "ALTER TABLE trig AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "ALTER TABLE Refdatacorrect AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q1);
			$q->execute();
			$q1 = "ALTER TABLE Notification AUTO_INCREMENT = 1";
			$q = $dbc->prepare($q1);
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
			$stmt = $dbc->query('SELECT * FROM Refdata WHERE ref='. $ref_id );
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach ($row as &$rowelement) 
					$rowelement = htmlspecialchars(urldecode(stripslashes(nl2br($rowelement))));
				$output_str.='<table border="0" cellpadding="0" cellspacing="5">
					<tbody>
						<tr>
							<td colspan="4"><b>Table #'. $row['id']. '</b></td>
						</tr>
						<tr>
							<td>Title of Document:</td>
							<td>'.$row['title'].'</td>
							<td><input id="title" name="title" value="'.$row['title'].'" /></td>
						</tr>
						<tr>
							<td>Author(s)/Editor(s):</td>
							<td>'.$row['author'].'</td>
							<td><input id="author" name="author" value="'.$row['author'].'"/></td>
						</tr>
						<tr>
							<td>Website Title:</td>
							<td>'.$row['website_title'].'</td>
							<td><input id="website_title" name="website_title" value="'.$row['website_title'].'"/></td>
						</tr>
						<tr>
							<td>Publisher:</td>
							<td>'.$row['publisher'].'</td>
							<td><input id="publisher" name="publisher" value="'.$row['publisher'].'" /></td>
						</tr>
						<tr>
							<td>Date Published:</td>
							<td>'.$row['date_published'].'</td>
							<td><input id="date_published" name="date_published" value="'.$row['date_published'].'" /></td>
						</tr>
						<tr>
							<td>Date Accessed:</td>
							<td>'.$row['date_accessed'].'</td>
							<td><input id="date_accessed" name="date_accessed" value="'.$row['date_accessed'].'" /></td>
						</tr>
						<tr>
							<td>Medium:</td>
							<td>'.$row['medium'].'</td>
							<td><input id="medium" name="medium" value="'.$row['medium'].'" /></td>
						</tr>
					</tbody>
				</table>';
		    }
		    return $output_str;
		} catch(PDOException $e) {
			trigger('error in table gen');
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function generate_table_options($ref_id){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$output_str = '<p><label for="result_selection">Please select the option that best describes that information:</label><select name="result_selection" id="result_selection">';
			$stmt = $dbc->query('SELECT * FROM Refdata WHERE ref='. $ref_id );
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$output_str.= '<option value="'.$row['id'].'">'.$row['id'].'</option>';
		    }

		    $output_str .= '<option value="99">None</option></select></p>';
		    return $output_str;
		} catch(PDOException $e) {
			trigger('error in table gen');
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function get_references($user){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$data = $dbc->query('SELECT * FROM Ref WHERE user="'.$user.'"');
			return $data->fetchAll (PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function get_references_by_id($id){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$data = $dbc->query('SELECT * FROM Ref WHERE id="'.$id.'"');
			return $data->fetchAll (PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function get_correct_references_by_id($id){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$data = $dbc->query('SELECT * FROM Refdatacorrect WHERE ref="'.$id.'"');
			return $data->fetchAll (PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}

	function get_notifications($user){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$data = $dbc->query('SELECT * FROM Notification WHERE email="'.$user.'"');
			return $data;
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}
	function get_notification_count($user){
		global $dbc, $db_connected;
		if (!$db_connected) return -1;
		try {
			$sql='SELECT count(*) FROM Notification WHERE email="'.$user.'" AND viewed=0';
			$result = $dbc->prepare($sql);
			$result->execute();
			return $result->fetchColumn();
		} catch(PDOException $e) {
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage());
	        return false;
		}
		return false;
	}

	function set_notification($action, $ref, $email=NULL){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			if ($email==NULL)
				$email = get_references_by_id($ref)[0]['user'];	//this is so mturk can keep track
			$sql = "INSERT INTO Notification (email,action,ref) VALUES ('". $email ."','".$action."',".$ref.")";
			$q = $dbc->prepare($sql);
			$q->execute();
		} catch(PDOException $e) {
			trigger('error');
			trigger($e->getMessage());
		    trigger('Set Notification Error: ' . $e->getMessage());
		    echo 'ERROR: ' . $e->getMessage();
		    error_log('ERROR: ' . $e->getMessage()); 
	        return false;
		}
		return true;
	}
	function mark_notifications_read($user_email){
		global $dbc, $db_connected;
		if (!$db_connected) return false;
		try {
			$sql = "UPDATE Notification SET viewed = 1 WHERE email=:user_email";
			$q = $dbc->prepare($sql);
			$q->bindParam(':user_email', $user_email);
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
?>