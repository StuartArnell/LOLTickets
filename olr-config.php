<?php
// Copyright 2016 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.4

// Configuration file example for ALL data fields.

// Change to your local timezone
date_default_timezone_set ("America/Chicago");
// Atlantic .......... America/Halifax
// Atlantic no DST.... America/Puerto_Rico
// Eastern ........... America/New_York or EST5EDT
// Eastern no DST .... EST
// Central ........... America/Chicago or CST6CDT
// Central no DST .... CST
// Mountain .......... America/Denver or MST7MDT
// Mountain no DST ... America/Phoenix or MST
// Pacific ........... America/Los_Angeles or PST8PDT
// Pacific no DST .... America/Dawson_Creek (or PST ?)
// Alaska ............ America/Anchorage
// Hawaii ............ America/Adak or US/Aleutian
// Hawaii no DST ...... Pacific/Honolulu

class Online_Reg_Config_Class{
  var $c_olr_version = "1.3.4";  // version of this release
  var $c_test_mode = 1;
  var $c_email_pending = 1;  // email pending registrations to webmaster
  var $c_email_pending_user = 0; // email pending registrations to user
  var $c_EventName = "LOL Event 2018";
  var $c_EventAbbrev = "Event2018";
  var $c_online_reg_fname = "online-reg-base.html";
  var $c_reg_system_info_in_footer = 1; // include reg system name and version in reg page footer
  var $c_notify_url = "http://ec2-18-188-186-78.us-east-2.compute.amazonaws.com/LOLTickets/Event2018/olr-pp-ipn.php";
  var $c_return_url = "http://ec2-18-188-186-78.us-east-2.compute.amazonaws.com/LOLTickets/Event2016/online-reg.html";
  var $c_paypal_page_style = "";
  var $c_excel_filename = "OnlineRegs.csv";  // download from Print Regs page
  var $c_payment_email = "stuart.arnell@gmail.com";  // "From" field for emails
  var $c_webmaster_email = "stuart.arnell@gmail.com"; // Webmaster email
  var $c_print_reg_login_pwd = "login1"; // for Print Registrations page, case sensitive
  var $c_edit_reg_login_pwd = "login2"; // for Edit Registration page, case sensitive
  var $c_build_reg_login_pwd = "login3"; // for Build Registrations page, case sensitive

  // MySQL Database parameters
  var $c_dbhost = "eu-cdbr-sl-lhr-01.cleardb.net";  // database host name
  var $c_dbname = "ibmx_2231abdff5adb2d";  // database name
  var $c_dbusername = "b0f0793766502c";  // user name
  var $c_dbpassword = "e9024ca5 ";  // user password
  var $c_dbtable = "EVENT2018"; // database table name
  var $c_db_table_def_file = "table-def.sql";

  // Maximum number of persons in registration form
  var $c_max_persons = 4;

  // Use child data
  var $c_UseChildData = 1;
  var $c_UseChildAge = 1;

  // Use student data
  var $c_UseStudentData = 1;

  // Use gender data
  var $c_UseGenderData = 1;

  // Use Do No Share Data checkbox
  var $c_Use_Do_Not_Share = 1;

  // Use donation field
  var $c_UseDonation = 1;

  // Deposit amount for 2-part payment
  var $c_DepositPrice = 150;
  var $c_DepositDueDate = "June 1, 2016";

  // Use PayPal fee checkbox
  var $c_UsePayPalFee = 1;

  // Put checkbox before or after the label
  var $c_checkbox_before_label = 1;

  // Put radio button before or after the label
  var $c_radio_before_label = 1;

