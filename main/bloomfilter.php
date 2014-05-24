<?php
require 'sql_op.php';
require 'bitarray.php';
initialize();
$bloom_filter_size = 1000;
$hash_algorithms_count = 10;
$bloom_filter = new bitArray($bloom_filter_size);
foreach (get_finished_reference_urls() as $ref_key => $ref) {
  for ($i = 0; $i < $hash_algorithms_count; $i++){
    $bloom_filter->setBit(abs((md5($ref['url']. $i. 'salty')) % $bloom_filter_size), 1); 
    // print $ref['url']. $i. 'salty'. '<br>'.abs((md5($ref['url']. $i. 'salty')) % $bloom_filter_size) . '<br>';
  }
}
for ($i = 0; $i<$bloom_filter_size; $i++)
  print $bloom_filter->getBit($i);
// print (checkBF($bloom_filter, get_finished_reference_urls()[2]['url'], $hash_algorithms_count, $bloom_filter_size)) ? "GOOD" : "BAD";

function checkBF($bloom_filter, $url, $hash_algorithms_count, $bloom_filter_size){
  for ($i = 0; $i < $hash_algorithms_count; $i++){
    if ($bloom_filter->getBit(abs((md5($url. $i. 'salty')) % $bloom_filter_size)) == 0) return false; 
  }
  return true;
}
?>