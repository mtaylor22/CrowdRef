<?php
/**
 * AMT qualification-related classes
 * @package amt_rest_api
 * @subpackage qualifications
 * @author CPKS <cpk@smithies.org>
 * @license Public Domain
 * @version 0.1
 */
namespace amt;

/**
 * QualificationRequirement - optional param. to registerHitType
 *
 * Use derived classes amt\locale_requirement, amt\score_requirement,
 *  amt\predicate_requirement
 * @package amt_rest_api
 * @subpackage qualifications
 */
class requirement {
  /**
   * qualification type ID
   * @var string
   */
  private $qt_id;
  /**
   * TRUE to disable viewing HIT by unqualified worker
   * @var boolean
   */
  private $required;
  /**
   * one of IntegerValue, LocaleValue.Country
   * @var string
   */
  protected $val_type;
  /**
   * the value to compare with
   * @var string
   */
  protected $value;
  /**
   * the comparison, e.g. 'Equal' or 'LessThan'
   * @var string
   */
  protected $comparator;

  /**
   * the qualification type ID
   * @param string
   */
  protected function __construct($qualtype_id) { $this->qt_id = $qualtype_id; }

  /**
   * Obtain array of key/value pairs for inclusion in query string
   * @param int $n Index 1..n for param names
   * @return string[] array name => value
   */
  public function restparams($n) {
    $prefix = 'QualificationRequirement.' . $n;
    $parms["$prefix.QualificationTypeId"] = $this->qt_id;
    $parms["$prefix.Comparator"] = $this->comparator;
    if ($this->comparator !== 'Exists')
      $parms["$prefix.$this->val_type"] = $this->value;
    if ($this->required) $parms["$prefix.RequiredToPreview"] = 'true';
    return $parms;
  }
  /**
   * Call to prevent HITs being visible to unqualified workers
   */
  public function set_required_for_preview() { $this->required = TRUE; }
}

/**
 * Locale qualification requirement
 *
 * This is a built-in system-generated qualification.
 * @package amt_rest_api
 * @subpackage qualifications
 * @api
 */
class locale_requirement extends requirement {
  /**
   * Construct requirement object
   * @param string $locale ISO 3166 country code
   * @param boolean $equal set false for workers NOT from $locale
   */
  public function __construct($locale, $equal = TRUE) {
    parent::__construct('00000000000000000071');
    $this->val_type = 'LocaleValue.Country';
    $this->value = $locale;
    $this->comparator = $equal ? 'EqualTo' : 'NotEqualTo';
  }
}

/**
 * Score qualification requirement
 *
 * Use this to check for a good score from a qualification test.
 * @package amt_rest_api
 * @subpackage qualifications
 * @api
 */
class score_requirement extends requirement {
  /**
   * Construct score requirement object
   * @param string $qualtype_id QualificationTypeId
   * @param string $comparator e.g. 'LessThan', 'GreaterThanOrEqualTo'
   * @param int $score
   */
  public function __construct($qualtype_id, $comparator, $score) {
    if (!\in_array($comparator, array(
      'LessThan', 'LessThanOrEqualTo', 'GreaterThan', 'GreaterThanOrEqualTo',
      'EqualTo', 'NotEqualTo'
    ))) throw new \InvalidArgumentException("Comparator $comparator illegal.");
    parent::__construct($qualtype_id);
    $this->comparator = $comparator;
    $this->value = $score;
    $this->val_type = 'IntegerValue';
  }
}

/**
 * Predicate qualification requirement, e.g. master qualification
 *
 *
 * No score/value checking here, simply whether the qualification is granted
 * @package amt_rest_api
 * @subpackage qualifications
 * @api
 */
class predicate_requirement extends requirement {
  /**
   * Construct requirement object
   * @param string $qualtype_id QualificationTypeId
   */
  public function __construct($qualtype_id) {
    parent::__construct($qualtype_id);
    $this->comparator = 'Exists';
  }
}

/**
 * GetHITsForQualificationType
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetHITsForQualificationType
 * @link manual.html#GetHITsForQualificationType
 */
