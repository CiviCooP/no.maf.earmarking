<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 17 Aug 2015
 * @license AGPL-3.0
 */
class CRM_Earmarking_Config {

  private static $_singleton;

  protected $resourcesPath = null;
  protected $translatedStrings = array();
  protected $paymentTypeOptionGroup = array();
  protected $earmarkingOptionGroup = array();

  /**
   * Constructor method
   *
   * @param string $context
   */
  function __construct($context) {
    $settings = civicrm_api3('Setting', 'Getsingle', array());
    $this->resourcesPath = $settings['extensionsDir'].'/no.maf.postnummer/resources/';
    $this->paymentTypeOptionGroup = $this->setOptionGroup('recurring_payment_type');
    $this->earmarkingOptionGroup = $this->setOptionGroup('earmarking');
    $this->setTranslationFile();
  }

  /**
   * Method to get the option group for recurring payment type
   *
   * @param string $key
   * @return mixed
   * @access public
   */
  public function getPaymentTypeOptionGroup($key = 'id') {
    return $this->paymentTypeOptionGroup[$key];
  }

  /**
   * Method to get the option group for earmarking
   *
   * @param string $key
   * @return mixed
   * @access public
   */
  public function getEarmarkingOptionGroup($key = 'id') {
    return $this->earmarkingOptionGroup[$key];
  }

  /**
   * This method offers translation of strings, such as
   *  - activity subjects
   *  - ...
   *
   * @param string $string
   * @return string
   * @access public
   */
  public function translate($string) {
    if (isset($this->translatedStrings[$string])) {
      return $this->translatedStrings[$string];
    } else {
      return ts($string);
    }
  }

  /**
   * Singleton method
   *
   * @param string $context to determine if triggered from install hook
   * @return CRM_Postnummer_Config
   * @access public
   * @static
   */
  public static function singleton($context = null) {
    if (!self::$_singleton) {
      self::$_singleton = new CRM_Earmarking_Config($context);
    }
    return self::$_singleton;
  }


  /**
   * Protected function to load translation json based on local language
   *
   * @access protected
   */
  protected function setTranslationFile()
  {
    $config = CRM_Core_Config::singleton();
    $jsonFile = $this->resourcesPath . $config->lcMessages . '_translate.json';
    if (file_exists($jsonFile)) {
      $translateJson = file_get_contents($jsonFile);
      $this->translatedStrings = json_decode($translateJson, true);

    } else {
      $this->translatedStrings = array();
    }
  }

    /**
     * Function to get option groups
     *
     * @param $optionGroupName
     * @return array|bool
     * @throws Exception
     */
  protected function setOptionGroup($optionGroupName) {
    if (method_exists('CRM_Costinvoicelink_Utils', 'getOptionGroup')) {
      return CRM_Costinvoicelink_Utils::getOptionGroup($optionGroupName);
    } else {
      try {
        return civicrm_api3('OptionGroup', 'Getsingle', array('name' => $optionGroupName));
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception("Could not find a single option group with name ".$optionGroupName.", meaning there might be none or more.
        Error from API OptionGroup Getsingle: ".$ex->getMessage());
      }
    }
  }
}