<?php
// Copyright 2014 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.1

require_once 'olr-config.php';
require_once 'olr-db.php';
require_once 'olr-lib.php';

class Online_Reg_Print_Class{
  var $config, $regdb, $reglib, $db_result, $page_title, $page_header;

  // Constructor
  function Online_Reg_Print_Class(){
    $this->config = new Online_Reg_Config_Class();
    $this->reglib = new Online_Reg_Lib_Class();
    $this->regdb = new Online_Reg_DB_Class();
    $this->db_result = null;
    $this->page_title = $this->config->c_EventName . " Registrations";
    $this->page_header = "Registrations for " . $this->config->c_EventName;
  } // Online_Reg_Print_Class constructor

   function Get_All_Reg_Data(){
   
      $dberr = $this->regdb->db_connect();
      if ($dberr != "") {
         $errmsg = "Error connecting to DB";
         $this->reglib->Report_Web_Errors("Print Regs Error: $errmsg", $dberr);
         echo("<p style='color:red; font-weight:bold;'>ERROR connecting to database, Webmaster has been notified</p>");
         return(false);
      }
   
      $sql = "SELECT * from " . $this->config->c_dbtable;
      $sql .= " ORDER By num_statusid, num_regnum, txt_lastname1, txt_firstname1 ";
      
      $this->db_result = mysqli_query($this->regdb->dbh, $sql);
      if (! $this->db_result){
         $errmsg = "Error getting registration data, SQL=\n$sql\n";
         $this->reglib->Report_Web_Errors("Print Regs Error: $errmsg", mysqli_error($this->regdb->dbh));
         echo("<p style='color:red; font-weight:bold;'>ERROR getting registration data, Webmaster has been notified</p>");
         $this->regdb->db_close($this->db_result);
         return(false);
      }
      return(true);
   } // Get_All_Reg_Data


  function HTMLEscape($strdata){
    // Replace carriage return with space
    $fvalue = preg_replace('[\x0A|\x0D]', " ", $strdata);
    // Replace double quote with single quote
    $fvalue = str_replace('<', '&lt;', str_replace('>', '&gt;', $fvalue));
    $fvalue = str_replace('&', '&amp;', $fvalue);
    // str_replace('"', '&quot;', $fvalue)
    return($fvalue);
   } // HTMLEscape


