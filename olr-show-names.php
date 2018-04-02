<?php
// Copyright 2016 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.1

require_once 'olr-config.php';
require_once 'olr-db.php';
require_once 'olr-lib.php';

class Online_Reg_Show_Names_Class{
  var $config, $regdb, $reglib, $db_result, $page_title, $page_header;

  // Constructor
  function Online_Reg_Show_Names_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->regdb = new Online_Reg_DB_Class();
    $this->db_result = null;
    $this->page_title = $this->config->c_EventName . " Registrations";
    $this->page_header = "Registrations for " . $this->config->c_EventName;
  } // Online_Reg_Show_Names_Class constructor

   function Get_All_Reg_Data(){
   
      $dberr = $this->regdb->db_connect();
      if ($dberr != "") {
         $errmsg = "Error connecting to DB";
         $this->reglib->Report_Web_Errors("Show Names Error: $errmsg", $dberr);
         echo("<p style='color:red; font-weight:bold;'>ERROR connecting to database, Webmaster has been notified</p>");
         return(false);
      }
   
      $sql = "SELECT * from " . $this->config->c_dbtable;
      $sql .= " WHERE num_statusid = 1";
      $sql .= " ORDER By txt_lastname1, txt_firstname1 ";
      
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
         $errmsg = "Error getting registration data, SQL=\n$sql\n";
         $this->reglib->Report_Web_Errors("Show Names Error: $errmsg", mysqli_error($this->regdb->dbh));
         echo("<p style='color:red; font-weight:bold;'>ERROR getting registration data, Webmaster has been notified</p>");
         $this->regdb->db_close($this->db_result);
         return(false);
      }
      return(true);
   } // Get_All_Reg_Data


  function Print_Names(){

    $reglist = array();
    $total = 0;
    
    if (mysqli_num_rows($this->db_result) <= 0){
       echo("<p>No Registrations Yet</p>");
       return(false);
    }
   
    while ($row = mysqli_fetch_assoc($this->db_result)){
       for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++) {
          if ($row['txt_firstname' . $ii] != "") {
            $total += 1;
            $reglist[] = $row['txt_lastname' . $ii] . "|" . $row['txt_firstname' . $ii];
          } // if firstname not empty
       }  // for each person
    } // while
    $this->regdb->db_close($this->db_result);

    sort($reglist);
    echo("<p>Number of people registered = $total </p>\n");
    echo("<p>");
    foreach ($reglist as $name) {
      $namelist = explode("|", $name);
      echo($namelist[1] . " " . $namelist[0] . "<br />\n");
    }  // for each name
      echo("</p>\n");
   } // Print_Names

  function main(){
    $this->reglib->Print_Page_Header($this->page_title, $this->page_header);
    if ($this->Get_All_Reg_Data()) { $this->Print_Names(); }
    $this->reglib->Print_Page_Footer();
  } // main
} // Online_Reg_Show_Class

$show = new Online_Reg_Show_Names_Class();
$show->main();

?>
