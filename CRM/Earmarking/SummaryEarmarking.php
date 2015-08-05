<?php

/**
 * Class CRM_Earmarking_SummaryEarmarking
 *
 * (BOS1506294) Adds earmarking of recurring contribution(s) to summary page
 * if contact has active recurring contribution(s)
 */
class CRM_Earmarking_SummaryEarmarking {
  /**
   * Method to retrieve the earmarking of the recurring contributions of  the contact (if there is any)
   *
   * @param int $contactId
   * @return string
   * @throws Exception when error from API
   * @access public
   * @static
   */
  public static function getEarmarking($contactId) {
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
      Error from API Optiongroup Getvalue : '.$ex->getMessage());
    }
  }

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

}