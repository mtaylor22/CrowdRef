<?php
/* AMT notification reception */
require 'amt_op.php';
require 'sql_op.php';

try {
  // $DB = new my_pdo_database;
  $hits_treated = array(); // prevent handling the same HIT twice

  $notifications = amt\notification_response::acquire();
  // prepare a query to store the worker's answer in the database
  // $s = $DB->prepare('CALL amt_record_answer(?,?,?,?,?,?)');
  foreach ($notifications as $n) {
    if (in_array($n->hit_id, $hits_treated)) continue; // already done
    // $s->bindValue(1, $n->hit_id);
    $hits_treated[] = $n->hit_id; // prevent this HIT being handled again
    $results = new amt\results($n->hit_id); // get the responses
    // record result answers in archive
    foreach ($results as $result) {
      $result->approve(); // approve the assignment - worker gets paid
      // $s->bindValue(2, $result->accept_time, PDO::PARAM_INT);
      // $s->bindValue(3, $result->submit_time, PDO::PARAM_INT);
      add_reference_result($result['title'], $result['author'], $result['website_title'], $result['publisher'], $result['date_published'], $result['date_accessed'], $result['medium'], $result['assignmentId']);
      // $s->execute();
    }
    // now let's delete the HIT
    $hit = new amt\minimal_hit($n->hit_id);
    $hit->dispose();
  }
}
catch (Exception $e) {
  error_log($e->getMessage());
}
?>