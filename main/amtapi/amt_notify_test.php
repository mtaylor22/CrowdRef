<?php
/**
 * Test AMT notification reception
 * @author cpks
 * @package amt_rest_api
 * @license Public Domain
 */

$logfile = fopen('amt_test.txt', 'a');
fwrite($logfile, date('Y-m-d H:i:s') . PHP_EOL . print_r($_GET, TRUE) . "\n===\n");
fclose($logfile);
?>
OK
