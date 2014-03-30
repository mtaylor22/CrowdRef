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
			handle_correct_reference_specified(urlencode($result['ref_id']), urlencode($result['title']), urlencode($result['author']), urlencode($result['website_title']), urlencode($result['publisher']), urlencode($result['date_published']), urlencode($result['date_accessed']), urlencode($result['medium']), urlencode($result['WorkerId']));
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