class hits_for_qual_request extends paging_request {
  /**
   * Construct request object
   * @param string $qual_type QualificationTypeId
   */
  public function __construct($qual_type) {
    $paramspec = parent::$paging_fields;
    $paramspec['QualificationTypeId'] = TRUE;
    parent::__construct('GetHITsForQualificationType', $paramspec);
    $this->add_param('QualificationTypeId', $qual_type);
  }
  /**
   * Sort property undefined - this may or may not be correct!
   * @todo check whether sort property supported for this API
  */
  protected function valid_sort_fields() {
    return array('CreationTime', 'Title', 'Reward', 'Expiration', 'Enumeration');
  }
  /**
   * Get the HITs corresponding to the qualification type
   * @return hits_for_qualtype iterable collection of amt\hit
   */
  public function execute() {
    return new hits_for_qualtype($this);
  }
}

/**
 * GetQualificationsForQualificationType
 *
 * The execute() method returns a collection of amt\qual
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationsForQualificationType
 * @uses amt\qual is the collection element
 * @link manual.html#GetQualificationsForQualificationType
 */
class qualifications_for_type_request extends paging_request {
  /**
   * 2nd param defaults to 'Granted' as it does in the AMT API
   * @param string $qual_type QualificationTypeId
   * @param string $status Granted/Revoked
   */
  public function __construct($qual_type, $status = 'Granted') {
    $paramspec = parent::$paging_fields;
    $paramspec['QualificationTypeId'] = TRUE;
    parent::__construct('GetQualificationsForQualificationType', $paramspec);
    $this->add_param('QualificationTypeId', $qual_type);
    if ($status === 'Revoked') $this->add_param('Status', $status);
  }
  /**
   * This is guesswork! Sort may be unavailable.
   * @todo check whether sort property supported for this API
   */
  protected function valid_sort_fields() {
    return array('QualificationTypeId', 'Status');
  }
  /**
   * Get the collection of qualifications
   * @return amt\qualifications_for_type Iterable collection of amt\qual
   */
  public function execute() {
    return new qualifications_for_type($this);
  }
}

/**
 * GetQualificationRequests
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationRequests
 * @link manual.html#GetQualificationRequests
 */
class qualification_requests_request extends paging_request {
  /**
   * Construct request object
   * @param string $qual_type QualificationTypeId
   */
  public function __construct($qual_type = FALSE) {
    parent::__construct('GetQualificationRequests', parent::$paging_fields);
    if ($qual_type) $this->add_param('QualificationTypeId', $qual_type);
  }
  /**
   * Return valid sort field names
   * @return string[]
   */
  protected function valid_sort_fields() {
    return array('SubmitTime', 'QualificationTypeId');
  }
  /**
   * Get the qualification requests
   * @return amt\qualification_requests iterable collection of amt\qual_rq
   */
  public function execute() {
    return new qualification_requests($this);
  }
}

/**
 * SearchQualificationTypes
 *
 * Obtains iterable collection of amt\qualtype
 * @package amt_rest_api
 * @subpackage qualifications
 * @api SearchQualificationTypes
 * @link manual.html#SearchQualificationTypes
 */
class search_qualtypes_request extends paging_request {
  /**
   * Create search request object
   * @param boolean $requestable - if TRUE, don't include non-requestable
   *   (system) qualifications e.g. no. of hits completed
   * @param boolean $ours - if TRUE, search only our own qualifications
   * @param string $search - search string
  */
  public function __construct($requestable, $ours, $search = FALSE) {
    $paramspec = parent::$paging_fields;
    $paramspec['MustBeRequestable'] = TRUE;
    $paramspec['Query'] = FALSE;
    $paramspec['MustBeOwnedByCaller'] = FALSE;
    parent::__construct('SearchQualificationTypes', $paramspec);
    $this->add_param('MustBeRequestable', $requestable ? 'true' : 'false');
    $this->add_param('MustBeOwnedByCaller', $ours ? 'true' : 'false');
    if ($search) $this->add_param('Query', $search);
  }
  /**
   * Call this to narrow results
   * @param string $search query to match
   */
  public function set_search($search) { $this->set_param('Query', $search); }
  /**
   * Return sort fields that are valid
   */
  protected function valid_sort_fields() {
    return array('Name');
  }
  /**
   * Obtain iterable collection of amt\qualtype
   * @return amt\search_qualtypes iterable collection of amt\qualtype
   * @uses amt\qualtype the type of the objects in the collection returned
   */
  public function execute() {
    return new search_qualtypes($this);
  }
}

