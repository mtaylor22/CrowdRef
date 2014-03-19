<?php
require 'sql_op.php';
initialize();
if (resetdb()==0)
	print 'clear successful';
?>