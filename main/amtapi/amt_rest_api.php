<?php
/**
 * AMT request classes
 * @package amt_rest_api
 * @author CPKS <cpk@smithies.org>
 * @license Public Domain
 * @version 0.1
 */
namespace amt;
/**
 * Register autoloads
 * @param string $name
 */
spl_autoload_register(function($name) {
  switch ($name) {
    case '\csv\writer' : include 'csvwriter.php'; break;
    case 'config' : include 'amt_config.php'; //break;
  }
}, FALSE);

/**
 * Base request class
 *
 * Generates complete request URL.
 * Static methods set options for all AMT requests.
 * @package amt_rest_api
 * @subpackage requests
 */
class request {
  /**
   * Web service access URL
   * @var string
   */
  private static $service_url = 'https://mechanicalturk.amazonaws.com/onca/xml';
  /**
   * complete URL with request string
   * @var string
   */
  private $url;
  /**
   * cached operation name
   * @var string
   */
  private $op;
  /**
   * AWS access key ID
   * @var string
   */
  private static $AWS_ACCESS_KEY_ID;
  /**
   * AWS secret access key used to sign requests
   * @var string
   */
  private static $AWS_SECRET_ACCESS_KEY;
  /**
   * Service name
   */
  const SERVICE_NAME = 'AWSMechanicalTurkRequester';
  /**
   * API version
   */
  const SERVICE_VERSION = '2008-08-02';

  /**
   * set test (sandbox) mode (call before creating an instance)
   */
  public final static function set_sandbox_mode() {
    self::$service_url = 'https://mechanicalturk.sandbox.amazonaws.com/onca/xml';
  }
  /**
   * Class initialization
   *
   * This function *must* be called before creating a request instance.
   * @param string $access_key Service access key
   * @param string $secret_key Secret verification key
   * @param boolean $sandbox TRUE if we are to use the sandbox
   */
  public final static function init_class($access_key, $secret_key, $sandbox = FALSE) {
    if (!function_exists('\hash_hmac') && !method_exists(__CLASS__, 'hmac_sha1'))
      throw new Exception('You need to Uncomment the local hmac_sha1 function');
    self::$AWS_ACCESS_KEY_ID = $access_key;
    self::$AWS_SECRET_ACCESS_KEY = $secret_key;
    if ($sandbox) self::set_sandbox_mode();
  }

  /* Uncomment this if you don't have the builtin hash_hmac function:
  private final static function hmac_sha1($key, $s) {
    return pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
      pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $s))));
  }*/

  /**
   * Create message HMAC
   *
   * NB service name is a param because this function is used to verify
   *  notification signatures FROM AMT.
   * @param string $service AWSMechanicalTurkRequester[Notification]
   * @param string $operation AMT operation name
   * @param string $timestamp AMT-style time string
   */
  private final static function generate_signature($service, $operation, $timestamp) {
    $string_to_encode = $service . $operation . $timestamp;
    // Uncomment if you don't have the builtin hash_hmac function:
    // $hmac = self::hmac_sha1(self::AWS_SECRET_ACCESS_KEY, $string_to_encode);
    // comment this out if you don't have the builtin hash_hmac function:
    $hmac = \hash_hmac('sha1', $string_to_encode, self::$AWS_SECRET_ACCESS_KEY, TRUE);
    return base64_encode($hmac);
  }
  /**
   * Constructor is protected: not allowed to create one of these directly.
   * @param string $operation Service operation to perform
   */
  protected function __construct($operation) {
    if (!isset(self::$AWS_ACCESS_KEY_ID))
      throw new \BadMethodCallException('Service Keys not initialized');

    $params = array(
      'Service' => self::SERVICE_NAME,
      'Operation' => $operation,
      'Version' => self::SERVICE_VERSION,
      'AWSAccessKeyId' => self::$AWS_ACCESS_KEY_ID
    );
    $params['Timestamp'] =
      $timestamp = gmdate('Y-m-d\TH:i:s\Z'); // AWS-style timestamp
    $params['Signature'] =
      self::generate_signature(self::SERVICE_NAME, $this->op = $operation, $timestamp);
    $this->url = self::$service_url . '?' . \http_build_query($params, NULL, '&');
  }
  /**
   * Translate key/value pair into a GET parameter
   * @param string $k param name
   * @param string $v param value
   */
  protected final static function restparam($k, $v) {
    return "&$k=" . urlencode($v);
  }
  /**
   * Add parameter
   * @param string $k Parameter name
   * @param string $v Parameter value
   */
  protected function add($k, $v) {
    $this->url .= self::restparam($k, $v);
  }
  /**
   * Return the URL as a string
   * @return string the URL
   */
  public function __toString() {
    return $this->url;
  }
  /**
   * Return the operation requested as a string
   * @return string the operation requested
   */
  public final function get_op() { return $this->op; }
  /**
   * Check validity of message signature on parsing notify payload
   * @param string timestamp notification message timestamp
   * @param string $signature notification message signature
   * @return boolean TRUE if signature matches
   */
  public final static function verify_notify_signature($timestamp, $signature) {
    return self::generate_signature('AWSMechanicalTurkRequesterNotification', 'Notify', $timestamp) === $signature;
  }
}

/**
 * Balance Request
 *
 * use like this: $balance = amt\balance_request::execute();
 * echo "Account balance: $balance\n";
 * @package amt_rest_api
 * @subpackage requests
 * @api GetAccountBalance
 * @link manual.html#GetAccountBalance
 * @category requests
 */
class balance_request extends request {
  /**
   * Don't create one of these
   */
  protected function __construct() { parent::__construct('GetAccountBalance'); }
  /**
   * Execute the query and return the balance as a string
   * @return string price in the form "$9999.99"
   */
  public static function execute() {
    $r = response::acquire_from(new self);
    return (string)$r->GetAccountBalanceResult->AvailableBalance->FormattedPrice;
  }
}

/**
 * GetRequesterStatistic
 * @package amt_rest_api
 * @subpackage requests
 * @api GetRequesterStatistic
 * @link manual.html#GetRequesterStatistic
 */
class stats_request extends request {
  /**
   * Constructor (protected)
   * @param string $statprop the property whose stats we're looking at
   * @param string $period OneDay/SevenDays/ThirtyDays/LifeToDate
   * @param integer $count the number of OneDays in the requested resultset
   * @throws InvalidArgumentException
   */
  protected function __construct($statprop, $period, $count = 1) {
    $this->check_params($statprop, $period, $count);
    parent::__construct('GetRequesterStatistic');
    $this->add('Statistic', $statprop);
    $this->add('TimePeriod', $period);
    $this->add('Count', $count);
  }
  /**
   * Throw exception if params incorrect
   * @param string $statprop the property whose stats we're looking at
   * @param string $period OneDay/SevenDays/ThirtyDays/LifeToDate
   * @param integer $count the number of OneDays in the requested resultset
   * @throws InvalidArgumentException
   */
  private function check_params($statprop, $period, $count) {
    if (!\in_array($statprop, array(
      'NumberAssignmentsAvailable', 'NumberAssignmentsAccepted',
      'NumberAssignmentsPending', 'NumberAssignmentsApproved',
      'NumberAssignmentsRejected', 'NumberAssignmentsAbandoned',
      'NumberAssignmentsReturned',
      'PercentAssignmentsApproved', 'PercentAssignmentsRejected',
      'TotalRewardPayout', 'AverageRewardAmount', 'TotalRewardFeePayout',
      'TotalBonusPayout',
      'TotalBonusFeePayout', 'NumberHITsCreated', 'NumberHITsCompleted',
      'NumberHITsAssignable', 'NumberHITsReviewable', 'EstimatedRewardLiability',
      'EstimatedFeeLiability', 'EstimatedTotalLiability'
      )))
      throw new \InvalidArgumentException("$statprop is an illegal statistic");
    if (!\in_array($period, array(
      'OneDay', 'SevenDays', 'ThirtyDays', 'LifeToDate'
      )))
      throw new \InvalidArgumentException("$period is not a legal period");
    if ($count != 1) {
      if ($period !== 'OneDay')
        throw new \InvalidArgumentException("Count != 1 legal only for OneDay period");
      if ($count < 1)
        throw new \InvalidArgumentException('Count must be a positive integer.');
    }
    if ($statprop === 'NumberHITsAssignable' && $period !== 'LifeToDate') {
      trigger_error("Period must be LifeToDate with NumberHITsAssignable: corrected", E_USER_WARNING);
      $period = 'LifeToDate';
    }
  }
  /**
   * Execute the query
   * @param string $statprop the property whose stats we're looking at
   * @param string $period OneDay/SevenDays/ThirtyDays/LifeToDate
   * @param integer $count the number of OneDays in the requested resultset
   * @throws amtException
   * @throws InvalidArgumentException
   * @return mixed[timestamp] array of int (counts) or float keyed on int timestamp
   */
  public static function execute($statprop, $period, $count = 1) {
    $r = response::acquire_from(new self($statprop, $period, $count), 'GetStatisticResult');
    $at = $statprop[0] === 'N' ? 'Long' : 'Double';
    $answerfield = $at . 'Value';
    $fconv = $at[0] !== 'L';
    foreach ($r->GetStatisticResult->DataPoint as $dp) {
      $val = (string)$dp->$answerfield;
      $val = $fconv ? (float)$val : (int)$val;
      $timekey = \strtotime((string)($dp->Date));
      $retval[$timekey] = $val;
    }
    if (count($retval) > 1) \ksort($retval);
    return $retval;
  }
  /**
   * Execute a query for a single value
   * @param string $statprop the property whose stats we're looking at
   * @param string $period OneDay/SevenDays/ThirtyDays/LifeToDate
   * @throws amtException
   * @throws InvalidArgumentException
   * @return numeric float or int statistic value - float for percentages
   */
  public static function exec1($statprop, $period) {
    return \array_pop(self::execute($statprop, $period, 1));
  }
}