/**
 * internal class for create/update_qualification_request
 *
 * Provides functionality to set the qualification type's test,
 * answer key and AutoGrant parameters
 * @package amt_rest_api
 * @subpackage qualifications
 */
class generic_qualification_request extends compound_request {
  // no constructor here: delegate to parent class
  /**
   * Call with $seconds = 0 for no retry
   * @param int $seconds positive integer
   * @throws InvalidArgumentException if $seconds < 0
   */
  public function set_retry_delay($seconds) {
    if ($seconds < 0)
      throw new \InvalidArgumentException("Retry delay must be +ve");
    $paramname = 'RetryDelayInSeconds';
    if ($seconds === 0)
      $this->clear_param($paramname);
    else
      $this->set_param($paramname, $seconds);
  }
  /**
   * Read an XML specification
   * @param string $xml_url filename of XML document parameter
   * @throws RuntimeException if file could not be read
   */
  private static function read_xml($xml_url) {
    if (!($xml = @\file_get_contents($xml_url)))
      throw new \RuntimeException("Could not read '$xml_url'");
    return $xml;
  }
  /**
   * Set the filename of the questions specification
   * @param string $test_url filename of questions specification
   * @param int $test_dur duration of test in seconds
   * @uses generic_qualification_request::read_xml()
   */
  public function set_test($test_url, $test_dur) {
    $this->clear_param('AutoGranted');
    $this->clear_param('AutoGrantedValue');
    $this->set_param('Test', self::read_xml($test_url));
    $this->set_param('TestDurationInSeconds', $test_dur);
  }
  /**
   * Set the filename of the answer key
   * @param string $ans_url filename of answer key
   * @uses generic_qualification_request::read_xml()
   */
  public function set_answers($ans_url) {
    $this->set_param('AnswerKey', self::read_xml($ans_url));
  }

  /**
   * if points is zero, deactivates autoGranted
   * API default is 1 point - good for "Parameter" qualifications
   * @param int $points No. of points to auto-grant
   * @throws InvalidArgumentException if $points < 0
   */
  public function set_auto($points) {
    if ($points < 0)
      throw new \InvalidArgumentException("Points must be +ve");
    if (!$points) {
      $this->clear_param('AutoGranted');
      $this->clear_param('AutoGrantedValue');
    }
    else {
      $this->clear_param('Test');
      $this->clear_param('TestDurationInSeconds');
      $this->set_param('AutoGranted', 'true');
      if ($points == 1)
        $this->clear_param('AutoGrantedValue');
      else
        $this->set_param('AutoGrantedValue', $points);
    }
  }
  /**
   * Convert to string (request URL)
   * @throws BadMethodCallException if both Test and AutoGranted set
   * @return string request URL
   */
  public function __toString() {
    if ($this->has_param('Test')) {
      if ($this->has_param('AutoGranted'))
        throw new \BadMethodCallException('Cannot have both Test and AutoGranted');
    }
    else {
      if ($this->has_param('AutoGranted'))
        throw new \BadMethodCallException('Cannot have both AutoGranted and Test');
      if ($this->has_param('AnswerKey'))
        throw new \BadMethodCallException('AnswerKey valid only for Test');
    }
    return parent::__toString();
  }
  /**
   * Activate / deactivate
   * @param boolean $yesno TRUE=activate, FALSE=deactivate
   */
  public function set_active($yesno) {
    $this->set_param('QualificationTypeStatus', $yesno ? 'Active' : 'Inactive');
  }
}

/**
 * CreateQualificationType
 * @package amt_rest_api
 * @subpackage qualifications
 * @api CreateQualificationType
 * @link manual.html#CreateQualificationType
 */
