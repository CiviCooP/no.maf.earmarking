<?php


/**
 * Class CRM_Earmarking_Earmarking
 *
 * Generic earmarking functions (based on several issues but trigger was BOS1506293)
 */
class CRM_Earmarking_Earmarking {

  /**
   * Method to determine if contact has active recurring contributions
   *
   * @param int $contactId
   * @return bool
   * @access public
   * @static
   */
  public static function hasActiveRecurring($contactId) {
    if (!empty($contactId)) {
      $query = 'SELECT COUNT(*) AS countRecur
  FROM civicrm_contribution_recur
  WHERE contact_id = %1 AND is_test = %2
  AND (end_date IS NULL OR end_date >= NOW())';

      $params = array(
        1 => array($contactId, 'Integer'),
        2 => array(0, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      if ($dao->fetch()) {
        if ($dao->countRecur > 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }
  /**
   * Method to retrieve the earmarking of the recurring contributions of  the contact (if there is any)
   *
   * @param int $contactId
   * @return string
   * @throws Exception when error from API
   * @access public
   * @static
   */
  public static function getRecurringEarmarkingForContact($contactId) {
    $optionGroupParams = array(
      'name' => "earmarking",
      'return' => 'id');
    try {
      $earmarkingOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $optionGroupParams);
      $query = 'SELECT DISTINCT(opt.label) AS earmark
FROM civicrm_contribution_recur_offline off
JOIN civicrm_contribution_recur recur ON off.recur_id = recur.id
JOIN civicrm_option_value opt ON option_group_id = %1 AND VALUE = off.earmarking_id
WHERE recur.contact_id = %2';
      $params = array(
        1 => array($earmarkingOptionGroupId, 'Integer'),
        2 => array($contactId, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $earmarks = array();
      while ($dao->fetch()) {
        $earmarks[] = $dao->earmark;
      }
      return implode('; ', $earmarks);

    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group with name earmarking, contact your CiviCRM supporter.
      Error from API OptionGroup Getvalue : '.$ex->getMessage());
    }
  }

  /**
   * Method to retrieve the earmarking of a recurring contribution
   *
   * @param int $recurId
   * @return int | boolean
   * @access public
   * @static
   */
  public static function getRecurringEarmarking($recurId) {
    if (!empty($recurId)) {
      $query = 'SELECT earmarking_id FROM civicrm_contribution_recur_offline WHERE recur_id = %1';
      $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($recurId, 'Integer')));
      if ($dao->fetch()) {
        return $dao->earmarking_id;
      }
    }
    return FALSE;
  }

  /**
   * Method to get the local table name of the custom data set used for nets transactions
   * (this is where the earmarking is stored)
   *
   * @return mixed
   * @throws Exception when error in API
   * @access public
   * @static
   */
  public static function getNetsTransactionsTable() {
    try {
      return civicrm_api3('CustomGroup', 'Getsingle', array('name' => 'nets_transactions'));
    } catch (CiviCRM_API3_Exception $e) {
      throw new Exception("Could not find a custom group with the name nets_transactions!");
    }
  }
  /**
   * Function to get the earmarking column from the nets file
   *
   * @return string
   * @throws Exception when error from API CustomField Getvalue
   * @access public
   * @static
   */
  public static function getEarmarkingColumn() {
    $netsTransactionsTable = self::getNetsTransactionsTable();
    $params = array(
      'custom_group_id' => $netsTransactionsTable['id'],
      'name' => "earmarking",
      'return' => 'column_name');
    try {
      return civicrm_api3('CustomField', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $e) {
      throw new Exception("Could not find a custom field with name earmarking in nets transactions custom group, unable to run contribution earmark");
    }
  }

  /**
   * Method to add an earmark to a contribution
   *
   * @param int $contributionId
   * @param int $earmarkingId
   * @throws Exception when mandatory params not present or empty
   * @access public
   * @static
   */
  public static function addContributionEarmark($contributionId, $earmarkingId) {
    if (empty($contributionId) || empty($earmarkingId)) {
      throw new Exception('No contributionId or earmarkingId passed when trying to add earmark to contribution');
    }
    $netsTransactionsTable = self::getNetsTransactionsTable();
    $earmarkingColumn = self::getEarmarkingColumn();
    if (self::netsExistsForContribution($contributionId) == TRUE) {
      $query = 'UPDATE '.$netsTransactionsTable['table_name'].' SET '.$earmarkingColumn.' = %1 WHERE entity_id = %2';
    } else {
      $query = 'INSERT INTO '.$netsTransactionsTable['table_name'].' SET '.$earmarkingColumn.' = %1, entity_id = %2';
    }
    $params = array(
      1 => array($earmarkingId, 'Integer'),
      2 => array($contributionId, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Method to check if there is a nets transactions record for a contribution
   *
   * @param int $contributionId
   * @return bool
   * @access public
   * @static
   */
  public static function netsExistsForContribution($contributionId) {
    $netsTransactionsTable = self::getNetsTransactionsTable();
    $query = 'SELECT COUNT(*) AS countNets FROM '.$netsTransactionsTable['table_name'].' WHERE entity_id = %1';
    $params = array(1 =>array($contributionId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch() && $dao->countNets > 0) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
}