/**
 * Base for compound/reusable request types
 *
 * Most AMT requests require more than the basic "common" parameters.
 * This class adds parameter checking and the facility to import
 * parameters from a config file.
 * It is also possible to modify the additional parameters to make repeated
 * requests, varying only a subset.
 * @package amt_rest_api
 * @subpackage requests
 */
class compound_request extends request {
  /**
   * param_name => required
   * @var boolean[] $param_spec param_name => required
   */
  private $param_spec;
  /**
   * param_name => value
   * @var string[] $xparams param_name => value
   */
  private $xparams;

  /**
   * Protected constructor
   * @param string $operation
   * @param boolean[] $param_spec indicates which params are required
   */
  protected function __construct($operation, array $param_spec) {
    $this->param_spec = $param_spec;
    $this->xparams = array();
    parent::__construct($operation);
  }
  /**
   * Set/change parameter
   * @param string $k param name
   * @param string $v param value
   */
  protected function set_param($k, $v) {
    $this->xparams[$k] = $v;
  }
  /**
   * Add parameter
   * @param string $k param name
   * @param string $v param value
   * @throws BadMethodCallException if $k already set
   */
  protected function add_param($k, $v) {
    if (isset($this->xparams[$k]))
      throw new \BadMethodCallException("Parameter $k already set.");
    $this->set_param($k, $v);
  }
  /**
   * Remove parameter
   * @param string $k param name
   */
  protected function clear_param($k) { unset($this->xparams[$k]); }
  /**
   * Return whether parameter is set
   * @param string $k param name
   * @return bool
   */
  protected function has_param($k) { return isset($this->xparams[$k]); }
  /**
   * Test whether specified param exceeds specified max length
   * @param string $k param name
   * @param int $maxlen max length permitted
   * @throws LengthException if param $k value exceeds $maxlen
   */
  protected function length_check($k, $maxlen) {
    if (isset($this->xparams[$k]) && \strlen($this->xparams[$k]) > $maxlen)
      throw new \LengthException("Parameter $k is over $maxlen characters", E_USER_ERROR);
  }
  /**
   * Test whether specified parameter is in range
   * @param string $k param name
   * @param int $min min permitted value
   * @param int $max max permitted value
   * @throws OutOfRangeException if out of range
   */
  protected function range_check($k, $min, $max) {
    if (!isset($this->xparams[$k])) return;
    $v = $this->xparams[$k];
    if ($v < $min)
      throw new \OutOfRangeException("Parameter $k: $v is below the minimum $min", E_USER_ERROR);
    if ($v > $max)
      throw new \OutOfRangeException("Parameter $k: $v is above the maximum $max", E_USER_ERROR);
  }
  /**
   * Read parameters from a config file
   *
   * To obtain warnings/notices about parameters in the config
   * file that are *not* used by this request, set $check_unused
   * to the appropriate error constant e.g. E_USER_NOTICE.
   * @throws RuntimeException when required parameter is missing
   * @param string $fn filename
   * @param int $check_unused - FALSE or error/notice code
   */
  public function read_config($fn, $check_unused = FALSE) {
    $f = new config($fn);
    if ($check_unused) {
      // check config doesn't contain illegal parameters
      $errs = array();
      $paramnames = array();
      foreach ($this->param_spec as $k => $v)
        $paramnames[\strtolower($k)] = TRUE;
      foreach ($f as $k => $v) {
        if (!isset($paramnames[\strtolower($k)]))
          $errs[] = $k;
      }
      if (!empty($errs)) \trigger_error('The following are illegal parameters: ' .
        \implode(',', $errs), $check_unused);
    }
    // now process all parameters in the config file
    foreach ($this->param_spec as $k => $required) {
      if (isset($f[$k]))
        $this->add_param($k, $f[$k]);
      else if ($required)
        throw new \RuntimeException("Required parameter $k not in $fn", E_USER_ERROR);
    }
  }
  /**
   * Check that all required params have been supplied
   * @throws LogicException if a required param is missing
   */
  public function check_required_params() {
    foreach ($this->param_spec as $k => $required) {
      if ($required && !isset($this->xparams[$k]))
        throw new \LogicException("Required parameter $k not set", E_USER_ERROR);
    }
  }
  /**
   * Return the full URL
   * @return string the full URL
   */
  public function __toString() {
    $retval = parent::__toString();
    foreach ($this->xparams as $k => $v) {
      if ($k === 'Reward') {
        $retval .= parent::restparam('Reward.1.CurrencyCode', 'USD');
        $retval .= parent::restparam('Reward.1.Amount', $v);
      }
      else
        $retval .= parent::restparam($k, $v);
    }
    return $retval;
  }
}

/**
 * Request for RegisterHITType
 * @package amt_rest_api
 * @subpackage requests
 * @api RegisterHITType
 * @link manual.html#RegisterHITType
 */

class hittype_request extends compound_request {
  /**
   * Set params via set_params()
   */
  public function __construct() {
    parent::__construct('RegisterHITType', array(
      'Reward' => TRUE,
      'Title' => TRUE,
      'Description' => TRUE,
      'AssignmentDurationInSeconds' => TRUE,
      'Keywords' => FALSE,
      'AutoApprovalDelayInSeconds' => FALSE,
      'QualificationRequirement' => FALSE
    ));
  }
  /**
   * Check whether any param exceeds specified length / range
   * @throws LogicException if any param exceeds length/value
   */
  public function check_params() {
    $this->length_check('Title', 120);
    $this->length_check('Description', 1999);
    $this->length_check('Keywords', 999);
    $this->range_check('AutoApprovalDelayInSeconds', 3600, 2592000);
    $this->range_check('AssignmentDurationInSeconds', 30, 31536000);
  }
  /**
   * set params directly, rather than from config file
   * @param float $reward amount paid for task
   * @param string $title HIT title
   * @param string $desc HIT description
   * @param int $duration No. of seconds to complete the HIT after acceptance
   * @param string $keywords comma-separated list of search keywords
   * @param int no. of seconds before auto-approval, or FALSE if not auto
   * @throws BadMethodCallException if called twice on the same object
   */
  public function set_params($reward, $title, $desc, $duration, $keywords = '', $auto_approve = FALSE) {
    $this->add_param('Reward', $reward);
    $this->add_param('Title', $title);
    $this->add_param('Description', $desc);
    $this->add_param('AssignmentDurationInSeconds', $duration);
    if ($keywords)
      $this->add_param('Keywords', $keywords);
    if ($auto_approve)
      $this->add_param('AutoApprovalDelayInSeconds', $auto_approve);
  }
  /**
   * Set qualification requirements for the HIT
   * @param amt\qualification_requirement[] $requirements
   * @throws BadMethodCallException if called twice on the same object
   */
  public function set_qual_requirements(array $requirements) {
    $rqi = 1;
    foreach ($requirements as $rq) {
      foreach ($rq->restparams($rqi) as $k => $v) $this->add_param($k, $v);
      ++$rqi;
    }
  }
  /**
   * Perform the request
   * @return string the HITTypeId
   */
  public function execute() {
    $r = response::acquire_from($this);
    return (string)$r->RegisterHITTypeResult->HITTypeId;
  }
}

// Edited by Mitchell Taylor



class custom_hit_request extends compound_request {
  
  public function __construct($title, $description, $question=NULL, $reward, $assignmentdurationinseconds,
                              $lifetimeinseconds, $keywords=NULL, $maxassignments=NULL, $autoapprovaldelayinseconds=NULL,
                              $qualificationrequirement=NULL, $assignmentreviewpolicy=NULL, $hitreviewpolicy=NULL, 
                              $requesterannotation=NULL, $uniquerequesttoken=NULL, $hitlayoutid=NULL, $hitlayoutparameter=NULL) {
    parent::__construct('CreateHIT', array(
      'Title' => TRUE,
      'Description' => TRUE,
      'Question' => FALSE,
      'HITLayoutId' => FALSE,
      'HITLayoutParameter' => FALSE,
      'Reward' => TRUE,
      'AssignmentDurationInSeconds' => TRUE,
      'LifetimeInSeconds' => TRUE,
      'Keywords' => FALSE,
      'MaxAssignments' => FALSE,
      'AutoApprovalDelayInSeconds' => FALSE,
      'QualificationRequirement' => FALSE,
      'AssignmentReviewPolicy' => FALSE,
      'HITReviewPolicy' => FALSE,
      'RequesterAnnotation' => FALSE,
      'UniqueRequestToken' => FALSE
    ));

    $this->add_param('Title', $title);
    $this->add_param('Description', $description);
    $this->add_param('Reward', $reward);
    $this->add_param('AssignmentDurationInSeconds', $assignmentdurationinseconds);
    $this->add_param('LifetimeInSeconds', $lifetimeinseconds);

    if ($question)
      $this->add_param('Question', $question);
    if ($hitlayoutid)
      $this->add_param('HITLayoutId', $hitlayoutid);
    if ($hitlayoutparameter)
      $this->add_param('HITLayoutParameter', $hitlayoutparameter);
    if ($keywords)
      $this->add_param('Keywords', $keywords);
    if ($maxassignments)
      $this->add_param('MaxAssignments', $maxassignments);
    if ($autoapprovaldelayinseconds)
      $this->add_param('AutoApprovalDelayInSeconds', $autoapprovaldelayinseconds);
    if ($qualificationrequirement)
      $this->add_param('QualificationRequirement', $qualificationrequirement);
    if ($assignmentreviewpolicy)
      $this->add_param('AssignmentReviewPolicy', $assignmentreviewpolicy);
    if ($hitreviewpolicy)
      $this->add_param('HITReviewPolicy', $hitreviewpolicy);
    if ($requesterannotation)
      $this->add_param('RequesterAnnotation', $requesterannotation);
    if ($uniquerequesttoken)
      $this->add_param('UniqueRequestToken', $uniquerequesttoken);    
  }

