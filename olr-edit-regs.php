<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.1

require_once 'olr-config.php';
require_once 'olr-db.php';
require_once 'olr-lib.php';

class Online_Reg_Table_Edit_Class{
  var $config, $regdb, $reglib, $db_result, $reg_data, $title1, $title2;
  var $regnum = "";
  var $date_width = 10;
  var $default_width = 40;
  var $non_edit_fields = "";
  var $error = false;

   // Constructor
  function Online_Reg_Table_Edit_Class(){
    $this->regdb = new Online_Reg_DB_Class();
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->db_result = null;
    // Each item in reg data array is another array of 3 values:
    // name, size, value
    $this->reg_data = array();
    $this->title1 = $this->config->c_EventName . " Registration Editor";
    $this->title2 = $this->title1;
    $this->non_edit_fields = ",num_regnum,txt_invoiceid,txt_transactionid," .
    "num_numqtys,num_earlyregdiscount,num_lateregfee," .
    "num_memsingleprice,num_memfamilyprice,";
  } // Online_Reg_Table_Edit_Class Constructor

  function Print_Error_Page($error){
    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("\n<form name='form1' method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n");
    echo("<p style='color: red; font-weight: bold;'>ERROR:<br />\n" . $error . "\n</p>\n");
    echo("<input type='hidden' name='cmd' value='select' />\n");
    echo("<p><input type='submit' name='back' value=' BACK ' /></p>\n");
    echo("</form>\n");
    $this->reglib->Print_Page_Footer();
    $this->error = true;
  } // Print_Error_Page

  function Connect_DB(){
    $result = $this->regdb->db_connect();
    if ($result != ""){
      $errmsg = "EditReg Error connecting to DB";
      $this->reglib->Report_Web_Errors($errmsg, $result);
      $this->Print_Error_Page("ERROR connecting to database, Webmaster has been notified.");
      return(false);
    } // if result error
    return(true);
  } // Connect_DB

  function Get_Table_Columns(){

    // Get Column Data table
    $sql = "DESCRIBE " . $this->config->c_dbtable . ";\n";
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $this->reglib->Report_Web_Errors("Edit-Regs: Error getting columns for table ". 
        $this->config->c_dbtable, mysqli_error($this->regdb->dbh));
      return "Error getting information about data table, Webmaster has been notified.<br />\n";
    }

    if (mysqli_num_rows($this->db_result) <= 0){
      $this->reglib->Report_Web_Errors("Edit-Regs: No column data found for table " . 
        $this->config->c_dbtable, "");
      return "No information found for data table, Webmaster has been notified.<br />\n";
    }

