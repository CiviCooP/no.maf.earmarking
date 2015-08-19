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

  /**
   * Constructor method
   *
   * @param string $context
   */
  function __construct($context) {
    $settings = civicrm_api3('Setting', 'Getsingle', array());
    $this->resourcesPath = $settings['extensionsDir'].'/no.maf.postnummer/resources/';
    $this->setTranslationFile();
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
  protected function setTranslationFile() {
    $config = CRM_Core_Config::singleton();
    $jsonFile = $this->resourcesPath.$config->lcMessages.'_translate.json';
    if (file_exists($jsonFile)) {
      $translateJson = file_get_contents($jsonFile);
      $this->translatedStrings = json_decode($translateJson, true);

    } else {
      $this->translatedStrings = array();
    }
  }
}