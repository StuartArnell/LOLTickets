<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3

// Updated for PHP 5.4.
// Class for holding all the registration data
// and common functions for processing it.

require_once 'olr-config.php';

class Online_Reg_Lib_Class{
   var $RegNum, $InvoiceID, $Status;
   var $NumPersons, $FNameAry, $LNameAry, $GenderAry, $Email, $PhoneData;
   var $ChildChkAry, $ChildAgeAry, $StudentAry;
   var $ShareData;
   var $NumAQtys, $AttendAry, $EarlyRegQty, $LateRegQty;
   var $NumMQtys, $MerchAry;
   var $NumDQtys, $DataAry, $RequiredAry;
   var $MusDataAry, $CallerDataAry;
   var $Deposit, $Donation;
   var $PayPalFeePay, $PayPalFee, $PayPalGross;
   var $TotalGross, $TotalNet, $BalDue;
   var $TransactionID, $PaymentStatus, $Duplicate;
   var $config, $dbh, $db_result;
   var $Membership_Single_Qty, $Membership_Family_Qty;
   var $RadioData, $SelListData;
   var $AryStatusIDs = array("Paid"=>1, "Pending"=>2, "Refunded"=>3, "Other"=>4);
   var $mail_headers;
   var $SubmitDate, $StatusDate;


   // Constructor
   function Online_Reg_Lib_Class(){

      $this->config = new Online_Reg_Config_Class();
      $this->dbh = null;
      $this->db_result = null;
      $this->InvoiceID = "";
      $this->Duplicate = false;
      $this->PayPalFeePay = 0;
      $this->PayPalFee = 0;
      $this->PayPalGross = 0;
      $this->TotalGross = 0;
      $this->TotalNet = 0;
      $this->BalDue = 0;
      $this->NumPersons = 0;
      $this->EarlyRegQty = 0;
      $this->LateRegQty = 0;
      $this->TransactionID = "";
      $this->Email = "";
      $this->Membership_Single_Qty = 0;
      $this->Membership_Family_Qty = 0;
      $this->FNameAry = array();
      $this->LNameAry = array();
      $this->GenderAry = array();
      $this->DataAry = array();
      $this->MusDataAry = array();
      $this->CallerDataAry = array();
      $this->RequiredAry = array();
      $this->Deposit = 0;
      $this->Donation = 0;
      $this->mail_headers = "From: " . $this->config->c_payment_email . "\r\n";
      $this->mail_headers .= "Reply-To: " . $this->config->c_payment_email . "\r\n";
      $this->SubmitDate = $this->config->c_today;
      $this->StatusDate = $this->config->c_today;

      // Initalize required fields.
      $this->RequiredAry[0] = "txt_firstname1";
      $this->RequiredAry[1] = "txt_lastname1";
      if ($this->config->c_UseGenderData){ $this->RequiredAry[2] = "rad_gender1"; }
      $rr = count($this->RequiredAry); // index for $RequiredAry

      // Initalize array of data values, and get required fields.
      $iimax = count($this->config->c_aryField_Defs);
      $this->NumDQtys = $iimax;
      for ($ii = 0; $ii < $iimax; $ii++){
        $this->DataAry[$ii] = "";
        if (array_key_exists("required", $this->config->c_aryField_Defs[$ii])){
          $this->RequiredAry[$rr] = $this->GetFieldCtrlType("data", $ii) . 
          $this->config->c_aryField_Defs[$ii]["name"];
          $rr += 1;
        } // if required
      } // for

      // Initalize array of Phone values, and get required fields.
      $iimax = count($this->config->c_aryPhone_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->GetFieldCtrlName("phone", $ii);
        $this->PhoneData[$ii] = array("name" => $ctl_name, 
          "label" => $this->config->c_aryPhone_Fields[$ii]["label"],
          "value" => "", "type" => "");
        if (array_key_exists("required", $this->config->c_aryPhone_Fields[$ii])){
          $this->RequiredAry[$rr] = $ctl_name;
          $rr += 1;
        } // if required
      } // for each phone number

      // Initalize array of Radio Button values, and get required fields.
      $iimax = count($this->config->c_aryRadio_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->GetFieldCtrlName("radio", $ii);
        $this->RadioData[$ii] = array("name" => $ctl_name, 
          "value" => "", "label" => $this->config->c_aryRadio_Fields[$ii]["label"]);
        if (array_key_exists("required", $this->config->c_aryRadio_Fields[$ii])){
          $this->RequiredAry[$rr] = $this->GetFieldCtrlType("radio", $ii) . 
          $this->config->c_aryRadio_Fields[$ii]["name"];
          $rr += 1;
        } // if required
      } // for each radio button

      // Initalize array of Selection List values, and get required fields.
      $iimax = count($this->config->c_arySelect_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->GetFieldCtrlName("select", $ii);
        $this->SelListData[$ii] = array("name" => $ctl_name, 
          "value" => "", "label" => $this->config->c_arySelect_Fields[$ii]["label"]);
        if (array_key_exists("required", $this->config->c_arySelect_Fields[$ii])){
          $this->RequiredAry[$rr] = $this->GetFieldCtrlType("select", $ii) . 
          $this->config->c_arySelect_Fields[$ii]["name"];
          $rr += 1;
        } // if required
      } // for each radio button

      // Initalize array of attendance quantities.
      $iimax = count($this->config->c_aryAttendance_Fields);
      $this->NumAQtys = $iimax;
      for ($ii = 0; $ii < $iimax; $ii++){
         $this->AttendAry[$ii] = 0;
      } // for

      // Initalize array of merchandise quantities.
      $this->NumMQtys = 0;
      $this->MerchAry = null;
      $iimax = count($this->config->c_aryMerchandise_Fields);
      if ($iimax > 0){
        $this->NumMQtys = $iimax;
        for ($ii = 0; $ii < $iimax; $ii++){
           $this->MerchAry[$ii] = 0;
        } // for
      } // if merchandise fields
  } // RegData_Class constructor
  
