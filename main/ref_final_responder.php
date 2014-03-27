<?php
require 'sql_op.php';
initialize();
try {
	$hits_treated = array(); // prevent handling the same HIT twice
	$notifications = amt\notification_response::acquire();
	foreach ($notifications as $n) {
		if (in_array($n->hit_id, $hits_treated)) 
			continue;
		$hits_treated[] = $n->hit_id;
		$results = new amt\results($n->hit_id);
		foreach ($results as $result) {
			$result->approve(); 
			increment_status($result['ref_id']);
			// handle_correct_reference($result['ref_id'], $result['result_selection']);
			trigger('ref_id: '. $result['ref_id']);
			trigger('title: '. $result['title']);
			trigger('author: '. $result['author']);
			trigger('website_title: '. $result['website_title']);
			trigger('publisher: '. $result['publisher']);
			trigger('date_published: '. $result['date_published']);
			trigger('date_accessed: '. $result['date_accessed']);
			trigger('medium: '. $result['medium']);
			trigger('ref: '. $result['ref']);
			trigger('WorkerId: '. $result['WorkerId']);
			handle_correct_reference_specified($result['ref_id'], $result['title'], $result['author'], $result['website_title'], $result['publisher'], $result['date_published'], $result['date_accessed'], $result['medium'], $result['WorkerId']);
			set_notification('ref_finished', $result['ref_id'], NULL);
		}
		$hit = new amt\minimal_hit($n->hit_id);
		$hit->dispose();
	}
} catch (Exception $e) {
	error_log($e->getMessage());
    trigger('errorr in responder: '. $e->getMessage());
}
?>