  // Data Fields.
  // label is what is displayed next to the field.
  // name attribute must be unique, including data, phone, radio, selections
  // required attribute is optional.
  var $c_aryField_Defs = array(
    array("label" => "Street", "abbrev" => "Street", "name" => "street", "type" => "text", "size" => 30, "required" => 1),
    array("label" => "City", "abbrev" => "City", "name" => "city", "type" => "text", "size" => 30, "required" => 1),
    array("label" => "State", "abbrev" => "State", "name" => "state", "type" => "state", "size" => 2, "required" => 1),
    array("label" => "Zip", "abbrev" => "Zip", "name" => "zip", "type" => "zip", "size" => 5, "required" => 1),
    array("label" => "Email", "abbrev" => "Email", "name" => "email1", "type" => "email", "size" => 40, "required" => 1),
    array("label" => "Number of Men Dancers", "abbrev" => "Men Dancers", "name" => "men_dancers", "type" => "num", "size" => 1, "required" => 1),
    array("label" => "Number of Women Dancers", "abbrev" => "Women Dancers", "name" => "women_dancers", "type" => "num", "size" => 1, "required" => 1),
    array("label" => "Custom Text", "abbrev" => "Custom Text", "name" => "custom_text", "type" => "text", "size" => 40),
    array("label" => "Custom Number", "abbrev" => "Custom Number", "name" => "custom_num", "type" => "num", "size" => 2),
    array("label" => "Custom Option", "abbrev" => "Custom Option", "name" => "custom_opt", "type" => "check", "size" => 1),
    array("label" => "Notes", "abbrev" => "Notes", "name" => "notes", "type" => "text", "size" => 1000),
    array("label" => "Hospitality Request: Number of People", "abbrev" => "Hosp Req Num", "name" => "hosp_req_num", "type" => "num", "size" => 1),
    array("label" => "Hospitality Request: Number of Beds", "abbrev" => "Hosp Req Beds", "name" => "hosp_req_beds", "type" => "num", "size" => 1),
    array("label" => "Hospitality Request: Smoker", "abbrev" => "Hosp Req Smoker", "name" => "hosp_req_smoker", "type" => "check", "size" => 1),
    array("label" => "Hospitality Request: Cats OK", "abbrev" => "Hosp Req Cats", "name" => "hosp_req_cats", "type" => "check", "size" => 1),
    array("label" => "Hospitality Request: Dogs OK", "abbrev" => "Hosp Req Dogs", "name" => "hosp_req_dogs", "type" => "check", "size" => 1),
    array("label" => "Hospitality Request: Notes (Allergies, etc.)", "abbrev" => "Hosp Req Notes", "name" => "hosp_req_notes", "type" => "text", "size" => 60),
    array("label" => "Hospitality Request: Carpooling With", "abbrev" => "Carpooler", "name" => "hosp_req_carpooler", "type" => "text", "size" => 40),
    array("label" => "Hospitality Offer: Number of People", "abbrev" => "Hosp Offer Num", "name" => "hosp_offer_num", "type" => "num", "size" => 1),
    array("label" => "Hospitality Offer: Number of Beds", "abbrev" => "Hosp Offer Beds", "name" => "hosp_offer_beds", "type" => "num", "size" => 1),
    array("label" => "Hospitality Offer: Smoker", "abbrev" => "Hosp Offer Smoker", "name" => "hosp_offer_smoker", "type" => "check", "size" => 1),
    array("label" => "Hospitality Offer: Have Cats", "abbrev" => "Hosp Offer Cats", "name" => "hosp_offer_cats", "type" => "check", "size" => 1),
    array("label" => "Hospitality Offer: Have Dogs", "abbrev" => "Hosp Offer Dogs", "name" => "hosp_offer_dogs", "type" => "check", "size" => 1),
    array("label" => "Housing: Offsite", "abbrev" => "Cab Offsite", "name" => "housing_offsite", "type" => "num", "size" => 1),
    array("label" => "Housing: Womens Cabin", "abbrev" => "Cab Women", "name" => "housing_women", "type" => "num", "size" => 1),
    array("label" => "Housing: Mens Cabin", "abbrev" => "Cab Men", "name" => "housing_men", "type" => "num", "size" => 1),
    array("label" => "Housing: Couples Cabin", "abbrev" => "Cab Couple", "name" => "housing_couple", "type" => "num", "size" => 1),
    array("label" => "Housing: Snorers Cabin", "abbrev" => "Cab Snore", "name" => "housing_snore", "type" => "num", "size" => 1),
    array("label" => "Housing: Adult in Family Cabin", "abbrev" => "Cab Fam Adult", "name" => "housing_family_adult", "type" => "num", "size" => 1),
    array("label" => "Housing: Child in Family Cabin", "abbrev" => "Cab Fam Child", "name" => "housing_family_child", "type" => "num", "size" => 1)
  );

  // Max width of single line of text.
  // Fields longer than this will be implemented as textarea with multiple lines.
  var $c_text_line_width = 80;

  // Max length of name fields
  var $c_name_max_length = 20;