  function CtlIsUsed($ctlName){
     $result = -1;
      for ($ii = 0; $ii < $this->NumDQtys; $ii++) {
         if ( $ctlName == $this->config->c_aryField_Defs[$ii]["name"]) {
            $result = $ii;
            break;
         }
      }
      return($result);
  } // CtlIsUsed

  function GetFieldCtrlName($which, $index){
    $farray = $this->config->c_aryField_Defs;
    $prefix = "";
    switch ($which){
      case "phone":
        $farray = $this->config->c_aryPhone_Fields;
        $prefix = "txt_";
        break;
      case "radio":
        $farray = $this->config->c_aryRadio_Fields;
        $prefix = "rad_";
        break;
      case "select":
        $farray = $this->config->c_arySelect_Fields;
        $prefix = "sel_";
        break;
      case "musician": $farray = $this->config->c_aryMusician_Fields; break;
      case "caller": $farray = $this->config->c_aryCaller_Fields; break;
      default: $farray = $this->config->c_aryField_Defs; break;
    } // switch for $which
    
    if ($prefix == ""){
      $ftype = $farray[$index]["type"];
      switch ($ftype){
        case "num": $prefix = "num_"; break;
        case "check": $prefix = "chk_"; break;
        default: $prefix = "txt_";
      } // switch for $ftype
    } // if prefix empty

    $ctlName = $prefix . $farray[$index]["name"];

    return($ctlName);
  } // GetFieldCtrlName

  function GetFieldCtrlType($which, $index){
     
    $farray = $this->config->c_aryField_Defs;
    $prefix = "";
    switch ($which){
      case "phone":
        $farray = $this->config->c_aryPhone_Fields;
        $prefix = "txt_";
        break;
      case "radio":
        $farray = $this->config->c_aryRadio_Fields;
        $prefix = "rad_";
        break;
      case "select":
        $farray = $this->config->c_arySelect_Fields;
        $prefix = "sel_";
        break;
      case "musician": $farray = $this->config->c_aryMusician_Fields; break;
      case "caller": $farray = $this->config->c_aryCaller_Fields; break;
      default: $farray = $this->config->c_aryField_Defs; break;
    } // switch for $which
    
    if ($prefix == ""){
      $ftype = $farray[$index]["type"];
      switch ($ftype){
        case "num": $prefix = "num_"; break;
        case "check": $prefix = "chk_"; break;
        default: $prefix = "txt_";
      } // switch for $ftype
    } // if prefix empty

    return($prefix);
  } // GetFieldCtrlType

   function FieldValue($fvalue, $ftype, $format){
    // Outputs field value in friendly manner,
    // depending on data type and format type.
    // Checkboxes are output as 0/1 or Yes/No.
    // Empty number fields are output as 0.
    // Empty text fields are ouput as "None".
    $rvalue = $fvalue;
    switch ($ftype){
      case "check":
        if ($fvalue == "") {
          if ($format == "text") { $rvalue = "No"; }
          else { $rvalue = 0; }
        } elseif ($fvalue == 0) {
            if ($format == "text") { $rvalue = "No"; }
            else { $rvalue = 0; }
        } else {
            if ($format == "text") { $rvalue = "Yes"; }
            else { $rvalue = 1; }
        } // else not empty
        break;
      case "num":
        if ($fvalue == "") { $rvalue = 0; }
        break;
      default: // text of some sort
        if ($fvalue == "" && $format == "text") { $rvalue = "None"; }
    } // switch $ftype
    return($rvalue);
   } // FieldValue


  function Ctrl_Is_Required($ctl_name){
    $jjmax = count($this->RequiredAry);
    $required = 0;
    // Determine if the control is required
    for ($jj = 0; $jj < $jjmax; $jj++) {
      if ($ctl_name == $this->RequiredAry[$jj]) {
        $required = 1;
        break;
      } // if
    } // for $jj required fields
    return($required);
  } // Ctrl_Is_Required

   function Report_Web_Errors($err_msg, $err_detail){
      
      // Skip sending messages for SiteLockSpider scans
      $ua = $_SERVER["HTTP_USER_AGENT"];
      if (strripos($ua, "SiteLockSpider") !== false) {return;}
      
      $msg_body = "Error in " . $this->config->c_EventName;
      $msg_body .= " Registration:\n\n" . $err_msg . "\n\n";
      
      // Output message detail if any
      if ($err_detail != "") { 
         $msg_body .= "Details:\n\n" . $err_detail . "\n"; 
      }
      
      // Output POST values if any
      $msg_body .= "\n\nPOST values:\n";
      if (count($_POST) == 0 ) {$msg_body .= "NONE"; }
      else {
         foreach ($_POST as $key => $value){
            $msg_body .= "Key=|$key|, Value=|$value|\n";
         } // foreach
      } // else POST not empty
      
      // Output SERVER/Header values
      $msg_body .= "\n\nSERVER/HEADER values:\n";
      foreach($_SERVER as $key => $value){
        if (is_array($value)) { $tempv = implode(", ", $value); }
        else { $tempv = $value;}
        $msg_body .= "Key=|$key|, Value=|$tempv|\n";
      }

      $mail_to = $this->config->c_webmaster_email;
      $mail_subject = "ERROR in " . 
        str_replace("'", "", $this->config->c_EventName) . " Registration";
      $status = "";
      if ($this->config->c_webmaster_email_errors){
        $status = mail("$mail_to", "$mail_subject", "$msg_body", "$this->mail_headers");
      } else {$this->Report_User_Errors($msg_body);}
   } // Report_Web_Errors


