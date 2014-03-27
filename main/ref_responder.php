<?php
require 'sql_op.php';
// trigger('hit responder');
initialize();
try {
	$hits_treated = array(); // prevent handling the same HIT twice
	$notifications = amt\notification_response::acquire();
	// trigger('hit responderacquired hit' );
	foreach ($notifications as $n) {
		if (in_array($n->hit_id, $hits_treated)) 
			continue;
		$hits_treated[] = $n->hit_id;
		$results = new amt\results($n->hit_id);
		foreach ($results as $result) {
			$result->approve(); 
			$arr = add_reference_result($result['title'], $result['author'], $result['website_title'], $result['publisher'], $result['date_published'], $result['date_accessed'], $result['medium'], $result['ref_id'], $result['WorkerId']);
			if ($arr != 0) continue;
			increment_status($result['ref_id']);
			$status = get_status($result['ref_id']);
			if ($status <= $status_cap){
				//time to post final job collector
				trigger("refid: ". $result['ref_id']);
				trigger("refurl: ". get_ref_url($result['ref_id']));

				execute_final_job($result['ref_id'], get_ref_url(intval($result['ref_id'])));
				// trigger('post final job');
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