  // Telephone Fields.
  // label is what is displayed next to the button.
  // name attribute must be unique, including data, phone, radio, selections
  // items is list of buttons.
  // default is item to be selected initially by default.
  // required attribute is optional.
  var $c_aryPhone_Fields = array(
    array("label" => "Phone 1", "abbrev" => "Phone1", "name" => "phone1", "size" => 12, "required" => 1,
      "items" => array(
        array("label" => "Home", "value" => "Home", "default" => 1),
        array("label" => "Cell", "value" => "Cell"),
        array("label" => "Work", "value" => "Work") ) ),
    array("label" => "Phone 2", "abbrev" => "Phone2", "name" => "phone2", "size" => 12, 
      "items" => array(
        array("label" => "Home", "value" => "Home"),
        array("label" => "Cell", "value" => "Cell", "default" => 1), 
        array("label" => "Work", "value" => "Work") ) ),
  );

  // Radio Buttons.
  // label is what is displayed next to the button.
  // name attribute must be unique, including data, phone, radio, selections
  // items is list of buttons.
  // default is item to be selected initially by default.
  // required attribute is optional.
  var $c_aryRadio_Fields = array(
    array("label" => "Please Select a Color", "abbrev" => "Color1", 
      "name" => "color1", "type" => "text", "size" => 10,
      "items" => array(
        array("label" => "Red", "value" => "red"),
        array("label" => "Green", "value" => "green", "default" => 1),
        array("label" => "Blue", "value" => "blue"),
      ) ),
    array("label" => "Please Select a Number", "abbrev" => "Number1", 
      "name" => "number1", "abbrev" => "Number 1", "type" => "num", 
      "size" => 1, "required" => 1,
      "items" => array(
        array("label" => "One", "value" => 1),
        array("label" => "Two", "value" => 2),
        array("label" => "Three", "value" => 3) ) ),
  );

  // Selection Lists.
  // label is what is displayed next to the list.
  // items is list of selections.
  // default is item to be selected initially by default.
  // required attribute is optional.
  var $c_arySelect_Fields = array(
    array("label" => "Please Select Another Color", "abbrev" => "Color2", 
      "name" => "color2", "type" => "text", "size" => 10, 
      "items" => array(
        array("label" => "Red", "value" => "red"),
        array("label" => "Green", "value" => "green", "default" => 1),
        array("label" => "Blue", "value" => "blue"),
         ) ),
    array("label" => "Please Select Another Number", "abbrev" => "Number2", 
      "name" => "number2", "type" => "num", "size" => 2, "required" => 1,
      "items" => array(
        array("label" => "", "value" => ""),
        array("label" => "One", "value" => 1),
        array("label" => "Two", "value" => 2),
        array("label" => "Three", "value" => 3) ) ),
  );

  // Attendance options.
  // Each item is (label, abbreviation, price).
  var $c_aryAttendance_Fields = array(
    array("label" => "Adult", "abbrev" => "Adult", "price" => 85),
    array("label" => "Student Jr High and up", "abbrev" => "Student", "price" => 45),
    array("label" => "Child 12 and under", "abbrev" => "Child", "price" => 35),
    array("label" => "CDSS Member Discount", "abbrev" => "CDSS", "price" => -5),
  );

  // Dis/Allow select of multiple attendance options.
  var $c_attendance_multi_select = 0;

  // Early registration discount. Use negative number.
  var $c_early_reg_discount = -10;

  // Late registration fee
  var $c_late_reg_fee = 0;

  // Early/Late registration cutoff date.
  // Format = "January 15, 2017"
  // This is the last date for early registration,
  // or the first date for late registration.
  // Example: for January 15, 2016, the early registration discount
  // will be applied through Jan 15, and stop on Jan 16.
  // The late registration fee will start on Jan 15.
  var $c_early_late_reg_cutoff_date = "January 15, 2016";

  // Merchandise Data Fields.
  // Each item is (label, abbreviation, price).
  var $c_aryMerchandise_Fields = array(
    array("label" => "T-Shirt Small", "abbrev" => "TShirtS",  "price" => 14),
    array("label" => "T-Shirt Medium",    "abbrev" => "TShirtM", "price" => 14),
    array("label" => "T-Shirt Large",  "abbrev" => "TShirtL",  "price" => 14),
    array("label" => "T-Shirt Extra Large",  "abbrev" => "TShirtXL", "price" => 14),
  );

