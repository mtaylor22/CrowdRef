<?php
/**
 * CSV file output
 * @author CPKS
 * @package csv
 * @license Public Domain
 */
namespace csv;

/**
 * csv\writer facilitates the writing of CSV files from associative arrays
 *
 * Provides methods to write a header line from the keys of the associative array
 * @package csv
 */
class writer {
  /**
   * file handle
   * @var resource
   */
  private $fp;
  /**
   * Header output control
   * @var boolean TRUE if we need to output headers
   */
  private $write_headers = TRUE;

  /**
   * Opens the file for write
   * @param string $fn filename
   * @param string $mode 'w' or 'a'
   * @throws InvalidArgumentException if $mode invalid
   * @throws RuntimeException if problems opening/creating
   */
  public function __construct($fn, $mode) {
    if ($mode !== 'w' && $mode !== 'a')
      throw new \InvalidArgumentException("Mode must be [wa], not '$mode'");
    if (\is_file($fn)) {
      if ($mode === 'w')
        throw new \RuntimeException("$fn already exists and would have been overwritten");
      if (!\is_writable($fn))
        throw new \RuntimeException("$fn is not writable.");
      $this->write_headers = FALSE;
    }
    if ($mode === 'a')
      $this->write_headers = FALSE;
    $this->fp = \fopen($fn, $mode);
  }
  /**
   * Writes a line of output to the file
   * @param string[] colheader => value
   */
  public function put(array $row) {
    if ($this->write_headers) {
      \fputcsv($this->fp, array_keys($row));
      $this->write_headers = FALSE;
    }
    \fputcsv($this->fp, $row);
  }
  /**
   * Write the header line
   * @param string[] $headers the column headings
   * @throws BadMethodCallException if headers already written
   */
  public function put_headers($headers) {
    if (is_array($headers)) {
      if (!$this->write_headers)
        throw new \BadMethodCallException("Headers off or already written");
      $this->write_headers = FALSE; // prevent automatic headers
      $this->put($headers);
    }
    $this->write_headers = FALSE;
  }
  /**
   * Write the header line from the array keys
   * @param string[] $data
   */
  public function put_key_headers(array $data) {
    $this->put_headers(array_keys($data));
  }
  /**
   * Close the file
   */
  public function close() {
    if ($this->fp) \fclose($this->fp);
    $this->fp = FALSE;
  }
  /**
   * Close the file if necessary
   */
  public function __destruct() {
    $this->close();
  }
}