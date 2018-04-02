<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3

// PayPal responds to pp-ipn
// pp-ipn
//   PayPAL_IPN_Reply
//   Process_IPN
//     Verify_IPN (gets data from database)
//     Reg_Pay (or Reg_Refund)
//       log_registration
//     Send_Emails

require_once 'olr-config.php';
require_once 'olr-db.php';
require_once 'olr-lib.php';

class Online_Reg_PP_IPN_Class{
  var $config, $regdb, $reglib, $db_result;

  // Constructor
  function Online_Reg_PP_IPN_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->regdb = new Online_Reg_DB_Class();
    $this->db_result = null;
  } // Online_Reg_PP_IPN_Class constructor

   function PayPAL_IPN_Reply(){
      
      $result = false;
      $errno = 0;
      $errstr = "";
      $errmsg = "";
      $value = "";
      $weberrors = "";

     // Check for invoice post variable
      $this->reglib->InvoiceID = "";
      if ( !isset($_POST['invoice']) || $_POST['invoice'] == "" ) {
         $errmsg .= "\nMissing Invoice ID";
         $this->reglib->log_ipn("***** PayPAL_IPN_Reply Error: $errmsg", 1);
         $weberrors .= "\nPayPAL_IPN_Reply Error: $errmsg";
      } else { // simple check for sql injection: no spaces
          $temp = trim($_POST['invoice']);
          if (strpos($temp, " ") > 0) {
            $errmsg .= "Invalid Invoice ID: [" . $_POST['invoice'] . "]";
            $this->reglib->log_ipn("***** PayPAL_IPN_Reply Error: $errmsg", 1);
            $weberrors .= "\nPayPAL_IPN_Reply Error: $errmsg";
          } // if space in InvoiceID
          else {$this->reglib->InvoiceID = $_POST['invoice']; }
      } // else not empty

      // If error(s), report them and quit
      if ( $weberrors != "" ) {
         $this->reglib->log_ipn("PayPAL_IPN_Reply Aborted with Error", 0);
         $this->reglib->Report_Web_Errors($weberrors, "");
         return(false);
      } // if $weberrors

    // Read POST data
    // Reading data from $_POST causes serialization issues 
    // with array data in POST. 
    // Read raw POST data from input stream instead.
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
      $keyval = explode ('=', $keyval);
      if (count($keyval) == 2){
        $myPost[$keyval[0]] = urldecode($keyval[1]);
      }
    }
    // read the post from PayPal system and add 'cmd'
    $req = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) {
      $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
      if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
      } else {
        $value = urlencode($value);
      }
      $req .= "&$key=$value";
    }
      
      $this->reglib->log_ipn("--- IPN Received", 1);

      // post back to PayPal system to validate
      $this->reglib->log_ipn("PayPAL_IPN_Reply posting back to PayPal to validate", 0);
      $ch = curl_init($this->config->c_paypal_ipn_url);
      if (!$ch) {
         $this->reglib->log_ipn("***** PayPAL_IPN_Reply Error connecting to PayPAL", 0);
         $this->reglib->Report_Web_Errors("PayPAL_IPN_Reply Error connecting to PayPAL", "");
         return false;
      }
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
      // Use TLS 1.2 for increased security
      curl_setopt($ch, CURLOPT_SSLVERSION, 6); // CURL_SSLVERSION_TLSv1_2 DOES NOT WORK!
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
      // Set TCP timeout to 30 seconds
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

      //curl_setopt($ch, CURLOPT_FAILONERROR, 1);
      //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      //curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
      //curl_setopt($ch, CURLOPT_VERBOSE, 1);
      //curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
      //curl_setopt($ch, CURLOPT_PORT, 443);

      // For debugging only. Displays full response header and content.
      //curl_setopt($ch, CURLOPT_HEADER, 1);
      //curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

      // CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" 
      // and set the directory path of the certificate as shown below. 
      // Ensure the file is readable by the webserver.
      // This is mandatory for some environments.
      // DO NOT USE. Causes Error 77.
      //$cert = __DIR__ . "./cacert.pem";
      //curl_setopt($ch, CURLOPT_CAINFO, $cert);

      $ipnres = curl_exec($ch);
      $errstr = curl_error($ch);
      $info = curl_getinfo($ch);
      $httpcode = "";
      if ($info){ $httpcode = $info['http_code'];}
      if (!$ipnres) {
         // cURL ERROR
         $this->reglib->log_ipn("***** PayPAL_IPN_Reply Error getting " .
         "validation response from PayPAL : Error = |$errstr|, HTTP Code = |$httpcode|", 0);
         $this->reglib->Report_Web_Errors("PayPAL_IPN_Reply Error getting " .
         "validation response from PayPAL", "Error = |$errstr|, HTTP Code = |$httpcode|");
         curl_close($ch);
      } else {
         $this->reglib->log_ipn("PayPAL_IPN_Reply: HTTP Code = |$httpcode|, Response = |$ipnres|, ", 0);
         curl_close($ch);
          if (strcmp ($ipnres, "VERIFIED") == 0) {
             $this->reglib->log_ipn("Reply VERIFIED", 0);
             $result = true;
          } else if (strcmp ($ipnres, "INVALID") == 0) {
             $this->reglib->log_ipn("Reply INVALID", 1);
             $this->reglib->Report_Web_Errors("PayPAL_IPN_Reply INVALID " .
             "Response for Invoice |" . $this->reglib->InvoiceID . "|", "");
          // log for manual investigation
          } else {
              $this->reglib->Report_Web_Errors("PayPAL_IPN_Reply Unknown Response: |$ipnres|", "");
              $this->reglib->log_ipn("PayPAL_IPN_Reply Unknown Response: |$ipnres|", 1);
          } // else
      } // else no error

      if ($result){
        $this->reglib->log_ipn("PayPAL_IPN_Reply Result = TRUE", 0);
      }else {
        $this->reglib->log_ipn("PayPAL_IPN_Reply Result = FALSE", 0);
      }
      return($result);
   } // PayPAL_Reply

   function Verify_IPN(){

      $rtnval = true;
      $weberrors = "";

      $this->reglib->log_ipn("Verify_IPN Start", 0);

      // Check for invoice post variable
      $this->reglib->InvoiceID = "";
      $temp = $_POST['invoice'];
      if ( !isset($_POST['invoice']) || $_POST['invoice'] == "" ) {
         $errmsg = "Missing Invoice ID";
         $this->reglib->log_ipn("***** $errmsg", 1);
         $weberrors .= "\nPayPAL Verify_IPN Error: $errmsg";
      } else { // simple check for sql injection: no spaces
          $temp = trim($_POST['invoice']);
          if (strpos($temp, " ") > 0) {
            $errmsg = "Invalid Invoice ID: [" . $_POST['invoice'] . "]";
            $this->reglib->log_ipn("***** $errmsg", 1);
            $weberrors .= "\nPayPAL Verify_IPN Error: $errmsg";
          } // if space in InvoiceID
          else {$this->reglib->InvoiceID = $_POST['invoice']; }
      } // else not empty

     // Check for item_name post variable
      if ( !isset($_POST['item_name']) 
          || $_POST['item_name'] != $this->config->c_EventName ) {
         $errmsg = "Incorrect item_name for invoice ";
         $errmsg .= $this->reglib->InvoiceID . ": |";
         $errmsg .= $_POST['item_name'] . "| instead of |" . 
            $this->config->c_EventName . "|";
         $this->reglib->log_ipn("***** $errmsg", 1);
         $weberrors .= "\nPayPAL Verify_IPN Error: $errmsg";
      } // if item_name
   
      // Check for business email post variable
      if ( !isset($_POST['receiver_email']) 
          || $_POST['receiver_email'] != $this->config->c_business_email ) {
         $errmsg = "Invalid Business Email for invoice ";
         $errmsg .= $this->reglib->InvoiceID . ": |" . 
            $_POST['receiver_email'] . "|";
         $this->reglib->log_ipn("***** $errmsg", 1);
         $weberrors .= "\nPayPAL Verify_IPN Error: $errmsg";
      } // if receiver_email
   
      // Check for payment status post variable
      $this->reglib->PaymentStatus = "";
      if ( ( !isset($_POST['payment_status'])) 
          || ($_POST['payment_status'] == "" )) {
         $errmsg = "Missing Payment Status";
         $this->reglib->log_ipn("***** $errmsg", 1);
         $weberrors .= "\nPayPAL Verify_IPN Error: $errmsg";
      } else { $this->reglib->PaymentStatus = $_POST['payment_status']; }
   
      // If error(s), report them and quit
      if ( $weberrors != "" ) {
         $this->reglib->log_ipn("Verify_IPN Aborted with Error", 0);
         $this->reglib->Report_Web_Errors($weberrors, "");
         return(false);
      } // if $weberrors
   
      // Get data for this registration, via Invoice ID
      $dberr = $this->regdb->db_connect();
      if ($dberr != "") {
         $errmsg = "Error connecting to DB";
         $this->reglib->log_ipn("***** $errmsg", 0);
         $this->reglib->Report_Web_Errors("PayPAL Verify_IPN Error: $errmsg", $dberr);
         return(false);
      } // if $dberr

      $sql = "SELECT * from " . $this->config->c_dbtable;
      $sql .= " WHERE txt_invoiceid = '";
      $sql .= $this->reglib->InvoiceID . "' ORDER By num_regnum;";
      
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
         $errmsg = "Error getting data for Invoice [" . $this->reglib->InvoiceID . "]";
         $this->reglib->log_ipn("***** $errmsg", 0);
         $this->reglib->Report_Web_Errors("PayPAL Verify_IPN Error: $errmsg", 
            mysqli_error($this->regdb->dbh));
         $this->regdb->db_close($this->db_result);
         return(false);
      } // if no db_result
   
      if (mysqli_num_rows($this->db_result) <= 0){
         $errmsg = "No data found for invoice " . $this->reglib->InvoiceID;
         $this->reglib->log_ipn("***** $errmsg", 0);
         $this->reglib->Report_Web_Errors("PayPAL Verify_IPN Error: $errmsg", "");
         $this->regdb->db_close($this->db_result);
         return(false);
      } // if now data rows
      return(true);
  } // Verify_IPN
  
  function GetRegData(){

    $row = mysqli_fetch_assoc($this->db_result);
    $this->reglib->TransactionID = $row['txt_transactionid'];
    $this->reglib->RegNum = $row['num_regnum'];
    if ($this->config->c_Use_Do_Not_Share){
      $this->reglib->ShareData = $row['chk_sharedata'];
    } // if use do not share
    $this->reglib->NumPersons = $row['num_numpersons'];
    $this->reglib->Email = $row['txt_email1'];

    // Get Person data
    for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
      $this->reglib->FNameAry[$ii] = $row['txt_firstname' . $ii];
      $this->reglib->LNameAry[$ii] = $row['txt_lastname' . $ii];
      if ($this->config->c_UseGenderData) {
        $this->reglib->GenderAry[$ii] = $row['rad_gender' . $ii];
      }
      if ($this->config->c_UseChildData && $ii > 1) {
        $this->reglib->ChildChkAry[$ii] = $row['chk_child' . $ii];
        if ($this->config->c_UseChildAge) {
          $this->reglib->ChildAgeAry[$ii] = $row['num_childage' . $ii];
        } // if UseChildAge
      } // if UseChildData
      if ($this->config->c_UseStudentData) {
        $this->reglib->StudentAry[$ii] = $row['chk_student' . $ii];
      } // if
    } // for
    
    // Phone fields
    $iimax = count($this->reglib->PhoneData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->PhoneData[$ii]["name"];
      $this->reglib->PhoneData[$ii]["value"] = $row[$ctl_name];
      $ctl_name .= "_type";
      $this->reglib->PhoneData[$ii]["type"] = $row[$ctl_name];
    } // for each phone

    // Other data fields.
    // DB column name = Ctrl field name minus 3-char prefix.
    // DB column names are NOT case sensitive
    for ($ii = 0; $ii < $this->reglib->NumDQtys; $ii++) {
      $ctl_name = $this->reglib->GetFieldCtrlName("data", $ii);
      $this->reglib->DataAry[$ii] = $row[$ctl_name];
    } // for

    // Radio Button fields
    $iimax = count($this->reglib->RadioData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->RadioData[$ii]["name"];
      $this->reglib->RadioData[$ii]["value"] = $row[$ctl_name];
    } // for each radio button

    // Selection List fields
    $iimax = count($this->reglib->SelListData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->SelListData[$ii]["name"];
      $this->reglib->SelListData[$ii]["value"] = $row[$ctl_name];
    } // for each selection list

    // Get Musician Data
    $this->reglib->MusDataAry = array();
    $jjmax = count($this->config->c_aryMusician_Fields);
    for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
      for ($jj = 0; $jj < $jjmax; $jj++) {
        $ctlname = $this->reglib->GetFieldCtrlName("musician", $jj) . $ii;
        $this->reglib->MusDataAry[$ctlname] = $row[$ctlname];
      } // for jj
    } // for ii
      
    // Get Caller Data
    $this->reglib->CallerDataAry = array();
    $jjmax = count($this->config->c_aryCaller_Fields);
    for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
      for ($jj = 0; $jj < $jjmax; $jj++) {
        $ctlname = $this->reglib->GetFieldCtrlName("caller", $jj) . $ii;
        $this->reglib->CallerDataAry[$ctlname] = $row[$ctlname];
      } // for jj
    } // for ii
      
    // Get Attendance quantity data
    $jj = 0;
    for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++) {
      $jj = $ii +1;
      $this->reglib->AttendAry[$ii] = $row['num_aqty' . $jj];
    } // for

    // Get Merchandise quantity data
    for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++) {
      $jj = $ii +1;
      $this->reglib->MerchAry[$ii] = $row['num_mqty' . $jj];
    } // for

    // Get membership fees
    $this->reglib->Membership_Single_Qty = 0;
    $this->reglib->Membership_Family_Qty = 0;
    if ($this->config->c_member_single_price > 0 
        || $this->config->c_member_family_price > 0){
      $this->reglib->Membership_Single_Qty = $row['num_memsingleqty'];
      $this->reglib->Membership_Family_Qty = $row['num_memfamilyqty'];
    } // if membership prices

    // Early Registration discount
    if ($this->config->c_early_reg_discount != 0){
       $this->reglib->EarlyRegQty = $row['num_earlyregqty'];
    }
      
    // Late Registration fee
    if ($this->config->c_late_reg_fee != 0){
       $this->reglib->LateRegQty = $row['num_lateregqty'];
    }
    
    // Deposit
    if ($this->config->c_DepositPrice > 0){
      $this->reglib->Deposit = $row['num_deposit'];
      $this->reglib->BalDue = $row['num_balancedue'];
    }

    // Donation
    if ($this->config->c_UseDonation){
      $this->reglib->Donation = $row['num_donation'];
    }

    $this->reglib->PayPalFeePay = 0;
    if ($this->config->c_UsePayPalFee) {
      $this->reglib->PayPalFeePay = $row['chk_paypalfeepay'];
    }
    $this->reglib->PayPalFee = $row['num_paypalfee'];
    $this->reglib->PayPalGross = $row['num_paypalgross'];
    $this->reglib->TotalGross = $row['num_totalgross'];
    $this->reglib->Status = $row['txt_status'];
    $this->reglib->SubmitDate = $row['txt_submitdate'];
    $this->reglib->StatusDate = $row['txt_statusdate'];
    $this->regdb->db_close($this->db_result);

    $this->reglib->log_ipn("Verify_IPN OK", 0);

    return(true);
  } // GetRegData

  function Reg_Pay(){
    
    $weberrors = "";
    
    // Check for the Transaction ID
    if ( ! isset($_POST['txn_id']) || $_POST['txn_id'] == "") {
      $errmsg = "Reg_Pay: Missing Transaction ID for Invoice |";
      $errmsg .= $this->reglib->InvoiceID . "|";
      $this->reglib->log_ipn("***** $errmsg", 1);
      $weberrors .= "\nPayPAL IPN Error: $errmsg";
    } // if no txn_id
    // Verify Transaction ID has no spaces
    else {
      $temp = trim($_POST['txn_id']);
      if (strpos($temp, " ") > 0) {
        $errmsg = "Reg_Pay: Invalid Transaction ID [" . $_POST['txn_id'] . "]";
        $errmsg .= " for Invoice |" . $this->reglib->InvoiceID . "|";
        $this->reglib->log_ipn("***** $errmsg", 1);
        $weberrors .= "\nPayPAL IPN Error: $errmsg";
      } // if space in Transaction ID
    } // else Transaction ID

    // Check for the PayPAL fee amount
    if ( ! isset($_POST['mc_fee']) || ($_POST['mc_fee'] == "")) {
      $errmsg = "Reg_Pay: Missing PayPAL Fee for Invoice |";
      $errmsg .= $this->reglib->InvoiceID . "|";
      $this->reglib->log_ipn("***** $errmsg", 1);
      $weberrors .= "\nPayPAL IPN Error: $errmsg";
    } else { $this->reglib->PayPalFee = $_POST['mc_fee']; }
    
    // Check for the total amount.
    $price = $this->reglib->PayPalGross;
    if ( ! isset($_POST['mc_gross']) || $price != $_POST['mc_gross']) {
      $errmsg = "Reg_Pay: Incorrect Price for Invoice ";
      $errmsg .= $this->reglib->InvoiceID . ": Saved=$price, ";
      $errmsg .= "PayPAL=|" . $_POST['mc_gross'] . "|";
      $this->reglib->log_ipn("***** $errmsg", 1);
      $weberrors .= "\nPayPAL IPN Error: $errmsg";
    } // if no mc_gross
    
    // If error(s), report them and quit
    if ( $weberrors != "" ) {
      $this->reglib->log_ipn("Reg_Pay Aborted with Error", 0);
      $this->reglib->Report_Web_Errors($weberrors, "");
      return(false);
    } // if $weberrors
    
    // Check for the duplicate transaction
    $this->reglib->Duplicate = false;
    if ( isset($_POST['txn_id']) 
        && $this->reglib->TransactionID == $_POST['txn_id'] 
        && $this->reglib->Status == "Paid" 
        && $_POST['payment_status'] == "Completed") {
      $errmsg = "Duplicate Payment for Invoice " . $this->reglib->InvoiceID;
      $this->reglib->log_ipn("***** $errmsg", 0);
      $this->reglib->Duplicate = true;
    } // if duplicate
    
    // Report error if this is a duplicate.
    if ($this->reglib->Duplicate) {
      $this->reglib->Report_Web_Errors("PayPAL IPN Reg_Pay Error: " .
      "Duplicate Payment for Invoice |" . 
      $this->reglib->InvoiceID . "|, IGNORED", "");
      return(true);
    } // if duplicate
    
    $dberr = $this->regdb->db_connect();
    if ($dberr != "") {
      $errmsg = "Error in Reg_Pay connecting to DB";
      $this->reglib->log_ipn("***** $errmsg", 0);
      $this->reglib->Report_Web_Errors("PayPAL IPN Reg_Pay Error: $errmsg", $dberr); 
      return(false);
    } // if $dberr
    
    // Fill in PayPAL Fee and Net for record with this InvoiceID
    $this->reglib->TransactionID = $_POST['txn_id'];
    $this->reglib->Status = "Paid";
    $this->reglib->StatusDate = $this->config->c_today;
    $this->reglib->TotalNet = $this->reglib->PayPalGross - $this->reglib->PayPalFee;
    $sql = "UPDATE " . $this->config->c_dbtable;
    $sql .= " Set txt_transactionid = '". $_POST['txn_id'] . "', "; 
    $sql .= "num_paypalfee = '" . sprintf("%.2f", $this->reglib->PayPalFee);
    $sql .= "', num_totalnet = '" . sprintf("%.2f", $this->reglib->TotalNet);
    $sql .= "', num_statusid = '";
    $sql .= $this->reglib->AryStatusIDs[$this->reglib->Status];
    $sql .= "', txt_status = '" . $this->reglib->Status;
    $sql .= "', txt_statusdate = '" . $this->config->c_today . "'";
    $sql .= "WHERE txt_invoiceid = '" . $this->reglib->InvoiceID . "';";
    
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $errmsg = "Error in Reg_Pay updating Fee/Net/Status data for Invoice |";
      $errmsg .= $this->reglib->InvoiceID . "|";
      $errmsg .= ",\nSQL=|" . $sql . "|";
      $errmsg .= ",\SQL Error=|" . mysqli_error($this->regdb->dbh) . "|";
      $this->reglib->log_ipn("***** $errmsg", 0);
      $this->reglib->Report_Web_Errors("PayPAL IPN Reg_Pay Error: $errmsg", 
        mysqli_error($this->regdb->dbh));
      return(false);
    } // if no $dbresult
    $this->reglib->log_registration();
    return(true);
  } // Reg_Pay


  function Reg_Refund(){
    
    $weberrors = "";

    // Check for the Transaction ID
    if ( ! isset($_POST['txn_id']) || $_POST['txn_id'] == "") {
      $errmsg = "Reg_Refund: Missing Transaction ID for Invoice |";
      $errmsg .= $this->reglib->InvoiceID . "|";
      $this->reglib->log_ipn("***** $errmsg", 1);
      $weberrors .= "\nPayPAL IPN Error: $errmsg";
    } // if no txn_id
    // Verify Transaction ID has no spaces
    else {
      $temp = trim($_POST['txn_id']);
      if (strpos($temp, " ") > 0) {
        $errmsg = "Reg_Refund: Invalid Transaction ID [" . $_POST['txn_id'] . "]";
        $errmsg .= " for Invoice |" . $this->reglib->InvoiceID . "|";
        $this->reglib->log_ipn("***** $errmsg", 1);
        $weberrors .= "\nPayPAL IPN Error: $errmsg";
      } // if space in Transaction ID
    } // else Transaction ID

    // If error(s), report them and quit
    if ( $weberrors != "" ) {
      $this->reglib->log_ipn("Reg_Refund Aborted with Error", 0);
      $this->reglib->Report_Web_Errors($weberrors, "");
      return(false);
    } // if $weberrors

    // Check for the duplicate or invalid transaction
    if ( isset($_POST['txn_id']) 
        && $this->reglib->TransactionID == $_POST['txn_id'] 
        && $this->reglib->Status != "Paid" 
        && $_POST['payment_status'] == "Refunded") {
      $errmsg = "Refund received for Invoice ";
      $errmsg .= $this->reglib->InvoiceID . ",\nwith current Status ";
      $errmsg .= $this->reglib->Status . " instead of Paid.";
      $this->reglib->log_ipn("***** $errmsg", 1);
      $this->reglib->Report_Web_Errors("PayPAL IPN Reg_Refund Error: $errmsg", "");
      return(false);
    } // if no txn_id
    
    $dberr = $this->regdb->db_connect();
    if ($dberr != "") {
      $this->reglib->Report_Web_Errors("PayPAL IPN Error in Reg_Refund " .
        "connecting to DB", $dberr); 
      return(false);
    }
    
    // Change Status to Refund for this invoice
    $this->reglib->Status = "Refunded";
    $this->reglib->StatusDate = $this->config->c_today;
    $sql = "UPDATE " . $this->config->c_dbtable . " Set num_statusid = "; 
    $sql .= $this->reglib->AryStatusIDs[$this->reglib->Status];
    $sql .= ", txt_status = '" . $this->reglib->Status;
    $sql .= "', txt_statusdate =  '" . $this->config->c_today . "', ";
    $sql .= "num_paypalgross = '";
    $sql .= sprintf("%.2f", ($this->reglib->PayPalGross + $_POST['mc_gross']));
    $sql .= "', num_paypalfee = '";
    $sql .= sprintf("%.2f", ($this->reglib->PayPalFee + $_POST['mc_fee']));
    $sql .= "', num_totalnet = '";
    $sql .= sprintf("%.2f", ($this->reglib->TotalNet + 
      $_POST['mc_gross'] - $_POST['mc_fee']));
    $sql .= "' WHERE txt_invoiceid = '" . $this->reglib->InvoiceID . "';";
    
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $errmsg = "Error in Reg_Refund updating Fee/Net/Status data for Invoice |";
      $errmsg .= $this->reglib->InvoiceID . "|";
      $errmsg .= ",\nSQL=|" . $sql . "|";
      $errmsg .= ",\SQL Error=|" . mysqli_error($this->regdb->dbh) . "|";
      $this->reglib->log_ipn("***** $errmsg", 0);
      $this->reglib->Report_Web_Errors("PayPAL IPN Reg_Refund Error: $errmsg", 
        mysqli_error($this->regdb->dbh));
      return(false);
    } // if no dbresult
    $this->reglib->log_registration();
    return(true);
  } // Reg_Refund

  function Process_IPN(){
    $this->reglib->log_ipn("Process_IPN Start", 0);
    if ($this->Verify_IPN()) {
      $this->GetRegData();
      $this->reglib->log_ipn("Payment Status = " . 
        $this->reglib->PaymentStatus, 0);
      if ($this->reglib->PaymentStatus == "Completed") {
        if ($this->Reg_Pay()) {$this->reglib->Send_Emails(0);}
      } // if completed
      else if ($this->reglib->PaymentStatus == "Refunded") {
        if ($this->Reg_Refund()) {$this->reglib->Send_Emails(0);}
      } // else if refunded
      else {
        $errmsg = "Unknown Payment Status for Invoice ";
        $errmsg .= $this->reglib->InvoiceID . ": |" . 
          $this->reglib->PaymentStatus . "|";
        $this->reglib->log_ipn("***** $errmsg", 1);
        $this->reglib->Report_Web_Errors("PayPAL IPN Error: $errmsg", "");
      } // else other status
    } // if Verify_IPN
    $this->reglib->log_ipn("Process_IPN End", 0);
  } // Process_IPN

  function main(){
    
    $hdr = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' ";
    $hdr .= "'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
    $hdr .= "<html>\n";
    $hdr .= "<head>\n";
    $hdr .= "<title>PayPAL IPN</title>\n";
    $hdr .= "<meta http-equiv='Content-Type' content='text/html; ";
    $hdr .= "charset=utf-8' />\n";
    $hdr .= "</head>\n";
    $hdr .= "<body>\n";
    echo($hdr);
    
    echo("<p>PayPAL IPN Processing for " . $this->config->c_EventName . "</p>");
    
    echo "\n</body>\n</html>\n";
    
    if ($this->PayPAL_IPN_Reply()) {
       $this->Process_IPN();
    } // if
  } // main
} // Online_Reg_PP_IPN_Class

$ppipn = new Online_Reg_PP_IPN_Class();
$ppipn->main();

?>