  public function check_params() {
    //disabled?
  }

  public function execute() {
    $r = response::acquire_from($this, 'HIT');
    return new hit($r->HIT);
  }
}



class custom_ext_hit_request extends compound_request {
  const MAX_ANNOTATION_LENGTH = 4096;
  const MIN_LIFETIME = 30;
  const MAX_LIFETIME = 31536000;

  public function __construct($hittype, $annotation=NULL, $lifetime, $assignments, $question=NULL, $frameheight = 640) {
    parent::__construct('CreateHIT', array(
      'HITTypeId' => TRUE,
      'Question' => TRUE,
      'LifetimeInSeconds' => TRUE,
      'MaxAssignments' => FALSE,
      'RequesterAnnotation' => FALSE
    ));
    $this->add_param('HITTypeId', $hittype);
    if (\strlen($annotation) > self::MAX_ANNOTATION_LENGTH)
      throw new \LengthException('Annotation exceeds ' . self::MAX_ANNOTATION_LENGTH . ' characters');
    if ($annotation)
      $this->add_param('RequesterAnnotation', $annotation);
    if ($lifetime < self::MIN_LIFETIME || $lifetime > self::MAX_LIFETIME)
      throw new \OutOfRangeException('Lifetime out of bounds');
    $this->add_param('LifetimeInSeconds', $lifetime);
    if (!is_int($assignments) || $assignments < 1)
      throw new \InvalidArgumentException('Assignments must be integer > 0');
    if ($assignments > 1)
      $this->add_param('MaxAssignments', $assignments);
    $this->add_param('Question', $question);
  }
  /**
   * Throw exception if params incorrect
   * @throws LogicException
   */
  public function check_params() {
    $this->length_check('Question', 131072);
    $this->length_check('RequesterAnnotation', self::MAX_ANNOTATION_LENGTH);
    $this->range_check('LifetimeInSeconds', self::MIN_LIFETIME, self::MAX_LIFETIME);
  }
  /**
   * Execute the query and return the HIT
   * @throws amtException
   * @return amt\hit
   */
  public function execute() {
    $r = response::acquire_from($this, 'HIT');
    return new hit($r->HIT);
  }
}


// end of edit


/**
 * CreateHIT - for external HIT with HITType
 *
 * The only createHIT request I'm implementing is for external HIT
 * with HITType (i.e. not individual parameters).
 * @package amt_rest_api
 * @subpackage requests
 * @uses amt\hit - return from execute()
 * @api CreateHIT
 * @link manual.html#CreateHIT
 */
class external_hit_request extends compound_request {
  /**
   * RequesterAnnotation max length
   */
  const MAX_ANNOTATION_LENGTH = 4096;
  /**
   * Minimum HIT lifetime in seconds, i.e. time before it becomes unavailable
   */
  const MIN_LIFETIME = 30;
  /**
   * Maximum HIT lifetime in seconds
   */
  const MAX_LIFETIME = 31536000;

  /**
   * Create request object
   * @param string $hittype HITTypeId
   * @param string $url URL of the external HIT page
   * @param string $annotation RequesterAnnotation - user documentation
   * @param int $lifetime LifetimeInSeconds
   * @param int $assignments how many workers max. to be given the HIT
   * @param int $frameheight pixel height of the embedded external HIT window
   * @throws InvalidArgumentException|LengthException|OutOfRangeException
   */


  public function __construct($hittype, $url, $annotation, $lifetime, $assignments, $frameheight = 640) {
    parent::__construct('CreateHIT', array(
      'HITTypeId' => TRUE,
      'Question' => TRUE,
      'LifetimeInSeconds' => TRUE,
      'MaxAssignments' => FALSE,
      'RequesterAnnotation' => FALSE
    ));
    $this->add_param('HITTypeId', $hittype);
    if (\strlen($annotation) > self::MAX_ANNOTATION_LENGTH)
      throw new \LengthException('Annotation exceeds ' . self::MAX_ANNOTATION_LENGTH . ' characters');
    if ($annotation)
      $this->add_param('RequesterAnnotation', $annotation);
    if ($lifetime < self::MIN_LIFETIME || $lifetime > self::MAX_LIFETIME)
      throw new \OutOfRangeException('Lifetime out of bounds');
    $this->add_param('LifetimeInSeconds', $lifetime);
    if (!is_int($assignments) || $assignments < 1)
      throw new \InvalidArgumentException('Assignments must be integer > 0');
    if ($assignments > 1)
      $this->add_param('MaxAssignments', $assignments);
    $this->add_param('Question', '<?xml version="1.0"?>'
    . '<ExternalQuestion xmlns="http://mechanicalturk.amazonaws.com/AWSMechanicalTurkDataSchemas/2006-07-14/ExternalQuestion.xsd">'
    . "<ExternalURL>$url</ExternalURL><FrameHeight>$frameheight</FrameHeight></ExternalQuestion>");
  }
  /**
   * Throw exception if params incorrect
   * @throws LogicException
   */
  public function check_params() {
    $this->length_check('Question', 131072);
    $this->length_check('RequesterAnnotation', self::MAX_ANNOTATION_LENGTH);
    $this->range_check('LifetimeInSeconds', self::MIN_LIFETIME, self::MAX_LIFETIME);
  }
  /**
   * Execute the query and return the HIT
   * @throws amtException
   * @return amt\hit
   */
  public function execute() {
    $r = response::acquire_from($this, 'HIT');
    return new hit($r->HIT);
  }
}

/**
 * Derived classes receive paged results. This class manages the standard
 * parameter settings - page size etc.
 * @package amt_rest_api
 * @subpackage requests
 */
abstract class paging_request extends compound_request {
  /**
   * Number of current page 1..n
   * @var int $current_page Number of current page 1..n
   */
  private $current_page = 1;

  /**
   * Used for parameter checking
   * @var boolean[] params name=>required
   */
  static protected $paging_fields = array(
    'SortProperty' => FALSE,
    'SortDirection' => FALSE,
    'PageSize' => FALSE,
    'PageNumber' => FALSE
  );
  /**
   * This is used by the set_sort function to check arguments.
   * @return string[] array of valid sort keys - the first is the default
   * @throws BadMethodCallException if sorting unavailable
  */
  abstract protected function valid_sort_fields();
  /**
   * set sort order ascending/descending on property
   * @param string $key_prop e.g. HITId
   * @param string $direction Ascending/Descending
   * @throws InvalidArgumentException for invalid property/direction
   */
  public function set_sort($key_prop, $direction = 'Ascending') {
    $valid_props = $this->valid_sort_fields();
    if (!\in_array($key_prop, $valid_props))
      throw new \InvalidArgumentException("$key_prop is not a permissible sort property", E_USER_ERROR);
    if (!\in_array($direction, array('Ascending', 'Descending')))
      throw new \InvalidArgumentException("Direction ($direction) must be 'Ascending' or 'Descending'");
    if ($key_prop !== $valid_props[0]) $this->add_param('SortProperty', $key_prop);
    if ($direction !== 'Ascending') $this->add_param('SortDirection', $direction);
  }
  /**
   * Set no. of results in each page of results
   * @param int $size
   * @throws InvalidArgumentException if 1 > $size > 100
   */
  public function set_pagesize($size) {
    if ($size < 1 || $size > 100)
      throw new \OutOfRangeException("Size ($size) must be between 1 and 100", E_USER_ERROR);
    $this->set_param('PageSize', $size);
  }
  /**
   * Set page number of results to fetch
   * @param int $pageno 1..n
   * @throws InvalidArgumentException if 1 > $size
   */
  public function set_pageno($pageno) {
    if (!is_int($pageno) || $pageno < 1)
      throw new \InvalidArgumentException("Page number ($pageno) must be +ve integer", E_USER_ERROR);
    if ($pageno !== $this->current_page) {
      if ($pageno === 1)
        $this->clear_param('PageNumber');
      else
        $this->set_param('PageNumber', $pageno);
      $this->current_page = $pageno;
    }
  }
  /**
   * Do not call this!
   * @param string $fn config. filename
   * @param boolean $check_unused
   * @throws BadMethodCallException always!
   */
  public function read_config($fn, $check_unused = FALSE) {
    throw new \BadMethodCallException('read_config not available for ' . __CLASS__, E_USER_ERROR);
  }
  /**
   * Returns iterable collection
  */
  abstract public function execute();
}

/**
 * Request for HIT results
 *
 * Use in conjunction with amt\results, e.g.
 *   $rq = new amt\results_request($hit_id);
 *   $rq->select_status('Submitted');
 *   $r = new amt\results($rq); // or, $r = $rq->execute();
 * @package amt_rest_api
 * @subpackage requests
 * @api GetAssignmentsForHIT
 * @link manual.html#GetAssignmentsForHIT
 */
