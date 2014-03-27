<?php
$url = 'http://www.googlkkkkkkkke.com';
$header = @get_headers($url, 1);
if ($header !== false)
print substr($header[0], 9, 3);
else 
print 'doesn\'t exist';

?> hit?