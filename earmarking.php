<?php

require_once 'earmarking.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function earmarking_civicrm_config(&$config) {
  _earmarking_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function earmarking_civicrm_xmlMenu(&$files) {
  _earmarking_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function earmarking_civicrm_install() {
  _earmarking_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function earmarking_civicrm_uninstall() {
  _earmarking_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function earmarking_civicrm_enable() {
  _earmarking_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function earmarking_civicrm_disable() {
  _earmarking_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function earmarking_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _earmarking_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function earmarking_civicrm_managed(&$entities) {
  _earmarking_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function earmarking_civicrm_caseTypes(&$caseTypes) {
  _earmarking_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function earmarking_civicrm_angularModules(&$angularModules) {
_earmarking_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function earmarking_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _earmarking_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook civicrm_pageRun to add element for mearmarking of recurring (BOS1506294)
 *
 * @param object $page
 */
function earmarking_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Contact_Page_View_Summary') {
    $contactId = $page->getVar('_contactId');
    $page->assign('earmarking', CRM_Earmarking_Earmarking::getRecurringEarmarkingForContact($contactId));
  }
}

/**
 * Implementation of hook civicrm_summary to add count main activities for expert (issue 1608)
 *
 * @param $contactId
 * @param $content
 */
function earmarking_civicrm_summary($contactId, &$content) {
  if (CRM_Earmarking_Earmarking::hasActiveRecurring($contactId) == TRUE) {
    CRM_Core_Region::instance('page-body')->add(array('template' => 'SummaryEarmarking.tpl'));
  }
}

/**
 * Implementation of hook civicrm_post to add default earmarking to new contributions
 *
 * @param string $op
 * @param string $objectName
 * @param int $objectId
 * @param object $objectRef
 */
function earmarking_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  /*
   * BOS1506293 if contribution created from recurring, default to earmarking of recurring
   */
  if ($objectName == 'Contribution' && $op == 'create') {
    if (!empty($objectRef->contribution_recur_id)) {
      $earmarkingId = CRM_Earmarking_Earmarking::getRecurringEarmarking($objectRef->contribution_recur_id);
      if ($earmarkingId) {
        CRM_Earmarking_Earmarking::addContributionEarmark($objectId, $earmarkingId);
      }
    }
  }
}