class results_request extends paging_request {
  /**
   * the ID of the HIT whose results we want
   * @var string the ID of the HIT whose results we want
   */
  private $hit_id;
  /**
   * Create HIT results request object
   * @param string $hit_id HITId
   */
  public function __construct($hit_id) {
    $valid_fields = parent::$paging_fields;
    $valid_fields['HITId'] = TRUE; // mandatory
    $valid_fields['AssignmentStatus'] = FALSE; // optional

    parent::__construct('GetAssignmentsForHIT', $valid_fields);
    $this->add_param('HITId', $hit_id);
  }
  /**
   * Call to limit resultset by status
   * @param string $stat Submitted/Approved/Rejected
   * @throws InvalidArgumentException if $stat not one of these
   * @throws BadMethodCallException if called twice on same object
   */
  public function select_status($stat) {
    if (!\in_array($stat, array('Submitted', 'Approved', 'Rejected')))
      throw new \InvalidArgumentException("$stat is not a permissible value", E_USER_ERROR);
    $this->add_param('AssignmentStatus', $stat);
  }
  /**
   * This is used by the set_sort function to check arguments.
   */
  protected function valid_sort_fields() {
    return array('SubmitTime', 'AcceptTime', 'AssignmentStatus');
  }
  /**
   * get the HITId
   * @return string the HITId
   */
  public function get_hit_id() { return $this->hit_id; }
  /**
   * Execute the query
   * @return amt\results iterable collection of amt\assignment
   */
  public function execute() {
    return new results($this);
  }
}

/**
 * This class is for internal use (as subclass) only
 * See the subclasses
 * @package amt_rest_api
 * @subpackage requests
 */
class simple_hit_request extends request {
  /**
   * Protected ctor
   * @param string $operation the AMT operation
   * @param string $hit_id HITId
   */
  protected function __construct($operation, $hit_id) {
    parent::__construct($operation);
    $this->add('HITId', $hit_id);
  }
}

/**
 * set HIT as under review
 *
 * i.e. filter from GetReviewableHITs
 * @package amt_rest_api
 * @subpackage requests
 */
class reviewing_hit_request extends simple_hit_request {
  /**
   * create request object
   * @param string $hit_id HITId
   * @param boolean $reviewing TRUE if set reviewing, else set reviewable
   */
  public function __construct($hit_id, $reviewing = TRUE) {
    parent::__construct('SetHITAsReviewing', $hit_id);
    if (!$reviewing) $this->add('Revert', 'true');
  }
}

/**
 * Disable HIT
 *
 * auto accept completed and pending submits, then destroy the HIT
 * HIT objects have a disable() method which is preferable to use
 * @package amt_rest_api
 * @subpackage requests
 */
class disable_hit_request extends simple_hit_request {
  /**
   * create request object
   * @param string $hit_id HITId
   */
  public function __construct($hit_id) {
    parent::__construct('DisableHIT', $hit_id);
  }
}

/**
 * destroy or Dispose the HIT
 *
 * HIT objects have a dispose() method which is preferable to use
 * @package amt_rest_api
 * @subpackage requests
 */
class dispose_hit_request extends simple_hit_request {
  /**
   * Create request object; prefer constructing a HIT object and calling dispose()
   * @param string $hit_id HITId
   */
  public function __construct($hit_id) {
    parent::__construct('DisposeHIT', $hit_id);
  }
}

/**
 * expire the HIT, preventing fresh assignments, but preserve data
 *
 * HIT objects have an expire() method which is preferable to use
 * @package amt_rest_api
 * @subpackage requests
 */
class expire_hit_request extends simple_hit_request {
  /**
   * Prefer calling expire() on a HIT object
   * @param string $hit_id HITId
   */
  public function __construct($hit_id) {
    parent::__construct('ForceExpireHIT', $hit_id);
  }
}

/**
 * extend the HIT
 * @package amt_rest_api
 * @subpackage requests
 */
class extend_hit_request extends simple_hit_request {
  /**
   * Protected ctor
   * @param string $hit_id HITId
   * @param string $value_to_increment
   * @param int $incr +ve integer in seconds
   * @throws InvalidArgumentException if $incr < 1
   */
  protected function __construct($hit_id, $value_to_increment, $incr) {
    if (!is_int($incr) || $incr < 1)
      throw new \InvalidArgumentException("$value_to_increment must be +ve integer");
    parent::__construct('ExtendHIT', $hit_id);
    $this->add($value_to_increment, $incr);
  }
}

/**
 * extend the HIT's max. assignments
 *
 * use amt\hit::extend_assignments($extra_workers)
 * @package amt_rest_api
 * @subpackage requests
 */
class extend_hit_asst_request extends extend_hit_request {
  /**
   * Prefer calling amt\hit::extend_assignments()
   * @param string $hit_id HITId
   * @param int $extra_assignments +ve integer in seconds
   * @throws InvalidArgumentException if $extra_assignments < 1
   */
  public function __construct($hit_id, $extra_assignments) {
    parent::__construct($hit_id, 'MaxAssignmentsIncrement', $extra_assignments);
  }
}

/**
 * extend the HIT's lifetime
 *
 * use amt\hit::extend_expiry($extra_seconds)
 * @package amt_rest_api
 * @subpackage requests
 */
class extend_hit_expiry_request extends extend_hit_request {
  /**
   * Prefer calling amt\hit::extend_expiry()
   * @param string $hit_id HITId
   * @param int $extra_seconds +ve integer in seconds
   * @throws InvalidArgumentException if $extra_seconds < 1
   */
  public function __construct($hit_id, $extra_seconds) {
    parent::__construct($hit_id, 'ExpirationIncrementInSeconds', $extra_seconds);
  }
}

/**
 * Change the HIT's HITType
 *
 * call statically by doing amt\change_hittype_for_hit_request::execute()
 * @package amt_rest_api
 * @subpackage requests
 * @api ChangeHITTypeOfHIT
 * @link manual.html#ChangeHITTypeOfHIT
 */
class change_hittype_for_hit_request extends request {
  /**
   * Protected; invoke by doing amt\change_hittype_for_hit_request::execute()
   * @param string $hit_id HITId
   * @param string $hittype_id HITTypeId
   */
  protected function __construct($hit_id, $hittype_id) {
    parent::__construct('ChangeHITTypeOfHIT');
    $this->add('HITId', $hit_id);
    $this->add('HITTypeId', $hittype_id);
  }
  /**
   * Execute the query
   * @param string $hit_id HITId
   * @param string $hittype_id HITTypeId
   */
  public static function execute($hit_id, $hittype_id) {
    response::acquire_from(new self($hit_id, $hittype_id));
  }
}

/**
 * get HIT details for single HIT
 *
 * You need this only if you are looking for specific HIT response data.
 * By default you have HITDetail and HITAssignmentSummary information.
 * If this is what you want, you can construct an amt\hit directly from the
 * HITId. Otherwise, do something like this:
 *  $rq = new amt\get_hit_request($hit_id);
 *  $rq->add_response_group('HITQuestion');
 *  $rq->remove_response_group('HitAssignmentSummary');
 *  $hit = new amt\hit($rq);
 * @package amt_rest_api
 * @subpackage requests
 * @api GetHIT
 * @link manual.html#GetHIT
 */
class get_hit_request extends simple_hit_request {
  /**
   * ResponseGroups selected
   * @var string[] $rgs
   */
  private $rgs = array('HITDetail', 'HITAssignmentSummary');
  /**
   * HITId
   * @var string $my_id
   */
  private $my_id;
  /**
   * Store whether default response includes HITType
   * @var boolean $minimal_contained TRUE if default response includes HITType
   */
  private $minimal_contained;

  /**
   * Create request object
   * @param string $hit_id HITId
   */
  public function __construct($hit_id) {
    parent::__construct('GetHIT', $this->my_id = $hit_id);
  }
  /**
   * Throw exception if specified response group is invalid
   * @param string $rg response group HITDetail/HITAssignmentSummary/HITQuestion/Minimal/Request
   * @throws InvalidArgumentException if $rg invalid
   */
  private static function check_response_group($rg) {
    if (!\in_array($rg, array('HITDetail', 'HITAssignmentSummary', 'HITQuestion', 'Minimal', 'Request')))
      throw new \InvalidArgumentException("$rg is an invalid response group.");
  }
  /**
   * Set desired response groups
   * @param string[] $rg array of response group specifiers
   * @throws InvalidArgumentException if $rg invalid
   */
  public function set_response_groups(array $rg) {
    foreach ($rg as $r) self::check_response_group($r);
    $this->rgs = $rg;
  }
  /**
   * Add a response group
   * @param string $rg HITDetail/HITAssignmentSummary/HITQuestion/Minimal/Request
   * @throws InvalidArgumentException if $rg invalid
   */
  public function add_response_group($rg) {
    self::check_response_group($rg);
    if (!\in_array($rg, $this->rgs)) $this->rgs[] = $rg;
  }
  /**
   * Remove a response group
   * @param string $rg HITDetail/HITAssignmentSummary/HITQuestion/Minimal/Request
   */
  public function remove_response_group($rg) {
    if (($pos = \array_find($this->rgs, $rg)) !== FALSE) unset($this->rgs[$pos]);
  }
  /**
   * Convert to string
   * @return string concatenated request parameter/value pairs
   */
  public function __toString() {
    $retval = parent::__toString();
    $this->minimal_contained = FALSE;
    $rgno = 0;
    foreach ($this->rgs as $rg) {
      $retval .= parent::restparam("ResponseGroup.$rgno", $rg);
      if ($rg === 'Minimal') $this->minimal_contained = TRUE;
      ++$rgno;
    }
    return $retval;
  }
  /**
   * Execute the query and return the HIT
   * @return amt\hit
   * @throws amt\Exception
   */
  public function execute() {
    $result = response::acquire_from($this, 'HIT');
    $retval = new hit($result->HIT);
    if (!$this->minimal_contained) $retval['HITId'] = $this->my_id;
    return $retval;
  }
}

/**
 * Evaluate assignment request
 *
 * Use amt\assignment::approve() or reject() in preference to this object
 * @package amt_rest_api
 * @subpackage requests
 */