  // Membership options
  var $c_member_single_price = 20;
  var $c_member_family_price = 30;

  // Maximum number of musicians
  var $c_max_musicians = 2;

  // Musician Data Fields.
  // Each item is (label, abbreviation, name, datatype, datasize).
  var $c_aryMusician_Fields = array(
    array("label" => "Musician Name", "abbrev" => "Mus Name", "name" => "mus_name", "type" => "text", "size" => 20),
    array("label" => "Instruments", "abbrev" => "Mus Inst", "name" => "mus_instruments", "type" => "text", "size" => 60),
    array("label" => "Bands", "abbrev" => "Mus Band", "name" => "mus_bands", "type" => "text", "size" => 60),
    array("label" => "In Band Scramble", "abbrev" => "Mus BScramble", "name" => "mus_bandscramble", "type" => "check", "size" => 1),
    array("label" => "In Open Band", "abbrev" => "Mus BOpen", "name" => "mus_bandopen", "type" => "check", "size" => 1),
    array("label" => "In Named Band", "abbrev" => "Mus BNamed", "name" => "mus_bandnamed", "type" => "check", "size" => 1)
  );

  // Maximum number of callers
  var $c_max_callers = 2;

  // Caller Data Fields.
  // Each item is (label, abbreviation, name, datatype, datasize, required).
  var $c_aryCaller_Fields = array(
    array("label" => "Caller Name",    "abbrev" => "Call Name",   "name" => "call_name",    "type" => "text", "size" => 20),
    array("label" => "Call Contra",  "abbrev" => "Call Contra", "name" => "call_contra",  "type" => "check", "size" => 1),
    array("label" => "Call English", "abbrev" => "Call Eng",    "name" => "call_english", "type" => "check", "size" => 1),
    array("label" => "Call Other",   "abbrev" => "Call Other",  "name" => "call_other",   "type" => "text", "size" => 60)
  );

  // PayPal Transaction Fees
  var $c_paypal_fee_fixed = 0.3;       // Fixed part of fee in dollars
  var $c_paypal_fee_percent = 0.029;  // Standard variable part of fee
//  var $c_paypal_fee_percent = 0.022;  // reduced rate for non-profit

  // These should not need to be changed.
  var $c_pp_logfile = "pp-ipn.log";  // PayPal IPN log file
  var $c_reg_logfile = "reg-log.csv";  // Registration log file, Excel CSV format
  var $c_error_logfile = "php-errors.log";  // PHP error log file

  // Dump data instead of processing it
  var $c_dump_form_debug = 0;

  // Email errors or display in page
  var $c_webmaster_email_errors = 1;

  // These are filled in during initialization, based on c_test_mode
  var $c_business_email = "";
  var $c_paypal_url = "";
  var $c_paypal_ipn_url = "";
  var $c_registrar_email = "";
  var $c_hospitality_email = "";

  // Today's date, filled in during initialization.
  var $c_today = "";

  // Class Constructor
  function Online_Reg_Config_Class(){
    // Setup values for normal and test modes
    if ($this->c_test_mode == 0) {
     // Production
       $this->c_business_email = 'payments@yourdomain.com';
       $this->c_paypal_url = "https://www.paypal.com"; // DO NOT CHANGE
       $this->c_paypal_ipn_url = "https://www.paypal.com/cgi-bin/webscr"; // DO NOT CHANGE
       $this->c_registrar_email = "registrar@yourdomain.com";
       $this->c_hospitality_email = "hospitality@yourdomain.com";
    } else {
     // Testing Sandbox
       $this->c_business_email = 'payments-seller1@yourdomain.com';
       $this->c_paypal_url = "https://www.sandbox.paypal.com"; // DO NOT CHANGE
       $this->c_paypal_ipn_url = "https://www.sandbox.paypal.com/cgi-bin/webscr"; // DO NOT CHANGE
       $this->c_registrar_email = "webmaster@yourdomain.com";
       $this->c_hospitality_email = "webmaster@yourdomain.com";
    } // else c_test_mode

    // Get Today's date
    $this->c_today = date("Y-m-d"); // DO NOT CHANGE

  } // Online_Reg_Config_Class constructor

} // Online_Reg_Config_Class
?>