   function Print_Table_Header($excel){
      
      // variables for HTML output
      $tr1 = "<tr>";
      $tr2 = "</tr>";
      $th1 = "<th>";
      $th2 = "</th>";
      $space = "&nbsp;";
      $lf = "\n";

      // variables for Excel output
      if ($excel) {
         $tr1 = "";
         $tr2 = "\n";
         $th1 = "";
         $th2 = "";
         $space = "";
         $lf = ",";
      }

      if (!$excel) {
        echo("<table border='1' cellspacing='0' cellpadding='1'>\n");
        echo("<thead>\n");
      }
      if (!$excel) {echo("$tr1$lf");};
      echo("$th1" . "RegNum" . "$th2$lf");
      echo("$th1" . "Date" . "$th2$lf");
      echo("$th1" . "Status" . "$th2$lf");
      if ($this->config->c_Use_Do_Not_Share){echo("$th1" . "Share Data" . "$th2$lf");}
      echo("$th1" . "Num Persons" . "$th2$lf");
      echo("$th1" . "First Name" . "$th2$lf");
      echo("$th1" . "Last Name" . "$th2$lf");
      if ($this->config->c_UseGenderData) {
        echo("$th1" . "Gender" . "$th2$lf");
      }
      if ($this->config->c_UseChildData) {
        echo("$th1" . "Child" . "$th2$lf");
        if ($this->config->c_UseChildAge) {
          echo("$th1" . "Child Age" . "$th2$lf");
        }
      }
      if ($this->config->c_UseStudentData) {
        echo("$th1" . "Student" . "$th2$lf");
      }
      
      // Phone fields
      $iimax = count($this->config->c_aryPhone_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
          echo("$th1" . str_replace('_', ' ', $this->config->c_aryPhone_Fields[$ii]["abbrev"]) . "$th2$lf");
          echo("$th1" . str_replace('_', ' ', $this->config->c_aryPhone_Fields[$ii]["abbrev"]) . " Type" . "$th2$lf");
      } // for each phone

      // Other data fields
      $iimax = count($this->config->c_aryField_Defs);
      for ($ii = 0; $ii < $iimax; $ii++) {
         echo("$th1" . str_replace('_', ' ', $this->config->c_aryField_Defs[$ii]["abbrev"]) . "$th2$lf");
      }

      // Radio Button fields
      $iimax = count($this->config->c_aryRadio_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
          echo("$th1" . str_replace('_', ' ', $this->config->c_aryRadio_Fields[$ii]["abbrev"]) . "$th2$lf");
      } // for each radio button

      // Selection List fields
      $iimax = count($this->config->c_arySelect_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
        echo("$th1" . str_replace('_', ' ', $this->config->c_arySelect_Fields[$ii]["abbrev"]) . "$th2$lf");
      } // for each radio button

      // Musician data fields, skip first one which is user name.
      $iimax = count($this->config->c_aryMusician_Fields);
      for ($ii = 1; $ii < $iimax; $ii++) {
         echo("$th1" . str_replace('_', ' ', $this->config->c_aryMusician_Fields[$ii]["abbrev"]) . "$th2$lf");
      } // for ii

      // Caller data fields, skip first one which is user name.
      $iimax = count($this->config->c_aryCaller_Fields);
      for ($ii = 1; $ii < $iimax; $ii++) {
         echo("$th1" . str_replace('_', ' ', $this->config->c_aryCaller_Fields[$ii]["abbrev"]) . "$th2$lf");
      } // for ii

      // Attendance options
      $iimax = count($this->config->c_aryAttendance_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
         echo("$th1" . $this->config->c_aryAttendance_Fields[$ii]["abbrev"] . " Price$th2$lf");
         echo("$th1" . $this->config->c_aryAttendance_Fields[$ii]["abbrev"] . " Qty$th2$lf");
      }

      // Merchandise options
      $iimax = count($this->config->c_aryMerchandise_Fields);
      for ($ii = 0; $ii < $iimax; $ii++) {
         echo("$th1" . $this->config->c_aryMerchandise_Fields[$ii]["abbrev"] . " Price$th2$lf");
         echo("$th1" . $this->config->c_aryMerchandise_Fields[$ii]["abbrev"] . " Qty$th2$lf");
      }

      // Membership fees
      if ($this->config->c_member_single_price > 0 || $this->config->c_member_family_price > 0){
          echo("$th1" . "Mem Single Price" . "$th2$lf"); 
          echo("$th1" . "Mem Single Qty" . "$th2$lf"); 
          echo("$th1" . "Mem Family Price" . "$th2$lf"); 
          echo("$th1" . "Mem Family Qty" . "$th2$lf"); 
      }

      if ($this->config->c_early_reg_discount != 0) {
         echo("$th1" . "Early Reg Discount" . "$th2$lf");
         echo("$th1" . "Early Reg Qty" . "$th2$lf");
      }
      if ($this->config->c_late_reg_fee != 0) {
         echo("$th1" . "Late Reg Fee" . "$th2$lf");
         echo("$th1" . "Late Reg Qty" . "$th2$lf");
      }

      if ($this->config->c_UseDonation) {echo("$th1" . "Donation" . "$th2$lf");}
      if ($this->config->c_DepositPrice > 0) {
        echo("$th1" . "Deposit" . "$th2$lf");
        echo("$th1" . "BalDue" . "$th2$lf");
      }
      if ($this->config->c_UsePayPalFee) {echo("$th1" . "PP Fee Paid" . "$th2$lf");}
      echo("$th1" . "PP Fee" . "$th2$lf");
      echo("$th1" . "PP Gross" . "$th2$lf");
      echo("$th1" . "Total" . "$th2$lf");
      echo("$th1" . "Net" . "$th2$lf");
      echo("$th1" . "TransactionID" . "$th2$lf");
      echo("$th1" . "InvoiceID" . "$th2$lf");
      if (!$excel) {
        echo("$tr2$lf");
        echo("</thead>\n");
        echo("<tbody>\n");
      } // if not excel
      if ($excel) {echo("\n");};
   } // Print_Table_Header