class create_qualification_request extends generic_qualification_request {
  /**
   * Create the request object
   * @param string $name qualification Name
   * @param string $desc qualification Description
   * @param boolean $active default TRUE, make qualification available
   * @param string $keywords search keywords (max length 1000 chars)
   * @throws LengthException if $keywords > 1000 chars
   */
  public function __construct($name, $desc, $active = TRUE, $keywords = '') {
    parent::__construct('CreateQualificationType', array(
      'Name' => TRUE, 'Description' => TRUE,
      'QualificationTypeStatus' => TRUE,
      'RetryDelayInSeconds' => FALSE, 'Keywords' => FALSE,
      'Test' => FALSE, 'AnswerKey' => FALSE,
      'AutoGranted' => FALSE, 'AutoGrantedValue' => FALSE
    ));
    $this->add('Name', $name);
    if (\strlen($desc) > 2000)
      throw new \LengthException('Description exceeds 2000 chars');
    $this->add('Description', $desc);
    if (!empty($keywords)) {
      if (\strlen($keywords) > 1000)
        throw new \LengthException("Keywords exceeds 1000 chars");
      $this->add('Keywords', $keywords);
    }
    $this->set_active($active);
  }
  /**
   * Create the qualification
   * @throws amtException
   * @return string QualificationTypeId
   */
  public function execute() {
    $r = response::acquire_from($this, 'QualificationType');
    return (string)$r->QualificationType->QualificationTypeId;
  }
}

/**
 * UpdateQualificationType
 * @package amt_rest_api
 * @subpackage qualifications
 * @api UpdateQualificationType
 * @link manual.html#UpdateQualificationType
 */
class update_qualification_request extends generic_qualification_request {
  /**
   * Create update request object
   * @param string $qual_id QualificationTypeId
   */
  public function __construct($qual_id) {
    parent::__construct('UpdateQualificationType', array(
      'QualificationTypeId' => TRUE,
      'QualificationTypeStatus' => FALSE,
      'RetryDelayInSeconds' => FALSE, 'Keywords' => FALSE,
      'Test' => FALSE, 'AnswerKey' => FALSE,
      'AutoGranted' => FALSE, 'AutoGrantedValue' => FALSE
    ));
    $this->add_param('QualificationTypeId', $qual_id);
  }
  /**
   * Set description
   * @param string $desc new description
   * @throws LengthException if over 2000 chars
   */
  public function set_desc($desc) {
    if (\strlen($desc) > 2000)
      throw new \LengthException("Description exceeds 2000 chars");
    $this->set_param('Description', $desc);
  }
  /**
   * Perform the update
   * @throws amtException
   */
  public function execute() {
    response::acquire_from($this); // no useful return data
  }
}

/**
 * GetQualificationType
 *
 * Use like this: $qual = amt\get_qualification_request::execute($qual_id);
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationType
 * @link manual.html#GetQualificationType
 * @uses amt\qualtype returned from execute()
 */
class get_qualification_request extends request {
  /**
   * Protected ctor
   * @param string $qual_id QualificationTypeId
   */
  protected function __construct($qual_id) {
    parent::__construct('GetQualificationType');
    $this->add('QualificationTypeId', $qual_id);
  }
  /**
   * Get the qualification type
   * @param string $qual_id the QualificationTypeId
   * @return amt\qualtype
   * @throws amtException
   */
  static public function execute($qual_id) {
    $r = response::acquire_from(new self($qual_id), 'QualificationType');
    return new qualtype($r->QualificationType);
  }
}

/**
 * DisposeQualificationType
 *
 * Use like this: amt\dispose_qualification_request::execute($qual_id);
 * @package amt_rest_api
 * @subpackage qualifications
 * @api DisposeQualificationType
 * @link manual.html#DisposeQualificationType
 */
class dispose_qualification_request extends request {
  /**
   * Protected ctor
   * @param string $qual_id QualificationTypeId
   */
  protected function __construct($qual_id) {
    parent::__construct('DisposeQualificationType');
    $this->add('QualificationTypeId', $qual_id);
  }
  /**
   * Make it happen
   * @param string $qual_id the QualificationTypeId
   * @throws amtException
   */
  static public function execute($qual_id) {
    $r = new self($qual_id);
    response::acquire_from($r);
  }
}

/**
 * AssignQualification
 *
 * Used to assign a qualification to someone who hasn't taken the test
 * Use like this:
 *   $rq = new assign_qualification_request($qual_id);
 *   foreach ($worker_ids as $w) $rq->execute($w, 20);
 * @package amt_rest_api
 * @subpackage qualifications
 * @api AssignQualification
 * @link manual.html#AssignQualification
 */
