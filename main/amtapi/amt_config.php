<?php
/**
 * Read config file
 * @author CPKS
 * @package amt_rest_api
 * @subpackage utils
 */
namespace amt;

 /**
  * ignoreHashIterator filters lines whose first non-blank char is a hash
  * @package amt_rest_api
  * @subpackage utils
  */
class ignoreHashIterator extends \FilterIterator {
  /**
   * Don't accept if first non-blank char is a hash
   * @return boolean
   */
  public function accept() {
    $s = \trim($this->current());
    if (empty($s)) return FALSE;
    return $s[0] !== '#';
  }
}

/**
 * amt\config: a configuration file reader
 *
 * Converts a configuration file into an associative ArrayObject.
 * Matches keys case-insensitively.
 * @package amt_rest_api
 * @subpackage utils
 */
class config extends \ArrayObject {
  /**
   * Read in from config file
   * @param string $fn name of file
   * @throws RuntimeException if config. line in wrong format
   */
  public function __construct($fn) {
    $inf = new \SPLFileObject($fn);
    $inf->setFlags(\SPLFileObject::DROP_NEW_LINE | \SPLFileObject::SKIP_EMPTY);
    foreach (new ignoreHashIterator($inf) as $line) {
      $line = \trim($line);
      if (($colon = \strpos($line, ':')) === FALSE)
        throw new \RuntimeException("'$line' doesn't contain a colon", \E_USER_ERROR);
      $key = \substr($line, 0, $colon);
      $val = \trim(\substr($line, $colon + 1));
      $this[\strtolower($key)] = $val;
    }
  }
  /**
   * Override offsetExists to be case-insensitive
   * @param string $k array key
   * @return boolean
   */
  public function offsetExists($k) {
    return parent::offsetExists(\strtolower($k));
  }
  /**
   * Override offsetGet to be case-insensitive
   * @param string $k array key
   * @return string
   */
  public function offsetGet($k) {
    return parent::offsetGet(\strtolower($k));
  }
}