class eval_assignment_request extends request {
  /**
   * Prefer using amt\assignment::approve() or reject()
   * @param string $operation ApproveAssignment/RejectAssignment
   * @param string $asst_id AssignmentId
   * @param string $feedback optional feedback to worker (max 1024 chars)
   * @throws InvalidArgumentException if $operation invalid
   * @throws LengthException if feedback string too long
   */
  public function __construct($operation, $asst_id, $feedback = NULL) {
    if (!\in_array($operation, array('ApproveAssignment', 'RejectAssignment')))
      throw new \InvalidArgumentException("$operation is illegal");
    parent::__construct($operation);
    $this->add('AssignmentId', $asst_id);
    if ($feedback) {
      if (\strlen($feedback > 1024))
        throw new \LengthException('Feedback string over 1024 chars');
      $this->add('RequesterFeedback', $feedback);
    }
  }
}

/**
 * Internal use, just simplifies the ctor
 * @package amt_rest_api
 * @subpackage requests
 */
abstract class generic_hits_request extends paging_request {
  /**
   * Protected ctor
   * @param string $operation the AMT function
   */
  protected function __construct($operation) {
    parent::__construct($operation, parent::$paging_fields);
  }
  /**
   * Does nothing
   */
  public function check_params() {}
  /**
   * This is used by the set_sort function to check arguments.
   */
  protected function valid_sort_fields() {
    return array('CreationTime', 'Title', 'Reward', 'Expiration', 'Enumeration');
  }
}

/**
 * SearchHITs
 *
 * Just use hitlist unless you need to modify the sorting behaviour
 * via the amt\paging_request ancestor
 * @package amt_rest_api
 * @subpackage requests
 * @api SearchHITs
 * @link manual.html#SearchHITs
 */
class search_hits_request extends generic_hits_request {
  /**
   * just call parent ctor with operation SearchHITs
  */
  public function __construct() {
    parent::__construct('SearchHITs');
  }
  /**
   * Execute the query and return hitlist
   * @return hitlist iterable collection of amt\hit
   */
  public function execute() {
    return new hitlist($this);
  }
}

/**
 * GetReviewableHITs
 * @package amt_rest_api
 * @subpackage requests
 * @api GetReviewableHITs
 * @link manual.html/GetReviewableHITs
 */
class reviewable_hits_request extends generic_hits_request {
  /**
   * just call parent ctor with operation GetReviewableHITs
   * @param string $status Reviewable/Reviewing/Both
  */
  public function __construct($status = 'Reviewable') {
    parent::__construct('GetReviewableHITs');
    switch ($status) {
      case 'Reviewable' : break;
      case 'Reviewing' :
        $this->add_param('Status', $status);
      break;
      case 'Both' :
        $this->add_param('Status.1', 'Reviewing');
        $this->add_param('Status.2', 'Reviewable');
      //break;
    }
  }
  /**
   * Execute the query and return reviewable_hitlist
   * @return hitlist iterable collection of amt\minimal_hit
   */
  public function execute() {
    return new reviewable_hitlist($this);
  }
}

/**
 * GetBonusPayments
 * @package amt_rest_api
 * @subpackage requests
 * @api GetBonusPayments
 * @link manual.html#GetBonusPayments
 */
class get_bonus_request extends generic_hits_request {
  /**
   * Generate request object
   * @param string $asst_or_hit_id
   * @param boolean $for_assignment TRUE = for assignment, FALSE = for HIT
   */
  public function __construct($asst_or_hit_id, $for_assignment = FALSE) {
    parent::__construct('GetBonusPayments');
    $idtype = $for_assignment ? 'AssignmentId' : 'HITId';
    $this->add_param($idtype, $asst_or_hit_id);
  }
  /**
   * Illegal for this class
   * @throws BadMethodCallException if called
  */
  protected function valid_sort_fields() {
    throw new \BadMethodCallException('Sorry, sorting not available');
  }
  /**
   * Execute the query and return bonus_payments
   * @return amt\bonus_payments
   */
  public function execute() {
    return new bonus_payments($this);
  }
}

/**
 * GetFileUploadURL
 *
 * Ask AMT for the temporary file upload URL
 * @package amt_rest_api
 * @subpackage requests
 * @api GetFileUploadURL
 * @link manual.html#GetFileUploadURL
 */
class file_upload_url_request extends request {
  /**
   * generate request object
   * @param string asst_id AssignmentId
   * @param string $question_name QuestionIdentifier
   */
  protected function __construct($asst_id, $question_name) {
    parent::__construct('GetFileUploadURL');
    $this->add('AssignmentId', $asst_id);
    $this->add('QuestionIdentifier', $question_name);
  }
  /**
   * Execute the query
   * @param string $asst_id AssignmentId
   * @param string $question_name name of the question field
   * @return string FileUploadURL
   * @throws amtException
   */
  public static function execute($asst_id, $question_name) {
    $r = response::acquire_from(new self($asst_id, $question_name));
    return (string)$r->GetFileUploadURLResult->FileUploadURL;
  }
}

/**
 * Block Worker
 *
 * Use like this:
 *  amt\block_worker_request::execute($worker_id, 'Stupid answers');
 * @package amt_rest_api
 * @subpackage requests
 * @api BlockWorker
 * @link manual.html#BlockWorker
 */
class block_worker_request extends request {
  /*
   * Protected: use amt\block_worker_request::execute()
   * @param string $worker_id WorkerId
   * @param string $reason documentary feedback
   */
  protected function __construct($worker_id, $reason) {
    parent::__construct('BlockWorker');
    $this->add('WorkerId', $worker_id);
    $this->add('Reason', $reason);
  }
  /**
   * Execute the query
   * @param string $worker_id WorkerId
   * @param string $reason documentaryfeedback
   */
  public static function execute($worker_id, $reason) {
    response::acquire_from(new self($worker_id, $reason));
  }
}

/**
 * Unblock Worker
 *
 * Use like this:
 *            amt\unblock_worker_request::execute($worker_id, 'Second chance');
 * @package amt_rest_api
 * @subpackage requests
 * @api UnblockWorker
 * @link manual.html#UnblockWorker
 */
class unblock_worker_request extends request {
  /**
   * Protected: use amt\unblock_worker_request::execute()
   * @param string $worker_id WorkerId
   * @param string $reason optional documentary reason
   */
  protected function __construct($worker_id, $reason) {
    parent::__construct('UnblockWorker');
    $this->add('WorkerId', $worker_id);
    if ($reason) $this->add('Reason', $reason);
  }
  /**
   * Execute the query
   * @param string $worker_id WorkerId
   * @param string $reason optional documentary reason
   */
  public static function execute($worker_id, $reason = NULL) {
    response::acquire_from(new self($worker_id, $reason));
  }
}

/**
 * Notify Workers
 *
 * Use like this:
 *        amt\notify_workers_request::execute('Thank you for your support!', $worker_ids);
 * @package amt_rest_api
 * @subpackage requests
 * @api NotifyWorkers
 * @link manual.html#NotifyWorkers
 */
class notify_workers_request extends request {
  /**
   * Protected: use amt\notify_workers_request::execute()
   * @param string $message string <= 2000 chars
   * @param string[] $worker_ids array of WorkerIds
   * @throws LengthException if $message too long
   * @throws InvalidArgumentException if $worker_ids empty
   */
  protected function __construct($message, array $worker_ids) {
    if (\strlen($message) > 2000)
      throw new \LengthException('Message cannot exceed 2000 characters');
    if (($nnotifications = count($worker_ids)) < 1)
      throw new \InvalidArgumentException('zero worker IDs supplied');
    parent::__construct('NotifyWorkers');
    $this->add('MessageText', $message);
    if ($nnotifications === 1)
      $this->add('WorkerId', $worker_ids[0]);
    else {
      $index = 0;
      foreach ($worker_ids as $wid) $this->add('WorkerId.' . ++$index, $wid);
    }
  }
  /**
   * Execute the query
   * @param string $message string <= 2000 chars
   * @param string[] $worker_ids array of WorkerIds
   * @throws LengthException if $message too long
   * @throws InvalidArgumentException if $worker_ids empty
   * @throws amtException
   */
  static public function execute($message, array $worker_ids) {
    response::acquire_from(new self($message, $worker_ids));
  }
}

/**
 * GrantBonus
 *
 * Use like this:
 *         amt\grant_bonus_request::execute($worker_id, $asst_id, 0.50, 'Good work!');
 * @package amt_rest_api
 * @subpackage requests
 * @api GrantBonus
 * @link manual.html#GrantBonus
 */
class grant_bonus_request extends request {
  /**
   * Prefer amt\grant_bonus_request::execute()
   * @param string $worker_id WorkerId
   * @param string $asst AssignmentId
   * @param float $amount amount in USD
   * @param string $reason message <= 2000 chars
   * @throws LengthException if $reason over-long
   */
  public function __construct($worker_id, $asst, $amount, $reason) {
    if (\strlen($reason) > 2000)
      throw new \LengthException('Reason cannot exceed 2000 characters');
    parent::__construct('GrantBonus');
    $this->add('WorkerId', $worker_id);
    $this->add('AssignmentId', $asst);
    $this->add('Amount.1.CurrencyCode', 'USD');
    $this->add('Amount.1.Value', $amount);
    $this->add('Reason', $reason);
  }
  /**
   * Execute the query
   * @param string $worker_id the WorkerId
   * @param string $asst the AssignmentId
   * @param float $amount amount to grant
   * @param string $reason Notification to worker (mandatory)
   */
  public static function execute($worker_id, $asst, $amount, $reason) {
    response::acquire_from(new self($worker_id, $asst, $amount, $reason));
  }
}

/*
  AMT Response types
*/
/**
 * AMT Exception - provides useful debugging extensions
 * @package amt_rest_api
 * @subpackage response_objects
 * @api
 */