class assign_qualification_request extends compound_request {
  /**
   * Construct request object
   * @param string $qual_id QualificationTypeId
   * @param boolean $no_notification specify as TRUE to prevent notification
   */
  public function __construct($qual_id, $no_notification = FALSE) {
    parent::__construct('AssignQualification', array(
      'QualificationTypeId' => TRUE,
      'WorkerId' => TRUE,
      'IntegerValue' => FALSE,
      'SendNotification' => FALSE
    ));
    $this->add_param('QualificationTypeId', $qual_id);
    if ($no_notification) $this->add_param('SendNotification', 'false');
  }
  /**
   * Assign the qualification
   * @param string $worker_id WorkerId
   * @param int $score score to assign
   * @throws amtException
   */
  public function execute($worker_id, $score) {
    $this->set_param('WorkerId', $worker_id);
    if ($score == 1)
      $this->clear_param('IntegerValue');
    else
      $this->set_param('IntegerValue', $score);
    response::acquire_from($this);
  }
}

/**
 * GrantQualification
 *
 * Don't use this class: use amt\qual_rq::grant()
 * But if you do, use like this:
 *         amt\grant_qualification_request::execute($rq_id, 20);
 * @package amt_rest_api
 * @subpackage qualifications
 */
class grant_qualification_request extends request {
  /**
   * Protected ctor
   * @param string $qr_id QualificationRequestId
   * @param int $score score to assign
   */
  protected function __construct($qr_id, $score) {
    parent::__construct('GrantQualification');
    $this->add('QualificationRequestId', $qr_id);
    if ($score != 1) $this->add('IntegerValue', $score);
  }
  /**
   * Activate the grant of the qualification
   * @param string $qr_id QualificationRequestId
   * @param int $score score to assign
   * @throws amtException on error
   */
  public static function execute($qr_id, $score) {
    response::acquire_from(new self($qr_id, $score));
  }
}

/**
 * RejectQualification
 *
 * Don't use this class: use amt\qual_rq::reject()
 * But if you do, use like this:
 *         amt\reject_qualification_request::execute($rq_id, 20);
 * @package amt_rest_api
 * @subpackage qualifications
 */
class reject_qualification_request extends request {
  /**
   * Protected ctor
   * @param string $qr_id QualificationRequestId
   * @param string $reason reason for rejection
   */
  protected function __construct($qr_id, $reason) {
    parent::__construct('RejectQualification');
    $this->add('QualificationRequestId', $qr_id);
    if ($reason) $this->add('Reason', $reason);
  }
  /**
   * Activate rejection
   * @param string $qr_id QualificationRequestId
   * @param string $reason reason for rejection
   * @throws amtException on error
   */
  public static function execute($qr_id, $reason) {
    response::acquire_from(new self($qr_id, $reason));
  }
}

/**
 * RevokeQualification
 *
 * Withdraw a previously granted qualification
 * Use like this:
 *   amt\revoke_qualification_request::execute($qualtype_id, $worker_id, 'Re-marked');
 * @package amt_rest_api
 * @subpackage qualifications
 * @api RevokeQualification
 * @link manual.html#RevokeQualification
 */
class revoke_qualification_request extends request {
  /**
   * Protected ctor
   * @param string $qaltype_id QualificationTypeId
   * @param string $worker_id WorkerId
   * @param string $reason reason for rejection (FALSE for none)
   */
  protected function __construct($qaltype_id, $worker_id, $reason) {
    parent::__construct('RevokeQualification');
    $this->add('SubjectId', $worker_id);
    $this->add('QualificationTypeId', $qaltype_id);
    if ($reason) $this->add('Reason', $reason);
  }
  /**
   * Activate the revocation
   * @param string $qaltype_id QualificationTypeId
   * @param string $worker_id WorkerId
   * @param string $reason reason for rejection (FALSE for none)
   * @throws amtException
   */
  public static function execute($qaltype_id, $worker_id, $reason = FALSE) {
    response::acquire_from(new self($qaltype_id, $worker_id, $reason));
  }
}

/**
 * UpdateQualificationScore
 *
 * Re-mark a previously granted qualification
 * Use like this:
 *         amt\update_qualification_score::execute($qualtype_id, $worker_id, 100);
 * @package amt_rest_api
 * @subpackage qualifications
 * @api UpdateQualificationScore
 * @link manual.html#UpdateQualificationScore
 */