    function Print_Phone_Data($namenum, $row, $td1, $td2, $lf, $space, $excel){
      
      $arydata = $this->config->c_aryPhone_Fields;
      $iimax = count($arydata);
      $ctrlname = "";
      for ($ii = 0; $ii < $iimax; $ii++){
        $ctlname = $this->reglib->GetFieldCtrlName("phone", $ii);
        $dbdata = $row[$ctlname];
        if ($dbdata == "" || $namenum > 1){echo("$td1$space$td2$lf");}
        else {
          if ($excel){
            echo($this->reglib->CSVEscape($dbdata));
          } // if excel
          else {
            echo("$td1" . $this->HTMLEscape($dbdata) . "$td2$lf");
          } // else not excel
        } // else not empty data
        $ctlname .= "_type";
        $dbdata = $row[$ctlname];
        if ($dbdata == "" || $namenum > 1){echo("$td1$space$td2$lf");}
        else {
          if ($excel){
            echo($this->reglib->CSVEscape($dbdata));
          } // if excel
          else {
            echo("$td1" . $this->HTMLEscape($dbdata) . "$td2$lf");
          } // else not excel
        } // else not empty data
      } // for ii
   } // Print_Phone_Data

    function Print_Radio_Select_Data($which, $namenum, $row, $td1, $td2, $lf, $space, $excel){
      
      $arydata = $this->config->c_aryRadio_Fields;
      if ($which == "select"){$arydata = $this->config->c_arySelect_Fields;}
      $iimax = count($arydata);
      $ctrlname = "";
      for ($ii = 0; $ii < $iimax; $ii++){
        $ctlname = $this->reglib->GetFieldCtrlName($which, $ii);
        $dbdata = $row[$ctlname];
        if ($dbdata == "" || $namenum > 1){echo("$td1$space$td2$lf");}
        else {
          if ($excel){
            echo($this->reglib->CSVEscape($dbdata));
          } // if excel
          else {
            echo("$td1" . $this->HTMLEscape($dbdata) . "$td2$lf");
          } // else not excel
        } // else not empty data
      } // for ii
   } // Print_Radio_Select_Data

   function Print_Mus_Caller_Data($which_data, $firstname, $row, $td1, $td2, $lf, $space, $excel){
      
      if ($which_data == "musician"){
         $numpeople = $this->config->c_max_musicians;
         $arydata = $this->config->c_aryMusician_Fields;
      } else {
         $numpeople = $this->config->c_max_callers;
         $arydata = $this->config->c_aryCaller_Fields;
      }
      
      if ($numpeople == 0) {return;}
      
      $foundit = 0;
      $jjmax = count($arydata);
      $namefield = $this->reglib->GetFieldCtrlName($which_data, 0);
      for ($ii = 1; $ii <= $numpeople; $ii++){
	      if ($firstname && ($firstname != "") && strcasecmp($row[$namefield . $ii], $firstname) == 0 ){
	         $foundit = 1;
            // Start with 1 to skip first field, which is label.
            // DB Field name is Control name
	         for ($jj = 1; $jj < $jjmax; $jj++) {
             $ctlname = $this->reglib->GetFieldCtrlName($which_data, $jj);
             $dbdata = $row[$ctlname . $ii];
             if (substr($ctlname, 0, 4) == "chk_") {
                if ($dbdata == 1){echo("$td1" . "Yes" . "$td2$lf");}
                else {echo("$td1" . "No" . "$td2$lf");}
             } else { // not checkbox
               if ($excel){
	               echo($this->reglib->CSVEscape($dbdata));
               } else {
	               echo("$td1" . $this->HTMLEscape($dbdata) . "$td2$lf");
               }
             } // else not checkbox
	        } // for jj
	      } // if user name
      } // for ii
      
      // If no data for user, output empty cells.
      if ($foundit == 0)
      {
         // Start with 1 to skip first field, which is name.
         for ($jj = 1; $jj < $jjmax; $jj++) {
            echo("$td1$space$td2$lf");
         } // for jj
      }
   } // Print_Mus_Caller_Data