   function Report_User_Errors($msg){

      echo("<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' ");
      echo("http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n");
      echo("<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>\n");
      echo("<head>\n");
      echo("<title>" . $this->config->c_EventName);
      echo(" Online Registration Error</title>\n");
      echo("<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n");
      echo("<meta http-equiv='Content-Script-Type' content='text/javascript' />\n");
      echo("</head>\n");
      echo("<body>\n");
      echo("<p style='color: red; font-weight: bold;'>\n");
      echo($this->config->c_EventName . " Registration ERROR:\n");
      echo("<br /><br/>$msg\n");
      echo("<br /><br /><a href='Javascript:history.back();'>BACK</a>\n");
      echo("</body>\n");
      echo("</html>\n");
      exit(0);
   } // Report_User_Errors


   function log_ipn($msg, $include_post){
      $now = date("Y_m_d_H_i_s");
      $fh = "";

     // If log file does not exist, create it.
     // Set file permissions to disallow direct viewing.
      if ( ! file_exists($this->config->c_pp_logfile) ) {
         $fh = fopen($this->config->c_pp_logfile, "w");
         chmod($this->config->c_pp_logfile, 0640);
         fwrite($fh,"PayPAL IPN Logfile\n");
      } else {  // If it exists, just open it.
         $fh = fopen($this->config->c_pp_logfile, "ab");
      }

      fwrite($fh, $now . " $msg\n");
      if ($include_post == 1){
         if (count($_POST) == 0 ) { 
            fwrite($fh, "POST = none\n");
            echo("POST = none<br />\n");
         }
         else {
            fwrite($fh, "POST Variables:\n");
            echo("POST Variables:<br />\n");
            foreach ($_POST as $key => $value) {
               fwrite($fh, "Key=|$key|, Value=|$value|\n");
               echo("Key=|$key|, Value=|$value|<br />\n");
            } // for
         }
      } // if $include_post
      fclose($fh);
   }  // log_ipn


   function build_email_message_body($pending){
      
      $volunteer_count = 0;
      $ename = $this->config->c_EventName;
      if ($pending){
         $msg = $ename . " PENDING Registration Confirmation";
      } else if ( $this->PaymentStatus == "Refunded") {
         $msg = "\n\n" . $ename . " Registration REFUND";
      } else {
         $msg = "\n\n" . $ename . " Registration Confirmation";
      }

      if ( ( isset($_POST['test_ipn']) && ($_POST['test_ipn'] == 1) ) ||
         ( (! isset($_POST['test_ipn'])) && 
          (strpos($this->config->c_paypal_ipn_url, "sandbox") > 0) ) ){
         $msg .= "\n\n***** TEST PAYPAL WEB SITE, NO ACTUAL MONEY TRANSFER *****";
      }
      if ($this->Duplicate) {
         $msg .= "\n\n***** DUPLICATE REGISTRATION, CONTACT US FOR A REFUND!!!";
      }
      $msg .= "\n\nRegistration Date: " . 
        $this->config->c_today . ", Registration Number: $this->RegNum";
      $msg .= "\nInvoice ID: $this->InvoiceID";
      if ($this->TransactionID != "") {
         $msg .= "\nPayPAL Transaction ID = $this->TransactionID";
      }

      $msg .= "\n";
      // Output person names
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
         if ( $this->FNameAry[$ii] != "" ) {
            $msg .= "\nName: " . $this->FNameAry[$ii];
            $msg .= " " . $this->LNameAry[$ii];

            if ($this->config->c_UseGenderData) {
              $msg .= ",  Gender: " . $this->GenderAry[$ii];
            } // if UseGenderData

            if ($this->config->c_UseChildData && $ii > 1) {
               if ($this->ChildChkAry[$ii] == 1) {
                $msg .= ",  Child: Yes";
                if ($this->config->c_UseChildAge){
                  $msg .= ", Age: " . $this->ChildAgeAry[$ii];
                }
              } // if ChildChkAry
              else { $msg .= ",  Child: No"; }
            } // if UseChildData

            if ($this->config->c_UseStudentData) {
               if ( $this->StudentAry[$ii] == 1 ) {
                  $msg .= ",  Student: Yes" ;
               } else {
                  $msg .= ",  Student: No";
               } 
           } // if UseStudentData
        } // if FNameAry not empty
      } // for

      // Phone fields
      $iimax = count($this->PhoneData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        if ($this->PhoneData[$ii]["value"] != ""){
          $msg .= "\n" . $this->PhoneData[$ii]["label"] . " (" .
          $this->PhoneData[$ii]["type"] . "): " .
             $this->PhoneData[$ii]["value"];
        } // not empty value
      } // for each phone

