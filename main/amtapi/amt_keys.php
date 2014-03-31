<?php
require 'pass.php';
/**
 * Initialize amt\request class
 * @author cpks
 * @license Public Domain
 */
//note the third parameter, which indicates sandbox
amt\request::init_class($amt_key, $amt_skey, $sandbox);