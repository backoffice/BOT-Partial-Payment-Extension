<?php

require_once 'payment.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function payment_civicrm_config(&$config) {
  _payment_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function payment_civicrm_xmlMenu(&$files) {
  _payment_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function payment_civicrm_install() {
  return _payment_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function payment_civicrm_uninstall() {
  return _payment_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function payment_civicrm_enable() {
  return _payment_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function payment_civicrm_disable() {
  return _payment_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function payment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _payment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function payment_civicrm_managed(&$entities) {
  return _payment_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function payment_civicrm_caseTypes(&$caseTypes) {
  _payment_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function payment_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _payment_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function payment_civicrm_process_partial_payments( $paymentParams, $participantInfo ) {
  foreach ( $participantInfo as $pId => $pInfo ) {
	if ( $pInfo['partial_payment_pay'] ) {
		if ( $pInfo['payLater'] ) {			
			$contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');	
			//Update contribution status from pending to partially paid
			$updateContribution = new CRM_Contribute_DAO_Contribution();
			$contributionParams = array ( 'id'                     => $pInfo['contribution_id'],
										  'contact_id'             => $pInfo['cid'],
							              'contribution_status_id' => array_search('Partially paid', $contributionStatuses)
							 );
			$updateContribution->copyValues($contributionParams);
			$t = $updateContribution->save();
			//Update participant Status from 'Pending from Pay Later' to 'Partially Paid'
			$pendingPayLater   = CRM_Core_DAO::getFieldValue( 'CRM_Event_BAO_ParticipantStatusType', 'Pending from pay later', 'id', 'name' );
			$partiallyPaid     = CRM_Core_DAO::getFieldValue( 'CRM_Event_BAO_ParticipantStatusType', 'Partially paid', 'id', 'name' );			
			$participantStatus = CRM_Core_DAO::getFieldValue( 'CRM_Event_BAO_Participant', $pId, 'status_id', 'id' );
			
			if ( $participantStatus == $pendingPayLater ) {
				CRM_Event_BAO_Participant::updateParticipantStatus($pId, $pendingPayLater, $partiallyPaid, TRUE );
			}			
		}
		
		//Add additional financial transactions for partial payments
		$paymentParams['total_amount'] = $pInfo['partial_payment_pay'];
		
               //recordAdditionalPayment method no longer supported as of CiviCRM 5.18.x
                //$trxnRecord = CRM_Contribute_BAO_Contribution::recordAdditionalPayment( $pInfo['contribution_id'], $paymentParams, 'owed', $pId );
                $paymentParams['participant_id']=$pId;
                $paymentParams['contribution_id']=$pInfo['contribution_id'];

		try {

			//Generating random transaction ID of length 12 characters
			$paymentParams['trxn_id'] = mt_rand(100000000000, 999999999999);
			//CRM_Core_Error::debug_var('new_paymentParams', $paymentParams);
			$trxnRecord = civicrm_api3('Payment', 'create', $paymentParams);
		}
		   catch (CiviCRM_API3_Exception $e) {
		      $error = $e->getMessage();
		      CRM_Core_Error::debug_var("Trxn Record", $trxnRecord);
		      CRM_Core_Error::debug_var("API Exception error",$error);
   		}
	}
  }
}
