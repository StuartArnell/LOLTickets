<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0.
// See separate license file for license details.
// Version 1.3

require_once 'olr-config.php';
require_once 'olr-db.php';

class Online_Reg_DB_Table_Class{
  var $config, $regdb, $reglib, $sql, $db_result;
   
   // Constructor
  function Online_Reg_DB_Table_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->regdb = new Online_Reg_DB_Class();
    $this->title1 = $this->config->c_EventName . " Registration Builder";
    $this->title2 = $this->title1;
    $this->db_result = null;
    $this->sql = "";
  }

  function Build_SQL_Table_Header(){
    
    $this->sql .= "Create Table " . $this->config->c_dbtable . "(\n";
    $this->sql .= "num_regnum int auto_increment primary key,\n";
    $this->sql .= "txt_invoiceid varchar(200) not null,\n";
    $this->sql .= "txt_transactionid varchar(100),\n";
    $this->sql .= "txt_submitdate date not null,\n";
    $this->sql .= "num_statusid tinyint not null,\n";
    $this->sql .= "txt_status varchar(20) not null,\n";
    $this->sql .= "txt_statusdate date not null,\n";
  } // Build_SQL_Table_Header

  function Build_SQL_Person_Fields(){
    
    $name_max_len = $this->config->c_name_max_length;
    // First person, the Payer
    $this->sql .= "txt_firstname1 varchar(" . $name_max_len . ") not null,\n";
    $this->sql .= "txt_lastname1 varchar(" . $name_max_len . ") not null,\n";
    if ($this->config->c_UseGenderData){
      $this->sql .= "rad_gender1 varchar(1) not null,\n";
    }
    if ($this->config->c_UseStudentData){
      $this->sql .= "chk_student1 bool,\n";
    }

    // Output additional user name fields
    $max_persons = $this->config->c_max_persons;
    if ($max_persons > 1){
      for ($ii = 2; $ii <= $max_persons; $ii++) {
        $this->sql .= "txt_firstname" . $ii . " varchar(" . $name_max_len . "),\n";
        $this->sql .= "txt_lastname" . $ii . " varchar(" . $name_max_len . "),\n";
        if ($this->config->c_UseGenderData){
          $this->sql .= "rad_gender$ii varchar(1),\n";
        }
        if ($this->config->c_UseChildData){
          $this->sql .= "chk_child$ii bool,\n";
        }
        if ($this->config->c_UseChildAge){
          $this->sql .= "num_childage$ii tinyint,\n";
        }
        if ($this->config->c_UseStudentData){
          $this->sql .= "chk_student$ii bool,\n";
        }
      } // for each person
    } // if max_persons > 1

    // Add number of persons
    $this->sql .= "num_numpersons tinyint,\n";
    
    // Add "Do Not Share Information" checkbox, if used
    if ($this->config->c_Use_Do_Not_Share){
      $this->sql .= "chk_sharedata bool,\n";
    }
  } // Build_SQL_Person_Fields

  function Build_SQL_One_Col($col_name, $ftype, $fsize, $required){
    switch ($ftype) {
      case "check":
        $this->sql .= "$col_name bool,\n";
        break;
      case "num":
        if ($fsize <= 2){
          $this->sql .= "$col_name tinyint" . $required . ",\n";
        } else {
          $this->sql .= "$col_name decimal(6,2)" . $required . ",\n";
        }
        break;
      default:
        $this->sql .= "$col_name varchar($fsize)" . $required . ",\n";
    } // switch
  } // Build_SQL_One_Col

  function Build_SQL_Data_Fields(){
    $ctl_name = "";
    
    // Add columns for data fields.
    $iimax = count($this->config->c_aryField_Defs);
    for ($ii = 0; $ii < $iimax; $ii++) {
      $ctl_name = $this->reglib->GetFieldCtrlName("data", $ii);
      $ftype = $this->config->c_aryField_Defs[$ii]["type"];
      $fsize = $this->config->c_aryField_Defs[$ii]["size"];
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " not null";
      } // if required
      $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, $required);
    } // for ii each data field
  } // Build_SQL_Data_Fields

  function Build_SQL_Phone_Fields(){
    $ctl_name = "";
    
    $iimax = count($this->config->c_aryPhone_Fields);

    for ($ii = 0; $ii < $iimax; $ii++) {
      // Build SQL field for phone number
      $ctl_name = $this->reglib->GetFieldCtrlName("phone", $ii);
      $ftype = "text";
      $fsize = $this->config->c_aryPhone_Fields[$ii]["size"];
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " not null";
      } // if required
      $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, $required);
  
      // Build SQL field for phone type
      $ctl_name .= "_type";
      $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, $required);
    } // for ii each phone field
  } // Build_SQL_Phone_Fields

  function Build_SQL_Radio_Fields(){
    $ctl_name = "";
    
    // Add columns for data fields.
    $iimax = count($this->config->c_aryRadio_Fields);
    for ($ii = 0; $ii < $iimax; $ii++) {
      // Column name = rad_<group>
      $ctl_name = $this->reglib->GetFieldCtrlName("radio", $ii);
      $ftype = $this->config->c_aryRadio_Fields[$ii]["type"];
      $fsize = $this->config->c_aryRadio_Fields[$ii]["size"];
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " not null";
      } // if required
      $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, $required);
    } // for ii each radio field
  } // Build_SQL_Radio_Fields

  function Build_SQL_Select_Fields(){
    $ctl_name = "";
    
    // Add columns for data fields.
    $iimax = count($this->config->c_arySelect_Fields);
    for ($ii = 0; $ii < $iimax; $ii++) {
      // Column name = sel_<name>
      $ctl_name = $this->reglib->GetFieldCtrlName("select", $ii);
      $ftype = $this->config->c_arySelect_Fields[$ii]["type"];
      $fsize = $this->config->c_arySelect_Fields[$ii]["size"];
      $required = "";
      if ($this->reglib->Ctrl_Is_Required($ctl_name)){
        $required = " not null";
      } // if required
      $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, $required);
    } // for ii each select field
  } // Build_SQL_Select_Fields

  function Build_SQL_Musician_Caller_Fields($which){
    
    if ($which == "musician"){
      $max_persons = $this->config->c_max_musicians;
      $ary_field = $this->config->c_aryMusician_Fields;
    } else {
      $max_persons = $this->config->c_max_callers;
      $ary_field = $this->config->c_aryCaller_Fields;
    }
  
    // Skip if none used
    if ($max_persons == 0){return;}
    $jjmax = count($ary_field);
    // For each person
    for ($ii = 1; $ii <= $max_persons; $ii++){
      // For each defined field
      for ($jj = 0; $jj < $jjmax; $jj++){
        $ftype = $ary_field[$jj]["type"];
        $fsize = $ary_field[$jj]["size"];
        $ctl_name = $this->reglib->GetFieldCtrlName($which, $jj) . $ii;
        $this->Build_SQL_One_Col($ctl_name, $ftype, $fsize, "");
      }
    }
  } // Build_SQL_Musician_Caller_Fields

  function Build_SQL_Attendance_Fields(){
    
    // For each attendance option
    $iimax = count($this->config->c_aryAttendance_Fields);
    for ($ii = 1; $ii <= $iimax; $ii++){
      $this->sql .= "txt_aname$ii varchar(50),\n";
      $this->sql .= "num_aprice$ii decimal(6,2),\n";
      $this->sql .= "num_aqty$ii tinyint,\n";
    } // for 
    
    // Optional Early Registration Discount
    if ($this->config->c_early_reg_discount != 0){
      $this->sql .= "num_earlyregdiscount decimal(6,2),\n";
      $this->sql .= "num_earlyregqty tinyint,\n";
    }
  
    // Optional Late Registration Fee
    if ($this->config->c_late_reg_fee != 0){
      $this->sql .= "num_lateregfee decimal(6,2),\n";
      $this->sql .= "num_lateregqty tinyint,\n";
    }
  } // Build_SQL_Attendance_Fields

  function Build_SQL_Merchandise_Fields(){
    
    // If no merchandise, return
    if ( count($this->config->c_aryMerchandise_Fields) == 0 ) {return;}
    
    // For each merchandise option
    $iimax = count($this->config->c_aryMerchandise_Fields);
    for ($ii = 1; $ii <= $iimax; $ii++){
      $this->sql .= "txt_mname$ii varchar(50),\n";
      $this->sql .= "num_mprice$ii decimal(6,2),\n";
      $this->sql .= "num_mqty$ii tinyint,\n";
    } // for 
  } // Build_SQL_Merchandise_Fields

  function Build_SQL_Membership_Fields(){
  
    if ($this->config->c_member_single_price == 0 
        || $this->config->c_member_family_price == 0){ return;}
    
    $this->sql .= "num_memsingleprice decimal(6,2),\n";
    $this->sql .= "num_memsingleqty tinyint,\n";
    $this->sql .= "num_memfamilyprice decimal(6,2),\n";
    $this->sql .= "num_memfamilyqty tinyint,\n";
  } //   Build_SQL_Membership_Fields

  function Build_SQL_Total_Fields(){
    
    if ($this->config->c_UseDonation){
      $this->sql .= "num_donation decimal(6,2),\n";
    }
    if ($this->config->c_DepositPrice > 0){
      $this->sql .= "num_deposit decimal(6,2),\n";
      $this->sql .= "num_balancedue decimal(6,2),\n";
    }
    if ($this->config->c_UsePayPalFee){
      $this->sql .= "chk_paypalfeepay bool,\n";
    }
    $this->sql .= "num_paypalfee decimal(6,2),\n";
    $this->sql .= "num_paypalgross decimal(6,2),\n";
    $this->sql .= "num_totalgross decimal(6,2),\n";
    $this->sql .= "num_totalnet  decimal(6,2),\n";
    $this->sql .= "Index invoiceid_idx (txt_invoiceid),\n";
    $this->sql .= "Index transid_idx (txt_transactionid),\n";
    $this->sql .= "Index fname_idx (txt_firstname1),\n";
    $this->sql .= "Index lname_idx (txt_lastname1),\n";
    $this->sql .= "Index email_idx (txt_email1)\n)\n";
  } // Build_SQL_Total_Fields

  function Build_DB_Table(){
    $result = "";
  
    // Delete existing files.
    $this->reglib->Delete_Files("all");
    
    // Connect to database, quit if error.
    $result = $this->regdb->db_connect();
    if ($result != ""){
      return "$result" . "<br />\n";
    }
    
    $this->Build_SQL_Table_Header();
    $this->Build_SQL_Person_Fields();
    $this->Build_SQL_Data_Fields();
    $this->Build_SQL_Phone_Fields();
    $this->Build_SQL_Radio_Fields();
    $this->Build_SQL_Select_Fields();
    $this->Build_SQL_Musician_Caller_Fields("musician");
    $this->Build_SQL_Musician_Caller_Fields("caller");
    $this->Build_SQL_Attendance_Fields();
    $this->Build_SQL_Merchandise_Fields();
    $this->Build_SQL_Membership_Fields();
    $this->Build_SQL_Total_Fields();
  
    // Create the ouput file and write the data.
    // Make it a PHP file so users cannot see contents.
    // Make sure it has .php extension.
    $fname = $this->config->c_db_table_def_file;
    $temp1 = preg_match('/.+\.php$/', $fname);
    if ( $temp1 == false || $temp1 == 0 ) { $fname .= ".php"; }
    $fh = fopen($fname, "w");
    fwrite($fh, "<?php\n");
    fwrite($fh, "/* *****\n");
    fwrite($fh, $this->sql);
    fwrite($fh, "***** */\n");
    fwrite($fh, "?>\n");
    fclose($fh);
  
    // Delete an existing table
    $sql2 = "DROP TABLE IF EXISTS " . $this->config->c_dbtable . ";\n";
    $this->db_result = mysqli_query($this->regdb->dbh, $sql2);
    if (! $this->db_result){
      $result = mysqli_error($this->regdb->dbh);
      $this->regdb->db_close($this->db_result);
      return "Delete Table Error:<br />\n" . $result . "<br />\n";
    }
    
    // Create the new table
    $this->db_result = mysqli_query($this->regdb->dbh, $this->sql);
    if (! $this->db_result){
      $result = mysqli_error($this->regdb->dbh);
      $this->regdb->db_close($this->db_result);
      return "Create Table Error:<br />\n" . $result . "<br />\n";
    }
    $this->regdb->db_close($this->db_result);
  
    return "Database Table " . $this->config->c_dbtable . 
      " created successfully.<br />\n";
//    "Click to view the <a href='" . $this->config->c_db_table_def_file . 
//    "'>Table Definition</a><br />\n";
  } // Build_DB_Table

} // Online_Reg_DB_Table_Class
?>
