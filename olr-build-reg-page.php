<?php
// Copyright 2016 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.4

require_once 'olr-config.php';
require_once 'olr-lib.php';

class Online_Reg_Page_Class{
  var $config, $reglib;

   // Constructor
  function Online_Reg_Page_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
  }

  function Build_Page_Header($fh){
  
    $hdr = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' ";
    $hdr .= "'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
    $hdr .= "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>\n";
    $hdr .= "<head>\n";
    $hdr .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n";
    $hdr .= "<meta http-equiv='Content-Script-Type' content='text/javascript' />\n";
    $hdr .= "<title>" . $this->config->c_EventName . " Online Registration</title>\n";
    $hdr .= "<meta name='viewport' content='width=device-width, initial-scale=1' />\n";
    $hdr .= "<link rel='stylesheet' type='text/css' href='olr-styles.css' />\n";
    $hdr .= "</head>\n";
    fwrite($fh, $hdr); 
  } // Build_Page_Header

  function Build_Js_Init_File(){
    // Delete existing file, create new file.
    $temp = "";
    $fh = "";
    $fname = "olr-init.js";
    if ( file_exists($fname) ) {
       $fh = fopen($fname, "r");
       fclose($fh);
       unlink(realpath($fname));
    } // if file exists
    $fh = fopen($fname, "w");

    // Write Javascript parameters
    if (isset($this->config->c_paypal_fee_fixed) & ! is_null($this->config->c_paypal_fee_fixed))
    {$temp .= "var g_paypal_fee_fixed = ". $this->config->c_paypal_fee_fixed . ";\n";}
    else {$temp .= "var g_paypal_fee_fixed = 0;\n";}
    
    if (isset($this->config->c_paypal_fee_percent) & ! is_null($this->config->c_paypal_fee_percent))
    {$temp .= "var g_paypal_fee_percent = ". $this->config->c_paypal_fee_percent . ";\n";}
    else {$temp .= "var g_paypal_fee_percent = 0;\n";}

    if (isset($this->config->c_attendance_multi_select) & ! is_null($this->config->c_attendance_multi_select))
    {$temp .= "var g_attendance_multi_select = ". $this->config->c_attendance_multi_select . ";\n";}
    else {$temp .= "var g_attendance_multi_select = 0;\n";}

    if (isset($this->config->c_UseGenderData) & ! is_null($this->config->c_UseGenderData))
    {$temp .= "var g_use_gender_data = ". $this->config->c_UseGenderData . ";\n";}
    else {$temp .= "var g_use_gender_data = 0;\n";}

    if (isset($this->config->c_early_late_reg_cutoff_date) 
        & ! is_null($this->config->c_early_late_reg_cutoff_date)) {
      if (isset($this->config->c_early_reg_discount) 
          & ! is_null($this->config->c_early_reg_discount)) {
            if ($this->config->c_early_reg_discount != 0) {
              $temp .= "CheckEarlyRegDate('";
              $temp .= $this->config->c_early_late_reg_cutoff_date . "', 0);\n";
           } // if discount != 0
        } // if early reg discount
      if (isset($this->config->c_late_reg_fee)
        & ! is_null($this->config->c_late_reg_fee)) {
        if ($this->config->c_late_reg_fee != 0) {
          $temp .= "CheckLateRegDate('";
          $temp .= $this->config->c_early_late_reg_cutoff_date . "', 0);\n";
        } // if late reg fee != 0
      } // if late reg fee
    } // if reg cutoff date

    $temp .= "UpdateTotal(false);\n";
    fwrite($fh, $temp);
    fclose($fh);
  } // Build_Js_Init_File

  function Build_Page_Footer($fh){
    $this->Build_Js_Init_File();
    $temp = "</form>\n";
    if ($this->config->c_reg_system_info_in_footer){
      $temp .= "<p style='font-family:sans-serif; font-size:8pt; text-align:center;'>";
      $temp .= "Created by <a href='http://www.glennman.com/online-reg/home.html' target='_blank'>GM Online Registration System</a>";
      $temp .= ", version " . $this->config->c_olr_version . "</p>\n";
    }
    $temp .= "<script type='text/javascript' src='olr-validate.js'></script>\n";
    $temp .= "<script type='text/javascript' src='olr-init.js'></script>\n";
    $temp .= "</div>\n";
    $temp .= "</body>\n";
    $temp .= "</html>\n";
    fwrite($fh, $temp); 
  } // Build_Page_Footer

  function Build_Person_Fields($fh){

    $temp = "";
    $name_max_length = $this->config->c_name_max_length;
    // First person, the Payer
    $temp ="<span class='olr-label olr-required'>First Name:&nbsp;</span>";
    $temp .= "  <input type='text' name='txt_firstname1'";
    $temp .= "  size='" . $name_max_length . "' maxlength='";
    $temp .= $name_max_length . "' />&nbsp;&nbsp;&nbsp;\n";
    $temp .= "<span class='olr-label olr-required'>Last Name:&nbsp;</span>";
    $temp .= "  <input type='text' name='txt_lastname1'";
    $temp .= "  size='" . $name_max_length . "' maxlength='" . $name_max_length . "'\n />";

    if ($this->config->c_UseGenderData){
      $temp .= "&nbsp;&nbsp;&nbsp;<span class='olr-label olr-required'>Gender:&nbsp;";
      if ($this->config->c_radio_before_label){
        $temp .= "<input type='radio' name='rad_gender1' value='F' /> F";
        $temp .= " &nbsp;&nbsp;<input type='radio' name='rad_gender1' value='M' /> M</span>\n";
      } else { // not c_radio_before_label
        $temp .= "F <input type='radio' name='rad_gender1' value='F' />";
        $temp .= " &nbsp;&nbsp;M <input type='radio' name='rad_gender1' value='M' /></span>\n";
      } // else not c_radio_before_label
    } // if c_UseGenderData

    if ($this->config->c_UseStudentData){
      if ($this->config->c_checkbox_before_label){
        $temp .= "&nbsp;&nbsp;&nbsp;<input type='checkbox' name='chk_student1' value='' />";
        $temp .= " <span class='olr-label'>Student</span>\n";
      } else { // not c_checkbox_before_label
        $temp .= "&nbsp;&nbsp;&nbsp;<span class='olr-label'>Student</span>";
        $temp .= " <input type='checkbox' name='chk_student1' value='' />\n";
      } // else not c_checkbox_before_label
    }  // if c_UseStudentData
    fwrite($fh, $temp . "<br />\n");
  
    // Output additional user name fields
    if ($this->config->c_max_persons > 1){
      // Create table, write header row
      $temp = "<table border='0' cellspacing='2' cellpadding='2'>\n";
      $temp .= "  <tbody>\n";
      $temp .= "    <tr>\n";
      $temp .= "      <td align='center' valign='top'>First Name</td>\n";
      $temp .= "      <td align='center' valign='top'>Last Name</td>\n";
      if ($this->config->c_UseGenderData){
        $temp .= "      <td align='center' valign='top'>Gender</td>\n";
      }
      if ($this->config->c_UseChildData){
        $temp .= "      <td align='center' valign='top'>Child</td>\n";
      }
      if ($this->config->c_UseChildAge){
        $temp .= "      <td align='center' valign='top'>Child Age</td>\n";
      }
      if ($this->config->c_UseStudentData){
        $temp .= "      <td align='center' valign='top'>Student</td>\n";
      }
      $temp .= "      </tr>\n";
      fwrite($fh, $temp);
  
      // One table row per person
      for ($ii = 2; $ii <= $this->config->c_max_persons; $ii++) {
        $temp = "    <tr>\n";
        $temp .= "      <td align='center'><input type='text' size='";
        $temp .= $name_max_length . "' maxlength='" . $name_max_length . "' \n";
        $temp .= "        name='txt_firstname" . $ii . "' /></td>\n";
        $temp .= "      <td align='center'><input type='text' size='";
        $temp .= $name_max_length . "' maxlength='" . $name_max_length . "' \n";
        $temp .= "        name='txt_lastname" . $ii . "' /></td>\n";
        if ($this->config->c_UseGenderData){
          $temp .= "      <td align='center' class='olr-required'>\n";
          if ($this->config->c_radio_before_label){
            $temp .= "        <input type='radio' name='rad_gender" . $ii . "' value='F' /> F&nbsp;\n";
            $temp .= "        <input type='radio' name='rad_gender" . $ii . "' value='M' /> M</td>\n";
          } else { // not c_radio_before_label
            $temp .= "        F <input type='radio' name='rad_gender" . $ii . "' value='F' />&nbsp;\n";
            $temp .= "        M <input type='radio' name='rad_gender" . $ii . "' value='M' /></td>\n";
          } // else not c_radio_before_label
        } // if c_UseGenderData

        if ($this->config->c_UseChildData){
          $temp .= "      <td align='center'><input type='checkbox' value='' \n";
          $temp .= "        name='chk_child" . $ii . "'  /></td>\n";
        }
        if ($this->config->c_UseChildAge){
          $temp .= "      <td align='center'><input type='text' size='2' maxlength='2' \n";
          $temp .= "        name='num_childage" . $ii . "' /></td>\n";
        }
        if ($this->config->c_UseStudentData){
          $temp .= "      <td align='center'><input type='checkbox' value='' \n";
          $temp .= "        name='chk_student" . $ii . "' /></td>\n";
        }
        $temp .= "    </tr>\n";
        fwrite($fh, $temp);
      } // for each person
      $temp = "  </tbody>\n";
      $temp .= "</table>\n";
      fwrite($fh, $temp);
    } // if max_persons > 1
  
    // Add "Do Not Share Information" checkbox
    if ($this->config->c_Use_Do_Not_Share){
      if ($this->config->c_checkbox_before_label){
        $temp = "<input type='checkbox' name='chk_noshare' value='' />";
        $temp .= "\nDo NOT share personal information<br />\n";
      } else { // not not c_checkbox_before_label
        $temp = "Do NOT share personal information";
        $temp .= "\n<input type='checkbox' name='chk_noshare' value='' /><br />\n";
      } // else not c_checkbox_before_label
      fwrite($fh, $temp);
    }
  } // Build_Person_Fields

  function Build_Phone_Fields($fh){
    $ctl_name = "";
    
    $iimax = count($this->config->c_aryPhone_Fields);
    for ($ii = 0; $ii < $iimax; $ii++) {
      // Build field for phone number
      $ctl_name = $this->reglib->GetFieldCtrlName("phone", $ii);
      $label = $this->config->c_aryPhone_Fields[$ii]["label"];
      $fsize = $this->config->c_aryPhone_Fields[$ii]["size"];

      // Determine if the control is required
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " olr-required";
      } // if required

      // Create label and field for phone number
      $temp = "<span class='olr-label" . $required . "'>$label:&nbsp;\n";
      $temp .= "<input type='text' name='" . $ctl_name . "' ";
      $temp .= "value='' maxlength='" . $fsize . "' />";
      $temp .= "&nbsp;&nbsp; (format: 123-456-7890) &nbsp;&nbsp;";

      // Create radio buttons for phone type
      $items = $this->config->c_aryPhone_Fields[$ii]["items"];
      $kkmax = count($items);
      $selindex = -1;
      // Get index of default selected item
      for ($kk = 0; $kk < $kkmax; $kk++){
        if (array_key_exists("default", $items[$kk])){
          $selindex = $kk;
          break;
        } // if default
      } // for $kk

      for ($kk = 0; $kk < $kkmax; $kk++){
        $ctl_name = str_replace("txt_", "rad_", $ctl_name);
        if ($this->config->c_radio_before_label){
          $temp .= "\n  <input type='radio' name='" . $ctl_name . "' ";
          $temp .= "value='" . $items[$kk]["value"] . "'";
          if ($kk == $selindex){$temp .= " checked='checked'";}
          $temp .= " /> " . $items[$kk]["label"] . "&nbsp;&nbsp;";
        } else { // not c_radio_before_label
          $temp .= "\n" . $items[$kk]["label"] . " <input type='radio' name='" . $ctl_name . "' ";
          $temp .= "value='" . $items[$kk]["value"] . "'";
          if ($kk == $selindex){$temp .= " checked='checked'";}
          $temp .= " />" . "&nbsp;&nbsp;";
        } // else not c_radio_before_label
      } // for each item
      $temp .= "\n</span><br />\n";
      fwrite($fh, $temp);
    } // for ii each phone field
  } // Build_Phone_Fields

  function Build_Data_Fields($fh){
    $ctl_name = "";
    $temp = "";
    
    // Add controls for data fields.
    $iimax = count($this->config->c_aryField_Defs);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->GetFieldCtrlName("data", $ii);
      // Determine if the control is required
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " olr-required";
      } // if required
      
      $ftype = $this->config->c_aryField_Defs[$ii]["type"];
      $line_width = $this->config->c_text_line_width;
      switch ($ftype) {
        case "check":
          if ($this->config->c_checkbox_before_label){
            $temp = "<input type='checkbox' name='" . $ctl_name;
            $temp .= "' value='' />\n";
            $temp .= "  <span class='olr-label" . $required . "'>";
            $temp .= $this->config->c_aryField_Defs[$ii]["label"] . "</span><br />\n";
          } else { // not not c_checkbox_before_label
            $temp = "<span class='olr-label" . $required . "'>";
            $temp .= $this->config->c_aryField_Defs[$ii]["label"] . "</span>";
            $temp .= "\n  <input type='checkbox' name='" . $ctl_name;
            $temp .= "' value='' /><br />\n";
          } // else not c_checkbox_before_label
          break;
        default:
          $temp = "<span class='olr-label" . $required . "'>";
          $temp .= $this->config->c_aryField_Defs[$ii]["label"] . " &nbsp;</span>";
          $fsize = $this->config->c_aryField_Defs[$ii]["size"];
          if ($fsize < $line_width){
            $temp .= "\n  <input type='text' name='" . $ctl_name;
            $temp .= "' size='" . $fsize;
            $temp .= "' maxlength='" . $fsize . "' />";
            if ( strpos(strtolower($ctl_name), "phone") !== false) {
              $temp .= "&nbsp;&nbsp; (format: 123-456-7890)";
            }
            $temp .= "<br />\n";
          } else {
            $rows = ceil($fsize/$line_width);
            $temp .= "(max length = $fsize chars)<br />\n";
            $temp .= "  <textarea name='" . $ctl_name;
            $temp .= "' cols='" . $line_width;
            $temp .= "' rows='" . $rows . "'></textarea><br />\n";
          }
      } // switch
      fwrite($fh, $temp);
    } // for ii each data field
  } // Build_Data_Fields// 

  function Build_Radio_Fields($fh){
    $ctl_name = "";
    
    // Add controls for radio fields.
    $iimax = count($this->config->c_aryRadio_Fields);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->GetFieldCtrlName("radio", $ii);
      $rlabel = $this->config->c_aryRadio_Fields[$ii]["label"];

      // Determine if the control is required
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " olr-required";
      } // if required
      
      $temp = "<br />\n";
      if ($rlabel != ""){
        $temp .= "<span class='olr-label" . $required . "'>$rlabel:&nbsp;\n";
      }
      $items = $this->config->c_aryRadio_Fields[$ii]["items"];
      $kkmax = count($items);
      // Default to first item selected, unless Required.
      // If Required, default to nothing selected,
      // to force user to select one.
      if ( $required == "" ) {$selindex = 0;}
      else {$selindex = -1;}
      // Get index of default selected item, if any
      for ($kk = 0; $kk < $kkmax; $kk++){
        if (array_key_exists("default", $items[$kk])){
          $selindex = $kk;
          break;
        } // if default
      } // for $kk
      for ($kk = 0; $kk < $kkmax; $kk++){
        if ($this->config->c_radio_before_label){
          $temp .= "  <input type='radio' name='" . $ctl_name . "' ";
          $temp .= "value='" . $items[$kk]["value"] . "'";
          if ($kk == $selindex){$temp .= " checked='checked'";}
          $temp .= " /> " . $items[$kk]["label"] . "&nbsp;&nbsp;\n";
        } else { // not c_radio_before_label
          $temp .= "  " . $items[$kk]["label"] . " <input type='radio' name='" . $ctl_name . "' ";
          $temp .= "value='" . $items[$kk]["value"] . "'";
          if ($kk == $selindex){$temp .= " checked='checked'";}
          $temp .= " />&nbsp;&nbsp;\n";
        } // else not c_radio_before_label
      } // for each item
      $temp .= "</span>\n";
      fwrite($fh, $temp);
    } // for ii each data field
  } // Build_Radio_Fields

  function Build_Select_Fields($fh){
    $ctl_name = "";
    
    // Add controls for select fields.
    $iimax = count($this->config->c_arySelect_Fields);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->GetFieldCtrlName("select", $ii);
      // Determine if the control is required
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " olr-required";
      } // if required
      
      $temp = "<br />\n";
      $temp .= "<span class='olr-label" . $required . "'>";
      $temp .= $this->config->c_arySelect_Fields[$ii]["label"] . "</span>\n";
      $temp .= "<select name='$ctl_name'>\n";
      $items = $this->config->c_arySelect_Fields[$ii]["items"];
      $kkmax = count($items);
      $selindex = 0;
      // Get index of default selected item
      for ($kk = 0; $kk < $kkmax; $kk++){
        if (array_key_exists("default", $items[$kk])){
          $selindex = $kk;
          break;
        } // if default
      } // for $kk
      for ($kk = 0; $kk < $kkmax; $kk++){
        $temp .= "  <option value='" . $items[$kk]["value"] . "'";
        if ($kk == $selindex){$temp .= " selected='selected'";}
        $temp .= ">" . $items[$kk]["label"] . "</option>\n";
      } // for each item
      $temp .= "</select>\n";
      fwrite($fh, $temp);
    } // for ii each data field
  } // Build_Select_Fields

  function Build_Musician_Caller_Fields($fh, $which){
    
    if ($which == "musician"){
      $max_persons = $this->config->c_max_musicians;
      $ary_field = $this->config->c_aryMusician_Fields;
      $legend = "Musicians";
    } else {
      $max_persons = $this->config->c_max_callers;
      $ary_field = $this->config->c_aryCaller_Fields;
      $legend = "Callers";
    }
    
    // Skip if none used
    if ($max_persons == 0){return(0);}
    $temp = "\n<fieldset class='olr-line-ht-150'>\n";
    $temp .= "  <legend> $legend </legend>\n";
    fwrite($fh, $temp);
    $jjmax = count($ary_field);
    // For each person
    for ($ii = 0; $ii < $max_persons; $ii++){
      // For each defined field
      $cbnum = 0;
      for ($jj = 0; $jj < $jjmax; $jj++){
        $kk = $ii + 1;
        $temp = "";
        if ($ii > 0 && $jj == 0) {$temp .= "\n<br />";}
        $ftype = $ary_field[$jj]["type"];
        $fsize = $ary_field[$jj]["size"];
        $ctl_name = $this->reglib->GetFieldCtrlName($which, $jj) . $kk;
        $line_width = $this->config->c_text_line_width;
        switch ($ftype) {
          case "check":
            if ($cbnum == 0) {$temp .= "\n<br />";}
            $cbnum += 1;
            if ($this->config->c_checkbox_before_label){
              $temp .= "\n<input type='checkbox' name='" . $ctl_name;
              $temp .= "' value='' />";
              $temp .= " <span class='olr-label'>";
              $temp .= $ary_field[$jj]["label"] . "</span>&nbsp;&nbsp;&nbsp;";
            } else { // not c_checkbox_before_label
              $temp = "\n<span class='olr-label'>";
              $temp .= $ary_field[$jj]["label"] . "</span>";
              $temp .= " <input type='checkbox' name='" . $ctl_name;
              $temp .= "' value='' />&nbsp;&nbsp;&nbsp;";
            } // else not c_checkbox_before_label
            break;
          default:
            if ($ii > 0 || $jj > 0) {$temp .= "\n<br />";}
            $temp .= "<span class='olr-label'>";
            $temp .= $ary_field[$jj]["label"] . "</span> ";
            if ($fsize < $line_width){
              $temp .= "<input type='text' name='" . $ctl_name;
              $temp .= "' size='" . $fsize;
              $temp .= "' maxlength='" . $fsize . "' />";
            } else {
              $rows = ceil($fsize/$line_width);
              $temp .= "(max length = $fsize chars)&nbsp;<br />\n";
              $temp .= "<textarea name='" . $ctl_name;
              $temp .= "' cols='" . $line_width;
              $temp .= "' rows='" . $rows . "'></textarea>";
            }
        } // switch
        fwrite($fh, $temp);
      } // for jj field
      fwrite($fh, "<br />\n");
    } // for ii person
    $temp = "</fieldset>\n";
    fwrite($fh, $temp);
  } // Build_Musician_Caller_Fields

  function Build_Attendance_Fields($fh){
    
    // Create fieldset
    $temp = "\n<fieldset class='olr-line-ht-150'>\n";
    if (count($this->config->c_aryMerchandise_Fields) == 0){
      $temp .= "  <legend> Attendance </legend>\n";
    } else {
      $temp .= "  <legend> Attendance and Merchandise </legend>\n";
    }
    fwrite($fh, $temp);
  
    // Create table for the options
    $temp = "<table border='0' cellspacing='0' cellpadding='4'>\n";
    $temp .= "  <tbody>\n";
    $temp .= "    <tr>\n";
    $temp .= "      <th align='left'>Option</th>\n";
    $temp .= "      <th align='left'>Price&nbsp;&nbsp;&nbsp;&nbsp;</th>\n";
    $temp .= "      <th align='right'>Qty</th>\n";
    $temp .= "    </tr>\n";
    fwrite($fh, $temp);
    
    // For each attendance option
    $iimax = count($this->config->c_aryAttendance_Fields);
    for ($ii = 0; $ii < $iimax; $ii++){
      $jj = $ii + 1;
      $temp = "    <tr>\n";
      $temp .= "      <td>" . $this->config->c_aryAttendance_Fields[$ii]["label"] . "</td>\n";
      $temp .= "      <td align='left'>$<span id='aprice" . $jj . "'>";
      $temp .= $this->config->c_aryAttendance_Fields[$ii]["price"] . "</span></td>\n";
      if ($this->config->c_max_persons > 1){
        $temp .= "      <td align='right'><input type='text' size='2' maxlength='2' value='0' \n";
        $temp .= "        name='num_aqty" . $jj . "' \n";
        $temp .= "        onchange='UpdateTotal(true);' /></td>\n";
      } else { // just 1 person, use radio buttons or check boxes
          if ($this->config->c_attendance_multi_select){
          $temp .= "      <td align='right'><input type='checkbox' ";
          $temp .= "name='chk_aqty" . $jj . "' value=''";
          $temp .= "\n        onchange='UpdateTotal(true);' /></td>\n";
        } // if multi_select
        else {
          $temp .= "      <td align='right'><input type='radio' ";
          $temp .= "name='rad_attend' value='" . $jj . "'";
          if ($ii == 0){ $temp .= " checked='checked'";}
          $temp .= "\n        onchange='UpdateTotal(true);' /></td>\n";
        } // else not multi_select
      }
      $temp .= "    </tr>\n";
      fwrite($fh, $temp);
    } // for 
    
    // Optional Early Registration Discount
    if ($this->config->c_early_reg_discount != 0){
      $temp = "    <tr>\n";
      $temp .= "      <td>Early Registration Discount</td>\n";
      $temp .= "      <td align='left'>$<span id='early_reg_discount'>";
      $temp .= $this->config->c_early_reg_discount . "</span></td>\n";
      $temp .= "      <td align='right'><span id='early_reg_qty'></span>\n";
      $temp .= "        <input type='hidden' value='' \n";
      $temp .= "         name='hid_early_reg_qty' /></td>\n";
      $temp .= "    </tr>\n";
      fwrite($fh, $temp);
    }
  
    // Optional Late Registration Fee
    if ($this->config->c_late_reg_fee != 0){
      $temp = "    <tr>\n";
      $temp .= "      <td>Late Registration Fee</td>\n";
      $temp .= "      <td align='left'>$<span id='late_reg_fee'>";
      $temp .= $this->config->c_late_reg_fee . "</span></td>\n";
      $temp .= "      <td align='right'><span id='late_reg_qty'></span>\n";
      $temp .= "        <input type='hidden' value='' \n";
      $temp .= "         name='hid_late_reg_qty' /></td>\n";
      $temp .= "    </tr>\n";
      fwrite($fh, $temp);
    }
  } // Build_Attendance_Fields

  function Build_Merchandise_Fields($fh){
   
    // If no merchandise, return
    if ( count($this->config->c_aryMerchandise_Fields) == 0 ) {return(0);}
    
    // For each merchandise option
    $iimax = count($this->config->c_aryMerchandise_Fields);
    for ($ii = 0; $ii < $iimax; $ii++){
      $jj = $ii + 1;
      $temp = "    <tr>\n";
      $temp .= "      <td>" . $this->config->c_aryMerchandise_Fields[$ii]["label"] . "</td>\n";
      $temp .= "      <td align='left'>$<span id='mprice" . $jj . "'>";
      $temp .= $this->config->c_aryMerchandise_Fields[$ii]["price"] . "</span></td>\n";
      if ($this->config->c_max_persons > 1){
        $temp .= "      <td align='right'><input type='text' size='2' maxlength='2' value='0' \n";
        $temp .= "        name='num_mqty" . $jj . "' \n";
      } else {
        $temp .= "      <td align='right'><input type='checkbox' value='' \n";
        $temp .= "        name='chk_mqty" . $jj . "' \n";
      }
      $temp .= "        onchange='UpdateTotal(true);' /></td>\n";
      $temp .= "    </tr>\n";
      fwrite($fh, $temp);
    } // for 
  } // Build_Merchandise_Fields

  function Build_Membership_Fields($fh){
    if ($this->config->c_member_single_price == 0 
        || $this->config->c_member_family_price == 0){ return;}
    
    $temp  = "    <tr>\n";
    $temp .= "      <td>Membership:</td>\n";
    if ($this->config->c_max_persons > 1){
      $temp .= "      <td colspan='2'>Single:&nbsp;$<span id='price_member_single'>";
      $temp .= $this->config->c_member_single_price . "</span>\n";
      $temp .= "        <input type='text' name='num_member_single'\n"; 
      $temp .= "          size='2' maxlength='2' value='' onchange='UpdateTotal(true);' />\n";
      if ($this->config->c_checkbox_before_label){
        $temp .= "          &nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='chk_member_family' value=''\n";
        $temp .= "          onchange='UpdateTotal(true);' />\n";
        $temp .= "          Family:&nbsp;$<span id='price_member_family'>";
        $temp .= $this->config->c_member_family_price . "</span></td>\n";
      } else { // not c_checkbox_before_label
        $temp .= "          &nbsp;&nbsp;&nbsp;&nbsp;Family:&nbsp;$<span id='price_member_family'>";
        $temp .= $this->config->c_member_family_price . "</span>\n";
        $temp .= "        <input type='checkbox' name='chk_member_family' value=''\n";
        $temp .= "          onchange='UpdateTotal(true);' /></td>\n";
      } // else not c_checkbox_before_label
    } else { // only 1 person, use radio buttons
      if ($this->config->c_radio_before_label){
        $temp .= "      <td colspan='2'>";
        $temp .= "        <input type='radio' name='rad_membership'\n"; 
        $temp .= "          value='none' checked='checked' onchange='UpdateTotal(true);' />";
        $temp .= "          None&nbsp;&nbsp;&nbsp;\n";
        $temp .= "        <input type='radio' name='rad_membership'\n"; 
        $temp .= "          value='single' onchange='UpdateTotal(true);' />";
        $temp .= "        Single:&nbsp;$<span id='price_member_single'>";
        $temp .= $this->config->c_member_single_price . "</span>&nbsp;&nbsp;&nbsp;\n";
        $temp .= "        <input type='radio' name='rad_membership' value='family'\n";
        $temp .= "          onchange='UpdateTotal(true);' />";
        $temp .= "          Family:&nbsp;$<span id='price_member_family'>";
        $temp .= $this->config->c_member_family_price . "</span></td>\n";
      } else { // not c_radio_before_label
        $temp .= "      <td colspan='2'>None:&nbsp;";
        $temp .= "        <input type='radio' name='rad_membership'\n"; 
        $temp .= "          value='none' checked='checked' onchange='UpdateTotal(true);' />\n";
        $temp .= "      &nbsp;&nbsp;&nbsp;&nbsp;Single:&nbsp;$<span id='price_member_single'>";
        $temp .= $this->config->c_member_single_price . "</span>\n";
        $temp .= "        <input type='radio' name='rad_membership'\n"; 
        $temp .= "          value='single' onchange='UpdateTotal(true);' />\n";
        $temp .= "          &nbsp;&nbsp;&nbsp;&nbsp;Family:&nbsp;$<span id='price_member_family'>";
        $temp .= $this->config->c_member_family_price . "</span>\n";
        $temp .= "        <input type='radio' name='rad_membership' value='family'\n";
        $temp .= "          onchange='UpdateTotal(true);' /></td>\n";
      } // else not c_radio_before_label
    } // else only 1 person
    $temp .= "    </tr>\n";
    fwrite($fh, $temp);
  } // Build_Membership_Fields

  function Build_Total_Fields($fh){
    
    $temp = "";
    if ($this->config->c_UseDonation > 0){
      $temp .= "    <tr>\n";
      $temp .= "      <td>Donation (OPTIONAL, but appreciated)</td>\n";
      $temp .= "      <td align='left' colspan='2'>$<input type='text' name='price_donation' " .
               "size='4' maxlength='4' value='0'\n" .
               "        onchange='UpdateTotal(true);' /></td>\n";
      $temp .= "    </tr>\n";
    } // donation
  
    // If using Deposit, output PP Fee, Total, Deposit, Payment, Balance
    if ($this->config->c_DepositPrice > 0){
      // PayPal Fee
      if ($this->config->c_UsePayPalFee) {
        $temp .= "    <tr>\n";
        $temp .= "      <td>PayPal Fee (OPTIONAL, but appreciated)</td>\n";
        $temp .= "      <td align='left'>$<span id='price_paypalfee'>0</span></td>\n";
        $temp .= "      <td align='right'>\n";
        $temp .= "        <input type='checkbox' name='chk_paypalfee' \n";
        $temp .= "         value='' onclick='UpdateTotal(true);'/>\n";
        $temp .= "        <input type='hidden' name='hid_paypalfee' \n";
        $temp .= "         value='0' /></td>\n";
        $temp .= "    </tr>\n";
      } // if UsePayPalFee
      $temp .= "    <tr>\n";
      $temp .= "      <td colspan='3'><hr /></td>\n";
      $temp .= "    </tr>\n";
      $temp .= "    <tr>\n";
      $temp .= "      <td>Total</td>\n";
      $temp .= "      <td align='left'>$<span id='total'>0</span></td>\n";
      $temp .= "      <td>&nbsp;</td>\n";
      $temp .= "    </tr>\n";
  
      $temp .= "    <tr>\n";
      $temp .= "      <td>Deposit ($<span id='price_deposit'>" . 
                    $this->config->c_DepositPrice . "</span>)</td>\n";
      $temp .= "      <td align='left'>$<span id='deposit_total'>0</span></td>\n";
      $temp .= "      <td align='right'><input type='checkbox' name='chk_deposit' \n";
      $temp .= "         value='' onclick='UpdateTotal(true);'/></td>\n";
      $temp .= "    </tr>\n";
  
  
      $temp .= "    <tr>\n";
      $temp .= "      <td>Payment</td>\n";
      $temp .= "      <td align='left'>$<span id='price_paypaltotal'>0</span></td>\n";
      $temp .= "      <td>&nbsp;</td>\n";
      $temp .= "    </tr>\n";
  
      $temp .= "    <tr>\n";
      if ($this->config->c_DepositDueDate == ""){
        $temp .= "      <td>Balance Due</td>\n";
      } else {
        $temp .= "      <td>Balance Due by " . $this->config->c_DepositDueDate . "</td>\n";
      }
      $temp .= "      <td align='left'>$<span id='price_bal_due'>0</span>\n";
      $temp .= "        <input type='hidden' name='hid_bal_due' value='' /></td>\n";
      $temp .= "      <td>&nbsp;</td>\n";
      $temp .= "    </tr>\n";
    } // if deposit
  
    // Not deposit, output PayPal Fee, Total
    else {
    // PayPal Fee
    if ($this->config->c_UsePayPalFee) {
      $temp .= "    <tr>\n";
      $temp .= "      <td>PayPal Fee (OPTIONAL, but appreciated)</td>\n";
      $temp .= "      <td align='left'>$<span id='price_paypalfee'>0</span></td>\n";
      $temp .= "      <td align='right'>\n";
      $temp .= "        <input type='checkbox' name='chk_paypalfee' \n";
      $temp .= "         value='' onclick='UpdateTotal(true);' />\n";
      $temp .= "        <input type='hidden' name='hid_paypalfee' value='0' /></td>\n";
      $temp .= "    </tr>\n";
    } // if UsePayPalFee
    $temp .= "    <tr>\n";
    $temp .= "      <td colspan='3'><hr /></td>\n";
    $temp .= "    </tr>\n";
    $temp .= "    <tr>\n";
    $temp .= "      <td>Total</td>\n";
    $temp .= "      <td align='left'>$<span id='total'>0</span></td>\n";
    $temp .= "      <td>&nbsp;</td>\n";
    $temp .= "    </tr>\n";
  
    } // else no deposit
  
    $temp .= "  </tbody>\n";
    $temp .= "</table>\n";
    $temp .= "<input type='hidden' name='hid_total' value='0' />\n";
    $temp .= "<input type='hidden' name='hid_paypaltotal' value='0' />\n";
    $temp .= "</fieldset>\n";
    fwrite($fh, $temp);
    
    // Output the Pay button
    $temp = "\n<fieldset>\n";
    $temp .= "  <legend> Payment </legend>\n";
    // Add text description of how to use the page.
    $temp .= "<p>Please fill in the form and click the Pay button. <br />\n";
    $temp .= "You will be asked to confirm before transferring to PayPal, ";
    $temp .= "and you can complete or cancel the transaction in PayPal. <br />\n";
    $temp .= "In PayPal you can pay via your PayPal account, or use a credit card. <br />\n";
    $temp .= "No PayPal account is needed for credit card payments. <br />\n";
    $temp .= "A confirmation email will be sent from both PayPal and from us ";
    $temp .= "when payment is completed (NO email if transaction is cancelled). </p>\n";
    fwrite($fh, $temp);
  
    $temp = "<p style='text-align:center;'>";
    $temp .= "<input type='submit' value=' Pay ' name='submit'\n";
    $temp .= "  onclick='return submit_form();' /></p>";
    $temp .= "</fieldset>\n";
    fwrite($fh, $temp);
  
    $temp = "<p>For online registration problems, please contact the webmaster: ";
    $temp .= $this->config->c_webmaster_email . "</p>\n";
    fwrite($fh, $temp);
  } // Build_Total_Fields

  function Build_Online_Reg_Page(){
    
    $temp = "";
    $fh = "";
  
    // Delete existing files, create new output file.
    $this->reglib->Delete_Files("not-db");
    $fh = fopen($this->config->c_online_reg_fname, "w");

    $this->Build_Page_Header($fh);
  
    // Create DIV for content, add online registration title
    $temp = "<body>\n";
    $temp .= "<div id='olr-content'>\n";
    $temp .= "<form method='post' id='OnlineRegForm' name='OnlineRegForm' action='olr-pp-send.php'>\n";
    $temp .= "<h2 style='text-align:center;'>";
    $temp .= $this->config->c_EventName;
    $temp .= "<br />Online Registration</h2>\n";
    fwrite($fh, $temp);
  
    fwrite($fh, "<p>Required fields are in <b>BOLD</b> </p>\n");

    // Output required fields
    // Add required fields hidden control.
    $temp = "\n<!-- list of required fields -->\n";
    $temp .= "<input type='hidden' name='required_fields' value='";
    $jjmax = count($this->reglib->RequiredAry);
    for ($jj = 0; $jj < $jjmax; $jj++) {
      if ($jj > 0) { $temp .= ",";}
      $temp .= $this->reglib->RequiredAry[$jj];
    }
    $temp .= "' />\n";
    fwrite($fh, $temp);

    $this->Build_Person_Fields($fh);
    $this->Build_Phone_Fields($fh);
    $this->Build_Data_Fields($fh);
    $this->Build_Radio_Fields($fh);
    $this->Build_Select_Fields($fh);
    $this->Build_Musician_Caller_Fields($fh, "musician");
    $this->Build_Musician_Caller_Fields($fh, "caller");
    $this->Build_Attendance_Fields($fh);
    $this->Build_Merchandise_Fields($fh);
    $this->Build_Membership_Fields($fh);
    $this->Build_Total_Fields($fh);
    $this->Build_Page_Footer($fh);
    
    fclose($fh);
  
    return "Registration Page Build Completed Successfully.<br />\n"; // .
//    "Click to view the <a target='_blank' href='" . 
//    $this->config->c_online_reg_fname . "'>Online Registration Page</a>";
  } // Build_Online_Reg_Page

} // Online_Reg_Page_Class
?>