class update_qualification_score extends request {
  /**
   * Protected ctor
   * @param string $qualtype_id QualificationTypeId
   * @param string $worker_id WorkerId
   * @param int $score new score
   */
  protected function __construct($qualtype_id, $worker_id, $score) {
    parent::__construct('UpdateQualificationScore');
    $this->add('SubjectId', $worker_id);
    $this->add('QualificationTypeId', $qualtype_id);
    $this->add('IntegerValue', $score);
  }
  /**
   * Do the update
   * @param string $qualtype_id QualificationTypeId
   * @param string $worker_id WorkerId
   * @param int $score new score
   */
  public static function execute($qualtype_id, $worker_id, $score) {
    response::acquire_from(new self($qualtype_id, $worker_id, $score));
  }
}

/**
 * GetQualificationScore

 * Use like this:
 *         $rq = new amt\qualification_score_request($qualtype_id);
 *         foreach ($worker_ids as $w) scores[$w] = $rq->execute($w)
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationScore
 * @link manual.html#GetQualificationScore
 */
class qualification_score_request extends compound_request {
  /**
   * Create request object
   * @param string $qualtype_id QualificationTypeId
   */
  public function __construct($qualtype_id) {
    parent::__construct('GetQualificationScore', array(
      'QualificationTypeId' => TRUE,
      'WorkerId' => TRUE
    ));
    $this->add_param('QualificationTypeId', $qualtype_id);
  }
  /**
   * Get the qualification score
   * @param string $worker_id WorkerId
   * @return int the score
   * @throws amtException
   */
  public function execute($worker_id) {
    $this->set_param('SubjectId', $worker_id);
    $r = response::acquire_from($this, 'Qualification');
    return (int)$r->Qualification->IntegerValue;
  }
}

/*
  AMT Response types
*/
/**
 * Qualification data structure
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationsForQualificationType
 * @link manual.html#GetQualificationsForQualificationType
 */
class qual {
  /**
   * QualificationTypeId
   * @var string $id the QualificationTypeId
   */
  public $id;
  /**
   * Worker ID
   * @var string $worker_id the 'SubjectId' or 'WorkerId'
   */
  public $worker_id;
  /**
   * Timestamp of time granted
   * @var string AMT timestamp of time granted
   */
  public $grant_time;
  /**
   * The status Granted/Revoked
   * @var string Granted/Revoked
   */
  public $status;
  /**
   * The score
   * @var int
   */
  public $value;

  /**
   * Create qualification object
   * @param amt\response $r the response XML
   */
  public function __construct(response $r) {
    $fields = array( // convert AMT's field names to ours
      'QualificationTypeId' => 'id',
      'SubjectId' => 'worker_id',
      'GrantTime' => 'grant_time',
      'IntegerValue' => 'value',
      'LocaleValue' => 'value',
      'Status' => 'status'
    );
    foreach ($fields as $k => $f) {
      if ($r->$k->exists()) switch ($k) {
        case 'LocaleValue':
          $this->value = (string)($r->LocaleValue->Country);
        break;
        case 'IntegerValue':
          $this->value = (int)($r->IntegerValue);
        break;
        default:
          $this->$f = (string)($r->$k);
        //break;
      }
    }
  }
}

/**
 * Qualification type data structure
 * @package amt_rest_api
 * @subpackage qualifications
 * @api SearchQualificationTypes
 * @link manual.html#SearchQualificationTypes
 * @property string $QualificationTypeId
 * @property string $CreationTime
 * @property int $creation_time the UNIX-style version of $CreationTime
 * @property string $Name
 * @property string $Description
 * @property string $Keywords
 * @property string $QualificationTypeStatus Active/Inactive
 * @property int $RetryDelayInSeconds
 * @property string $Test the XML question form data
 * @property boolean $IsRequestable FALSE if it's a system qualtype like no. of HITs completed
 * @property int $TestDurationInSeconds
 * @property string $AnswerKey the XML answer key data
 * @property boolean $AutoGranted
 * @property int $AutoGrantedValue
 */
