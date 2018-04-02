<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.3

// Registration form executes pp-send
// pp-send
//   Validate_Form
//   Save_Data (saves data to database)
//   Send_Paypal

require_once 'olr-config.php';
require_once 'olr-db.php';
require_once 'olr-lib.php';

class Online_Reg_PP_Send_Class{
  var $config, $regdb, $reglib, $db_result;

  // Constructor
  function Online_Reg_PP_Send_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->regdb = new Online_Reg_DB_Class();
    $this->db_result = null;
  } // Online_Reg_PP_Send_Class constructor

   function SQLEscape($strdata){
      return(str_replace("'", "''", $strdata));
   }
   
   function ValidateFname($namein){
      $result = false;
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
         if (strcasecmp($namein, $this->reglib->FNameAry[$ii]) == 0){$result = true;}
      }
      return($result);
   } // ValidateFname
   
   function Validate_Form(){
      
      $weberrors = "";
      $usererrors = "";
      $temp1 = "";
      $temp2 = "";
      $temp3 = "";
      $regex_email = '/^([A-Z]|[a-z]|\d|[\._\-])+@([A-Z]|[a-z]|\d|[\._\-])+\.([A-Z]|[a-z])+$/';
      $regex_phone = '/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/';
      $regex_state = '/^[a-wA-Z][a-zA-Z]$/';
      $regex_zip = '/^[0-9]{5}$/';
      $regex_num = '/^[0-9]+$/';
      //$regex_text = '/^([A-Z]|[a-z]|\d|[ \.\,\;\:\"\#\&\(\)\[\]\-_\'])+$/';
      $regex_text = '/.+/';
      $regex_check = '/^(on|off)+$/';
      $num_housing = -1;
      $this->reglib->EarlyRegQty = 0;
      $this->reglib->LateRegQty = 0;

      // Check for Required fields
      $ary_req = "";
      $req_name = "";
      if ( ! isset($_POST['required_fields']) ) {
         $weberrors .= "\n Missing Required Fields list.";
      } else {
         $ary_req = explode(",", trim($_POST['required_fields']));
         if ( count($ary_req) == 0 ) {
            $weberrors .= "\n Required Fields is empty.";
         } else {
            foreach ( $ary_req as $req_name) {
               if ( (! isset($_POST[$req_name])) || (trim($_POST[$req_name]) == "") ) {
                  $usererrors .= "\n<br />Empty Required Field: $req_name";
               } // if empty req_name
            } // foreach
         } // else required not empty
      } // if required_fields
   
      // Persons
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
         $ctl_name = "txt_firstname$ii";
         $this->reglib->FNameAry[$ii] = "";
         if (! isset($_POST[$ctl_name]) ) {
            $weberrors .= "\n Missing Attendee $ii First Name.";  
         } else {
            $temp2 = trim($_POST[$ctl_name]);
            if ($temp2 != "") {
               $temp1 = preg_match($regex_text, $temp2);
               if ( $temp1 == false || $temp1 == 0 ) {
                  $usererrors .= "\n<br />Invalid Attendee $ii First Name.";
               } else { $this->reglib->FNameAry[$ii] = $temp2; }
            } // if not empty
         } // POST is set
         if ( $this->reglib->FNameAry[$ii] != "" ) {
            $this->reglib->NumPersons = $this->reglib->NumPersons + 1;
         } // if first name not empty

          // Initialize LastName 2-N to LastName1
         $ctl_name = "txt_lastname$ii";
         if ( $ii == 1 ) {
            $this->reglib->LNameAry[$ii] = "";
         } else {
            $this->reglib->LNameAry[$ii] = $this->reglib->LNameAry[1];
         } // else last name not empty
         if (! isset($_POST[$ctl_name]) ) { 
            $weberrors .= "\n Missing Attendee $ii Last Name.";   
         } else {
            $temp2 = trim($_POST[$ctl_name]);
            if ($temp2 != "") {
               $temp1 = preg_match($regex_text, $temp2);
               if ( $temp1 == false || $temp1 == 0 ) {
                  $usererrors .= "\n<br />Invalid Attendee $ii Last Name.";
               } else { $this->reglib->LNameAry[$ii] = $temp2; }
            } // if value not empty
         } // else POST is set
         // Clear the last name if first name is empty
         if ( $this->reglib->FNameAry[$ii] == "" ) {
            $this->reglib->LNameAry[$ii] = "";
         } // if first name is empty

        // Check Gender field, if used
        $this->reglib->GenderAry[$ii] = "";
        if ($this->config->c_UseGenderData) {
          // Get gender data if first name not empty
          if ($this->reglib->FNameAry[$ii] != ""){
            $ctl_name = "rad_gender$ii";
             if (! isset($_POST[$ctl_name])) { 
                $weberrors .= "\n Missing Gender $ii data.";   
             } else {
                $temp2 = strtoupper(trim($_POST[$ctl_name]));
                if ($temp2 != "") {
                   if ( $temp2 !="M" && $temp2 != "F" ) {
                      $usererrors .= "\n<br />Invalid Gender $ii : $temp2.";
                   } else { $this->reglib->GenderAry[$ii] = $temp2; }
                } else {
                  $usererrors .= "\n<br />Empty Gender $ii .";
                } // else value is empty
             } // else POST is set
          } // if FName
        } // if g_UseGenderData
        
        // Check if Child fields are used
        $this->reglib->ChildChkAry[$ii] = 0;
        $this->reglib->ChildAgeAry[$ii] = 0;
         if ($this->config->c_UseChildData && $ii > 1) {
            // Get child checkbox
            $ctl_name = "chk_child$ii";
            if ( isset($_POST[$ctl_name]) ) { 
              $this->reglib->ChildChkAry[$ii] = 1;
            } // if POST is set
            if ($this->config->c_UseChildAge && $ii > 1) {
               // Get child age
               $ctl_name = "num_childage$ii";
               if ( ! isset($_POST[$ctl_name]) ) { 
                  $weberrors .= "\n Missing Person $ii Child Age.";   
               } else {
                  $temp2 = trim($_POST[$ctl_name]);
                  if ($temp2 != "") {
                     $temp1 = preg_match($regex_num, $temp2);
                     if ( $temp1 == false || $temp1 == 0 ) {
                        $usererrors .= "\n<br />Invalid Person $ii Child Age.";
                     } else { $this->reglib->ChildAgeAry[$ii] = $temp2; }
                  } // if child age not empty
               } // else child age
            } // if g_UseChildAge
         } // if UseChildData

          // Check if Student fields are used
          $this->reglib->StudentAry[$ii] = 0;
          if ($this->config->c_UseStudentData) {
            // Get student checkbox
            $ctl_name = "chk_student$ii";
            if ( isset($_POST[$ctl_name]) ) { 
              $this->reglib->StudentAry[$ii] = 1;
            } // if student
         } // if g_UseStudentData
      } // for each person

      // Phone fields
      $iimax = count($this->reglib->PhoneData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->reglib->PhoneData[$ii]["name"];
        $field_label = $this->reglib->PhoneData[$ii]["label"];
        if (! isset($_POST[$ctl_name]) ){
          $weberrors .= "\n Missing data for " . $field_label . ".";   
        } else {
          $regex_name = "regex_phone";
          $temp2 = trim($_POST[$ctl_name]);
          if ($temp2 != "") {
            $temp1 = preg_match(${$regex_name}, $temp2);
            if ( $temp1 == false || $temp1 == 0 ) {
              $usererrors .= "\n<br />Invalid value for $field_label" . ": [" . $temp2 . "].";
            } else { 
              $this->reglib->PhoneData[$ii]["value"] = $temp2;
            } // else regex match
            $ctl_name = str_replace("txt_", "rad_", $ctl_name);
            if (! isset($_POST[$ctl_name]) ){
              $weberrors .= "\n Missing data for " . $field_label . " Type.";   
            } else {
              $this->reglib->PhoneData[$ii]["type"] = $_POST[$ctl_name];
            } // else POST is set
          } // if $temp2 not empty
        } // else POST is set
      } // for each phone field

      // Radio Button fields
      $iimax = count($this->reglib->RadioData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->reglib->RadioData[$ii]["name"];
        if (isset($_POST[$ctl_name]) ){
          $this->reglib->RadioData[$ii]["value"] = $_POST[$ctl_name];
       } else {
//%%%          $weberrors .= "\n Missing Radio Button $ctl_name.";   
          $this->reglib->RadioData[$ii]["value"] = "";
        } // else POST is set
      } // for each radio button

      // Selection List data
      $iimax = count($this->reglib->SelListData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $ctl_name = $this->reglib->GetFieldCtrlName("select", $ii);
        if (! isset($_POST[$ctl_name]) ){
          $weberrors .= "\n Missing Selection List $ctl_name.";   
        } else {
          $this->reglib->SelListData[$ii]["value"] = $_POST[$ctl_name];
        } // else POST is set
      } // for each selection list

      // Musician Data
      for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
         $jjmax = count($this->config->c_aryMusician_Fields);
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ctl_name = $this->reglib->GetFieldCtrlName("musician", $jj) . $ii;
            $field_label = $this->config->c_aryMusician_Fields[$jj]["label"];
            $field_type = $this->config->c_aryMusician_Fields[$jj]["type"];
            $regex_name = "regex_" . $field_type;
            $this->reglib->MusDataAry[$ctl_name] = "";
            if (isset($_POST[$ctl_name])) { 
              if ($field_type == "check")
              {
           		  $this->reglib->MusDataAry[$ctl_name] = 1;
              }
              else { // not checkbox
               $temp2 = trim($_POST[$ctl_name]);
               if ($temp2 != "") {
                  $temp1 = preg_match(${$regex_name}, $temp2);
                  if ( $temp1 == false || $temp1 == 0 ) {
                     $usererrors .= "\n<br />Invalid $field_label $ii value: [" . $temp2 . "].";
                  } else { 
                       $this->reglib->MusDataAry[$ctl_name] = $temp2;
                       // Check for musician first name in registration first name list
                       if ($jj == 0 && ! $this->ValidateFname($temp2)){
                          $usererrors .= "\n<br />Invalid $field_label $ii value: [" . $temp2 . "].";
                       }
                     } // else valid type of data
                  } // if not empty value
               } // else not checkbox
            } else { // post not present
               if ($field_type == "check")
               { $this->reglib->MusDataAry[$ctl_name] = 0; }
               else {$weberrors .= "\n Missing Data for $field_label $ii";}
            } // else post not present
         } // for jj
      } // for ii

      // Caller Data
      for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
         $jjmax = count($this->config->c_aryCaller_Fields);
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ctl_name = $this->reglib->GetFieldCtrlName("caller", $jj) . $ii;
            $field_label = $this->config->c_aryCaller_Fields[$jj]["label"];
            $field_type = $this->config->c_aryCaller_Fields[$jj]["type"];
            $regex_name = "regex_" . $field_type;
            $this->reglib->CallerDataAry[$ctl_name] = "";
            if (isset($_POST[$ctl_name])) { 
              if ($field_type == "check")
              {
             		$this->reglib->CallerDataAry[$ctl_name] = 1;
              }
              else { // not checkbox
               $temp2 = trim($_POST[$ctl_name]);
               if ($temp2 != "") {
                  $temp1 = preg_match(${$regex_name}, $temp2);
                  if ( $temp1 == false || $temp1 == 0 ) {
                     $usererrors .= "\n<br />Invalid $field_label $ii value: [" . $temp2 . "].";
                  } else { 
                       $this->reglib->CallerDataAry[$ctl_name] = $temp2;
                       // Check for caller first name in registration first name list
                       if ($jj == 0 && ! $this->ValidateFname($temp2)){
                          $usererrors .= "\n<br />Invalid $field_label $ii value: [" . $temp2 . "].";
                       } // first name bad
                     } // else valid data type
                  } // if not empty value
               } // else not checkbox
            } // if POST is set
            else {  // POST not set
              if ($field_type == "check"){
                $this->reglib->CallerDataAry[$ctl_name] = 0;
              }
              else {$weberrors .= "\n Missing Data for $field_label $ii" . ".";}
            } // else post not present
         } // for each caller field
      } // for each caller

      // Fields other than payment and merchandise options
      for ($ii = 0; $ii < $this->reglib->NumDQtys; $ii++) {
         $ctl_name = $this->reglib->GetFieldCtrlName("data", $ii);
         $field_label = $this->config->c_aryField_Defs[$ii]["label"];
         $field_type = $this->config->c_aryField_Defs[$ii]["type"];
         $regex_name = "regex_" . $field_type;
         $this->reglib->DataAry[$ii] = "";
         if (isset($_POST[$ctl_name])) { 
           if ($field_type == "check"){$this->reglib->DataAry[$ii] = 1;}
           else {
            $temp2 = trim($_POST[$ctl_name]);
            if ($temp2 != "") {
               $temp1 = preg_match(${$regex_name}, $temp2);
               if ( $temp1 == false || $temp1 == 0 ) {
                  $usererrors .= "\n<br />Invalid value for $field_label" . ": [" . $temp2 . "].";
               } else { 
                    $this->reglib->DataAry[$ii] = $temp2;
                    // Save first email address
                    if ($ctl_name == "txt_email1"){
                        $this->reglib->Email = $temp2;
                    } // if email
                    if (substr($ctl_name, 0, 11) == "num_housing"){
                       if ($num_housing < 0) {$num_housing = 0;}
                       $num_housing += $temp2;
                    } // if num_housing
                  } // else regex match
               } // if $temp2 not empty
            } // else not checkbox
         } // if POST is set
         else {
           if ($field_type == "check"){$this->reglib->DataAry[$ii] = 0;}
           else {$weberrors .= "\n Missing Data for " . $field_label . ".";}
         } // else POST is not set
      } // for each data element
      
      // Check housing qty = num persons
      if ($num_housing > -1 && $num_housing != $this->reglib->NumPersons){
         $usererrors .= "\n<br />Housing count [" . $num_housing .
         "] does not match person count [" . number_format($this->reglib->NumPersons) . "].";
      }

      // Get share data value
      $this->reglib->ShareData = 1;
      if ($this->config->c_Use_Do_Not_Share){
        if ( isset($_POST['chk_noshare']) ) { $this->reglib->ShareData = 0; }
      } // if use do not share

      // Get attendance quantities
      $jj = 0;
      $rad_error = false;
      for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++){
         $jj = $ii + 1;
         $label = $this->config->c_aryAttendance_Fields[$ii]["label"];
         if ($this->config->c_max_persons > 1){
           $temp = "num_aqty" . $jj;
           if (! isset($_POST[$temp])) { 
              $weberrors .= "\n Missing Attendance Qty for $label" . ".";   
           } else {
              if (isset($_POST[$temp])) {$temp2 = trim($_POST[$temp]);}
              if ( $temp2 != "" ){
                 $temp1 = preg_match($regex_num, $temp2);
                 if ( $temp1 == false || $temp1 == 0 ) {
                    $usererrors .= "\n<br />Invalid Qty for $label" . ": [" . $temp2 . "].";
                 } else if ( $temp2 > $this->reglib->NumPersons ) {
                    $usererrors .= "\n<br />Qty for $label [" . $temp2 . "] " .
                    "is greater than the number of people [" . 
                    number_format($this->reglib->NumPersons) . "].";
                 } else {
                    $this->reglib->AttendAry[$ii] = $temp2;
                 } // else
              } // if $temp2 not empty
           } // else POST exists
         } // if max_persons > 1
         else { // max_persons = 1, use radio buttons or checkboxes
           if ($this->config->c_attendance_multi_select){
             $temp = "chk_aqty" . $jj;
             if (isset($_POST[$temp])) {$this->reglib->AttendAry[$ii] = 1;}
           } // if multi_select
           else {
             $temp = "rad_attend";
             if (! isset($_POST[$temp])) {
                if (! $rad_error) {
                  $weberrors .= "\n Missing Attendance Radios Buttons.";
                  $rad_error = true; 
                } // if no previous error
             } // if POST not set
             else { // POST is set
              // value is index number, 1 to N
              $temp2 = $_POST[$temp];
              if ($temp2 == $jj){$this->reglib->AttendAry[$ii] = 1;}
             } // else POST is set
           } // else not multi_select
         } // else max_persons = 1
      } // for each NumAQtys

      // Get merchandise quantities
      for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++){
        $jj = $ii + 1;
        $label = $this->config->c_aryMerchandise_Fields[$ii]["label"];
        $temp = "num_mqty" . $jj;
        if ($this->config->c_max_persons > 1){ // text boxes
          if (! isset($_POST[$temp])) { 
            $weberrors .= "\n Missing Merchandise #" . $jj . " Qty for $label" . ".";   
          } else {
            if (isset($_POST[$temp])) {$temp2 = trim($_POST[$temp]);}
            if ( $temp2 != "" ){
               $temp1 = preg_match($regex_num, $temp2);
               if ( $temp1 == false || $temp1 == 0 ) {
                  $usererrors .= "\n<br />Invalid Qty for $label" . ": [" . $temp2 . "].";
               } else {
                  $this->reglib->MerchAry[$ii] = $temp2;
               }
            } // if temp2 not empty
          } // else POST not missing
        } // if max_persons > 1
         else { // max_persons = 1, use check boxes
           $temp = "chk_mqty" . $jj;
           if (isset($_POST[$temp])) {$this->reglib->MerchAry[$ii] = 1;}
         } // else max_persons = 1
      } // for each NumMQtys

      // Check for Single Memberships (text box for qty)
      if ($this->config->c_member_single_price != 0 
          && $this->config->c_max_persons > 1){
        if (! isset($_POST['num_member_single'])){
            $weberrors .= "\n Missing Qty for Single Membership.";
         } else {   
            $temp2 = trim($_POST['num_member_single']);
            if ( $temp2 != "" ){
               $temp1 = preg_match($regex_num, $temp2);
               if ( $temp1 == false || $temp1 == 0 ) {
                  $usererrors .= "\n<br />Invalid Single Membership Qty.";
               } else if ( $temp2 > $this->reglib->NumPersons ) {
                  $usererrors .= "\n<br />Qty for Single Membership [".
                  $temp2 . "] is greater than the number of people [" .
                  number_format($this->reglib->NumPersons) . "].";
               } else {$this->reglib->Membership_Single_Qty = $temp2;}
            } // if not blank
        } // else POST is set
      } // if member_single_price not zero

       // Check for Family Memberships (checkbox)
      if ($this->config->c_member_family_price != 0 
          && $this->config->c_max_persons > 1){
        if (isset($_POST['chk_member_family'])){
          $this->reglib->Membership_Family_Qty = 1;
        } // if POST is set
      } // if member_family_price not zero

      // Check memberships for Single person registration,
      // using radio buttons.
      if ($this->config->c_member_single_price != 0 
          && $this->config->c_max_persons == 1){
        if (! isset($_POST['rad_membership'])){
            $weberrors .= "\n Missing Membership Radio Button.";
         } else {   
            $temp2 = $_POST['rad_membership'];
            if ($temp2 == "single"){$this->reglib->Membership_Single_Qty = 1;}
            elseif ($temp2 == "family"){$this->reglib->Membership_Family_Qty = 1;}
        } // else POST is set
      } // max_persons = 1 and memberships

      // Check for Early Reg discount
      if ( is_numeric($this->config->c_early_reg_discount)
            && $this->config->c_early_reg_discount < 0 ) {
         if (! isset($_POST['hid_early_reg_qty']) ) { 
            $weberrors .= "\n Missing Early Reg Qty.";   
         } else {
            $temp2 = trim($_POST['hid_early_reg_qty']);
            $temp1 = preg_match($regex_num, $temp2);
            if ( $temp2 == "" || $temp1 == false || $temp1 == 0 ) {
              $weberrors .= "\n Invalid Reg Page Early Reg Qty [" . $temp2 . "].";
            } else if ( $temp2 > $this->reglib->NumPersons ) {
              $weberrors .= "\n Reg Page Early Reg Count [" . $temp2 . "] " .
              "is greater than the number of people [" . 
              number_format($this->reglib->NumPersons) . "].";
            } else { $this->reglib->EarlyRegQty = $temp2; }
        } // else POST is set
      } // if early_reg_discount not zero

      // Check for Late Reg fee
      if ( is_numeric($this->config->c_late_reg_fee)
            && $this->config->c_late_reg_fee > 0 ) {
         if (! isset($_POST['hid_late_reg_qty']) ) { 
            $weberrors .= "\n Missing Late Reg Qty.";   
         } else {
            $temp2 = trim($_POST['hid_late_reg_qty']);
            $temp1 = preg_match($regex_num, $temp2);
            if ( $temp2 == "" || $temp1 == false || $temp1 == 0 ) {
              $weberrors .= "\n Invalid Reg Page Late Reg Qty [" . $temp2 . "].";
            } else if ( $temp2 > $this->reglib->NumPersons ) {
              $weberrors .= "\n Reg Page Late Reg Count [" . $temp2 . "] " .
              "is greater than the number of people [" . 
              number_format($this->reglib->NumPersons) . "].";
            } else { $this->reglib->LateRegQty = $temp2; }
        } // else POST is set
      } // if late_reg_fee not zero

      // Check for Donation
      $this->reglib->Donation = 0;
      if ( $this->config->c_UseDonation != 0) {
        if (isset($_POST['price_donation'])) {
          $temp2 = trim($_POST['price_donation']);
          if ($temp2 != ""){
            $temp1 = preg_match($regex_num, $temp2);
            if ( $temp1 == false || $temp1 == 0 ) {
              $usererrors .= "\n<br />Invalid Donation Amount.";
            } // if invalid number
            else if ( $temp2 > 0 ){$this->reglib->Donation = $temp2 * 1.0;}
          } // if donation not empty
        } // if price_donation
        else { $weberrors .= "\n Missing Donation Field.";  }
      } // if Donation != 0
      
      // Check for Deposit and Balance Due
      $this->reglib->Deposit = 0;
      $this->reglib->BalDue = 0;
      if ($this->config->c_DepositPrice > 0){
        if (isset($_POST['chk_deposit'])) {
          $this->reglib->Deposit = $this->config->c_DepositPrice;
      } // if deposit > 0
      if (! isset($_POST['hid_bal_due']) ) { 
         $weberrors .= "\n Missing Balance Due.";  
      } else {
         $temp1 = trim($_POST['hid_bal_due']);
         if ( $temp1 != "" && (! is_numeric($temp1)) ) { 
            $weberrors .= "\n Balance Due is not numeric.";
         }
         if ( $temp1 * 1.0 < 0 ) { 
            $weberrors .= "\n Balance Due is less than zero."; 
         } else {$this->reglib->BalDue = $temp1 * 1.0;}
        } // else POST hid_bal_due is set
      } // if Deposit > 0
        
      // Check for PayPal fee checkbox and value
      $this->reglib->PayPalFeePay = 0;
      $this->reglib->PayPalFee = 0;
      if ($this->config->c_UsePayPalFee){
        if ( isset($_POST['chk_paypalfee']) ){
          $this->reglib->PayPalFeePay = 1;
        }
        if (! isset($_POST['hid_paypalfee']) ) { 
           $weberrors .= "\n Missing PayPal Fee amount.";  
        } 
        else {
          $temp1 = trim($_POST['hid_paypalfee']);
          if ( $temp1 != "" && (! is_numeric($temp1)) ) { 
            $weberrors .= "\n PayPal Fee is not numeric.";
          }
          if ( $temp1 * 1.0 <= 0 ) { 
            $weberrors .= "\n PayPal Fee is zero or less."; 
          } else {
            $this->reglib->PayPalFee = $temp1;
          }
        } // else hid_paypalfee exists
      } // if UsePayPalFee

      // Check for Total
      $this->reglib->TotalGross = 0;
      if (! isset($_POST['hid_total']) ) { 
         $weberrors .= "\n Missing Total Price.";  
      } else {
         $temp1 = trim($_POST['hid_total']);
         if ( $temp1 != "" && (! is_numeric($temp1)) ) { 
            $weberrors .= "\n Total Price is not numeric.";
         }
         if ( $temp1 * 1.0 <= 0 ) { 
            $weberrors .= "\n Total Price is zero or less."; 
         } else {
            $this->reglib->TotalGross = $temp1;
         }
      } // else hid_total

      // Check for PayPal Total
      $this->reglib->PayPalGross = 0;
      if (! isset($_POST['hid_paypaltotal']) ) { 
         $weberrors .= "\n Missing Payment Total.";  
      } else {
         $temp1 = trim($_POST['hid_paypaltotal']);
         if ( $temp1 != "" && (! is_numeric($temp1)) ) { 
            $weberrors .= "\n Payment Price is not numeric.";
         }
         if ( $temp1 * 1.0 <= 0 ) { 
            $weberrors .= "\n Payment Price is zero or less."; 
         } else {
            $this->reglib->PayPalGross = $temp1;
         }
      } // else hid_paypaltotal

      // Check Total Price value
      $Total = 0;
      if ($this->reglib->TotalGross > 0) {
        // Add up all registration options
        for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++) {
          $Total += $this->config->c_aryAttendance_Fields[$ii]["price"] * 
            $this->reglib->AttendAry[$ii];
        }
          
        // Add up all merchandise options
        for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++) {
          $Total += $this->config->c_aryMerchandise_Fields[$ii]["price"] * 
            $this->reglib->MerchAry[$ii];
        }
        // Subtract early reg discount
        $Total += $this->config->c_early_reg_discount * $this->reglib->EarlyRegQty;
        
        // Add late reg fee
        $Total += $this->config->c_late_reg_fee * $this->reglib->LateRegQty;
        
        // Add membership fees
        $Total += $this->reglib->Membership_Single_Qty * 
          $this->config->c_member_single_price * 1.0;
        $Total += $this->reglib->Membership_Family_Qty * 
          $this->config->c_member_family_price * 1.0;
        
        // Add Donation
        $Total += $this->reglib->Donation * 1.0;

        // Calculate PayPal Gross and PayPal Fee
        $PayPalGross = 0;
        $PPFee = 0;
        $BalDue = 0;
        if ($this->reglib->Deposit > 0){
          $PPFee = (($this->reglib->Deposit + $this->config->c_paypal_fee_fixed) / 
            (1.0 - $this->config->c_paypal_fee_percent)) - $this->reglib->Deposit;
          if ( $this->reglib->PayPalFeePay ) { 
            $PayPalGross = ($this->reglib->Deposit + 
              $this->config->c_paypal_fee_fixed ) / 
              (1.0 - $this->config->c_paypal_fee_percent);
            $Total += $PPFee;
            $BalDue = sprintf("%.2f", ($Total - $PPFee - $this->reglib->Deposit));
          } 
          else { // not paying paypal fee
            $PayPalGross = $this->reglib->Deposit;
            $BalDue = sprintf("%.2f", ($Total - $this->reglib->Deposit));
          }
        } // if deposit
        else { // no deposit
          $PPFee = (($Total + $this->config->c_paypal_fee_fixed ) / 
            (1.0 - $this->config->c_paypal_fee_percent)) - $Total;
          if ( $this->reglib->PayPalFeePay ) {
            $PayPalGross = ($Total + $this->config->c_paypal_fee_fixed ) / 
              (1.0 - $this->config->c_paypal_fee_percent);
            $Total += $PPFee;
          }
          else { $PayPalGross = $Total; }
        } // else no deposit
        $Total = sprintf("%.2f", $Total);
        $PayPalGross = sprintf("%.2f", $PayPalGross);
        $PPFee = sprintf("%.2f", $PPFee);

        $totalerr = 0;
        if ( $Total != $this->reglib->TotalGross ){
          $weberrors .= "\n Total not correct: Calculated=|" . 
          $Total . "|, Specified=|" . $this->reglib->TotalGross ."|\n";
          $totalerr = 1;
        }
        if ( $PayPalGross != $this->reglib->PayPalGross ){
          $weberrors .= "\n Payment not correct: Calculated=|" . 
          $PayPalGross . "|, Specified=|" . $this->reglib->PayPalGross ."|\n";
          $totalerr = 1;
        }

        if ( $this->config->c_UsePayPalFee 
            && ($PPFee != $this->reglib->PayPalFee) ){
          $weberrors .= "\n PayPal Fee not correct: Calculated=|" . 
          $PPFee . "|, Specified=|" . $this->reglib->PayPalFee ."|\n";
          $totalerr = 1;
        }
        if ( $BalDue != $this->reglib->BalDue ){
          $weberrors .= "\n Balance Due not correct: Calculated=|" . 
          $BalDue . "|, Specified=|" . $this->reglib->BalDue ."|\n";
          $totalerr = 1;
        }
        if ( $totalerr ){
          $weberrors .= "\n Total=|" . $Total . "|,";
          $weberrors .= "\n UsePayPalFee=|" . $this->config->c_UsePayPalFee . "|,";
          $weberrors .= "\n PayPalFeePay=|" . $this->reglib->PayPalFeePay . "|,";
          $weberrors .= "\n PayPal Fee=|" . $this->reglib->PayPalFee . "|,";
          $weberrors .= "\n PayPal Gross=|" . $this->reglib->PayPalGross . "|,";
          $weberrors .= "\n Deposit=|" . $this->reglib->Deposit . "|,";
          $weberrors .= "\n Bal Due=|" . $this->reglib->BalDue . "|,";
          $weberrors .= "\n NumAQtys=|" . $this->reglib->NumAQtys . "|,";
          for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++) {
            $weberrors .= "\nQty " . 
            $this->config->c_aryAttendance_Fields[$ii]["abbrev"] . "=|" .
            $this->reglib->AttendAry[$ii] . "|, Price=|" . 
            $this->config->c_aryAttendance_Fields[$ii]["price"] . "|";
          }
          for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++) {
            $weberrors .= "\nQty " . 
            $this->config->c_aryMerchandise_Fields[$ii]["abbrev"] . "=|" 
            . $this->reglib->MerchAry[$ii] . "|, Price=|" . 
            $this->config->c_aryMerchandise_Fields[$ii]["price"] . "|";
          }
          if ( $this->config->c_early_reg_discount != 0) {
            $weberrors .= "\nEarly Reg Qty=|" . $this->reglib->EarlyRegQty 
            . "|, Discount=|" . $this->config->c_early_reg_discount . "|";
          }
          if ( $this->config->c_member_single_price != 0) {
            $weberrors .= "\nSingle Membership Qty=|" 
            . $this->reglib->Membership_Single_Qty 
            . "|, Price=|" . $this->config->c_member_single_price . "|";
          }
          if ( $this->config->c_member_family_price != 0) {
            $weberrors .= "\nFamily Membership Qty=|" 
            . $this->reglib->Membership_Family_Qty 
            . "|, Price=|" . $this->config->c_member_family_price . "|";
          }
        } // if $totalerr
      } // if TotalGross > 0

      $temp = "";
      if ( $weberrors != "" ) {
        if ($usererrors != "") {
          $temp = "User Errors:\n" . $usererrors . "\n\n";
        }
        $temp .= "Web Errors:\n" . $weberrors . "\n";
        $this->reglib->Report_Web_Errors($temp, "");
        $this->reglib->Report_User_Errors($usererrors . 
          "\n<br /><br />An Unexpected Error has occurred." .
          "\n<br />The webmaster has been notified." .
          "\n<br />Please try again later.");
        return(false);
      } // if weberrors
      elseif ( $usererrors != "" ) { 
        $this->reglib->Report_User_Errors($usererrors);
        return(false);
      } // elseif usererrors


      // Create an Invoice ID for this submission:
      // Date_Time_FirstName_LastName_Email
      $InvoiceID = $this->config->c_EventAbbrev . 
        "_" . date("Y_m_d_H_i_s") . "_";
      $InvoiceID .= $this->reglib->FNameAry[1] . 
        "_" . $this->reglib->LNameAry[1];
      $InvoiceID .= "_" . $this->reglib->Email;
      $this->reglib->InvoiceID = 
        str_replace("'", "", str_replace(" ", "_", $InvoiceID));
      $this->reglib->Status = "Pending";

      return(true);
   } // Validate_Form


   function Save_Data(){

      $sql = "";

      $err = $this->regdb->db_connect();
      if ($err != "") {
         $this->reglib->Report_Web_Errors($err, ""); 
         $this->reglib->Report_User_Errors("An Unexpected Error has occurred." .
               "\n<br />The webmaster has been notified." .
               "\n<br />Please try again later.");
         return(false);
      }

      // Check if already registered
      $sql = "Select num_regnum, txt_invoiceid, txt_status From ";
      $sql .= $this->config->c_dbtable;
      $sql .= " WHERE txt_firstname1 = '" . $this->SQLEscape($this->reglib->FNameAry[1]);
      $sql .= "' AND txt_lastname1 = '" . $this->SQLEscape($this->reglib->LNameAry[1]);
      $sql .= "' AND txt_email1 = '" . $this->SQLEscape($this->reglib->Email);
      $sql .= "' ORDER BY num_regnum";
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      $msg = "";
      if ( $this->db_result && mysqli_num_rows($this->db_result) > 0 ) {
         while ($row = mysqli_fetch_assoc($this->db_result)){
            if ($row['txt_status'] == 'Pending' || $row['txt_status'] == 'Refunded') {
               // Do nothing, allow multiple pending registrations
               // and a new registration after a refund
               $msg = $msg; 
            } elseif ($row['txt_status'] == 'Paid') {
               $msg .= "\n<br /> Already registered and paid.\n<br />";
            } else {
               $msg .= "\n<br /> Already registered, Status = " . $row['txt_status'];
            }
         } //while
         if ( $msg != ""){
            $msg = $this->reglib->FNameAry[1] . " " . $this->reglib->LNameAry[1] .
               " (" . $this->reglib->Email . ")\n<br />" . $msg;
            $msg .= "\n<br /> Contact the <a href='" . "mailto:";
            $msg .= $this->config->c_webmaster_email . "?subject=" . 
              str_replace("'", "", $this->config->c_EventName);
            $msg .= " Registration Problem'>Webmaster</a>";
            $msg .= " to correct registration problems.\n<br />";
            $this->reglib->Report_User_Errors($msg);
            return(false);            
         }
         mysqli_free_result($this->db_result);
      } // if result

      // Add record to database, with all data
      $sql = "INSERT INTO " . $this->config->c_dbtable;
      $sql .= " (txt_invoiceid, ";
      if ($this->config->c_Use_Do_Not_Share){$sql .= "chk_sharedata, ";}
      $sql .= "num_numpersons, ";
      // Add person data field names
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
         $sql .= "txt_firstname$ii, txt_lastname$ii, ";
         if ($this->config->c_UseGenderData) { $sql .= "rad_gender$ii, "; }
         if ($this->config->c_UseChildData && $ii > 1) {
           $sql .= "chk_child$ii, ";
           if ($this->config->c_UseChildAge) {
             $sql .= "num_childage$ii, ";
           }
         } // if UseChildData

         if ($this->config->c_UseStudentData) {
           $sql .= "chk_student$ii, ";
         }
      } // for

      // Phone fields
      $iimax = count($this->reglib->PhoneData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= $this->reglib->PhoneData[$ii]["name"] . ", ";
        $sql .= $this->reglib->PhoneData[$ii]["name"] . "_type, ";
      } // for each phone
  
     // Other data field names. 
     // DB column name = Ctrl field name.
     // DB column names are NOT case sensitive.
     for ($ii = 0; $ii < $this->reglib->NumDQtys; $ii++) {
         $sql .= $this->reglib->GetFieldCtrlName("data", $ii) . ", ";
      } // for

      // Radio Button fields
      $iimax = count($this->reglib->RadioData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= $this->reglib->RadioData[$ii]["name"] . ", ";
      } // for each radio button
  
      // Selection List fields
      $iimax = count($this->reglib->SelListData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= $this->reglib->GetFieldCtrlName("select", $ii) . ", ";
      } // for each selection list

      // Musician Data field names
      $jjmax = count($this->config->c_aryMusician_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $sql .= $this->reglib->GetFieldCtrlName("musician", $jj) . "$ii, ";
         } // for jj
      } // for ii

      // Caller Data field names
      $jjmax = count($this->config->c_aryCaller_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
           $sql .= $this->reglib->GetFieldCtrlName("caller", $jj) . "$ii, ";
         } // for jj
      } // for ii

      // Add attendance qty names
      $jj = 0;
      for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++) {
        $jj = $ii + 1;
        $sql .= "txt_aname$jj, num_aprice$jj, num_aqty$jj, ";
      }

      // Add merchandise qty names
      for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++) {
        $jj = $ii + 1;
        $sql .= "txt_mname$jj, num_mprice$jj, num_mqty$jj, ";
      }

      // Add membership fees
      if ($this->config->c_member_single_price > 0 
          || $this->config->c_member_family_price > 0){
        $sql .= "num_memsingleprice, num_memsingleqty, ";
        $sql .= "num_memfamilyprice, num_memfamilyqty, ";
      }

      // Early Registration discount
      if ($this->config->c_early_reg_discount != 0){
         $sql .= "num_earlyregdiscount, num_earlyregqty, ";
      }
      
      // Late Registration fee
      if ($this->config->c_late_reg_fee != 0){
         $sql .= "num_lateregfee, num_lateregqty, ";
      }
      
      // Donation
      if ($this->config->c_UseDonation != 0){$sql .= "num_donation, ";}

      // Deposit
      if ($this->config->c_DepositPrice > 0){
        $sql .= "num_deposit, num_balancedue, ";
      }

      if ($this->config->c_UsePayPalFee){$sql .= "chk_paypalfeepay, ";}
      $sql .= "num_paypalfee, num_paypalgross, ";
      $sql .= "num_totalgross, num_totalnet, txt_submitdate, ";
      $sql .= "num_statusid, txt_status, txt_statusdate";

      // Add values of the data fields
      $sql .= " ) Values ( ";
      $sql .= "'" . $this->reglib->InvoiceID . "', ";
      if ($this->config->c_Use_Do_Not_Share){
        $sql .= $this->reglib->ShareData . ", ";
      }
      $sql .= $this->reglib->NumPersons . ", ";
      
      // Add person data
      for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++){
        $sql .= "'" . $this->SQLEscape($this->reglib->FNameAry[$ii]);
        $sql .= "', '" . $this->SQLEscape($this->reglib->LNameAry[$ii]) . "', ";
        if ($this->config->c_UseGenderData) {
          $sql .= "'" . $this->reglib->GenderAry[$ii] . "', ";
        }

        if ($this->config->c_UseChildData && $ii > 1) {
          $sql .= $this->reglib->ChildChkAry[$ii] . ", ";
          if ($this->config->c_UseChildAge) {
            $sql .= $this->reglib->ChildAgeAry[$ii] . ", ";
          }
        } // if UseChildData
        
        if ($this->config->c_UseStudentData) {
          $sql .= $this->reglib->StudentAry[$ii] . ", ";
        }
      } // for

      // Phone fields
      $iimax = count($this->reglib->PhoneData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= "'" . $this->reglib->PhoneData[$ii]["value"] . "', '";
        $sql .= $this->reglib->PhoneData[$ii]["type"] . "', ";
      } // for each phone
  
     // Other data field values.
     for ($ii = 0; $ii < $this->reglib->NumDQtys; $ii++) {
        $ftype = $this->config->c_aryField_Defs[$ii]["type"];
        $temp = $this->reglib->DataAry[$ii];
        $fvalue = $this->reglib->FieldValue($temp, $ftype, "num");
        $sql .= "'" . $this->SQLEscape($fvalue) . "', ";
      }

      // Radio Button fields
      $iimax = count($this->reglib->RadioData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= "'" . $this->reglib->RadioData[$ii]["value"] . "', ";
      } // for each radio button

      // Selection List fields
      $iimax = count($this->reglib->SelListData);
      for ($ii = 0; $ii < $iimax; $ii++) {
        $sql .= "'" . $this->reglib->SelListData[$ii]["value"] . "', ";
      } // for each selection list

      // Musician Data
      $jjmax = count($this->config->c_aryMusician_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_musicians; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ftype = $this->config->c_aryMusician_Fields[$jj]["type"];
            $ctl_name = $this->reglib->GetFieldCtrlName("musician", $jj) . $ii;
         		$temp = $this->reglib->MusDataAry[$ctl_name];
            $fvalue = $this->reglib->FieldValue($temp, $ftype, "num");
            $sql .= "'" . $this->SQLEscape($fvalue) . "', ";
         } // for jj
      } // for ii

      // Caller Data
      $jjmax = count($this->config->c_aryCaller_Fields);
      for ($ii = 1; $ii <= $this->config->c_max_callers; $ii++){
         for ($jj = 0; $jj < $jjmax; $jj++) {
            $ftype = $this->config->c_aryCaller_Fields[$jj]["type"];
            $ctl_name = $this->reglib->GetFieldCtrlName("caller", $jj) . $ii;
         		$temp = $this->reglib->CallerDataAry[$ctl_name];
            $fvalue = $this->reglib->FieldValue($temp, $ftype, "num");
            $sql .= "'" . $this->SQLEscape($fvalue) . "', ";
         } // for jj
      } // for ii

      // Add Attendance quantities
      for ($ii = 0; $ii < $this->reglib->NumAQtys; $ii++) {
         $sql .= "'" . $this->config->c_aryAttendance_Fields[$ii]["abbrev"] . "', ";
         $sql .= sprintf("%.2f", $this->config->c_aryAttendance_Fields[$ii]["price"]) . ", ";
         $sql .= $this->reglib->AttendAry[$ii] . ", ";
      }

      // Add Merchandise quantities
      for ($ii = 0; $ii < $this->reglib->NumMQtys; $ii++) {
         $sql .= "'" . $this->config->c_aryMerchandise_Fields[$ii]["abbrev"] . "', ";
         $sql .= sprintf("%.2f", $this->config->c_aryMerchandise_Fields[$ii]["price"]) . ", ";
         $sql .= $this->reglib->MerchAry[$ii] . ", ";
      }

      // Add membership fees
      if ($this->config->c_member_single_price > 0 
          || $this->config->c_member_family_price > 0){
        $sql .= sprintf("%.2f", $this->config->c_member_single_price) . ", ";
        $sql .= $this->reglib->Membership_Single_Qty . ", ";
        $sql .= sprintf("%.2f", $this->config->c_member_family_price) . ", ";
        $sql .= $this->reglib->Membership_Family_Qty . ", ";
      }

      // Early Registration discount
      if ($this->config->c_early_reg_discount != 0){
         $sql .= sprintf("%.2f", $this->config->c_early_reg_discount) . ", ";
         $sql .= $this->reglib->EarlyRegQty . ", ";
      }
      
      // Late Registration fee
      if ($this->config->c_late_reg_fee != 0){
         $sql .= sprintf("%.2f", $this->config->c_late_reg_fee) . ", ";
         $sql .= $this->reglib->LateRegQty . ", ";
      }
      
      // Donation
      if ($this->config->c_UseDonation != 0){
        $sql .= sprintf("%.2f", $this->reglib->Donation) . ", ";
      }

      // Deposit
      if ($this->config->c_DepositPrice > 0){
        $sql .= sprintf("%.2f", $this->reglib->Deposit) . ", ";
        $sql .= sprintf("%.2f", $this->reglib->BalDue) . ", ";
      }

      if ($this->config->c_UsePayPalFee){
        $sql .= $this->reglib->PayPalFeePay . ", ";
      }
      $sql .= sprintf("%.2f", $this->reglib->PayPalFee) . ", ";
      $sql .= sprintf("%.2f", $this->reglib->PayPalGross) . ", ";
      $sql .= sprintf("%.2f", $this->reglib->TotalGross) . ", ";
      $sql .= sprintf("%.2f", $this->reglib->TotalNet) . ", '";
      $sql .= $this->config->c_today  . "', ";
      $sql .= $this->reglib->AryStatusIDs[$this->reglib->Status]; 
      $sql .= ", '" . $this->reglib->Status . "', '" . $this->config->c_today;
      $sql .= "' ) ";

      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
         $this->reglib->Report_Web_Errors("Error saving data to database:\n" .
          "SQL=$sql\nERROR:" , mysqli_error($this->regdb->dbh));
         $this->reglib->Report_User_Errors("An Unexpected Error has occurred." .
               "\n<br />The webmaster has been notified." .
               "\n<br />Please try again later.");
         return(false);
      } // if 

      // Get Registration Number
      $sql = "SELECT * from " . $this->config->c_dbtable;
      $sql .= " WHERE txt_invoiceid = '";
      $sql .= $this->reglib->InvoiceID . "' ";
      $sql .= " ORDER By num_regnum";
      
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
         $errmsg = "Error getting data for Invoice " . $this->reglib->InvoiceID;
         $this->reglib->Report_Web_Errors("***** $errmsg", 0);
         $this->regdb->db_close($this->db_result);
         return(false);
      } // if
   
      if (mysqli_num_rows($this->db_result) <= 0){
         $errmsg = "No data found for invoice " .$this->reglib->InvoiceID;
         $this->reglib->Report_Web_Errors("PayPAL IPN Error: $errmsg", "");
         $this->regdb->db_close($this->db_result);
         return(false);
      } // if
  
      $row = mysqli_fetch_assoc($this->db_result);
      $this->reglib->RegNum = $row['num_regnum'];
      
      // Log registration data to file, in case database goes bad
      $this->reglib->log_registration();

      // optionally send pending email to webmaster
      if ($this->config->c_email_pending) {$this->reglib->Send_Emails(1);}

      // //// return(false); 
      return(true);
   } // Save_Data


   function Send_Paypal(){

      $query = "";
      $idx1 = -1;

      // Build GET values to send
      $query = "?cmd=_xclick";
      $query .= "&invoice=" . $this->reglib->InvoiceID;
      $query .= "&item_name=" . str_replace(" ", "+", $this->config->c_EventName);
      $query .= "&business=" . $this->config->c_business_email;
      $query .= "&amount=" . sprintf("%.2f", $this->reglib->PayPalGross);
      $query .= "&first_name=" . urlencode($this->reglib->FNameAry[1]);
      $query .= "&last_name=" . urlencode($this->reglib->LNameAry[1]);
      $idx1 = $this->reglib->CtlIsUsed('street');
      if ( $idx1 >= 0 ) {
        $query .= "&address1=" . urlencode($this->reglib->DataAry[$idx1]);
      }
      $idx1 = $this->reglib->CtlIsUsed('city');
      if ( $idx1 >= 0 ) {
        $query .= "&city=" . urlencode($this->reglib->DataAry[$idx1]);
      }
      $idx1 = $this->reglib->CtlIsUsed('state');
      if ( $idx1 >= 0 ) {
        $query .= "&state=" . urlencode($this->reglib->DataAry[$idx1]);
      }
      $idx1 = $this->reglib->CtlIsUsed('zip');
      if ( $idx1 >= 0 ) {
        $query .= "&zip=" . urlencode($this->reglib->DataAry[$idx1]);
      }

      $query .= "&no_shipping=1&no_note=1";
      $query .= "&notify_url=" . $this->config->c_notify_url;
      if ( strpos($this->config->c_paypal_ipn_url, "sandbox") > 0 ) {
         // //// $query .= "&return=" . $this->config->c_return_url;
      }

      if ( $this->config->c_paypal_page_style != "" ) {
        $query .= "&page_style=" . $this->config->c_paypal_page_style;
      }

      $this->reglib->log_ipn("Sending to Paypal, Invoice=[" . 
        $this->reglib->InvoiceID . "]", 0);

      // send via GET
      header('Content-Type: application/x-www-form-urlencoded');
      header("Location: " . $this->config->c_paypal_ipn_url  . $query);
      
   } // Send_Paypal


  function main(){
    if ($this->config->c_dump_form_debug) { $this->reglib->Dump_Form(); }
    else {
      if ( $this->Validate_Form() ){
        $this->reglib->log_ipn("***** Starting PP-Send for Invoice=[" . 
          $this->reglib->InvoiceID . "]", 0);
        if ( $this->Save_Data() ) {
          $this->Send_Paypal();
         } // if Save_Data
      } // if Validate_Form
    } // else
  } // main

} // Online_Reg_PP_Send_Class

$ppsender = new Online_Reg_PP_Send_Class();
$ppsender->main();

?>
