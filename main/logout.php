<?php
session_start();
session_destroy();
header('location: message.php?message=logout');
?>