<?php
/**
 * AMT notification classes
 * @package amt_rest_api
 * @author CPKS <cpk@smithies.org>
 * @license Public Domain
 * @version 0.1
 */
namespace amt;

/**
 * Internal base class
 * @package amt_rest_api
 * @subpackage notifications
 */
class notification_request extends request {
  /**
   * event types to trigger a notification
   * @var string[] $evtypes
   */
  private $evtypes = array();
  /**
   * Protected ctor
   * @param string $operation
   * @param string $dest_url Our handler service URL
   * @param string[] $event_types Events to trigger a notification
   */
  protected function __construct($operation, $dest_url, array $event_types) {
    foreach ($event_types as $ev) $this->check_evtype($ev);
    parent::__construct($operation);
    $this->add('Notification.1.Destination', $dest_url);
    $this->add('Notification.1.Transport', 'REST'); // that's all we support
    $this->add('Notification.1.Version', '2006-05-05');
    if (count($event_types) === 1)
      $this->add('Notification.1.EventType', $event_types[0]);
    else {
      $index = 0;
      foreach ($event_types as $ev) $this->add('Notification.1.EventType.' . ++$index, $ev);
    }
  }
  /**
   * Check $ev is valid
   * @param string $ev HITReviewable/HITExpired etc.
   * @throws InvalidArgumentException if $ev invalid
   */
  protected function check_evtype($ev) {
    if (!\in_array($ev, array('AssignmentAbandoned', 'AssignmentReturned',
      'AssignmentSubmitted', 'AssignmentAccepted', 'HITReviewable', 'HITExpired')))
      throw new \InvalidArgumentException("Event type $ev is invalid");
  }
  /**
   * Get response object
   * @throws amtException if response cannot be parsed or shows an error
   */
  public function execute() {
    response::acquire_from($this);
  }
}

/**
 * SetHITTypeNotification
 * @package amt_rest_api
 * @subpackage notifications
 * @api SetHITTypeNotification
 * @link manual.html#SetHITTypeNotification
 */
class hittype_notification_request extends notification_request {
  /**
   * Construct request object
   * @param string $hittype_id HITTypeId
   * @param string $dest_url Our service URL
   * @param string[] $event_types Event types to trigger notification
   */
  public function __construct($hittype_id, $dest_url, array $event_types) {
    parent::__construct('SetHITTypeNotification', $dest_url, $event_types);
    $this->add('HITTypeId', $hittype_id);
  }
}

/**
 * SendTestEventNotification
 * For testing the service handler
 * @package amt_rest_api
 * @subpackage notifications
 * @api SendTestEventNotification
 * @link manual.html#SendTestEventNotification
 */
class test_notification_request extends notification_request {
  /**
   * Construct request object
   * @param string $test_op an Event Type (can be 'Ping')
   * @param string $dest_url Our service URL
   * @param string[] $event_types Event types to trigger notification
   */
  public function __construct($test_op, $dest_url, array $event_types) {
    $this->check_evtype($test_op);
    parent::__construct('SendTestEventNotification', $dest_url, $event_types);
    $this->add('TestEventType', $test_op);
  }
  /**
   * Add 'Ping' to allowed event types
   * @param string $ev event type name
   * @throws InvalidArgumentException if $ev invalid
   */
  protected function check_evtype($ev) {
    if ($ev !== 'Ping') parent::check_evtype($ev);
  }
}

/*
  Notification API
*/

/**
 * amt\notification is the data structure containing the specific data returned.
 * All members are public.
 * @package amt_rest_api
 * @subpackage notifications
 * @api
 */