class qualtype extends \ArrayObject {
  /**
   * Create qualification type object
   * @param amt\response $r the response XML
   */
  public function __construct(response $r) {
    $fields = array(
      'QualificationTypeId', 'CreationTime', 'Name', 'Description', 'Keywords',
      'QualificationTypeStatus', 'RetryDelayInSeconds', 'Test', 'IsRequestable',
      'TestDurationInSeconds', 'AnswerKey', 'AutoGranted', 'AutoGrantedValue'
    );
    foreach ($fields as $f) {
      if ($r->$f->exists()) switch ($f) {
        case 'RetryDelayInSeconds' :
        case 'TestDurationInSeconds':
        case 'AutoGrantedValue' :
          $setvals[$f] = (int)($r->$f);
        break;
        case 'IsRequestable':
        case 'AutoGranted' :
          $setvals[$f] = (string)($r->$f) === 'true';
        break;
        case 'Test' :
        case 'AnswerKey' :
          $setvals[$f] = \htmlspecialchars_decode((string)$r->$f);
        break;
        case 'CreationTime': $setvals['creation_time'] = \strtotime((string)($r->$f));
        default:
          $setvals[$f] = (string)($r->$f);
        //break;
      }
    }
    parent::__construct($setvals, \ArrayObject::ARRAY_AS_PROPS);
  }
}

/**
 * Qualification request - may contain worker's test answers
 *
 * Constructed by the amt\qualification_requests pager
 * @package amt_rest_api
 * @subpackage qualifications
 * @uses amt\grant_qualification_request
 * @uses amt\reject_qualification_request
 * @api GetQualificationRequests
 * @link manual.html#GetQualificationRequests
*/
class qual_rq {
  /**
   * Qualification type ID
   * @var string
  */
  public $qual_id;
  /**
   * Request ID
   * @var string request ID
  */
  private $rq_id;
  /**
   * ID of the worker
   * @var string worker ID
   */
  public $worker_id;
  /**
   * Time the request was made
   * @var string time the request was made
   */
  public $rq_time;
  /**
   * Worker's test answers
   * @var mixed[] - indexed on the question identifiers, may be either
   * - a simple string (free text or selection identifier)
   * - an array with an answer and an otherAnswer element whose values are strings;
   * - an array('size' => integer, 'fileKey' => string) - if
   *   the response was a file upload.
   */
  public $answers;

  /**
   * Create qualification request object
   * @param amt\response $r the response XML
   */
  public function __construct(response $r) {
    $fields = array( // convert AMT's field names to ours
      'QualificationRequestId' => 'rq_id', //? useful
      'QualificationTypeId' => 'qual_id',
      'SubjectId' => 'worker_id',
      'SubmitTime' => 'rq_time',
      'Test' => 'XML',
      'Answer' => 'XML'
    );
    foreach ($fields as $k => $f) {
      if ($r->$k->exists()) {
        if ($f !== 'XML')
          $this->$f = (string)($r->$k);
        else if ($k === 'Answer') { // ignore Test
          $xml = \html_entity_decode($r->Answer);
          $xml = \simplexml_load_string($xml);
          foreach ($xml->Question as $q) {
            $ad = array();
            $ad_array = FALSE;
            foreach ($q->children() as $qc) switch ($qc->getName()) {
              case 'QuestionIdentifier' :
                $qi = (string)$qc->QuestionIdentifier;
              break;
              case 'FreeText' :
                $ad['answer'] = (string)$qc->FreeText;
              break;
              case 'SelectionIdentifier' :
                $ad['answer'] = (string)$qc->SelectionIdentifier;
              break;
              case 'OtherSelectionText' :
                $ad['otherAnswer'] = (string)$qc->OtherSelectionText;
                $ad_array = TRUE;
              break;
              case 'UploadedFileKey' :
                trigger_error('There is a file upload in your test, but no API to retrieve the file', E_USER_WARNING);
                $ad['fileKey'] = (string)$qc->UploadedFileKey;
                $ad_array = TRUE;
              break;
              case 'UploadedFileSizeInBytes' :
                $ad['size'] = (int)$qc->UploadedFileSizeInBytes;
                $ad_array = TRUE;
              break;
            }
            $this->answers[$qi] = $ad_array ? $ad : $ad['answer'];
          }
        }
      }
    }
  }
  /**
   * Grant the requested qualification
   * @param int $score the score to grant
   * @throws amtException
   */
  public function grant($score) {
    grant_qualification_request::execute($this->rq_id, $score);
  }
  /**
   * Reject the request
   * @param string $reason optional reason for rejection
   */
  public function reject($reason = FALSE) {
    reject_qualification_request::execute($this->rq_id, $reason);
  }
}