   function Print_Row($row, $rownum, $namenum, $seqnum, $excel){
      // Outputs one row, as HTML Table Row or as Excel row.

      // variables for HTML output
      $tr1 = "<tr>";
      $tr2 = "</tr>";
      $td1 = "<td>";
      $td2 = "</td>";
      $space = "&nbsp;";
      $lf = "\n";

      // variables for Excel output
      if ($excel) {
         $tr1 = "";
         $tr2 = "\n";
         $td1 = "";
         $td2 = "";
         $space = "";
         $lf = ",";
      }

      $temp = "txt_firstname" . $namenum;
      if ($row[$temp] != "") {
        if (!$excel) {echo("$tr1$lf");}
        echo("$td1" . $row['num_regnum'] . ".$seqnum$td2$lf");
        echo("$td1" . $row['txt_statusdate'] . "$td2$lf");
        echo("$td1" . $row['txt_status'] . "$td2$lf");
        if ($this->config->c_Use_Do_Not_Share){
          if ($row['chk_sharedata'] == 1) {
             echo("$td1" . "Yes" . "$td2$lf");
          } else { echo("$td1" . "No" . "$td2$lf");}
        } // if use do not share
        if ($namenum == 1){
          echo("$td1" . $row['num_numpersons'] . "$td2$lf");
        } else {echo("$td1$space$td2$lf");}
        echo("$td1" . $row[$temp] . "$td2$lf");
        echo("$td1" . $row['txt_lastname' . $namenum] . "$td2$lf");
        if ($this->config->c_UseGenderData) {
          echo("$td1" . $row['rad_gender' . $namenum] . "$td2$lf");
        }
        if ($this->config->c_UseChildData) {
          // First person cannot be child
          if ($namenum == 1){
            echo("$td1" . "No" . "$td2$lf");
            if ($this->config->c_UseChildAge) { echo("$td1$space$td2$lf"); }
          } else { // Persons 2 to N
             if ( $row['chk_child' . $namenum] == 1 ){
               echo("$td1" . "Yes" . "$td2$lf");
               if ($this->config->c_UseChildAge) {
                echo("$td1" . $row['num_childage' . $namenum] . "$td2$lf");
              }
             } else {
               echo("$td1" . "No" . "$td2$lf");
               if ($this->config->c_UseChildAge) { echo("$td1$space$td2$lf"); }
             }
           } // else not first person
        } // if UseChildData
        
        if ($this->config->c_UseStudentData) {
           if ( $row['chk_student' . $namenum] == 1 ){
             echo("$td1" . "Yes" . "$td2$lf");
           } else {
             echo("$td1" . "No" . "$td2$lf");
           }
        } // if UseStudentData
      } // if _firstname not empty

      // Radio Button data
      $this->Print_Phone_Data($namenum, $row, $td1, $td2, $lf, $space, $excel);

        // DB Column Name is Control Name
        // DB Column Name is NOT case sensitive
        $iimax = count($this->config->c_aryField_Defs);
        for ($ii = 0; $ii < $iimax; $ii++) {
          $ctl_name = $this->reglib->GetFieldCtrlName("data", $ii);
          $temp2 = $row[$ctl_name];
          if ( (strpos($ctl_name, "txt_street") !== false) ||
             (strpos($ctl_name, "txt_city") !== false) ||
             (strpos($ctl_name, "txt_state")!== false) ||
             (strpos($ctl_name, "txt_zip") !== false) ||
             (strpos($ctl_name, "txt_phone") !== false)||
             (strpos($ctl_name, "txt_email") !== false) ) {
          if ( $temp2 == ""){echo("$td1$space$td2$lf"); }
              else {
                if($excel){
                  echo($this->reglib->CSVEscape($temp2));
                } else {
                  echo("$td1" . $this->HTMLEscape($temp2) . "$td2$lf");
                }
              } // else not empty
            } // if address, etc.
            else { // Not address, etc. only output for first person
              if ( $namenum > 1 ) {echo("$td1$space$td2$lf");}
              else { // not first person
                if ( $temp2 == ""){echo("$td1$space$td2$lf"); }
                else {
                  if ( $this->config->c_aryField_Defs[$ii]["type"] == "check") {
                    if ($temp2 == 1){echo("$td1" . "Yes" . "$td2$lf");}
                    else {echo("$td1" . "No" . "$td2$lf");}
                  } else {
                    if($excel){
                      echo($this->reglib->CSVEscape($temp2));
                    } else {
	                      echo("$td1" . $this->HTMLEscape($temp2) . "$td2$lf");
                    }
                  } // else not checkbox
                } // else not empty value
              }  // not first person
            } // else not address, etc.
          } // for each field

          // Radio Button data
          $this->Print_Radio_Select_Data("radio", $namenum, $row, $td1, $td2, $lf, $space, $excel);

          // Selection List data
          $this->Print_Radio_Select_Data("select", $namenum, $row, $td1, $td2, $lf, $space, $excel);

         // Musician data, for person in this row
         $this->Print_Mus_Caller_Data("musician", $row["txt_firstname" . $namenum], $row, $td1, $td2, $lf, $space, $excel);

         // Caller data, for person in this row
         $this->Print_Mus_Caller_Data("caller", $row["txt_firstname" . $namenum], $row, $td1, $td2, $lf, $space, $excel);

        // Only output rest of data for first person.
        
        // Attendance quantity data
        $jj = 0;
        $iimax = count($this->config->c_aryAttendance_Fields);
        for ($ii = 0; $ii < $iimax; $ii++) {
          if ( $namenum == 1 ) {
            $jj = $ii + 1;
            echo("$td1" . sprintf("%.2f", $row['num_aprice' . $jj])  . "$td2$lf");
            echo("$td1" . $row['num_aqty' . $jj] . "$td2$lf");
          } 
          else {
            echo("$td1$space$td2$lf");
            echo("$td1$space$td2$lf");
          }
        } // for each attendance qty
   
        // Merchandise quantity data
        $iimax = count($this->config->c_aryMerchandise_Fields);
        for ($ii = 0; $ii < $iimax; $ii++) {
          if ( $namenum == 1 ) {
            $jj = $ii + 1;
            echo("$td1" . sprintf("%.2f", $row['num_mprice' . $jj]) . "$td2$lf");
            echo("$td1" . $row['num_mqty' . $jj] . "$td2$lf");
          }
          else {
            echo("$td1$space$td2$lf");
            echo("$td1$space$td2$lf");
          }
        } // for each merchandise qty
   
        // Membership fees
        if ($this->config->c_member_single_price > 0 || $this->config->c_member_family_price > 0){
          if ( $namenum == 1 ) {
            echo("$td1" . sprintf("%.2f", $row['num_memsingleprice']) . "$td2$lf");
            echo("$td1" . $row['num_memsingleqty'] . "$td2$lf");
            echo("$td1" . sprintf("%.2f", $row['num_memfamilyprice']) . "$td2$lf");
            echo("$td1" . $row['num_memfamilyqty'] . "$td2$lf");
          }
          else {
            echo("$td1$space$td2$lf");
            echo("$td1$space$td2$lf");
            echo("$td1$space$td2$lf");
            echo("$td1$space$td2$lf");
          }
        }

        if ($this->config->c_early_reg_discount != 0) {
          if ( $namenum == 1 ) {
            echo("$td1" . sprintf("%.2f", $row['num_earlyregdiscount']) . "$td2$lf");
            echo("$td1" . $row['num_earlyregqty'] . "$td2$lf");
          }
          else {
              echo("$td1$space$td2$lf");
              echo("$td1$space$td2$lf");
          }
        }
         
        if ($this->config->c_late_reg_fee != 0) {
          if ( $namenum == 1 ) {
            echo("$td1" . sprintf("%.2f", $row['num_lateregfee']) . "$td2$lf");
            echo("$td1" . $row['num_lateregqty'] . "$td2$lf");
          }
          else {
              echo("$td1$space$td2$lf");
              echo("$td1$space$td2$lf");
          }
        }

        if ($this->config->c_UseDonation){
          if ( ( $namenum > 1 )  || ($row['num_donation'] == "") ) {
            echo("$td1$space$td2$lf");
          } else { echo("$td1" . sprintf("%.2f", $row['num_donation']) . "$td2$lf");}
        }

        if ($this->config->c_DepositPrice > 0){
          if ( ( $namenum > 1 )  || ($row['num_deposit'] == "") ) {
            echo("$td1$space$td2$lf" . "$td1$space$td2$lf");
          } else { 
            echo("$td1" . sprintf("%.2f", $row['num_deposit']) . "$td2$lf");
            echo("$td1" . sprintf("%.2f", $row['num_balancedue']) . "$td2$lf");
          }
        }

        if ( $namenum == 1 ) {
          if ($this->config->c_UsePayPalFee) {
            if ($row['chk_paypalfeepay']) {
                echo("$td1" . "Yes" . "$td2$lf");
            } else { echo("$td1" . "No" . "$td2$lf");}
          } // if UsePayPalFee
        } // if $namenum == 1
        else { echo("$td1$space$td2$lf"); }
   
        if ( ( $namenum > 1 )  || ($row['num_paypalfee'] == "") ) {
          echo("$td1$space$td2$lf");
        } else { echo("$td1" . sprintf("%.2f", $row['num_paypalfee']) . "$td2$lf");}
   
        if ( ( $namenum > 1 )  || ($row['num_paypalgross'] == "") ) {
          echo("$td1$space$td2$lf");
        } else { echo("$td1" . sprintf("%.2f", $row['num_paypalgross']) . "$td2$lf");}
   
        if ( ( $namenum > 1 ) || ($row['num_totalgross'] == "") ) {
          echo("$td1$space$td2$lf");
        } else { echo("$td1" . sprintf("%.2f", $row['num_totalgross']) . "$td2$lf");}
   
        if ( ( $namenum > 1 ) || ($row['num_totalnet'] == "") ) {
          echo("$td1$space$td2$lf");
        } else { echo("$td1" . sprintf("%.2f", $row['num_totalnet']) . "$td2$lf");}
   
        if ($row['txt_transactionid'] == "") {
          echo("$td1$space$td2$lf");
        } else { echo("$td1" . $row['txt_transactionid'] . "$td2$lf");}
        echo("$td1" . $row['txt_invoiceid'] . "$td2$lf");
        if ($excel) {echo("$tr2");}
        else {echo("$tr2$lf");};

   } // Print_Row