      if ($this->config->c_Use_Do_Not_Share){
        if ($this->ShareData){
         $msg .= "\n\nDO share my/our personal information\n";
        } else {
          $msg .= "\n\nDo NOT share my/our personal information\n";
        } // share data
      } // if use do not share
   
      // Determine which hospitality fields are used, if any
      $hosp_req = false;
      $hosp_offer = false;
      $idx1 = $this->CtlIsUsed('hosp_req_beds');
      if ( $idx1 >= 0 ) {
        if ( $this->DataAry[$idx1] > 0 ) { $hosp_req = true; }
      }
      $idx1 = $this->CtlIsUsed('hosp_req_num');
      if ( $idx1 >= 0 ) {
        if ( $this->DataAry[$idx1] > 0 ) { $hosp_req = true; }
      }
      $idx1 = $this->CtlIsUsed('hosp_offer_beds');
      if ( $idx1 >= 0 ) {
        if ( $this->DataAry[$idx1] > 0 ) { $hosp_offer = true; }
      }
      $idx1 = $this->CtlIsUsed('hosp_offer_num');
      if ( $idx1 >= 0 ) {
        if ( $this->DataAry[$idx1] > 0 ) { $hosp_offer = true; }
      }
      
      // Data fields other than attendance and merchandise
      for ($ii = 0; $ii < $this->NumDQtys; $ii++) {
        $cname = strtolower($this->config->c_aryField_Defs[$ii]["name"]);
        $flabel = $this->config->c_aryField_Defs[$ii]["label"];
        $ftype = $this->config->c_aryField_Defs[$ii]["type"];
        $temp = $this->DataAry[$ii];
        $output = true;

        // Skip hospitality fields if not used
        if (strpos($cname, "hosp_req") !== false) {
          $output = $hosp_req;
        } else if (strpos($cname, "hosp_offer") !== false) {
          $output = $hosp_offer;
        }
      
        if ($output){
          $msg .= "\n" . $flabel . ": ";
          $fvalue = $this->FieldValue($temp, $ftype, "text");
          $msg .= $fvalue;
        } // if $output
      } // for each data field

      // Radio Button fields
      $iimax = count($this->RadioData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $msg .= "\n" . $this->RadioData[$ii]["label"] .
          ": " . $this->RadioData[$ii]["value"];
      } // for each radio button

      // Selection List fields
      $iimax = count($this->SelListData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $msg .= "\n" . $this->SelListData[$ii]["label"] .
        ": " . $this->SelListData[$ii]["value"];
      } // for each selection list