class Exception extends \RuntimeException {
  /**
   * The XML returned by the AMT service
   * @var amt\response $xml
   */
  private $xml;
  /**
   * Create exception object
   * @param string $msg Exception message
   * @param amt\response $x the XML returned by the AMT service
   */
  public function __construct($msg, response $x) {
    $this->xml = $x;
    parent::__construct($msg, E_USER_ERROR);
  }
  /**
   * Output the XML to the console
   */
  public function dump() { $this->xml->dump(); }
  /**
   * Save the XML in a file
   * @param string $fn Filename to use (defaults to amt_response.xml)
   */
  public function dump_to_file($fn = NULL) { $this->xml->dump_to_file($fn); }
  /**
   * The XML data as a string
   * @return string
   */
  public function xmldata() { return $this->xml->asXML(); }
}

/**
 * amt\response is the only true response class. It handles error checking.
 *
 * It does not function as a base class and it doesn't really have a ctor:
 * this is because of the constraints of deriving from SimpleXMLElement.
 * Other response objects simply use this class to do their dirty work.
 * Many request objects employ it for their execute() functionality.
 * @package amt_rest_api
 * @subpackage responses
 */
final class response extends \SimpleXMLElement {
  /**
   * Print errors to console
   * @param amt\response $basenode XML to scan for Error elements
   */
  public function print_errors(response $basenode = NULL) {
    if (empty($basenode)) $basenode = $this->OperationRequest;
    echo "There was an error processing your request:\n";
    foreach ($basenode->Errors->Error as $error)
      echo "  Error code:    $error->Code\n  Error message: $error->Message\n";
  }
  /**
   * Print errors to console if any, else do nothing
   * @param amt\response $basenode XML to scan for Error elements
   * @return boolean TRUE if there were errors printed
   */
  public function print_any_errors(response $basenode = NULL) {
    $retval = FALSE;
    if (empty($basenode)) $basenode = $this->OperationRequest;
    if ($basenode->Errors) {
      $this->print_errors($basenode);
      $retval = TRUE;
    }
    return $retval;
  }
  /**
   * Check response for errors
   * @throws amtException if any found
   */
  public function checkerror() {
    if ($this->Errors) {
      $err = $this->Errors;
      if ($err->count() === 1) {
        $err = $err->Error;
        throw new Exception("$err->Code: $err->Message", $this);
      }
      else {
        $emsg = '';
        foreach ($err->children() as $error)
          $emsg .= "Error $error->Code: $error->Message\n";
        throw new Exception($emsg, $this);
      }
    }
  }
  /**
   * Catch stream error via notification callback
   * @param int $notification_code
   * @param int $severity (STREAM_NOTIFY_SEVERITY_ERR == 2)
   * @param string $message
   * @param int $message_code the HTTP status
   * @param int $bytes_transferred N/A
   * @param int $bytes_max N/A
   * @throws RuntimeException on failure
   */
  public static function stream_error($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
    if ($notification_code === STREAM_NOTIFY_FAILURE)
      throw new \RuntimeException($message, $message_code);
  }
  /**
   * This is the "constructor"
   *
   * This is what sends the request to the AMT service.
   * @param amt\request $rq The request to send to the AMT service
   * @param string $resultfield defaults to OperationResult, but might be e.g. HIT
   * @throws RuntimeException if no response or if bad XML received.
   * @throws amtException if the response indicated errors
   * @return amt\response the response as a simpleXML object
   */
  public static function acquire_from(request $rq, $resultfield = NULL) {
    $url = (string)$rq;
    $httpopts = array('timeout' => 10.0); // allow 10 seconds for response
    if (\strlen($url) > 8192) {
      $httpopts['method'] = 'POST';
      $httpopts['header'] = 'Content-type: application/x-www-form-urlencoded';
      $a = \explode('?', $url);
      $url = $a[0]; // strip params off the URL
      $httpopts['content'] = $a[1];
    }
    $retval = @\file_get_contents($url, FALSE,
      \stream_context_create(
        array('http' => $httpopts),
        array('notification' => array(__NAMESPACE__ . '\\' . __CLASS__, 'stream_error'))
      )
    );
    if ($retval === FALSE) throw new \RuntimeException('Failed to load from URL supplied');
    $retval = \simplexml_load_string($retval, __CLASS__);
    if ($retval === FALSE) throw new \RuntimeException('Failed to parse XML response');
    $retval->OperationRequest->checkerror();
    if ($resultfield === NULL)
      $resultfield = $rq->get_op() . 'Result';
    if (!$retval->$resultfield)
      throw new amtException("Result field $resultfield not found.", $retval);
    $retval->$resultfield->Request->checkerror();
    return $retval;
  }
  /**
   * Dump XML to stdout
   */
  public function dump() {
    echo $this->asXML();
  }
  /**
   * Dump XML to file
   * @param string $fn default amt_response.xml
   */
  public function dump_to_file($fn = 'amt_response.xml') {
    $this->asXML($fn);
  }
  /**
   * Hack to check for existence of possibly absent node
   *
   * A feature unaccountably absent from simpleXMLElement
   * @return boolean FALSE iff this is a non-existent node
   */
  public function exists() {
    return $this->getName() !== '';
  }
}

/**
 * assignment data structure
 * @package amt_rest_api
 * @subpackage response_objects
 * @uses amt\grant_bonus_request in grant_bonus()
 * @api GetAssignmentsForHIT
 * @link manual.html#GetAssignmentsForHIT
 */
class assignment extends \ArrayObject {
  /**
   * AssignmentId
   * @var string $id
   */
  public $id;
  /**
   * the UNIX accept time
   * @var int $accept_time
   */
  public $accept_time;
  /**
   * the UNIX submit time
   * @var int $submit_time
   */
  public $submit_time;

