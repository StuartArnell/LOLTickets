<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.1

require_once 'olr-config.php';
require_once 'olr-lib.php';
require_once 'olr-build-reg-page.php';
require_once 'olr-build-db-table.php';

class Online_Reg_Build_Class{
  var $config, $reglib, $title1, $title2;

  // Constructor
  function Online_Reg_Build_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->title1 = $this->config->c_EventName . " Registration Builder";
    $this->title2 = $this->title1;
  } // Online_Reg_Build_Class constructor

  function Print_Build_Page($msg, $reg_page){
    
    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("\n<form name='form1' method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n");
    echo("<input type='hidden' name='cmd' value='login' />\n");
    echo("<p style='font-weight: bold; color:#CC0000;'>CAUTION: THIS WILL OVERWRITE EXISTING FILES AND DATABASE TABLES!</p>\n");
    echo("<p>Build Database Table '" . $this->config->c_dbtable . "'");
    echo(": <input type='submit' name='build_db' value=' BUILD '\n");
    echo("    onclick='document.form1.cmd.value=\"build_db\"; document.form1.submit();' />\n");
    echo("<p>Build Online Registration HTML Page '" . $this->config->c_online_reg_fname . "'");
    echo(": &nbsp;<input type='submit' name='build_page' value=' BUILD '\n");
    echo("    onclick='document.form1.cmd.value=\"build_page\"; document.form1.submit();' />\n");
    if ($reg_page){
      echo("<br />Click to view the <a target='_blank' href='" . 
            $this->config->c_online_reg_fname . "'>Online Registration Page</a></p>");
    } // if $reg_page
    if ($msg != ""){
      echo("<p>--------------------<br />\nResults:<br />\n");
      echo("$msg\n");
      echo("</p>\n");
    }
    echo("</form>\n");
    $this->reglib->Print_Page_Footer();
  } // Print_Build_Page

  function Main(){

    session_start();

    // If already logged in, do specified command
    if (isset($_SESSION['olr-build-login'])) {
      if (isset($_POST['cmd']) && $_POST['cmd'] == "build_page") {
        $page_builder = new Online_Reg_Page_Class();
        $result = $page_builder->Build_Online_Reg_Page();
        $this->Print_Build_Page($result, true);
      } // command = build_page

      elseif (isset($_POST['cmd']) && $_POST['cmd'] == "build_db") {
        $db_builder = new Online_Reg_DB_Table_Class();
        $result = $db_builder->Build_DB_Table();
        $this->Print_Build_Page($result, false);
      } // command = build_db
      
      else {$this->Print_Build_Page("", false);}
    } // if login SESSION var set

    else { // Login SESSION var not set
      if (isset($_POST['cmd']) && $_POST['cmd'] == "login") {
        if ( $this->reglib->Verify_Login($this->config->c_build_reg_login_pwd) ) {
          $_SESSION['olr-build-login'] = "yes"; 
          $this->Print_Build_Page("", false);
        } // if Verify_Login
        // login failed, re-display Login page with error
        else { $this->reglib->Print_Login_Page(true, $this->title1, $this->title2); }
      } // If cmd = login
      else { // any other cmd, print login page
        $this->reglib->Print_Login_Page(false, $this->title1, $this->title2);
      } // else any other cmd
    } // else login SESSION var not set
  } // Main
} // Online_Reg_Build_Class

$builder = new Online_Reg_Build_Class();
$builder->Main();

?>
