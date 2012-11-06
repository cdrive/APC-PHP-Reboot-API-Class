<?php

	// this is the test file for cAPC Reboot
	error_reporting(E_ALL);
	ini_set('display_errors','On');

	require("cAPCReboot.php");
	
	// STEP 1: LOG IN TO THE APC DEVICE
	$apc = new apcReboot("IP", "USERNAME", "PASSWORD");
	
	// STEP 2: ENUMERATE THE DEVICES ATTACHED TO THIS APC DEVICE
	print_r($apc->enumerate());
	
	// STEP 3: WHICH TYPE OF OPERATION DO WE WANT? PICK THE ID HERE.
	print_r($apc->rebootOptions);
	
	$operationID = "6"; // this is a "reboot immediate"
	$deviceID = "?4,2"; // as picked from the "id" field in the enumerate array
	
	// STEP 4: PICK THE "id" FIELD FROM THE ENUMERATION ARRAY (PRINTED ABOVE).
	// USING THAT, PERFORM AN OPERATE REQUEST
	$apc->operate($operationID,$deviceID);
	
	// STEP 5: THIS IS REALLY IMPORTANT! IF YOU DON'T LOG OUT OF THE DEVICE, YOU WILL LOCK YOURSELF OUT FOR FUTURE REQUESTS.
	$apc->done();
?>