    // Save column names in assoc array.
    // Each array item is an array of (name, size, value)
    $ii = 0;
    while ($row = mysqli_fetch_assoc($this->db_result)){
      $fname = $row['Field'];
      $ftype = strtolower($row['Type']);
      if (strpos($ftype, "bool") !== false){
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => 2, "value" => "");
      } elseif (strpos($ftype, "tinyint") !== false){
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => 3, "value" => "");
      } elseif (strpos($ftype, "int") !== false){
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => 8, "value" => "");
      } elseif (strpos($ftype, "decimal") !== false){
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => 8, "value" => "");
      } elseif (strpos($ftype, "date") !== false){
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => $this->date_width, "value" => "");
      } elseif (strpos($ftype, "varchar") !== false){
        $p1 = strpos($ftype, "(");
        $p2 = strpos($ftype, ")");
        $numchars = substr($ftype, $p1 + 1, $p2 - $p1 - 1);
        $this->reg_data[$ii] = array("name" => $fname, 
        "size" => $numchars, "value" => "");
      } else{$this->reg_data[$ii] = array("name" => $fname, 
        "size" => $this->default_width, "value" => "");}
      $ii += 1;
    } // while columns
    return "";
  } // Get_Table_Columns

  function Get_All_Reg_Data(){
    $result = "";
   
    $sql = "SELECT * from " . $this->config->c_dbtable;
    $sql .= " ORDER By num_regnum";
      
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $errmsg = "EditReg Get_All_Reg_Data Error getting reg data, SQL=\n$sql\n";
      $this->reglib->Report_Web_Errors($errmsg, mysqli_error($this->regdb->dbh));
      $result = "ERROR getting registration data, Webmaster has been notified.<br />\n";
      return($result);
    } // if db_result
    return($result);
   } // Get_All_Reg_Data

  function Get_Reg_Data(){
    $result = "";

    if (! isset($_POST['regnum'])){
      return "Missing Registration Number<br />\n";
    }

    $temp = trim($_POST['regnum']);
    if ($temp == "") {
      return "Empty Registration Number<br />\n";
    }

    $regex_num = '/[0-9]/';
    if (! preg_match($regex_num, $temp)){
      return "Invalid Registration Number: [" . $temp . "]";
    }

    $this->regnum = $temp;
    // Get Registration Data
    $sql = "SELECT * FROM " . $this->config->c_dbtable . 
      " WHERE num_regnum = '" . $this->regnum . "';";
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $result = "Error getting data for Registration Number $this->regnum";
      $this->reglib->Report_Web_Errors("Edit-Regs: $result \nSQL=|" . $sql . "|", mysqli_error($this->regdb->dbh));
      return $result . "<br />\n"; 
    }

    if (mysqli_num_rows($this->db_result) <= 0){
      return "No data found for Registration Number $this->regnum <br />\n";
    }
    // Save data in array.
    $ii = 0;
    $numfields = count($this->reg_data);
    $row = mysqli_fetch_assoc($this->db_result);
    for ($ii = 0; $ii < $numfields; $ii++){
      $fname = $this->reg_data[$ii]["name"];
      $this->reg_data[$ii]["value"] = $row[$fname];
    } // for
    return "";
  } // Get_Reg_Data

  function Print_Select_Page(){
    if ( ! $this->Connect_DB() ){ return; }

    $result = $this->Get_All_Reg_Data();
    if ($result != ""){
      $this->Print_Error_Page($result);
      $this->regdb->db_close($this->db_result);
      return;
    } // if Get_All_Reg_Data error

    if (mysqli_num_rows($this->db_result) <= 0){
       echo("<p>No Registrations Yet</p>");
       return;
    } // no registrations

    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("<script type='text/javascript'>\n");
    echo("  function edit_reg(regnum){\n");
    echo("    document.form1.regnum.value = regnum;\n");
    echo("    document.form1.submit();\n");
    echo("  }\n</script>\n");
    echo("\n<form name='form1' method='post' action='" .
      $_SERVER['PHP_SELF'] . "'>\n");
    echo("<input type='hidden' name='cmd' value='edit' />\n");
    echo("<input type='hidden' name='regnum' value='' />\n");
    echo("<p>Registrations</p>\n");
    echo("<table border='1' cellspacing='1' cellpadding='2'>\n");
    echo("<thead>\n");
    echo("  <tr>");
    echo("    <td>Registration<br />Number</td>\n");
    echo("    <td>Registration<br />Date</td>\n");
    echo("    <td>Registration<br />Status</td>\n");
    echo("    <td>Name</td>\n");
    echo("    <td>Email</td>\n");
    echo("    <td>&nbsp;</td>\n");
    echo("  </tr>\n");
    echo("</thead>\n");
    echo("<tbody>\n");

    while ($row = mysqli_fetch_assoc($this->db_result)){
      $regnum = $row['num_regnum'];
      echo("  <tr>");
      echo("    <td>$regnum</td>\n");
      echo("    <td>" . $row['txt_submitdate'] . "</td>\n");
      echo("    <td>" . $row['txt_status'] . "</td>\n");
      echo("    <td>" . $row['txt_firstname1'] . " " . $row['txt_lastname1'] . "</td>\n");
      echo("    <td>" . $row['txt_email1'] . "</td>\n");
      echo("    <td><input type='button' name='edit_" . $regnum . "' ");
      echo("value=' EDIT '\n");
      echo("      onclick='edit_reg(" . $regnum . ");' /></td>\n");
      echo("  </tr>\n");
    } // while

    echo("</tbody>\n");
    echo("</table>\n");
    echo("</form>\n");
    $this->reglib->Print_Page_Footer();
    $this->regdb->db_close($this->db_result);
  } // Print_Select_Page

  function Print_Radio_List($ii){
    $fname = $this->reg_data[$ii]["name"];
    $fvalue = $this->reg_data[$ii]["value"];

    if (strpos($fname, "phone") !== false){
      $list = $this->config->c_aryPhone_Fields;
    } else {
      $list = $this->config->c_aryRadio_Fields;
    }
    echo("  <tr>\n");
    echo("    <td>$fname</td>\n");
    echo("    <td>\n");

    if (strpos($fname, "gender") !== false){
      echo("      " . substr($fname, 4) . ":&nbsp;&nbsp;&nbsp;\n");
      $selected = "";
      if($fvalue == "F") {$selected = " checked='checked'";}
      echo("      F <input type='radio' name='" . $fname . "'\n");
      echo("        value='F'" . $selected .
          " onchange='value_changed(this);' />&nbsp;&nbsp;&nbsp;\n");
      $selected = "";
      if($fvalue == "M") {$selected = " checked='checked'";}
      echo("      M <input type='radio' name='" . $fname . "'\n");
      echo("        value='M'" . $selected .
          " onchange='value_changed(this);' />&nbsp;&nbsp;&nbsp;\n");
    } else { // not gender
      // Find Radio Button group name for this item.
      $jjmax = count($list);
      for ($jj = 0; $jj < $jjmax; $jj++){
        $radio = $list[$jj];
        $ctlname = "rad_" . $radio["name"];
        if ($ctlname == $fname){
          // Output label and buttons for this button group.
          echo("      " . $radio["label"] . ":&nbsp;&nbsp;&nbsp;\n");
          $items = $radio["items"];
          $kkmax = count($items);
          for ($kk = 0;  $kk < $kkmax; $kk++){
            $ivalue = $items[$kk]["value"];
            $selected = "";
            if ($fvalue == $ivalue){$selected = " checked='checked'";}
            echo("      " . $items[$kk]["label"] . 
              "<input type='radio' name='" . $ctlname . "'\n");
            echo("        value='" . $ivalue . "'" . $selected .
                " onchange='value_changed(this);' />&nbsp;&nbsp;&nbsp;\n");
          } // for each item
        } // if group matches fname
      } // for each Radio field
    } // else not gender
    echo("    </td>\n  </tr>\n");
  } //Print_Radio_List

  function Print_Sel_List($ii){
    $fname = $this->reg_data[$ii]["name"];
    $fvalue = $this->reg_data[$ii]["value"];
    echo("  <tr>\n");
    echo("    <td>$fname</td>\n");
    echo("    <td>");
    $jjmax = count($this->config->c_arySelect_Fields);
    // Get Selection List for this reg data item
    for ($jj = 0; $jj < $jjmax; $jj++){
      $sel = $this->config->c_arySelect_Fields[$jj];
      if ("sel_" . $sel["name"] == $fname){
        // Output label and buttons for this button group.
        echo($sel["label"] . ":&nbsp;\n");
        echo("      <select name='$fname' onchange='value_changed(this);'>\n");
        $items = $sel["items"];
        $kkmax = count($items);
        for ($kk = 0;  $kk < $kkmax; $kk++){
          $ivalue = $items[$kk]["value"];
          $selected = "";
          if ($fvalue == $ivalue){$selected = " selected='selected'";}
          echo("        <option value='$ivalue'$selected>" .
                $items[$kk]["label"] . "</option>\n");
        } // for each item
      } // if sel matches fname
    } // for each sel field
    echo("      </select>\n");
    echo("    </td>\n  </tr>\n");
  } // Print_Sel_List

  function Print_Edit_Page(){
    if ( ! $this->Connect_DB() ){ return; }

    $result = $this->Get_Table_Columns();
    if ($result != ""){
      $this->Print_Error_Page($result);
      $this->regdb->db_close($this->db_result);
      return;
    } // if no Get_Table_Columns error
    else {
      $result = $this->Get_Reg_Data();
      if ($result != ""){
        $this->Print_Error_Page($result);
        $this->regdb->db_close($this->db_result);
        return;
      } // if Get_Reg_Data error
    } // else no Get_Table_Columns

    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("<script type='text/javascript'>\n");
    echo("  function value_changed(obj){\n");
    echo("    document.form1.changed_list.value = " .
      "document.form1.changed_list.value + obj.name + ',';\n");
    echo("  }\n  function delete_reg(){\n");
    echo("    var confirm = window.confirm('Confirm Deletion " .
      "of this Registration')\n");
    echo("    if (confirm) { \n");
    echo("      document.form1.cmd.value='delete';\n");
    echo("      return true;\n");
    echo("    } else {return false;}\n");
//    echo("      document.form1.cmd.value='edit';\n");
//    echo("      document.form1.reset();\n");
//    echo("    } // else\n  }\n</script>\n");
    echo("  }\n</script>\n");
    echo("\n<form name='form1' method='post' action='" .
      $_SERVER['PHP_SELF'] . "'>\n");
    echo("<input type='hidden' name='cmd' value='save' />\n");
    echo("<input type='hidden' name='regnum' value='" . $this->regnum . "' />\n");
    echo("<input type='hidden' name='changed_list' value=',' />\n");
    echo("<p style='font-weight: bold; color:#CC0000;'>" .
      "CAUTION: THIS WILL MODIFY THE DATABASE TABLE!</p>\n");
    echo("<p><input type='submit' name='cancel1' value=' CANCEL '\n");
    echo("    onclick='document.form1.cmd.value=\"cancel\"; document.form1.submit();' />\n");
    echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n");
    echo("<input type='submit' name='delete1' value=' DELETE THIS REGISTRATION '\n");
    echo("    onclick='return delete_reg();' />\n");
    echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n");
    echo("<input type='submit' name='save1' value=' SAVE ' /></p>\n");
    echo("<table border='0' cellspacing='2' cellpadding='2'>\n");
//    echo("<thead>\n");
//    echo("  <tr>");
//    echo("    <td>Field</td>\n");
//    echo("    <td>Value</td>\n");
//    echo("  </tr>\n");
//    echo("</thead>\n");
    echo("<tbody>\n");
    $numfields = count($this->reg_data);
    $fname = "";
    $fsize = "";
    for ($ii = 0; $ii < $numfields; $ii++){
      $fname = $this->reg_data[$ii]["name"];
      $fsize = $this->reg_data[$ii]["size"];
      $fvalue = $this->reg_data[$ii]["value"];
      // Non-Editable field, just display value.
      if ((strpos($this->non_edit_fields, "," . $fname . ",") !== false) ||
          (strpos($fname, "_aname") !== false) ||
          (strpos($fname, "_aprice") !== false) ||
          (strpos($fname, "_mname") !== false) ||
          (strpos($fname, "_mprice") !== false) )
      {
        echo("  <tr>");
        echo("    <td>$fname</td>\n");
        echo("    <td>$fvalue</td>\n");
        echo("  </tr>\n");
      } // if non_edit_field

      else { // editable field
        $line_width = $this->config->c_text_line_width;
        if (substr($fname, 0, 4) == "rad_"){$this->Print_Radio_List($ii);}
        elseif (substr($fname, 0, 4) == "sel_"){$this->Print_Sel_List($ii);}
        elseif ($fsize <= $line_width){
          echo("  <tr>");
          echo("    <td>$fname</td>\n");
          echo("    <td><input type='text' name='" . $fname);
          echo("' size='" . $fsize . "' value='" . $fvalue . "'\n");
          echo("      onchange='value_changed(this);' /></td>\n");
          echo("  </tr>\n");
        } else {
          $numrows = ceil($fsize / $line_width);
          echo("  <tr>");
          echo("    <td>$fname</td>\n");
          echo("    <td><textarea name='" . $fname);
          echo("' cols='" . $line_width . "' rows='" . $numrows . "'\n");
          echo("      onchange='value_changed(this);'>\n");
          echo("$fvalue");
          echo("\n      </textarea>\n    </td>\n");
          echo("  </tr>\n");
        } // else long text field
      } // else editable field
    } // for each field

    echo("</tbody>\n");
    echo("</table>\n");
    echo("<p><input type='submit' name='cancel2' value=' CANCEL '\n");
    echo("    onclick='document.form1.cmd.value=\"cancel\"; " .
      "document.form1.submit();' />\n");
    echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n");
    echo("<input type='submit' name='delete2' " .
      "value=' DELETE THIS REGISTRATION '\n");
    echo("    onclick='return delete_reg();' />\n");
    echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n");
    echo("<input type='submit' name='save2' value=' SAVE ' /></p>\n");
    echo("</form>\n");
    $this->reglib->Print_Page_Footer();
    $this->regdb->db_close($this->db_result);
  } // Print_Edit_Page

  function Change_Reg(){
    $result = "";
    if (! isset($_POST['cmd']) ){
       $result = "Missing Command field.<br />\n";
    }

    if (! isset($_POST['changed_list']) ){
       $result .= "Missing Changed List field.<br />\n";
    }
    
    if (! isset($_POST['regnum']) ){
       $result .= "Missing Registration Number field.<br />\n";
    }

    $temp = trim($_POST['regnum']);
    if ($temp == "") {
       $result .= "Empty Registration Number field.<br />\n";
    }

    $regex_num = '/[0-9]/';
    if (! preg_match($regex_num, $temp)){
      $result .= "Invalid Registration Number: [" . $temp . "].<br />\n";
    }
    
    if ($result != ""){
      $this->Print_Error_Page($result);
      return;
    } // if $result

    if ( ! $this->Connect_DB() ){ return; }

    $chglist = $_POST['changed_list'];
    $chgary = explode(",", $chglist);
    $numfields = count($chgary);
    if ($numfields == 0){return "";}
    $this->regnum = $temp;
    $sql = "UPDATE " . $this->config->c_dbtable . " SET ";
    $firstone = true;
    for ($ii = 0; $ii < $numfields; $ii++){
      $fname = $chgary[$ii];
      if ($fname != ""){
        $fvalue = $_POST[$fname];
        if (! $firstone){$sql .= ", ";}
        $sql .= "$fname = '$fvalue'"; // %%% sql escape???
        $firstone = false;
      } // if not empty name
    } // for each changed field
    $sql .= " WHERE num_regnum = '" . $this->regnum . "';";
    
    $msg = "<p>Data Updated Successfully</p>\n";
    if ($firstone){
      $msg = "<p>Nothing Changed</p>\n";
    } else {
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
        $result = "Error updating database for Registration Number $this->regnum";
        $this->reglib->Report_Web_Errors("Change_Reg: $result \nSQL=|" . $sql . "|", mysqli_error($this->regdb->dbh));
        $this->Print_Error_Page($result . ", Webmaster has been notified.<br />\n");
        $this->regdb->db_close($this->db_result);
        return;
      } // if no result
    } // else not $firstone

    $this->regdb->db_close($this->db_result);
    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("\n<form name='form1' method='post' action='" . 
      $_SERVER['PHP_SELF'] . "'>\n");
    echo("<input type='hidden' name='cmd' value='select' />\n");
    echo("$msg");
    echo("<p><input type='submit' name='back' value=' BACK ' /></p>\n");
    echo("</form>");
    $this->reglib->Print_Page_Footer();
    return;
  } // Change_Reg

  function Delete_Reg(){
    $result = "";
    if (! isset($_POST['cmd']) ){
       $result = "Missing Command field.<br />\n";
    }

    if (! isset($_POST['regnum']) ){
       $result .= "Missing Registration Number field.<br />\n";
    }
    
    $temp = trim($_POST['regnum']);
    if ($temp == "") {
       $result .= "Empty Registration Number field.<br />\n";
    }

    $regex_num = '/[0-9]/';
    if (! preg_match($regex_num, $temp)){
      $result .= "Invalid Registration Number: [" . $temp . "].<br />\n";
    }

    if ($result != ""){
      $this->Print_Error_Page($result);
      return;
    } // if $result
    if ( ! $this->Connect_DB() ){ return; }
    
    $this->regnum = $temp;
    $sql = "DELETE FROM " . $this->config->c_dbtable . 
      " WHERE num_regnum = '" . $this->regnum . "';";
    $this->db_result = mysqli_query($this->regdb->dbh, $sql);
    if (! $this->db_result){
      $result = "Error deleting data for Registration Number $this->regnum";
      $this->reglib->Report_Web_Errors("Delete_Reg: $result", mysqli_error($this->regdb->dbh));
      $this->Print_Error_Page($result . "<br />\n");
      $this->regdb->db_close($this->db_result);
      return;
    } // if no result

    $this->regdb->db_close($this->db_result);
    $this->reglib->Print_Page_Header($this->title1, $this->title2);
    echo("\n<form name='form1' method='post' action='" . 
      $_SERVER['PHP_SELF'] . "'>\n");
    echo("<input type='hidden' name='cmd' value='select' />\n");
    echo("<p>Data Deleted Successfully</p>\n");
    echo("<p><input type='submit' name='back' value=' BACK ' /></p>\n");
    echo("</form>");
    $this->reglib->Print_Page_Footer();
    return;
  } // Delete_Reg

  function Main(){
    
    // Print login page if no session login variable
    session_start();

    // If already logged in, do specified command
    if (isset($_SESSION['olr-edit-login'])) {
      if (isset($_POST['cmd']) && $_POST['cmd'] == "edit") {$this->Print_Edit_Page();}
      elseif (isset($_POST['cmd']) && $_POST['cmd'] == "save") {$this->Change_Reg();}
      elseif (isset($_POST['cmd']) && $_POST['cmd'] == "delete") {$this->Delete_Reg();}
      else {$this->Print_Select_Page();}
    } // if login SESSION var set

    else { // Login SESSION var not set
      if (isset($_POST['cmd']) && $_POST['cmd'] == "login") {
        if ( $this->reglib->Verify_Login($this->config->c_edit_reg_login_pwd) ) {
          $_SESSION['olr-edit-login'] = "yes"; 
          $this->Print_Select_Page();
        } // if Verify_Login
        // login failed, re-display page with error
        else { $this->reglib->Print_Login_Page(true, $this->title1, $this->title2); }
      } // If cmd = login
      else { // any other cmd, print login page
        $this->reglib->Print_Login_Page(false, $this->title1, $this->title2);
      } // else any other cmd
    } // else login SESSION var not set
  } // Main

} // Online_Reg_Table_Edit_Class

$teditor = new Online_Reg_Table_Edit_Class();
$teditor->Main();

?>