      // Output musician data
      if ($this->config->c_max_musicians > 0 
          && count($this->MusDataAry) == 0)
      { $msg .= "\n\nMusician Data: none"; }
      else {
        $jjmax = count($this->config->c_aryMusician_Fields);
	      for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
          // First field must be musician name
          $ctlname = $this->GetFieldCtrlName("musician", 0);
          $uname = $this->MusDataAry[$ctlname . $ii];
          if ($uname && ($uname != "")) {
            for ($jj = 0; $jj < $jjmax; $jj++) {
              $ctlname = $this->GetFieldCtrlName("musician", $jj);
              $fname = $this->config->c_aryMusician_Fields[$jj]["label"];
              $ftype = $this->config->c_aryMusician_Fields[$jj]["type"];
              $temp = $this->MusDataAry[$ctlname . $ii];
              $fvalue = $this->FieldValue($temp, $ftype, "text");
              if ($jj == 0){$msg .= "\n";}
              $msg .= "\n$fname $ii =  $fvalue";
  	        } // for jj
          } // if name not empty
	      } // for ii
      } // else musician data

      // Output caller data
      if ($this->config->c_max_callers > 0 
          && count($this->CallerDataAry) == 0)
      { $msg .= "\n\nCaller Data: none"; }
      else {
        $jjmax = count($this->config->c_aryCaller_Fields);
        for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
          // First field must be caller name
          $ctlname = $this->GetFieldCtrlName("caller", 0);
          $uname = $this->CallerDataAry[$ctlname . $ii];
          if ($uname && ($uname != "")) {
            for ($jj = 0; $jj < $jjmax; $jj++) {
              $ctlname = $this->GetFieldCtrlName("caller", $jj);
              $fname = $this->config->c_aryCaller_Fields[$jj]["label"];
              $ftype = $this->config->c_aryCaller_Fields[$jj]["type"];
              $temp = $this->CallerDataAry[$ctlname . $ii];
              $fvalue = $this->FieldValue($temp, $ftype, "text");
              if ($jj == 0){$msg .= "\n";}
              $msg .= "\n$fname $ii =  $fvalue";
            } // for jj
          } // if name not empty
        } // for ii
      } // else caller data
      
      $msg .= "\n\nFEES:";

      // Attendance quantities and costs
      for ($ii = 0; $ii < $this->NumAQtys; $ii++) {
        if ($this->AttendAry[$ii] > 0){
          $msg .= "\n" . $this->config->c_aryAttendance_Fields[$ii]["label"] . ": " . 
          $this->AttendAry[$ii] . " x $" . 
          sprintf("%.2f", $this->config->c_aryAttendance_Fields[$ii]["price"] * 1.0) . 
          " = $" . sprintf("%.2f", $this->AttendAry[$ii] * 
          $this->config->c_aryAttendance_Fields[$ii]["price"] * 1.0);
        }
      } // for each attendance qty

      if ($this->EarlyRegQty != 0) {
        $msg .= "\nEarly Reg: " . 
        $this->EarlyRegQty . " x $" . 
        $this->config->c_early_reg_discount . " = $" . 
        sprintf("%.2f", $this->EarlyRegQty * $this->config->c_early_reg_discount * 1.0);
      }

      if ($this->LateRegQty != 0) {
        $msg .= "\nLate Reg: " . 
        $this->LateRegQty . " x $" . 
        $this->config->c_late_reg_fee . " = $" . 
        sprintf("%.2f", $this->LateRegQty * $this->config->c_late_reg_fee * 1.0);
      }

      // Membership fees
      if ( $this->Membership_Single_Qty > 0 ){
        $msg .= "\nSingle Membership: " . $this->Membership_Single_Qty;
        $msg .= " x $" . $this->config->c_member_single_price . " = $";
        $msg .= sprintf("%.2f", $this->Membership_Single_Qty * 
          $this->config->c_member_single_price * 1.0);
      }
      if ( $this->Membership_Family_Qty > 0 ){
        $msg .= "\nFamily Membership: 1 x $";
        $msg .= $this->config->c_member_family_price . " = $";
        $msg .= sprintf("%.2f", $this->Membership_Family_Qty * 
          $this->config->c_member_family_price * 1.0);
      }

      // Merchandise quantities and costs
      for ($ii = 0; $ii < $this->NumMQtys; $ii++) {
        if ($this->MerchAry[$ii] > 0){
          $msg .= "\n" . $this->config->c_aryMerchandise_Fields[$ii]["label"] . ": " . 
          $this->MerchAry[$ii] . " x $" . 
          sprintf("%.2f", $this->config->c_aryMerchandise_Fields[$ii]["price"] * 1.0) . 
          " = $" . sprintf("%.2f", $this->MerchAry[$ii] * 
          $this->config->c_aryMerchandise_Fields[$ii]["price"] * 1.0);
        }
      }

      if ($this->config->c_UseDonation){
        $msg .= "\nDonation: $" . sprintf("%.2f", $this->Donation);
      }

      if ($this->config->c_DepositPrice > 0){
        if ($this->config->c_UsePayPalFee && $this->PayPalFeePay){
          $msg .= "\nPayPal Fee: $" . sprintf("%.2f", $this->PayPalFee);
        }
        $msg .= "\nTotal: $" . sprintf("%.2f", $this->TotalGross);
        $msg .= "\nDeposit: $" . sprintf("%.2f", $this->Deposit);
        $msg .= "\nPayment: $" . sprintf("%.2f", $this->PayPalGross);
        $msg .= "\nBalance Due";
        if ($this->config->c_DepositDueDate != ""){$msg .= " by " . 
          $this->config->c_DepositDueDate;}
        $msg .= ": $" . sprintf("%.2f", $this->BalDue) . "\n";
      }
      else { // no deposit
        if ($this->config->c_UsePayPalFee && $this->PayPalFeePay){
          $msg .= "\nTotal: $";
          $msg .= sprintf("%.2f", $this->TotalGross - $this->PayPalFee);
          $msg .= "\nPayPal Fee: $" . sprintf("%.2f", $this->PayPalFee);
          $msg .= "\nPayment: $" . sprintf("%.2f", $this->TotalGross) . "\n";
        } else {
          $msg .= "\nTotal: $" . sprintf("%.2f", $this->TotalGross) . "\n";
        }
      } // else no deposit

      if ( $this->PaymentStatus == "Refunded") {
        $msg .= "\n*** REFUNDED *** \n";
      }

      return($msg);

   } // build_email_message_body


   function Send_Emails($pending){

      $this->log_ipn("Send_Emails Start, Pending=$pending", 0);

      $sandbox = "";
      if ( strpos($this->config->c_paypal_ipn_url, "sandbox") > 0){
         $sandbox .= "TEST";
      }

      if ($pending) {
        $mail_to = $this->config->c_webmaster_email;
        if ($this->config->c_email_pending_user) {
          $mail_to .= ",$this->Email";
        }
        $mail_subject = str_replace("'", "", $this->config->c_EventName) . 
          " $sandbox PENDING Registration for " .  
          $this->FNameAry[1] . " " . $this->LNameAry[1];
      }
      else {
        $mail_to = "$this->Email," . 
          $this->config->c_registrar_email . "," . 
          $this->config->c_payment_email;
        $temp = 0;
        $idx1 = $this->CtlIsUsed('hosp_req_beds');
        if ( $idx1 >= 0 ) {
         if ( $this->DataAry[$idx1] > 0 ) { $temp += 1; }
        }
        $idx1 = $this->CtlIsUsed('hosp_req_num');
        if ( $idx1 >= 0 ) {
         if ( $this->DataAry[$idx1] > 0 ) { $temp += 1; }
        }
        $idx1 = $this->CtlIsUsed('hosp_offer_beds');
        if ( $idx1 >= 0 ) {
         if ( $this->DataAry[$idx1] > 0 ) { $temp += 1; }
        }
        $idx1 = $this->CtlIsUsed('hosp_offer_num');
        if ( $idx1 >= 0 ) {
         if ( $this->DataAry[$idx1] > 0 ) { $temp += 1; }
        }
        if ( $temp > 0 && $this->config->c_hospitality_email != "") {
           $mail_to .= "," . $this->config->c_hospitality_email;
        }
        $reg_action = "Registration";
        if ( $this->PaymentStatus == "Refunded") {$reg_action = "REFUND";}
        $mail_subject = str_replace("'", "", $this->config->c_EventName) . 
        " $sandbox $reg_action for " .  
        $this->FNameAry[1] . " " . $this->LNameAry[1];
      }
      $msg_body = $this->build_email_message_body($pending);
      
      $status = mail($mail_to, $mail_subject, $msg_body, $this->mail_headers);

      $this->log_ipn("Send_Emails TO=|$mail_to|", 0);
      $this->log_ipn("Send_Emails End, Status=|$status|", 0);
      return($status);
   } // Send_Emails


  function CSVEscape($strdata){
    // Replace carriage return with space
    $fvalue = preg_replace('[\x0A|\x0D]', " ", $strdata);
    // Replace double quote with single quote
    // Enclose in double quotes, add comma to end
    $fvalue = "\"" . str_replace('"', '\'', $fvalue) . "\",";
    return($fvalue);
   } // CSVEscape


  function create_reg_log_file(){

    // Create log file.
    // Set permission to dissallow direct viewing.
    $fh = fopen($this->config->c_reg_logfile, "w");
    chmod($this->config->c_reg_logfile, 0640);
    
    // Write header line
    $temp = "num_regnum,txt_invoiceid,txt_transactionid,";
    $temp .= "txt_submitdate,num_statusid,txt_status,txt_statusdate,";
    if ($this->config->c_Use_Do_Not_Share){$temp .= "chk_sharedata,";}
    $temp .= "num_numpersons,";
    // Person data field names
    for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
      $temp .= "txt_firstname$ii,txt_lastname$ii,";
      if ($this->config->c_UseGenderData) {$temp .= "rad_gender$ii,";}
      if ($this->config->c_UseChildData && $ii > 1) {
        $temp .= "chk_child$ii,";
        if ($this->config->c_UseChildAge) {$temp .= "num_childage$ii,";}
      } // if use child data
      if ($this->config->c_UseStudentData) {$temp .= "chk_student$ii,";}
    } // for each person

    // Phone fields
    $iimax = count($this->PhoneData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->PhoneData[$ii]["name"];
      $temp .= "\"" . $ctl_name . "\",";
      $temp .= "\"" . $ctl_name . "_type\",";
    } // for each phone

    // Other data field names. 
    // DB field name = Ctrl field name.
    for ($ii = 0; $ii < $this->NumDQtys; $ii++) {
      $temp .= "\"" . $this->GetFieldCtrlName("data", $ii) . "\",";
    } // for

    // Radio Button fields
    $iimax = count($this->RadioData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $temp .= "\"" . $this->RadioData[$ii]["name"] . "\",";
    } // for each radio button

    // Selection List fields
    $iimax = count($this->SelListData);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $temp .= "\"" . $this->SelListData[$ii]["name"] . "\",";
    } // for each selection list

    // Musician Data field names
    $jjmax = count($this->config->c_aryMusician_Fields);
    for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
      for ($jj = 0; $jj < $jjmax; $jj++) {
        $temp .= "\"" . $this->GetFieldCtrlName("musician", $jj) . 
        "$ii" . "\",";
      } // for jj
    } // for ii

    // Caller Data field names
    $jjmax = count($this->config->c_aryCaller_Fields);
    for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
      for ($jj = 0; $jj < $jjmax; $jj++) {
        $temp .= "\"" . $this->GetFieldCtrlName("caller", $jj) . 
        "$ii" . "\",";
      } // for jj
    } // for ii

    // Add Attendance quantity names
    $jj = 0;
    for ($ii = 0; $ii < $this->NumAQtys; $ii++) {
      $jj = $ii + 1;
      $temp .= "txt_aname$jj,num_aprice$jj,num_aqty$jj,";
    } // for each attedance option

    // Add Merchandise quantity names
    for ($ii = 0; $ii < $this->NumMQtys; $ii++) {
      $jj = $ii + 1;
      $temp .= "txt_mname$jj,num_mprice$jj,num_mqty$jj,";
    } // for each merchandise option

    // Add membership fee field names
    if ($this->config->c_member_single_price > 0 
        || $this->config->c_member_family_price > 0){
      $temp .= "num_memsingleprice,num_memsingleqty,";
      $temp .= "num_memfamilyprice,num_memfamilyqty,";
    } // if membership prices

    // Early Registration discount names
    if ($this->config->c_early_reg_discount != 0){
      $temp .= "num_earlyregdiscount,num_earlyregqty,";
    }

    // Late Registration fee names
    if ($this->config->c_late_reg_fee != 0){
      $temp .= "num_lateregfee,num_lateregqty,";
    }

    // Donation
    if ($this->config->c_UseDonation){$temp .= "num_donation,";}

    // Deposit
    if ($this->config->c_DepositPrice > 0){
      $temp .= "num_deposit,num_balancedue,";
    }

    if ($this->config->c_UsePayPalFee){$temp .= "chk_paypalfeepay,";}
    
    $temp .= "num_paypalfee,num_paypalgross,";
    $temp .= "num_totalgross,num_totalnet\n";
    fwrite($fh, $temp); 
  } // create_reg_log_file


   function log_registration(){
    // Write registration data to a file in CSV format.
    // first line is field names, which is same as
    // web site control names, which is same as 
    // database column names. Thus this file could be used
    // to re-load the database if it somehow got corrupted.

      // If log file does not exist, create it.
      if ( ! file_exists($this->config->c_reg_logfile) ) {
        $this->create_reg_log_file();
      }
      $fh = fopen($this->config->c_reg_logfile, "ab");

      // Build line of data values to output
      
      $temp = "$this->RegNum,$this->InvoiceID,$this->TransactionID,";
      $temp .= $this->SubmitDate . ",";
      $temp .= $this->AryStatusIDs[$this->Status] . ",";
      $temp .= $this->Status . "," . $this->StatusDate . ",";
      if ($this->config->c_Use_Do_Not_Share){$temp .= "$this->ShareData,";}
      $temp .= "$this->NumPersons,";

      // Person fields
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
        $temp .= $this->FNameAry[$ii] . "," . $this->LNameAry[$ii] . ",";
        if ($this->config->c_UseGenderData) {
          $temp .= $this->GenderAry[$ii] . ",";
        }
        if ($this->config->c_UseChildData && $ii > 1) {
          $temp .= $this->ChildChkAry[$ii] . ",";
          if ($this->config->c_UseChildAge) {
            $temp .= $this->ChildAgeAry[$ii] . ",";
          } // if use child age
        } // if use child data
        if ($this->config->c_UseStudentData) {
          $temp .= $this->StudentAry[$ii] . ",";
        }
      } // for each person

        // Phone fields
        $iimax = count($this->PhoneData);
        for ($ii = 0; $ii < $iimax; $ii++) {
          $temp .= "\"" . $this->PhoneData[$ii]["value"] . "\",";
          $temp .= "\"" . $this->PhoneData[$ii]["type"] . "\",";
        } // for each phone
  
      // Data fields other than attendance and merchandise options
      for ($ii = 0; $ii < $this->NumDQtys; $ii++) {
        $ftype = $this->config->c_aryField_Defs[$ii]["type"];
     		$temp2 = $this->DataAry[$ii];
        $fvalue = $this->FieldValue($temp2, $ftype, "num");
        $temp .= $this->CSVEscape($fvalue);
       } // for each field

        // Radio Button fields
        $iimax = count($this->RadioData);
        for ($ii = 0; $ii < $iimax; $ii++) {
          $temp .= "\"" . $this->RadioData[$ii]["value"] . "\",";
        } // for each radio button
  
        // Selection List fields
        $iimax = count($this->SelListData);
        for ($ii = 0; $ii < $iimax; $ii++) {
          $temp .= "\"" . $this->SelListData[$ii]["value"] . "\",";
        } // for each selection list

      // Musician Data
      $jjmax = count($this->config->c_aryMusician_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ftype = $this->config->c_aryMusician_Fields[$jj]["type"];
            $ctlname = $this->GetFieldCtrlName("musician", $jj) . $ii;
         		$temp2 = $this->MusDataAry[$ctlname];
            $fvalue = $this->FieldValue($temp2, $ftype, "num");
            $temp .= $this->CSVEscape($fvalue);
         } // for jj
      } // for ii

      // Caller Data
      $jjmax = count($this->config->c_aryCaller_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ftype = $this->config->c_aryCaller_Fields[$jj]["type"];
            $ctlname = $this->GetFieldCtrlName("caller", $jj) . $ii;
         		$temp2 = $this->CallerDataAry[$ctlname];
            $fvalue = $this->FieldValue($temp2, $ftype, "num");
            $temp .= $this->CSVEscape($fvalue);
         } // for jj
      } // for ii

      // Add Attendance quantities
      for ($ii = 0; $ii < $this->NumAQtys; $ii++) {
         $temp .= $this->config->c_aryAttendance_Fields[$ii]["abbrev"] . ",";
         $temp .= sprintf("%.2f", $this->config->c_aryAttendance_Fields[$ii]["price"]);
         $temp .= "," . $this->AttendAry[$ii] . ",";
      }

      // Add Merchandise quantities
      for ($ii = 0; $ii < $this->NumMQtys; $ii++) {
         $temp .= $this->config->c_aryMerchandise_Fields[$ii]["abbrev"] . ",";
         $temp .= sprintf("%.2f", $this->config->c_aryMerchandise_Fields[$ii]["price"]);
         $temp .= "," . $this->MerchAry[$ii] . ",";
      }

      // Add membership fees
      if ($this->config->c_member_single_price > 0 
          || $this->config->c_member_family_price > 0){
        $temp .= sprintf("%.2f", $this->config->c_member_single_price) . ",";
        $temp .= $this->Membership_Single_Qty . ",";
        $temp .= sprintf("%.2f", $this->config->c_member_family_price) . ",";
        $temp .= $this->Membership_Family_Qty . ",";
      }

      // Early Registration discount
      if ($this->config->c_early_reg_discount != 0){
         $temp .= sprintf("%.2f", $this->config->c_early_reg_discount) . ",";
         $temp .= $this->EarlyRegQty . ",";
      }

      // Late Registration fee
      if ($this->config->c_late_reg_fee != 0){
         $temp .= sprintf("%.2f", $this->config->c_late_reg_fee) . ",";
         $temp .= $this->LateRegQty . ",";
      }

      // Donation
      if ($this->config->c_UseDonation != 0){
        $temp .= sprintf("%.2f", $this->Donation) . ",";
      }

      // Deposit
      if ($this->config->c_DepositPrice > 0){
        $temp .= sprintf("%.2f", $this->Deposit) . ",";
        $temp .= sprintf("%.2f", $this->BalDue) . ",";
      } // if g_DepositPrice

      if ($this->config->c_UsePayPalFee){$temp .= "$this->PayPalFeePay,";}
      $temp .= sprintf("%.2f", $this->PayPalFee) . ",";
      $temp .= sprintf("%.2f", $this->PayPalGross) . ",";
      $temp .= sprintf("%.2f", $this->TotalGross) . ",";
      $temp .= sprintf("%.2f", $this->TotalNet) . "\n";
      fwrite($fh, $temp); 

      fclose($fh);
   }  // log_registration


   function Print_Page_Header($title1, $title2){
      echo("<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' ");
      echo("'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n");
      echo("<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>\n");
      echo("<head>\n");
      echo("<title>$title1</title>\n");
      echo("<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n");
      echo("<meta http-equiv='Content-Script-Type' content='text/javascript' />\n");
      echo("<style type='text/css'>\n");
      echo("body, p, td, input {font-family:arial; font-size:14px;}\n");
//      echo("td {text-align: center;}\n");
      echo("</style>\n");
      echo("</head>\n");
      echo("<body>\n");
      echo("\n<h3>$title2</h3>");
   } // Print_Page_Header
   
   
   function Print_Page_Footer(){
      echo "\n<p style='font-family:sans-serif; font-size:8pt;'>";
      echo "Created by <a href='http://www.glennman.com/online-reg/home.html' target='_blank'>GM Online Registration System</a>";
      echo ", version " . $this->config->c_olr_version . "</p>";
      echo "\n</body>\n</html>\n";
   }


   function Print_Login(){
      $temp = "\n<form name='form1' method='post' action='"; 
      $temp .= $_SERVER['PHP_SELF'] . "'>\n";
      $temp .= "<input type='hidden' name='cmd' value='login' />\n";
      $temp .= "<p>Password: <input type='text' name='pwd' value='' ";
      $temp .= "size='20' maxlength='20' /></p>\n";
      $temp .= "<p><input type='submit' name='login' value=' Login ' /></p>\n";
      $temp .= "</form>\n";
      echo($temp);
   } // Print_Login
   

   function Print_Login_Page($error, $title1, $title2){
      $this->Print_Page_Header($title1, $title2);
      if ($error) {
         echo ("<p style='color: red; font-weight: bold;'>Login " .
          "Error, please try again.</p>");
      }
      $this->Print_Login();
      $this->Print_Page_Footer();
   } // Print_Login_Page
   
   function Verify_Login($pwd){
      if (! isset($_POST['pwd'])) { return(false); };
      if ($_POST['pwd'] != $pwd) { return(false); };
      return(true);
   } // Verify_Login
   

  function Delete_Files($which){
    
    // If "all", delete database def file.
    if ($which == 'all'){
      $fname = $this->config->c_db_table_def_file;
      if ( file_exists($fname) ) {
         $fh = fopen($fname, "r");
         fclose($fh);
         unlink(realpath($fname));
      } // if file exists
      $temp1 = preg_match('/.+\.php$/', $fname);
      if ( $temp1 == true || $temp1 > 0 ) {
        $fname .= ".php";
        if ( file_exists($fname) ) {
           $fh = fopen($fname, "r");
           fclose($fh);
           unlink(realpath($fname));
        } // if file exists
      } // if
    } // if all

    // Delete html registration file
    $fname = $this->config->c_online_reg_fname;
    if ( file_exists($fname) ) {
       $fh = fopen($fname, "r");
       fclose($fh);
       unlink(realpath($fname));
    } // if file exists

    // Delete paypal ipn log file
    $fname = $this->config->c_pp_logfile;
    if ( file_exists($fname) ) {
       $fh = fopen($fname, "r");
       fclose($fh);
       unlink(realpath($fname));
    } // if file exists

    // Delete registration log file
    $fname = $this->config->c_reg_logfile;
    if ( file_exists($fname) ) {
       $fh = fopen($fname, "r");
       fclose($fh);
       unlink(realpath($fname));
    } // if file exists

    // Delete error log file
    $fname = $this->config->c_error_logfile;
    if ( file_exists($fname) ) {
       $fh = fopen($fname, "r");
       fclose($fh);
       unlink(realpath($fname));
    } // if file exists

  } // Delete_Files


  function Dump_Form(){ 
  //%%% For debugging only. Set config $c_dump_form_debug.
    foreach ($_POST as $key => $value) {
       echo("Key=|$key|, Value=|$value|<br />\n");
    } // for
  } // Dump_Form

  function Print_Fields(){
   // %%% For debugging only.
    
    for ($ii = 0; $ii < $this->NumDQtys; $ii++) {
      echo("\n<br>Name = [" . 
        $this->config->c_aryField_Defs[$ii]["name"] . "], ");
      echo("Value=[" . $this->DataAry[$ii] . "] ");
      error_log("Name = [" . $this->config->c_aryField_Defs[$ii]["name"] . 
        "], Value=[" . $this->DataAry[$ii] . "]");

    } // for
  } // Print_Fields

  function Dump_Data($varname, $var){
   // %%% For debugging only.
   // Dump variable value to error log
   error_log($varname . " = " . print_r($var, true));
  } // Dump_Data

}  // Online_Reg_Lib_Class

?>
