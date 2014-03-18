<?php
require 'amt_op.php';
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
			add_reference_result($result['title'], $result['author'], $result['website_title'], $result['publisher'], $result['date_published'], $result['date_accessed'], $result['medium'], $result['url']);
		}
		$hit = new amt\minimal_hit($n->hit_id);
		$hit->dispose();
		trigger("Possibly should've worked");
	}
} catch (Exception $e) {
	error_log($e->getMessage());
	trigger("Exception in ref_res: ". $e->getMessage());
}
?>