class notification {
  /**
   * the event that occurred
   * @var string
   */
  public $event_type;
  /**
   * the AMT-style timestamp
   * @var string
   */
  public $timestamp;
  /**
   * the HITTypeId of the HIT
   * @var string
   */
  public $hit_type;
  /**
   * the HITId of the HIT
   * @var string
   */
  public $hit_id;
  /**
   * the AssignmentId of this assignment
   * @var string
  */
  public $assignment_id;
  /**
   * Construct wrapper for notification data
   * @param string $evt the event
   * @param string $timestamp the AMT-style timestamp
   * @param string $hittype the HITTypeId of the HIT
   * @param string $hit_id the HITId of the HIT
   * @param string $asst_id the AssignmentId of this assignment
   */
  public function __construct($evt, $timestamp, $hittype, $hit_id, $asst_id) {
    $this->event_type = $evt;
    $this->timestamp = $timestamp;
    $this->hit_type = $hittype;
    $this->hit_id = $hit_id;
    $this->assignment_id = $asst_id;
  }
  /**
   * The UNIX-style time
   * @return int the UNIX-style time
   */
  public function time() { return \strtotime($this->timestamp); }
}

/**
 * Iterate over this to get each amt\notification received.
 *
 * Notification response is handled differently, as it will typically arrive
 * as GET parameters to the notification handler script. It's a singleton. We
 * don't waste resources implementing a singleton pattern, but in order to
 * check params before generating the object we keep the constructor private
 * and use amt\notification_response::acquire(), which does extensive checking
 * to ensure that we have a valid response to start with.
 *
 * @package amt_rest_api
 * @subpackage notifications
 * @api
*/
class notification_response implements \Countable, IteratorAggregate {
  /**
   * the events notified
   * @var amt\notification[]
   */
  private $events;
  /**
   * Private ctor
   * @param string[] $input get_param_name => param_value
   */
  private function __construct(array $input) {
    for ($index = 1; isset($input["Event_{$index}_EventType"]); ++$index) {
      $prefix = "Event_{$index}_";
      $evt = $input[$prefix . 'EventType'];
      $timestamp = $input[$prefix . 'EventTime'];
      $hittype = $input[$prefix . 'HITTypeId'];
      $hit_id = $input[$prefix . 'HITId'];
      $prefix .= 'AssignmentId';
      $asst_id = isset($input[$prefix]) ? $input[$prefix] : NULL;
      $this->events[] = new notification($evt, $timestamp, $hittype, $hit_id, $asst_id);
    }
  }
  /**
   * "constructor" method
   * @return amt\notification_response
   * @throws RuntimeException on parse failure
   */
  public static function acquire() {
    $input = filter_input_array(INPUT_GET, array(
      'method' => FILTER_SANITIZE_STRING,
      'Signature' => FILTER_SANITIZE_STRING,
      'Timestamp' => FILTER_SANITIZE_STRING,
      'Version' => FILTER_SANITIZE_STRING
    ));
    if (!$input || $input['method'] !== 'Notify')
      throw new \RuntimeException('No \'method\' parameter.');
    if (!$input['Signature'] || !$input['Timestamp'] || !$input['Version'])
      throw new \RuntimeException('Essential header params missing');
    if (!request::verify_notify_signature($input['Timestamp'], $input['Signature']))
      throw new \RuntimeException('Bad signature');
    return new self($_GET);
  }
  /**
   * Debug function
   * @param string $get_string the raw GET data
   * @return amt\notification_response
   */
  public static function debug_acquire($get_string) {
    if (($pos = \strpos($get_string, '?')) !== FALSE)
      $get_string = \substr($get_string, $pos + 1); // strip initial query &c
    $get_string = \explode('&', $get_string); // break into key=value pairs
    $params = array();
    foreach ($get_string as $rawparam) {
      $cooked = \explode('=', $rawparam);
      $key = \strtr($cooked[0], '.', '_');
      $params[$key] = isset($cooked[1]) ? \urldecode($cooked[1]) : '';
    }
    return new self($params);
  }
  /**
   * Implement IteratorAggregate
   * @return \ArrayIterator
   */
  public function getIterator() { return new \ArrayIterator($this->events); }
  /**
   * Implement \Countable
   * @return int
   */
  public function count() { return count($this->events); }
}