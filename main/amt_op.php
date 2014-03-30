<?php
require 'amtapi/amt_rest_api.php';
require 'amtapi/amt_notification.php';

function amt_get_balance(){
	try{
		$balance = amt\balance_request::execute();
	} catch (amt\Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return $balance;
}

function create_request(){
	try{
		$r = new amt\hittype_request;
		$title = "Gather information about a webpage or document";
		$description = "Using the given URL, you will be asked to gather simple information such as: title, author, date created, etc.";
		$reward = 0.03;
		$assignmentduration = 60*60;
		$keywords = "Reference, citation, mla, apa, title, author";
		$maxassignments = 1;
		$autoapprovaldelay = 3600;
		$r->set_params($reward, $title, $description, $assignmentduration, $keywords, $autoapprovaldelay);
		$typeid = $r->execute();
		return $typeid;
	} catch (amt\Exception $e) {
		trigger("Couldnt create request");
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}

function create_custom_hit($hit_type, $reference_url, $ref_id){
	// custom_ext_hit_request
	global $status_cap;
	try{
		$annotation = 0;
		$lifetime = 60*60;
		$job_qty =  $status_cap;
		$question = "<HTMLQuestion xmlns='http://mechanicalturk.amazonaws.com/AWSMechanicalTurkDataSchemas/2011-11-11/HTMLQuestion.xsd'>
		<HTMLContent><![CDATA[
		<!DOCTYPE html>
		<html>
		 <head>
		  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
		  <script type='text/javascript' src='https://s3.amazonaws.com/mturk-public/externalHIT_v1.js'></script>

		 </head>
		 <body>
		  <form name='mturk_form' method='post' id='mturk_form' action='https://www.mturk.com/mturk/externalSubmit'>
		  <input type='hidden' value='' name='assignmentId' id='assignmentId'/>
		  <input type='hidden' value='". $ref_id ."' name='ref_id' id='ref_id'/>
		  <input type='hidden' value='' name='url' id='url'/>
		  <h1>Hello, please help us gather reference information for a citation</h1>
		  <p>Please go to <a href='". ($reference_url) ."'>". ($reference_url) ."</a> and answer the questions below</p>
		  <table>
		  	<tbody>
		  		<tr>
		  			<td>What is the title of the webpage/post/article?</td>
		  			<td>U.S. Reports Modestly Better Economic Growth</td>
		  			<td><input name='title' id='title' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>Who are the author(s)/editor(s) of the webpage (seperated by a semicolon)?</td>
		  			<td> Schwartz, Nelson </td>
		  			<td><input name='author' id='author' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>What was the title of the website itself? </td>
		  			<td>The New York Times</td>
		  			<td><input name='website_title' id='website_title' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>Who published the webpage? </td>
		  			<td>The New York Times Company</td>
		  			<td><input name='publisher' id='publisher' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>When was the webpage published? </td>
		  			<td>March 27, 2014</td>
		  			<td><input name='date_published' id='date_published' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>What is the date you accessed this? </td>
		  			<td>March 27, 2014</td>
		  			<td><input name='date_accessed' id='date_accessed' type='text' /></td>
		  		</tr>
		  		<tr>
		  			<td>What was the medium of the publication? </td>
		  			<td>Web</td>
		  			<td><input name='medium' id='medium' type='text' value='Web'/></td>
		  		</tr>
		  	</tbody>
		  </table>
		  <p><input type='submit' id='submitButton' value='Submit' /></p></form>
		  <script language='Javascript'>turkSetAssignmentID();</script>
		 </body>
		</html>
		]]>
		  </HTMLContent>
		  <FrameHeight>450</FrameHeight>
		</HTMLQuestion>";
		$r = new amt\custom_ext_hit_request($hit_type, $annotation, $lifetime, $job_qty, $question);
		$hit = $r->execute();   // after calling this, the HIT is 'assignable'
		return $hit;
	} catch (amt\Exception $e) {
		trigger("COULDNT POST: ");
		trigger($e->getMessage());
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}

function create_review_request(){
	try{
		$r = new amt\hittype_request;
		$title = "Confirm or Supplement Webpage Reference information";
		$description = "Using the given URL, you will be asked to check or correct supplied reference information such as author, title, etc.";
		$reward = 0.03;
		$assignmentduration = 60*60;
		$keywords = "Reference, citation, mla, apa, title, author, review";
		$maxassignments = 1;
		$autoapprovaldelay = 3600;
		$r->set_params($reward, $title, $description, $assignmentduration, $keywords, $autoapprovaldelay);
		$typeid = $r->execute();
		return $typeid;
	} catch (amt\Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}


function create_custom_review_hit($hit_type, $reference_url, $ref_id){
	// custom_ext_hit_request
	global $status_cap;
	try{
		$annotation = 0;
		$lifetime = 60*60;
		$job_qty =  $status_cap;
		$question = "<HTMLQuestion xmlns='http://mechanicalturk.amazonaws.com/AWSMechanicalTurkDataSchemas/2011-11-11/HTMLQuestion.xsd'>
		<HTMLContent><![CDATA[
		<!DOCTYPE html>
		<html>
		 <head>
		  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
		  <script type='text/javascript' src='https://s3.amazonaws.com/mturk-public/externalHIT_v1.js'></script>
		 </head>
		 <body>
		  <form name='mturk_form' method='post' id='mturk_form' action='https://www.mturk.com/mturk/externalSubmit'>
		  <input type='hidden' value='' name='assignmentId' id='assignmentId'/>
		  <input type='hidden' value='". $ref_id ."' name='ref_id' id='ref_id'/>
		  <input type='hidden' value='' name='url' id='url'/>  
		  <h1>Hello, please verify the following reference information.</h1>
		  <p>Please go to <a href='". ($reference_url) ."'>". ($reference_url) ."</a> and answer the question below</p>
		  <p>If there is a correction, edit the box on the right. Otherwise, leave it as is.</p>
		  ". generate_comparison_table($ref_id) ."
		  <p><input type='submit' id='submitButton' value='Submit' /></p></form>
		<script language='Javascript'>turkSetAssignmentID();</script>
		</body>
		</html>
		]]>
		  </HTMLContent>
		  <FrameHeight>450</FrameHeight>
		</HTMLQuestion>";//do unicode vers
		$r = new amt\custom_ext_hit_request($hit_type, $annotation, $lifetime, $job_qty, $question);
		$hit = $r->execute();   // after calling this, the HIT is 'assignable'
		return $hit;
	} catch (amt\Exception $e) {
		trigger('error: '. $e->getMessage() );
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}

function reviewable_hits(){
	$r = new amt\reviewable_hitlist;
	foreach ($r as $mhit) {
		foreach ($mhit->results() as $result) {
		print '<table><tr><td colspan=2>'. $result['url'] . '</td></tr>';
			echo '<tr><td>AssignmentId</td><td>'. $result['AssignmentId']. '</td></tr>';
			echo '<tr><td>title</td><td>'. $result['title']. '</td></tr>';
			echo '<tr><td>author</td><td>'. $result['author']. '</td></tr>';
			echo '<tr><td>website_title</td><td>'. $result['website_title']. '</td></tr>';
			echo '<tr><td>publisher</td><td>'. $result['publisher']. '</td></tr>';
			echo '<tr><td>date_published</td><td>'. $result['date_published']. '</td></tr>';
			echo '<tr><td>date_accessed</td><td>'. $result['date_accessed']. '</td></tr>';
			echo '<tr><td>medium</td><td>'. $result['medium']. '</td></tr>';
	  print '</table><p>';
		print_r($result);
		}
	}
}
function execute_job($reference_url, $ref_id){
	$reference_url = htmlspecialchars(urldecode(stripslashes(nl2br($reference_url))));
	$hittype_id = create_request();
	$hit = create_custom_hit($hittype_id, $reference_url, $ref_id);
	$url = 'http://crowdref.atwebpages.com/ref_responder.php';
	attach_trigger($hittype_id, $url);
}
function execute_final_job($ref_id, $reference_url){
	$hittype_id = create_review_request();
	$reference_url = htmlspecialchars(urldecode(stripslashes(nl2br($reference_url))));
	$hit = create_custom_review_hit($hittype_id, $reference_url, $ref_id);
	$url = 'http://crowdref.atwebpages.com/ref_final_responder.php';
	attach_trigger($hittype_id, $url);
	trigger( $hit->HITId . ' - ' . $hit->HITTypeId );
}

function attach_trigger($hittype_id, $url){
	$a = array('HITReviewable', 'HITExpired');
	$r = new amt\hittype_notification_request($hittype_id, $url, $a);
	$r->execute();
}

function get_hits(){
	echo "HIT ID: HIT Type IDn";
	foreach (new amt\hitlist as $hit) 
		echo "($hit->HITId: $hit->HITTypeId)\n";
}
function get_hit_details($hit_id){
	$hit = new amt\hit_details($hit_id);
}
function custom_hit_create(){
	$title = "Gather information about a webpage or document";
	$description = "Using the given URL, you will be asked to gather simple information such as: title, author, date created, etc.";
	$question = "<HTMLQuestion xmlns='http://mechanicalturk.amazonaws.com/AWSMechanicalTurkDataSchemas/2011-11-11/HTMLQuestion.xsd'>
	  <HTMLContent><![CDATA[
	<!DOCTYPE html>
	<html>
	 <head>
	  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
	  <script type='text/javascript' src='https://s3.amazonaws.com/mturk-public/externalHIT_v1.js'></script>
	 </head>
	 <body>
	  <form name='mturk_form' method='post' id='mturk_form' action='https://www.mturk.com/mturk/externalSubmit'>
	  <input type='hidden' value='' name='assignmentId' id='assignmentId'/>
	  <h1>What's up?</h1>
	  <p><textarea name='comment' cols='80' rows='3'></textarea></p>
	  <p><input type='submit' id='submitButton' value='Submit' /></p></form>
	  <script language='Javascript'>turkSetAssignmentID();</script>
	 </body>
	</html>
	]]>
	  </HTMLContent>
	  <FrameHeight>450</FrameHeight>
	</HTMLQuestion>";
	$reward = 0.03;
	$assignmentdurationinseconds = 60*60;
	$lifetimeinseconds = 60*60;
	$keywords = "Reference, citation, mla, apa, title, author";
	$maxassignments = 1;
	// $autoapprovaldelayinseconds = ;
	// $qualificationrequirement = ;
	// $assignmentreviewpolicy = ;
	// $hitreviewpolicy = ;
	// $requesterannotation = ;	
	// $uniquerequesttoken = ;

	/*
	  The HITLayoutId allows you to use a pre-existing HIT design
	  with placeholder values and create an additional HIT by providing
	  those values as HITLayoutParameters. For more information, see HITLayout.
	*/

	// $hitlayoutid = ;

	/*
	  If the HITLayoutId is provided, any placeholder values must be filled in
	  with values using the HITLayoutParameter structure. For more information,
	  see HITLayout.
	*/

	// $hitlayoutparameter = ;
	try{
		$r = new amt\custom_hit_request($title, $description, $question, $reward, $assignmentdurationinseconds, $lifetimeinseconds, $keywords, $maxassignments);
		$hit_id = $r->execute();   // after calling this, the HIT is 'assignable'
		return $hit_id;
	} catch (Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return 0;
}
?>