   function Print_Regs(){
   
      $status = "empty";
      $rownum = 0;
      $mysql_row = -1;
      $numregs = 0;
   
      // Create a form with cmd = data to get past login.
      echo("<script type='text/javascript' language='javascript'>\n");
      echo("   function ExcelExport(){\n");
      echo("      document.form1.cmd.value='excel';\n");
      echo("      document.form1.submit();\n");
      echo("   }\n");
      echo("</script>\n");
      echo("<form name='form1' method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n");
      echo("<input type='hidden' name='cmd' value='data' />\n");
      echo("<input type='submit' name='excel' value=' Export to Excel '\n");
      echo(" onclick='ExcelExport();' />\n");
      echo("</form>\n");

      if (mysqli_num_rows($this->db_result) <= 0){
         echo("<p>No Registrations Yet</p>");
         return(false);
      }
   
      while ($row = mysqli_fetch_assoc($this->db_result)){
         $rownum += 1;
         $mysql_row += 1;
         // Start of new status
         if (strcasecmp($row['txt_status'], $status) != 0 ) {
           $numregs = 0;
           $status = $row['txt_status'];
            if ($rownum > 1){
              echo("</tbody>\n");
              echo("</table>\n\n");
            }
         
            // Get number of registrations for this status
            while ( $row ){
               if ($row['txt_status'] == $status){
                  for ($jj = 1; $jj <= $this->config->c_max_persons; $jj++){
                     if ($row['txt_firstname' . $jj] != "") {
                        $numregs += 1;
                     }
                  } // for each person
               } // same status
               $row = mysqli_fetch_assoc($this->db_result);
            } // while row
         
            // Reset data pointer to the current row
            mysqli_data_seek($this->db_result, $mysql_row);
            $row = mysqli_fetch_assoc($this->db_result);
            
            echo("<h4>$status Registrations: count = $numregs</h4>\n");
            $this->Print_Table_Header(false);
         } // if status change

         $seqnum = 0;
         for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++) {
            if ($row['txt_firstname' . $ii] != "") {
               // seqnum is second digit of registration number x.y.
               $seqnum += 1;
               $this->Print_Row($row, $rownum, $ii, $seqnum, false);
            } // if firstname not empty
         }  // for each person
      } // while
      echo("</tbody>\n");
      echo("</table>\n\n");
      $this->regdb->db_close($this->db_result);
   } // Print_Regs

   function Print_Data_Page(){
      $this->reglib->Print_Page_Header($this->page_title, $this->page_header);
      if ($this->Get_All_Reg_Data()) { $this->Print_Regs(); }
      else { echo("<p>No Registrations Yet</p>"); }
      $this->reglib->Print_Page_Footer();
   } // Print_Data_Page
   
   function Excel_Export(){

      if (! $this->Get_All_Reg_Data()) { 
         echo("Error getting Registration Data");
         return false; 
      }

      if (mysqli_num_rows($this->db_result) <= 0){
         echo("No Registrations Yet");
         return(false);
      }
   
      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=" . 
        $this->config->c_excel_filename . ";");

      $rownum = 0;
      $this->Print_Table_Header(true);
      while ($row = mysqli_fetch_assoc($this->db_result)){
         $rownum += 1;
         // seqnum is second digit of registration number x.y.
         $seqnum = 0;
         for ($ii = 1; $ii <= $this->config->c_max_persons; $ii++) {
            if ($row['txt_firstname' . $ii] != "") {
               $seqnum += 1;
               $this->Print_Row($row, $rownum, $ii, $seqnum, true);
            }
         }  // for
      }
      $this->regdb->db_close($this->db_result);
   } // Excel_Export

  function main(){

    session_start();

    // If already logged in, do Excel export or print data page
    if (isset($_SESSION['olr-print-login'])) {
      if (isset($_POST['cmd']) && $_POST['cmd'] == "excel") { $this->Excel_Export(); }
      else { $this->Print_Data_Page(); }
    } // if login SESSION var set

    else { // Login SESSION var not set
      if (isset($_POST['cmd']) && $_POST['cmd'] == "login") {
        if ( $this->reglib->Verify_Login($this->config->c_print_reg_login_pwd) ) {
          $_SESSION['olr-print-login'] = "yes"; 
          $this->Print_Data_Page();
        } // if Verify_Login
        // login failed, re-display page with error
        else { $this->reglib->Print_Login_Page(true, $this->page_title, $this->page_header); }
      } // If cmd = login
      else { // any other cmd, print login page
        $this->reglib->Print_Login_Page(false, $this->page_title, $this->page_header);
      } // else any other cmd
    } // else login SESSION var not set
  } // main
} // Online_Reg_Print_Class

$printer = new Online_Reg_Print_Class();
$printer->main();

?>
