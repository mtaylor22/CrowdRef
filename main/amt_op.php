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
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}

function create_hit($hit_type){
	try{
		// $hit_type = create_request(); // somehow acquire the HITType ID of the HIT
		$job_id = 0;    // somehow identify the specific task
		$url = 'http://my_site/hit_page.php?job_id=' . $job_id;
		$lifetime = 5 * 60;        // The task is expired after 5 minutes
		$job_qty = 5;              // We want 5 workers to have a go at this
		$r = new amt\external_hit_request($hit_type, $url, $job_id, $lifetime, $job_qty);
		$hit_id = $r->execute();   // after calling this, the HIT is 'assignable'
		// print '.- '.$hit_id. '<br>';
		return $hit_id;
	} catch (Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}

function create_custom_hit($hit_type){
	// custom_ext_hit_request
	try{
		$annotation = 0;
		$lifetime = 5*60;
		$job_qty = 3;
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
		$r = new amt\custom_ext_hit_request($hit_type, $annotation, $lifetime, $job_qty, $question);
		$hit = $r->execute();   // after calling this, the HIT is 'assignable'
		return $hit;
	} catch (amt\Exception $e) {
		print $e->getMessage() . $e->xmldata();
		error_log($e->getMessage() . $e->xmldata());
	}
	return -1;
}
function execute_job(){
	$hittype_id = create_request();
	$hit = create_custom_hit($hittype_id);

	$a = array('HITReviewable', 'HITExpired');
	$r = new amt\hittype_notification_request($hittype_id, 'http://crowdref.atwebpages.com/triggerscript.php', $a);
	$r->execute();


	print $hit->HITId . ' - ' . $hit->HITTypeId . '<br>';
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