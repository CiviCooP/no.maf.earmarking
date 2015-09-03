<?php

/**
 * A custom contact search
 */
class CRM_Earmarking_Form_Search_EarmarkSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    $config = CRM_Earmarking_Config::singleton();
    CRM_Utils_System::setTitle($config->translate('Search for Contacts with Recurring Contributions'));
    $paymentTypeList = $this->getPaymentTypeList();
    $earmarkingList = $this->getEarmarkingList();
    $contibutionStatusList = $this->getContributionStatusList();

    $form->add('select', 'earmarking_id', $config->translate('Earmarking'), $earmarkingList);
    $form->add('select', 'payment_type_id', $config->translate('Payment Type'), $paymentTypeList);
    $form->add('select', 'status_id', ts('Contribution Status'), $contibutionStatusList);
    $form->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'custom'));
    $form->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'custom'));
    $form->assign('elements', array('earmarking_id', 'payment_type_id', 'status_id', 'start_date', 'end_date'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    $config = CRM_Earmarking_Config::singleton();
    $columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'display_name',
      $config->translate('Earmarking(s) for Contact') => 'earmarking',
      $config->translate('Payment Type(s) for Contact') => 'payment_type',
      $config->translate('No of Active Recurring Contr.') => 'contribution_count'
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {

    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Method to count selected contacts
   *
   * @return string
   */
  function count() {
    $query = "SELECT COUNT(DISTINCT(contr.contact_id)) AS total ".$this->from()." WHERE ".$this->where();
    return CRM_Core_DAO::singleValueQuery($query) ;
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "DISTINCT(contr.contact_id), contact_a.contact_type, contact_a.display_name, '' AS contribution_count,
    '' AS earmarking, '' AS payment_type ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    $transactionTable = CRM_Earmarking_Earmarking::getNetsTransactionsTable();
    return "
FROM civicrm_contribution_recur_offline off
JOIN civicrm_contribution_recur recur ON off.recur_id = recur.id
JOIN civicrm_contribution contr ON off.recur_id = contr.contribution_recur_id
LEFT JOIN ".$transactionTable['table_name']." nets ON contr.id = nets.entity_id
LEFT JOIN civicrm_contact contact_a ON contr.contact_id = contact_a.id";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();
    $clause = array();
    $count = 0;

    if (!empty($this->_formValues['status_id'])) {
      $count++;
      $clause[] = "contr.contribution_status_id = %".$count;
      $params[$count] = array($this->_formValues['status_id'], 'Integer');
    }

    if (!empty($this->_formValues['earmarking_id'])) {
      $count++;
      $clause[] = "off.earmarking_id = %".$count;
      $params[$count] = array($this->_formValues['earmarking_id'], 'Integer');
    }

    if (!empty($this->_formValues['payment_type_id'])) {
      $count++;
      $clause[] = "off.payment_type_id = %".$count;
      $params[$count] = array($this->_formValues['payment_type_id'], 'Integer');
    }

    if (!empty($this->_formValues['start_date'])) {
      $count++;
      $clause[] = "contr.receive_date >= %".$count;
      $params[$count] = array(date('Ymd', strtotime($this->_formValues['start_date'])), 'Date');
    }

    if (!empty($this->_formValues['end_date'])) {
      $count++;
      $clause[] = "contr.receive_date <= %".$count;
      $params[$count] = array(date('Ymd', strtotime($this->_formValues['end_date'])), 'Date');
    }

    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
    }
    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @throws exception if function getOptionGroup not found
   * @return void
   */
  function alterRow(&$row) {
    $row['earmarking'] = CRM_Earmarking_Earmarking::getRecurringEarmarkingForContact($row['contact_id']);
    $row['contribution_count'] = $this->getRecurringCountForContact($row['contact_id']);
    $row['payment_type'] = $this->getPaymentTypesForContact($row['contact_id']);
  }

  private function getRecurringCountForContact($contactId) {
    $contributionCount = 0;
    $query = "SELECT COUNT(*)
FROM civicrm_contribution_recur JOIN civicrm_contribution_recur_offline ON id = recur_id
WHERE contact_id = %1 AND (end_date >= NOW() OR end_date IS NULL)";
    $params = array(1 => array($contactId, 'Integer'));
    return CRM_COre_DAO::singleValueQuery($query, $params);
  }
  /**
   * Method to get payment terms for contact, retrieved from selected recurring contributions
   *
   * @param int $contactId
   * @return string $paymentTypes
   * @access public
   */
  public function getPaymentTypesForContact($contactId) {
    $paymentTypes = array();
    $config = CRM_Earmarking_Config::singleton();
    $query = "SELECT DISTINCT(opt.label) as paymentType
FROM civicrm_contribution_recur recur
JOIN civicrm_contribution_recur_offline off ON recur.id = off.recur_id
JOIN civicrm_option_value opt ON off.payment_type_id = opt.value AND option_group_id = %1
WHERE contact_id = %2";
    $params = array(
      1 => array($config->getPaymentTypeOptionGroup(), 'Integer'),
      2 => array($contactId, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $paymentTypes[] = $dao->paymentType;
    }
    return implode("; ", $paymentTypes);
  }

  /**
   * Method calculates total contributed amount from contributions based on recurring
   *
   * @param array $recurringContributions containing contributions
   * @return float $donorAmount
   * @access public
   */
  public function getContactDonorAmount($recurringContributions) {
    // TODO : implement
    $donorAmount = 0;
    return $donorAmount;
  }

  /**
   * Method to get payment lists from option group
   *
   * @return array
   * @throws Exception when no getOptionGroup function
   * @access private
   *
   */
  private function getPaymentTypeList() {
    $paymentTypeList = array();
    $config = CRM_Earmarking_Config::singleton();
    $params = array(
      'option_group_id' => $config->getPaymentTypeOptionGroup());
    try {
      $optionValues = civicrm_api3('OptionValue', 'Get', $params);
      foreach($optionValues['values'] as $optionValue) {
        $paymentTypeList[$optionValue['value']] = $optionValue['label'];
      }
      $paymentTypeList[0] = '- select -';
      asort($paymentTypeList);
      return $paymentTypeList;
    } catch (CiviCRM_API3_Exception $ex) {
      $paymentTypeList[0] = '- select -';
      return $paymentTypeList;
    }
  }

  /**
   * Method to get earmarking lists from option group
   *
   * @return array
   * @throws Exception when no getOptionGroup function
   * @access private
   *
   */
  private function getEarmarkingList() {
    $earmarkingList = array();
    $config = CRM_Earmarking_Config::singleton();
    $params = array(
      'option_group_id' => $config->getEarmarkingOptionGroup());
    try {
      $optionValues = civicrm_api3('OptionValue', 'Get', $params);
      foreach($optionValues['values'] as $optionValue) {
        $earmarkingList[$optionValue['value']] = $optionValue['label'];
      }
      $earmarkingList[0] = '- select -';
      asort($earmarkingList);
      return $earmarkingList;
    } catch (CiviCRM_API3_Exception $ex) {
      $earmarkingList[0] = '- select -';
      return $earmarkingList;
    }
  }

  /**
   * Method to get contribution status lists from option group
   *
   * @return array
   * @throws Exception when no getOptionGroup function
   * @access private
   *
   */
  private function getContributionStatusList() {
    $contributionStatusList = array();
    if (method_exists('CRM_Costinvoicelink_Utils', 'getOptionGroup')) {
      $earmarkingOptionGroup = CRM_Costinvoicelink_Utils::getOptionGroup('contribution_status');
      $params = array(
        'option_group_id' => $earmarkingOptionGroup['id']);
      try {
        $optionValues = civicrm_api3('OptionValue', 'Get', $params);
        foreach($optionValues['values'] as $optionValue) {
          $contributionStatusList[$optionValue['value']] = $optionValue['label'];
        }
        $contributionStatusList[0] = '- select -';
        asort($contributionStatusList);
        return $contributionStatusList;
      } catch (CiviCRM_API3_Exception $ex) {
        $contributionStatusList[0] = '- select -';
        return $contributionStatusList;
      }
    } else {
      throw new Exception('Could not find extension Costinvoicelink, check your CiviCRM support team (not found method getOptionGroup)');
    }
  }
}
