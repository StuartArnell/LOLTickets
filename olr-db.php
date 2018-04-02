<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3

require_once 'olr-config.php';

class Online_Reg_DB_Class{
  var $config, $dbh;

  // Constructor
  function Online_Reg_DB_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->dbh = null;
  }

  function db_connect(){
    // Connect to the database, returns error message, if any.
    $result = "";
    
    $this->dbh = mysqli_connect($this->config->c_dbhost, 
    $this->config->c_dbusername, $this->config->c_dbpassword, 
    $this->config->c_dbname);
    
    if (mysqli_connect_errno()){
      $result = "Error connecting to database:\n" . mysqli_connect_error();
    } else {mysqli_set_charset($this->dbh, 'utf8');}
    return $result;
  } // db_connect

  function db_close($db_result){
    if ($this->dbh) {
      if (gettype($db_result) == "object"){mysqli_free_result($db_result);}
      mysqli_close($this->dbh);
    } // if dbh
  } // db_close
   
  function SQLEscape($strdata){
    return(str_replace("'", "''", $strdata));
  }

} // Online_Reg_DB_Class

?>