/**
 * single page returned by GetQualificationsForQualificationType
 * @uses amt\qual
 * @package amt_rest_api
 * @subpackage responses
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class qualifications_for_type_response extends paged_response {
  /**
   * Array of qual
   * @var amt\qual[]
   */
  private $quals = array();
  /**
   * Construct single page response object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->GetQualificationsForQualificationTypeResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->quals[] = new qual($r->Qualification[$i]);
  }
  /**
   * Get iterator over qualifications
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->quals); }
}

/**
 * single page returned by GetQualificationRequests
 * @uses amt\qual_rq
 * @package amt_rest_api
 * @subpackage qualifications
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class qualification_requests_response extends paged_response {
  /**
   * QualificationRequests
   * @var amt\qual_rq[] QualificationRequests
   */
  private $requests = array();
  /**
   * Create page response object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->GetQualificationRequestsResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->requests[] = new qual_rq($r->QualificationRequest[$i]);
  }
  /**
   * Get iterator over qualification requests
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->requests); }
}

/**
 * single page returned by SearchQualificationTypes
 * @package amt_rest_api
 * @subpackage qualifications
 * @todo Put more ctor functionality into amt\paged_response
 * @internal
 */
class search_qualtypes_response extends paged_response {
  /**
   * qualtypes
   * @var amt\qualtype[]
   */
  private $quals = array();
  /**
   * Create page of qualtypes response object
   * @param amt\request $rq the request XML
   */
  public function __construct(request $rq) {
    $r = response::acquire_from($rq);
    $r = $r->SearchQualificationTypesResult;
    parent::__construct($r->TotalNumResults, $r->PageNumber);
    $n_results = (int)$r->NumResults;
    for ($i = 0; $i < $n_results; ++$i)
      $this->quals[] = new qualtype($r->QualificationType[$i]);
  }
  /**
   * Get iterator over qualification types
   * @return \ArrayIterator
   */
  public function answers() { return new \ArrayIterator($this->quals); }
}

/**
 * Collection of HITs for QualificationType
 *
 * Acts like an array of amt\hit
 * @uses amt\hit as iterable object
 * @uses hitsearch_response as paged response
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetHITsForQualificationType
 * @link manual.html#GetHITsForQualificationType
 */
class hits_for_qualtype extends pager {
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return hitsearch_response
   */
  protected function paged_response(paging_request $rq) {
    return new hitsearch_response($rq);
  }
}

/**
 * Collection of Qualifications for GetQualificationsForQualificationType
 *
 * Acts like an array of amt\qual
 * @uses amt\qual as iterable element
 * @uses amt\qualifications_for_type_response as page response object
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationsForQualificationType
 * @link manual.html#GetQualificationsForQualificationType
 */
class qualifications_for_type extends pager {
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return amt\qualifications_for_type_response
   */
  protected function paged_response(paging_request $rq) {
    return new qualifications_for_type_response($rq);
  }
}

/**
 * Collection of Qualification Requests for GetQualificationRequests
 *
 * Acts like an array of amt_qual_rq
 * @uses amt\qual_rq as iterable element
 * @uses amt\qualification_requests_response as page response object
 * @package amt_rest_api
 * @subpackage qualifications
 * @api GetQualificationRequests
 * @link manual.html#GetQualificationRequests
 */
class qualification_requests extends pager {
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return amt\qualification_requests_response
   */
  protected function paged_response(paging_request $rq) {
    return new qualification_requests_response($rq);
  }
}

/**
 * Collection of Qualification Types for SearchQualificationTypes
 *
 * Acts like an array of amt\qualtype
 * @package amt_rest_api
 * @subpackage qualifications
 * @uses amt\qualtype as iterable object
 * @uses amt\search_qualtypes_response as page response object
 * @api SearchQualificationTypes
 * @link manual.html#SearchQualificationTypes
 */
class search_qualtypes extends pager {
  /**
   * get the next page object from the subclass
   * @param amt\paging_request $rq
   * @return amt\search_qualtypes_response
   */
  protected function paged_response(paging_request $rq) {
    return new search_qualtypes_response($rq);
  }
}