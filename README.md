# BOT-Partial-Payment-Extension

This extension provides an ability to process multiple partial payments for event registrations.

* Components
  + Payment.php - process_partial_payments( ) function
   
* Steps to use this extension:
  + Make a call to the process_partial_payments() function from the User Interface Extension
  + This function needs two parameters:
      - Payment processor parameters
      - Partial payment information array - 
			Key: participantID
			Values:
				'cid' (contactID)
				'contribution_id' (contributionID)
				'payLater' (PayLater flag)
				'partial_payment_pay' (Partial Payment Amount)
	  - Example - $partialPaymentInfo = array ( 27 => array ( 'cid'                 => 44,
															  'contribution_id'     => 25,
															  'payLater'            => 0,
															  'partial_payment_pay' => 11.50 ));
  + Returns: Partial Payment Information array with an additional flag for 'Success' for each payment.

