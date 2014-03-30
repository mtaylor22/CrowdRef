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
			$arr = add_reference_result(urlencode($result['title']), urlencode($result['author']), urlencode($result['website_title']), urlencode($result['publisher']), urlencode($result['date_published']), urlencode($result['date_accessed']), urlencode($result['medium']), urlencode($result['ref_id']), urlencode($result['WorkerId']));
			if ($arr != 0) continue;
			increment_status($result['ref_id']);
			$status = get_status($result['ref_id']);
			if ($status <= $status_cap){
				execute_final_job($result['ref_id'], get_ref_url(intval($result['ref_id'])));
			}
		}
		$hit = new amt\minimal_hit($n->hit_id);
		$hit->dispose();
	}
} catch (Exception $e) {
	error_log($e->getMessage());
    trigger('errorr in responder: '. $e->getMessage());
}
?>