  /**
   * Create assignment object
   * @param amt\response $r the response XML
   * @throws RuntimeException with Answer XML in the message if parse error
   */
  public function __construct(response $r) {
    $this->id = $this['AssignmentId'] = (string)$r->AssignmentId;
    $this['WorkerId'] = (string)$r->WorkerId;
    $this['AssignmentStatus'] = (string)$r->AssignmentStatus;
    $this->accept_time = \strtotime($this['AcceptTime'] = (string)$r->AcceptTime);
    $this->submit_time = \strtotime($this['SubmitTime'] = (string)$r->SubmitTime);
    if ($r->Answer->exists()) {
      $xml = \htmlspecialchars_decode($r->Answer);
      $ans = \simplexml_load_string($xml, __NAMESPACE__ . '\\' . 'response');
      if (!$ans)
        throw new \RuntimeException("Failed to parse Answer\n$xml", E_USER_ERROR);
      foreach ($ans->Answer as $ca) {
        $cav = '';
        if ($ca->FreeText->exists())
          $cav = (string)$ca->FreeText;
        else if ($ca->SelectionIdentifier->exists()) {
          $cav = (string)$ca->SelectionIdentifier;
          if ($ca->OtherSelectionText->exists())
            $cav = array((string)$cav, (string)$ca->OtherSelectionText);
        }
        else if ($ca->UploadedFileKey->exists())
          $cav = array('filekey' => (string)$ca->UploadedFileKey, 'size' => (string)$ca->UploadedFileSizeInBytes);
        $this->offsetSet((string)$ca->QuestionIdentifier, $cav);
      }
    }
  }
  /**
   * approve/reject assignment
   * @uses amt\eval_assignment_request
   * @param string $operation ApproveAssignment / RejectAssignment
   * @param string $feedback Worker feedback
   */
  private function evaluate($operation, $feedback) {
    $r = new eval_assignment_request($operation, $this->id, $feedback);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Approve assignment
   * @param string $feedback optional
   * @throws BadMethodCallException if reject() already called on this object
   */
  public function approve($feedback = NULL) {
    switch ($this['AssignmentStatus']) {
    case 'Rejected' :
      throw new \BadMethodCallException('Assignment already rejected!');
    case 'Submitted':
      $this->evaluate('ApproveAssignment', $feedback);
    case 'Approved' : // do nothing
      break;
    }
  }
  /**
   * Obtain URL for download of worker's uploaded file
   * @param string $fieldname the name of the answer field
   * @throws InvalidArgumentException if field name not a file field
   * @return string the URL - you have 60 seconds to use it.
   */
  public function get_upload_url($fieldname) {
    if (!$this->offsetExists($fieldname) || !is_array($this[$fieldname]))
      throw new \InvalidArgumentException("$fieldname is not a file upload field.");
    return file_upload_url_request::execute($this->id, $fieldname);
  }
  /**
   * Reject assignment
   * @param string $feedback required feedback
   * @throws BadMethodCallException if approve() already called on this object
   */
  public function reject($feedback) {
    switch ($this['AssignmentStatus']) {
    case 'Approved' :
      throw new \BadMethodCallException('Assignment already approved!');
    case 'Submitted':
      $this->evaluate('RejectAssignment', $feedback);
    case 'Rejected' : // already done, so NOP
      break;
    }
  }
  /**
   * Grant bonus specifying amount and reason
   * @param float $amount the amount of the bonus in USD
   * @param string $reason reason for giving the bonus
   */
  public function grant_bonus($amount, $reason) {
    grant_bonus_request::execute($this['WorkerId'], $this->id, $amount, $reason);
  }
  /**
   * Block worker
   * @api BlockWorker
   * @param string $reason write-only reason! Only Amazon can access this
   * @throws amtException if, e.g. the worker doesn't exist
   * @link manual.html#BlockWorker
   */
  public function block_worker($reason) {
    block_worker_request::execute($this['WorkerId'], $reason);
  }
  /**
   * Unblock worker
   * @api UnblockWorker
   * @link manual.html#UnblockWorker
   * @param string $reason optional reason, again accessible only to Amazon
   * @throws amtException if, e.g. the worker doesn't exist
   */
  public function unblock_worker($reason = NULL) {
    unblock_worker_request::execute($this['WorkerId'], $reason);
  }
}

/**
 * Functionality in common with amt\hit, amt\hit_details and amt\minimal_hit
 * @api DisposeHIT,DisableHIT,ForceExpireHIT
 * @package amt_rest_api
 * @subpackage response_objects
 */
interface hit_i {
  /**
   * Dispose the HIT
   * @throws amtException
   * @api DisposeHIT
   * @link manual.html#DisposeHIT
   */
  public function dispose();
  /**
   * Disable the HIT
   * @throws amtException
   * @api DisableHIT
   * @link manual.html#DisableHIT
   */
  public function disable();
  /**
   * Expire the HIT
   * @throws amtException
   * @api ForceExpireHIT
   * @link manual.html#ForceExpireHIT
   */
  public function expire();
  /**
   * Change HITType
   * @throws amtException
   * @param string $new_hittype_id
   * @api ChangeHITTypeOfHIT
   * @link manual.html#ChangeHITTypeOfHIT
   */
  public function change_hittype($new_hittype_id);
  /**
   * Switch status Reviewing/Reviewable
   * @param boolean $reviewing TRUE for Reviewing
   * @throws amtException if not Reviewable/Reviewing
   * @api SetHITAsReviewing
   * @link manual.html#SetHITAsReviewing
   */
  public function set_reviewing($reviewing = TRUE);
}

/**
 * data structure for individual HIT
 * NB not constructed from the raw amt\response but from its HIT member
 * @package amt_rest_api
 * @subpackage response_objects
 * @api GetHIT
 * @link manual.html#GetHIT Example
 */
class hit extends \ArrayObject {
  /**
   * create HIT object
   * @param amt\response $r the HIT member of the raw response
   */
  public function __construct(response $r) {
    $fields = array( // some are omitted as derivable from HITType
      'HITId', 'HITTypeId', 'HITStatus', 'Expiration', 'MaxAssignments',
      'Title', 'Description', 'CreationTime', 'Reward', 'Keywords',
      'RequesterAnnotation', 'NumberOfSimilarHITs', 'NumberOfAssignmentsPending',
      'NumberOfAssignmentsAvailable', 'NumberOfAssignmentsCompleted',
      'HITReviewStatus', 'Question', 'LifetimeInSeconds',
      'AssignmentDurationInSeconds', 'AutoApprovalDelayInSeconds'
    );
    $setvals = array();
    foreach ($fields as $f) {
      if ($r->$f->exists()) switch ($f) {
        // case 'CreationTime':
        // case 'Expiration' : $setvals[$f] = \strtotime((string)($r->$f)); break;
        case 'NumberOfSimilarHITs' :
        case 'NumberOfAssignmentsPending':
        case 'NumberOfAssignmentsAvailable':
        case 'NumberOfAssignmentsCompleted':
        case 'LifetimeInSeconds':
        case 'AssignmentDurationInSeconds':
        case 'MaxAssignments':
        case 'AutoApprovalDelayInSeconds':
          $setvals[$f] = (int)($r->$f);
        break;
        case 'Reward':
          $setvals[$f] = (string)($r->Reward->FormattedPrice);
        break;
        case 'Question':
          $setvals[$f] = \htmlspecialchars_decode((string)$r->Question);
        break;
        default:
          $setvals[$f] = (string)($r->$f);
        //break;
      }
    }
    if ($r->QualificationRequirement->exists()) {
      $setvals['QualificationRequirement'] = array();
      foreach ($r->QualificationRequirement as $qual)
        $setvals['QualificationRequirement'][] = $qual;
    }
    parent::__construct($setvals, \ArrayObject::ARRAY_AS_PROPS);
  }
  /**
   * Dispose the HIT
   * @throws amtException
   */
  public function dispose() {
    $r = new dispose_hit_request($this['HITId']);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Disable the HIT
   * @throws amtException
   */
  public function disable() {
    $r = new disable_hit_request($this['HITId']);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Expire the HIT
   * @throws amtException
   */
  public function expire() {
    $r = new expire_hit_request($this['HITId']);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Extend the HIT's max. assignments
   * @param int $extra_assignments the no. of assignments to add
   * @throws amtException
   */
  public function extend_assignments($extra_assignments) {
    $r = new extend_hit_asst_request($this['HITId'], $extra_assignments);
    response::acquire_from($r);
  }
  /**
   * Extend the HIT's lifetime
   * @param int $extra_seconds the no. of extra seconds to add
   * @throws amtException
   */
  public function extend_expiry($extra_seconds) {
    $r = new extend_hit_expiry_request($this['HITId'], $extra_seconds);
    response::acquire_from($r);
  }
  /**
   * Set different HIT Type
   * @param string $new_hittype_id the new HITTypeId to use
   * @throws amtException if invalid HITTypeId
  */
  public function change_hittype($new_hittype_id) {
    change_hittype_for_hit_request::execute($this['HITId'], $new_hittype_id);
    $this['HITTypeId'] = $new_hittype_id;
  }
  /**
   * Set HIT status to reviewing / reviewable
   * @param boolean $reviewing default TRUE, set FALSE to return to reviewable
   * @throws amtException
   */
  public function set_reviewing($reviewing = TRUE) {
    $r = new reviewing_hit_request($this['HITId'], $reviewing);
    response::acquire_from($r);
  }
  /**
   * Dump properties to stdout
   */
  public function dump() {
    foreach ($this as $prop => $v) echo "$prop: $v\n";
  }
}

/**
 * amt\hit_details is used to get more data about a hit
 * Can be constructed from a HITId, an amt\minimal_hit or an amt\get_hit_request
 * @package amt_rest_api
 * @subpackage response_objects
 * @api GetHIT
 * @link manual.html#GetHIT
 */
class hit_details extends hit {
  /**
   * Create HIT details object
   * @param mixed $hit_id can be either a string (HITId) or amt\minimal_hit
   *  or even amt\get_hit_request
   * @throws InvalidArgumentException if foxed by the type of $hit_id
   */
  public function __construct($hit_id) {
    if ($hit_id instanceof minimal_hit) $hit_id = (string)$hit_id;
    if (\is_string($hit_id))
      $rq = new get_hit_request($hit_id);
    else if ($hit_id instanceof get_hit_request)
      $rq = $hit_id;
    else
      throw new \InvalidArgumentException("Don't know how to handle ctor arg");
    $response = response::acquire_from($rq, 'HIT');
    parent::__construct($response->HIT);
    $this['HITId'] = $hit_id;
  }
}

/**
 * amt\minimal_hit is used to facilitate functionality based on just a HITId
 * @package amt_rest_api
 * @subpackage response_objects
 * @api GetReviewableHITs
 * @link manual.html#GetReviewableHITs
 */
class minimal_hit implements hit_i {
  /**
   * The ID of the HIT
   * @var string $HITId
   */
  public $HITId;

  /**
   * Create minimal_hit
   * @param string $hit_id the HITId
   */
  public function __construct($hit_id) {
    $this->HITId = $hit_id;
  }
  /**
   * Dispose the HIT
   * @throws amtException
   */
  public function dispose() {
    $r = new dispose_hit_request($this->HITId);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Disable the HIT
   * @throws amtException
   */
  public function disable() {
    $r = new disable_hit_request($this->HITId);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Expire the HIT
   * @throws amtException
   */
  public function expire() {
    $r = new expire_hit_request($this->HITId);
    response::acquire_from($r); // throw away result!
  }
  /**
   * Change HIT type
   * @param string $new_hittype_id the new HITTypeId to use
   * @throws amtException if invalid HITTypeId
  */
  public function change_hittype($new_hittype_id) {
    change_hittype_for_hit_request::execute($this->HITId, $new_hittype_id);
  }
  /**
   * change HIT status to reviewing/reviewable
   * @param boolean $reviewing default TRUE, set FALSE to set reviewable
   * @throws amtException if already in target state or incorrect state
   */
  public function set_reviewing($reviewing = TRUE) {
    $r = new reviewing_hit_request($this->HITId, $reviewing);
    response::acquire_from($r);
  }
  /**
   * get HIT results
   * @return amt\results
   * @throws amtException
   */
  public function results() {
    return new results($this->HITId);
  }
  /**
   * String conversion returns HITId
   * @return string the HITId
   */
  public function __toString() { return $this->HITId; }
}

/**
 * paged response interface
 *
 * Base class for concrete paged responses
 * Used by amt\pager, the paging iterator, to provide a pageless iteration
 * @package amt_rest_api
 * @subpackage responses
 */
abstract class paged_response {
  /**
   * No. of results
   * @var int $total_results No. of results not just on this page - may vary
   */
  private $total_results;
  /**
   * Page sequence no.
   * @var int $pageno the number of this page
   */
  private $pageno;

  /**
   * Base constructor
   * @param int $total no. of results
   * @param int $page_no no. of this page 1..n
   */
  public function __construct($total, $page_no) {
    $this->total_results = (int)$total;
    $this->pageno = (int)$page_no;
  }
  /**
   * get total
   * @return int total no. of results
   */
  public function get_total() { return $this->total_results; }
  /**
   * Number of next page
   * @return int number of the next page
   */
  public function nextpage() { return $this->pageno + 1; }
  /**
   * must return a Traversable
   */
  abstract public function answers();
}

/**
 * single page returned by GetAssignmentsForHit
 *
 * used (internally) by amt\results
 * @package amt_rest_api
 * @subpackage responses
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class gas_response extends paged_response {
  /**
   * Array of assignments paged over
   * @var amt\assignment[] $assignments
   */
  private $assignments = array();

  /**
   * Create page object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->GetAssignmentsForHITResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->assignments[] = new assignment($r->Assignment[$i]);
  }
  /**
   * Return assignments
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->assignments); }
}

/**
 * single page returned by SearchHITs, GetHITsForQualificationType
 * @uses amt\hit
 * @package amt_rest_api
 * @subpackage responses
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class hitsearch_response extends paged_response {
  /**
   * Array of hits paged over
   * @var amt\hit[]
   */
  private $hits = array();

  /**
   * Create page object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $resultfield = $rq->get_op() . 'Result';
    $r = $r->$resultfield;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->hits[] = new hit($r->HIT[$i]);
  }
  /**
   * Return HITs
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->hits); }
}

/**
 * single page returned by GetReviewableHITs
 * @uses amt\minimal_hit
 * @package amt_rest_api
 * @subpackage responses
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class reviewable_hit_response extends paged_response {
  /**
   * Array of hits paged over
   * @var amt\minimal_hit[]
   */
  private $hits = array();

  /**
   * Create response object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->GetReviewableHITsResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->hits[] = new minimal_hit((string)($r->HIT[$i]->HITId));
  }
  /**
   * Return HITs
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->hits); }
}

/**
 * Bonus Payment
 * @package amt_rest_api
 * @subpackage response_objects
 * @property string $WorkerId
 * @property string $AssignmentId
 * @property string $Reason given when awarded
 * @property string $GrantTime
 * @property float $BonusAmount USD implied
 * @property int $grant_time the UNIX-style timestamp from GrantTime
 */
class bonus extends \ArrayObject {
  /**
   * Create response object
   * @param amt\response $r XML data from AMT
   */
  public function __construct(response $r) {
    $fields = array('WorkerId', 'BonusAmount', 'AssignmentId', 'Reason', 'GrantTime');
    foreach ($fields as $k) {
      if ($r->$k->exists()) switch ($k) {
        case 'BonusAmount' :
          $props[$k] = (float)($r->BonusAmount->Amount);
          break;
        case 'GrantTime' :
          $props['grant_time'] = \strtotime((string)($r->GrantTime));
          // no break
        default:
          $props[$k] = (string)($r->$k);
      }
    }
    parent::__construct($props, \ArrayObject::ARRAY_AS_PROPS);
  }
}

/**
 * single page returned by GetBonusPayments
 * @uses amt\bonus
 * @package amt_rest_api
 * @subpackage responses
 * @internal
 */
class bonus_response extends paged_response {
  /**
   * Array of bonus details paged over
   * @var amt\bonus[]
   */
  private $bonuses = array();
  /**
   * create response object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->GetBonusPaymentsResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->hits[] = new bonus((string)($r->BonusPayment[$i]));
  }
  /**
   * return bonuses
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->bonuses); }
}

/**
 * Paged response iterator
 *
 * Hides details of paged responses and provides a simple Iterator
 * @package amt_rest_api
 * @subpackage responses
 * @internal
 */
abstract class pager implements \Countable, \Iterator {
  /**
   * the cached request object, reused for each page
   * @var amt\paging_request $rq the cached request object, reused for each page
   */
  private $rq;
  /**
   * the number of elements in the entire collection
   * @var int $n_rows
   */
  private $n_rows;
  /**
   * The current page object
   * @var amt\paged_response $gas the current page object
   */
  private $gas;
  /**
   * Current iterator position index over entire collection
   * @var int $index 0..n current iterator position index over entire collection
   * @internal set to 1 to force initial rewind
   */
  private $index = 1;
  /**
   * index in current page
   * @var int $gas_i index in current page object
   */
  private $gas_i;
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $request
   */
  abstract protected function paged_response(paging_request $request);
  /**
   * Implement Iterator
   */
  public function rewind() {
    if ($this->index) {
      $this->index = 0;
      // we need to fetch page 1 if the next page object is uninitialized, or
      // if it's > page 1 (i.e. nextpage > 2)
      if (!is_object($this->gas) || $this->gas->nextpage() > 2) {
        $this->rq->set_pageno(1);
        $this->gas = $this->paged_response($this->rq); // instanceof amt\paged_response
        $this->n_rows = $this->gas->get_total();
        $this->gas_i = $this->gas->answers();
      }
      $this->gas_i->rewind();
    }
  }
  /**
   * Construct the iterator
   * @param amt\paging_request $request
   */
  public function __construct(paging_request $request) {
    $this->rq = $request;
    $this->rewind();
  }
  /**
   * Implement Iterator
   * @return mixed the page row element
   */
  public function current() {
    return $this->gas_i->current();
  }
  /**
   * Implement Iterator
   * @return boolean
   */
  public function valid() {
    $ret = TRUE;
    if (!$this->gas_i->valid()) {
      $ret = $this->index + 1 < $this->n_rows;
    }
    return $ret;
  }
  /**
   * Implement Iterator
   */
  public function next() {
    $this->gas_i->next();
    if ($this->gas_i->valid())
      ++$this->index;
    else if ($this->index + 1 < $this->n_rows) {
      $this->rq->set_pageno($this->gas->nextpage());
      $this->gas = $this->paged_response($this->rq);
      $this->n_rows = $this->gas->get_total(); // might have increased!
      $this->gas_i = $this->gas->answers();
      $this->gas_i->rewind();
      ++$this->index; // OK, we can increment
    }
    // else we're already done, take no action
  }
  /**
   * Implement Iterator
   * @return int current index
   */
  public function key() { return $this->index; }
  /**
   * Implement \Countable
   * @return int the total number of items on all pages
   */
  public function count() { return $this->n_rows; }
}

/**
 * Assignment results for a HIT
 *
 * Each iterated element is an amt\assignment
 * Can be generated directly from a HITId
 * @package amt_rest_api
 * @subpackage response_objects
 * @uses amt\assignment
 * @api GetAssignmentsForHIT
 * @link manual.html#GetAssignmentsForHIT
 */
class results extends pager {
  /**
   * HITId of HIT in question
   * @var string
   */
  private $hit_id;
  /**
   * Construct a results collection
   * @param mixed $rq either an amt\results_request or a string HITId
   * @throws InvalidArgumentException if $rq is neither string nor amt\results_request
   */
  public function __construct($rq) {
    if ($rq instanceof results_request)
      $this->hit_id = $rq->get_hit_id();
    else if (\is_string($rq))
      $rq = new results_request($this->hit_id = $rq);
    else
      throw new \InvalidArgumentException('Argument must be amt\results_request or string, not ' . gettype($rq));
    parent::__construct($rq);
  }
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return gas_response page data
   */
  protected function paged_response(paging_request $rq) {
    return new gas_response($rq);
  }
  /**
   * Write CSV file into path provided
   *
   * The file written will begin with $path and have HITId.csv appended.
   * This is to assist batch storage of multiple HITs to the same location.
   * @param string $path Directory or partial filename for CSV record of results
   */
  public function write_csv($path) {
    if (is_dir($path) && \substr($path, -1) !== '/')
      $path .= '/';
    $f = new \csv\writer($path . $this->hit_id . '.csv', 'w');
    foreach ($this as $row) $f->put((array)$row);
    $f->close();
  }
  /**
   * Approve all assignments
   * @param string $feedback optional feedback
   * @throws amtException
   */
  public function approve_all($feedback = NULL) {
    foreach ($this as $row) $row->approve($feedback);
  }
}

/**
 * Collection of HITs
 *
 * Acts like an array of amt\hit
 * @uses amt\hit as iterable object
 * @uses hitsearch_response as paged response
 * @package amt_rest_api
 * @subpackage response_objects
 * @api SearchHITs
 * @link manual.html#SearchHITs
 */
class hitlist extends pager {
  /**
   * Create collection of amt\hit
   * @param amt\generic_hits_request $rq may be omitted to do a SearchHITs
   */
  public function __construct(generic_hits_request $rq = NULL) {
    if ($rq === NULL) $rq = new search_hits_request;
    parent::__construct($rq);
  }
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return hitsearch_response
   */
  protected function paged_response(paging_request $rq) {
    return new hitsearch_response($rq);
  }
  /**
   * write/append HIT data to single CSV file
   *
   * csvwriter is smart enough to add headers only to a new file
   * @param string $fn Filename
   */
  public function write_csv($fn) {
    if (!class_exists('csvwriter')) require 'csvwriter.php';

    $fp = new csvwriter($fn, file_exists($fn) ? 'a' : 'w');
    foreach ($this as $row) $fp->put($this->current()->getArrayCopy());
    $fp->close();
  }
}

/**
 * Collection of reviewable HITs
 *
 * Acts like an array of amt\minimal_hit
 * @uses amt\minimal_hit as iterable element
 * @uses amt\reviewable_hit_response as paged response
 * @package amt_rest_api
 * @subpackage response_objects
 * @api GetReviewableHITs
 * @link manual.html#GetReviewableHITs
 */
class reviewable_hitlist extends pager {
  /**
   * Create collection of amt\minimal_hit
   * @param amt\reviewable_hits_request $rq may be omitted to get default
   */
  public function __construct(reviewable_hits_request $rq = NULL) {
    if ($rq === NULL) $rq = new reviewable_hits_request;
    parent::__construct($rq);
  }
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return amt\reviewable_hit_response
   */
  protected function paged_response(paging_request $rq) {
    return new reviewable_hit_response($rq);
  }
}

/**
 * Collection of Bonus Payments
 *
 * Acts like array of amt\bonus
 * @uses amt\bonus as iterable element
 * @uses amt\bonus_response as paged response
 * @package amt_rest_api
 * @subpackage response_objects
*/
class bonus_payments extends pager {
  /**
   * Create collection of amt\bonus
   * @param amt\get_bonus_request $rq
   */
  public function __construct(get_bonus_request $rq) {
    parent::__construct($rq);
  }
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return amt\bonus_response
  */
  protected function paged_response(paging_request $rq) {
    return new bonus_response($rq);
  }
}

// initialize the request class
require 'amt_keys.php';
// require 'amtapi/amt_custom_request.php';