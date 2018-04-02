// Copyright 2016 by Glenn Manuel.
// Licensed under the Open Software License version 3.0
// See separate license file for license details.
// Version 1.3.4
//
// Functions for verifying the online registration form.
// Attendance Option fields:
// span, id=priceX: price for each attendance option
// Attendance quanties can be textbox with user-supplied quantity
// or checkboxes or radio buttons
// type=text, name=num_aqtyX: quantity for each attendance option
// type=checkbox, name=chk_aqtyX: checkbox for each attendance option
// type=radio, name=rad_attend: radio button for each attendance option
// Merchandise Option fields:
// span, id=mpriceX: price for each merchandise option
// Merchandise quanties can be textbox with user-supplied quantity
// or checkboxes or radio buttons
// type=text, name=num_mqtyX: quantity for each merchandise option
// type=checkbox, name=chk_mqtyX: checkbox for each merchandise option
// type=radio, name=rad_merch: radio button for each merchandise option
// span, id=early_reg_discount: early registration discount
// span, id=early_reg_qty: calculated early registration qty
// type=hidden, name/id=hid_early_reg_qty: calculated early registration qty
// span, id=late_reg_fee: late registration fee
// span, id=late_reg_qty: calculated late registration qty
// type=hidden, name/id=hid_late_reg_qty: calculated late registration qty
// span, id=price_paypalfee: calculated PayPal fee
// type=checkbox, name/id=chk_paypalfee: user will pay PayPal fee
// span, id=total: calculated total price
// type=hidden, name/id=hid_total: calculated total price
// Early Reg Qty and Total have a span for display,
// and a hidden field to pass the value in the request variables.

if (! document.getElementById ) {
   alert("Your browser is not modern enough for this web page." +
    "Please use a more modern brower, like MS IE 5.x or later\n" +
    "or Netscape 6.x or later or Mozilla Firefox or Google Chrome.");
}

var g_numregs = 0;
var g_numdiscounts = 0;
var g_maxpersons = 0;
var g_numpersons = 0;

var g_early_reg = false;
var g_late_reg = false;

var g_fnames = new Array();

// A utility function that returns true if a string is empty or contains
// only whitespace characters.
function isblank(s) {
    var filter = /^\s*$/;
    if (filter.test(s)) {
        return true;
    }
    return false;
} // isblank

// A utility function that returns true if a string contains only 
// characters used in names of people, streets, cities, etc.
function isname(s) {
    var filter = /^([A-Z]|[a-z]|\d|[ \.\#\&\(\)\-_\'])+$/;
    if (filter.test(s)) { return true; }
    return false;
} // isname

// A utility function that returns true if a string contains  
// a standard 2-letter State abbreviation.
function isstate(s) {
    var filter = /^([A-W]|[a-w])([A-Z]|[a-z])$/;
    if (filter.test(s)) {
        return true;
    }
    return false;
} // isstate

// A utility function that returns true if a string is in correct zipcode
// format.  Below is the supported format ([] = optional characters):
//
//      xxxxx[-xxxx]
//
function iszip(s) {
    var filter = /^\d{5}(-\d{4})?$/;
    if (filter.test(s)) {
        return true;
    }
    return false;
} // iszip

// A utility function that returns true if a string is in correct phone
// format.  Supported formats include the following ([] = optional numbers):
//
//      xxx-xxx-xxxx, xxx/xxx-xxxx, (xxx)xxx-xxxx, and xxx.xxx.xxxx
//
function isphone(s) {
    var filter = new Array();
    filter[0] = /^\d{3}-\d{3}-\d{4}$/;
    filter[1] = /^\d{3}\/\d{3}-\d{4}$/;
    filter[2] = /^\(\d{3}\)\s*\d{3}-\d{4}$/;
    filter[3] = /^\d{3}\.\d{3}\.\d{4}$/;
    
    for (i = 0; i < filter.length; i++) {
        if (filter[i].test(s)) {
            return true;
        }
    }
    
    return false;
} // isphone

// A utility function that returns true if a string is in correct email address
// format.
function isemail(s) {
    var filter = /^([A-Z]|[a-z]|\d|[\._\-])+@([A-Z]|[a-z]|\d|[_\-])+\.([A-Z]|[a-z]|[_\-\.])+$/;
    if (filter.test(s)) {
        return true;
    }
    return false;
} // isemail

// A utility function that returns true if a string is numeric
// and 0 or greater.
function isnumeric(s) {
    var filter = /^\d+$/;
    if (filter.test(s)) {
		  if ( s >= 0 ) { return true; }
    }
    return false;
} // isnumeric

function StringTrim(strIn) {
   var strOut = strIn;
   var regex1 = /^\s+/;
   strOut = strIn.replace(regex1, "");
   regex1 = /\s+$/;
   strOut = strOut.replace(regex1, "");
   return(strOut);
} // StringTrim

function CheckEarlyRegDate(strDeadlineDate, test){
  var frm = document.forms[0];
  var today = new Date();
  today.setHours(0);
  today.setMinutes(0);
  today.setSeconds(0);
  today.setMilliseconds(0);
  var deadline = new Date(strDeadlineDate);

  //testing of early reg expiration.
  // Sets today to one month past deadline.
  if ( test > 0 ){
    today.setDate(1);
    today.setMonth(deadline.getMonth() + 1);
  }

  if ( today <= deadline) { g_early_reg = true; }
//alert("DateIn=[" + strDeadlineDate + "]" +
//"\n Deadline=[" + deadline + "]" +
//"\n Today=[" + today + "]" +
//"\n EarlyReg=[" + g_early_reg + "], Test=[" + test + "]");
} // CheckEarlyRegDate

function CheckLateRegDate(strDeadlineDate, test){
  var today = new Date();
  today.setHours(0);
  today.setMinutes(0);
  today.setSeconds(0);
  today.setMilliseconds(0);

  var deadline = new Date(strDeadlineDate);

  // testing of early reg expiration.
  // Sets today to one month past deadline.
  if ( test > 0 ){
    today.setDate(1);
    today.setMonth(deadline.getMonth() - 1);
  }

  if ( today >= deadline) { g_late_reg = true; }
//alert("DateIn=[" + strDeadlineDate + "]" +
//"\n Deadline=[" + deadline + "]" +
//"\n Today=[" + today + "]" +
//"\n LateReg=[" + g_late_reg + "], Test=[" + test + "]");
} // CheckLateRegDate

function format_dollars(value){
// Make sure the number has 2 decimal places.
  var num1 = value * 100;
  var num2 = Math.round(num1);
  var num3 = num2 / 100;
  var str1 = num3.toString();
  var i1 = str1.indexOf(".");
  var d1 = 0;
  // Determine number of existing decimal places.
  if (i1 > 0){
    d1 = str1.length - i1 - 1;
  }
  if ( d1 == 0 ) { return(str1 + ".00"); }
  else if (d1 == 1) { return(str1 + "0"); }
  else { return(str1); }
} // format_dollars

function GetNumPersons(){
  var ii = 0;
  var done = false;
  var ctrlname = "";
  var ctrlobj = "";
  var ctrlvalue = "";
  var frm = document.forms[0];
  g_numpersons = 0;

  while ( ! done ) {
    ii += 1;
    ctrlname = "txt_firstname" + ii;
    ctrlobj = frm.elements[ctrlname];
    if ( ctrlobj ) {
      g_maxpersons += 1;
      ctrlvalue = ctrlobj.value;
      if (StringTrim(ctrlvalue) != "" ) {
        g_fnames[g_fnames.length] = ctrlvalue.toLowerCase();
        g_numpersons += 1;
      }
    } else { done = true;}
  } // while
} // GetNumPersons

function UpdateTotal(display_alert) {
  var result = true;
  var ii = 0;
  var jj = 0;
  var kk = 0;
  var frm = document.forms[0];
  var numctrls = frm.elements.length;
  var ctrls = "";
  var ctrl_price = "";
  var ctrl_qty = "";
  var ctrl_total = "";
  var ctrl_ppfee = null;
  var ctrl_ppfee_hid = null;
  var ppfee = 0;
  var qtyname = "";
  var qtyvalue = "";
  var pricename = "";
  var pricevalue = "";
  var errs = "";
  var total = 0;
  g_numregs = 0;
  g_numdiscounts = 0;
  
  // Get the number of persons
  GetNumPersons();

  // Total all items.
  // control named num_qtyX = attendance quantity entered by user.
  // control named rad_attend = radio button for attendance option.
  // control named priceX = price of each item (-5 or 25).

  // Check for Donation amount
  ctrl_qty = frm["price_donation"];
  if (ctrl_qty){
    qtyvalue = ctrl_qty.value * 1.0;
    ctrl_qty.className = "";
    if (! isblank(qtyvalue) ) {
      if ( isnumeric(qtyvalue) && qtyvalue >= 0) {
        total += qtyvalue;
      }
      else {
        result = false;
        ctrl_qty.className = "olr-err";
      } // else invalid value
    }  // if not blank
  } // if donation
  
  // Check for radio button attendance options
  // when single person registration form
  ctrls = document.getElementsByName("rad_attend");
  if (ctrls.length > 0){
    for (ii = 0; ii < ctrls.length; ii++){
      if (ctrls[ii].checked){
        qtyvalue = ctrls[ii].value;
        pricename = "aprice" + qtyvalue;
      } // if
    } // for
    ctrl_price = document.getElementById(pricename);
    if ( ctrl_price ) {
      pricevalue = ctrl_price.innerHTML;
      g_numregs += 1.0;
      total += pricevalue * 1.0;
      if (pricevalue > 0){g_numdiscounts += 1;}
    } // if price control
  } // radio controls
  
  // Attendance and Merchandise options and prices
  for (ii=0; ii < numctrls; ii++){
    ctrl_qty = frm.elements[ii];
    if (ctrl_qty.type == "text"){
        qtyname = ctrl_qty.name;
       if (qtyname.substring(0,8) == "num_aqty") { 
        // Get Qty for this option
        qtyvalue = ctrl_qty.value;
        ctrl_qty.className = "";
        jj += 1;
        if (! isblank(qtyvalue) ) {
          // Check for valid Qty value
          if ( isnumeric(qtyvalue) && qtyvalue >= 0) {
            // Get Price for this option
            pricename = "aprice" + jj;
            ctrl_price = document.getElementById(pricename);
            if ( ctrl_price ) {
              pricevalue = ctrl_price.innerHTML;
              // Add positive price amounts to the totals
              if (pricevalue * 1.0 >= 0) {
                // Don't count discounts
                if (qtyvalue >= 0) {
                  g_numregs += qtyvalue * 1.0;
                  total += pricevalue * qtyvalue;
                } // if qtyvalue >= 0
                // Only give early reg discount for positive values 
                if (pricevalue > 0){g_numdiscounts += qtyvalue * 1.0;}
              } // if positive price
              // Special handling for discount amounts
              else {
                // Ignore discount if nothing else selected
                if ( total > 0) {
                  total += pricevalue * qtyvalue;
                } // if total > 0
              } // else price is negative
            }
          } else { // Invalid value
              result = false;
              ctrl_qty.className = "olr-err";
          } // else invalid value
        } // if not blank
      } // if qty control
      else {  // merchandise quantities and prices
        if (qtyname.substring(0,8) == "num_mqty") { 
          // Get Qty for this option
          qtyvalue = ctrl_qty.value;
          ctrl_qty.className = "";
          kk += 1;
          if (! isblank(qtyvalue) ) {
            // Check for valid Qty value
            if ( isnumeric(qtyvalue) && qtyvalue >= 0) {
              // Get Price for this option
              pricename = "mprice" + kk;
              ctrl_price = document.getElementById(pricename);
              if ( ctrl_price ) {
                pricevalue = ctrl_price.innerHTML;
                total += pricevalue * qtyvalue;
              } // if price
            } else { // Invalid value
              result = false;
              ctrl_qty.className = "olr-err";
            }
          } // if not blank
        } // if num_m_qty
      } // else
    } // if textbox
    else {
      if (ctrl_qty.type == "checkbox") {
        qtyname = ctrl_qty.name;
        if (qtyname.substring(0,8) == "chk_aqty") { 
           jj += 1;
          if (ctrl_qty.checked){
            // Get Price for this option
            pricename = "aprice" + jj;
            ctrl_price = document.getElementById(pricename);
            if ( ctrl_price ) {
              pricevalue = ctrl_price.innerHTML;
              total += pricevalue * 1.0;
              g_numregs += 1.0;
              // Only give early reg discount for positive values 
              if (pricevalue > 0){g_numdiscounts += 1;}
            } // if price
          } // if checked
        } // if chk_aqty
        else {
          if (qtyname.substring(0,8) == "chk_mqty") { 
            kk += 1;
            if (ctrl_qty.checked){
              // Get Price for this option
              pricename = "mprice" + kk;
              ctrl_price = document.getElementById(pricename);
              if ( ctrl_price ) {
                pricevalue = ctrl_price.innerHTML;
                total += pricevalue * 1.0;
              } // if price
            } // if checked
          } // if chk_mqty
        } // else not chk_aqty
      } // if checkbox
    } // else not textbox
  } // for each control

  if (document.getElementById("early_reg_qty")) {
    document.getElementById("early_reg_qty").innerHTML = "0";
    frm.hid_early_reg_qty.value = 0;
  } // early reg qty exists
  
  if (document.getElementById("late_reg_qty")) {
    document.getElementById("late_reg_qty").innerHTML = "0";
    frm.hid_late_reg_qty.value = 0;
  } // late reg qty exists

  // Check Single Membership (textbox)
  ctrl_qty = frm["num_member_single"];
  if ( ctrl_qty ) {
    qtyvalue = StringTrim(ctrl_qty.value);
    if ( (! isblank(qtyvalue) ) && (! isnumeric(qtyvalue)) ){
      result = false;
      ctrl_qty.className = "olr-err";
    } // qty is invalid
  } // qty control exists

  // Clear total fields
  ctrl_total = document.getElementById("total");
  ctrl_total.innerHTML = "0";
  frm.hid_total.value = "0";
  ctrl_pptotal1 = document.getElementById("price_paypaltotal");
  if (ctrl_pptotal1){ // Only used with deposit
    ctrl_pptotal1.innerHTML = "0";
  }
  ctrl_pptotal2 = frm.hid_paypaltotal;
  ctrl_pptotal2.value = "0";
  ctrl_ppfee = document.getElementById("price_paypalfee");
  ctrl_ppfee_hid = null;
  if (ctrl_ppfee){
    ctrl_ppfee.innerHTML = "0";
    ctrl_ppfee_hid = frm.hid_paypalfee;
    if (ctrl_ppfee_hid){ctrl_ppfee_hid.value = "0";}
  } // paypal fee control exists
    
  // Add Early Reg discount. Price should be negative.
  ctrl_qty = document.getElementById("early_reg_qty");
  ctrl_price = document.getElementById("early_reg_discount");
  if ( ctrl_qty && ctrl_price && g_early_reg) {
    ctrl_qty.innerHTML = g_numdiscounts;
    frm.hid_early_reg_qty.value = g_numdiscounts;
    total += g_numdiscounts * ctrl_price.innerHTML
  } // early reg controls exist

  // Add Late Reg fee
  ctrl_qty = document.getElementById("late_reg_qty");
  ctrl_price = document.getElementById("late_reg_fee");
  if ( ctrl_qty && ctrl_price && g_late_reg ) {
    ctrl_qty.innerHTML = g_numdiscounts;
    frm.hid_late_reg_qty.value = g_numdiscounts;
    total += g_numdiscounts * ctrl_price.innerHTML
  } // late reg controls exist
    
  if (! result) {
     if ( display_alert ) {
      alert("Invalid Quantities, see values with red border.\n");
    }
  } else if ( total == 0) {  return result; }

  // Add Single Membership (textbox)
  ctrl_qty = frm["num_member_single"];
  ctrl_price = document.getElementById("price_member_single");
  if ( ctrl_qty && ctrl_price ) {
    qtyvalue = ctrl_qty.value;
    if (! isblank(qtyvalue) && ( qtyvalue > 0) ) {
        // Get Price for this option
         total += ctrl_price.innerHTML * qtyvalue * 1.0;
    } // valid qty
  } // qty control exist

  // Add Family Membership (checkbox)
  ctrl_qty = frm["chk_member_family"];
  ctrl_price = document.getElementById("price_member_family");
  if ( ctrl_qty && ctrl_price && ctrl_qty.checked ) {
    total += ctrl_price.innerHTML * 1.0
  }

  // Add Single/Family Membership (radio button)
  ctrls = document.getElementsByName("rad_membership");
  value = "";
  if (ctrls){
    for (ii = 0; ii < ctrls.length; ii++){
      if (ctrls[ii].checked){
        value = ctrls[ii].value;
      } // if
    } // for
    if (value != "" && value != "none"){
      pricename = "price_member_family";
      if (value == "single"){pricename = "price_member_single";}
      ctrl_price = document.getElementById(pricename);
      if ( ctrl_price ) {
        pricevalue = ctrl_price.innerHTML;
        total += pricevalue * 1.0;
      } // if price control
    } // value not empty
  } // if radio controls

  // Calculate PayPal Fee, use Deposit amount if present.
  chk_deposit = frm.chk_deposit;
  deposit_price = 0;
  ppfee = 0;
  ctrl_deposit1 = document.getElementById("price_deposit");
  ctrl_deposit2 = document.getElementById("deposit_total");
  if (ctrl_deposit2){ctrl_deposit2.innerHTML = "0"};
  // If using deposit, base paypal fee on it
  if (chk_deposit && chk_deposit.checked){
    if (ctrl_deposit1){
      deposit_price = ctrl_deposit1.innerHTML * 1.0;
      ppfee = format_dollars(((deposit_price + g_paypal_fee_fixed) / (1.0 - g_paypal_fee_percent)) - deposit_price);
      if (ctrl_deposit2){
        ctrl_deposit2.innerHTML = format_dollars(deposit_price);
      }
    }
  } else {  // no deposit
    ppfee = format_dollars(((total + g_paypal_fee_fixed) / (1.0 - g_paypal_fee_percent)) - total);
    if (ctrl_deposit2){ctrl_deposit2.innerHTML = "0"; }
  } // else no deposit
  ppfee = format_dollars(ppfee);

  // Output PayPal fee
  if (ctrl_ppfee){ ctrl_ppfee.innerHTML = ppfee; }
  if (ctrl_ppfee_hid){ctrl_ppfee_hid.value = ppfee;}
  
  // If paying PayPal fee, update total and PayPal total.
  // PayPal total is only used when deposit is used.
  if (frm.chk_paypalfee && frm.chk_paypalfee.checked){
    total = (total * 1.0) + (ppfee * 1.0);
    if (ctrl_pptotal1){
      ctrl_pptotal1.innerHTML = format_dollars(total);
      ctrl_pptotal2.value = ctrl_pptotal1.innerHTML;
      // If using deposit, fill in PayPal Total
      if (chk_deposit && chk_deposit.checked) {
        ctrl_pptotal1.innerHTML = format_dollars((deposit_price * 1.0) + (ppfee * 1.0));
        ctrl_pptotal2.value = ctrl_pptotal1.innerHTML;
      } // if paying deposit
    } // if paypal total control exists
    // No deposit, paypal total = total
    else {ctrl_pptotal2.value = total;}
  } // if paying paypal fee 
  else { // not paying paypal fee
    if (ctrl_pptotal1){
      ctrl_pptotal1.innerHTML = format_dollars(total);
      ctrl_pptotal2.value = ctrl_pptotal1.innerHTML;
      // If using deposit, fill in PayPal Total
      if (chk_deposit && chk_deposit.checked) {
        ctrl_pptotal1.innerHTML = format_dollars(deposit_price);
        ctrl_pptotal2.value = ctrl_pptotal1.innerHTML;
      } // if paypal deposit
    } // if paying total control exists
    // No deposit, paypal total = total
    else {ctrl_pptotal2.value = total;}
  } // else not paying paypal fee

  // Update totals
  ctrl_total.innerHTML = format_dollars(total);
  frm.hid_total.value = format_dollars(total);
  // Update Balance Due, only present when Deposit is used.
  ctrl_baldue = document.getElementById("price_bal_due")
  if (ctrl_baldue){
    if (chk_deposit && chk_deposit.checked){
      if (frm.chk_paypalfee && frm.chk_paypalfee.checked){
        ctrl_baldue.innerHTML = format_dollars(total - ppfee - deposit_price);
      } else {
        ctrl_baldue.innerHTML = format_dollars(total - deposit_price);
      }
      if (frm.hid_bal_due){frm.hid_bal_due.value = ctrl_baldue.innerHTML;}
    } // if deposit
    else {
      ctrl_baldue.innerHTML = "0";
      if (frm.hid_bal_due){frm.hid_bal_due.value = "0";}
    } // else no deposit
  } // if balance due

  return result;
} // UpdateTotal

function ValidateText(strname, strvalue){
  var result = true;
  var ii = 0;

  if (isblank(strvalue)) { return true;}
  strvalue = StringTrim(strvalue);

   if (strname.substring(0,4) == "txt_") {
    if (strname.indexOf("name") > 1) {
      if (! isname(strvalue) ) {result = false;}
    } else if (strname.indexOf("street") > 1) {
      if (! isname(strvalue) ) {result = false;}
    } else if (strname.indexOf("city") > 1) {
      if (! isname(strvalue) ) {result = false;}
    } else if (strname.indexOf("state") > 1) {
      if (! isstate(strvalue) ) {result = false;}
    } else if (strname.indexOf("zip") > 1) {
      if (! iszip(strvalue) ) {result = false;}
    } else if (strname.indexOf("phone") > 1) {
      if (! isphone(strvalue) ) {result = false;}
    } else if (strname.indexOf("email") > 1) {
      if (! isemail(strvalue) ) {result = false;}
    }
  } else if (strname.substring(0,4) == "num_") {
    if (! isnumeric(strvalue)) {result = false;}
  }
  return result;
} // ValidateText

function ValidateQtys(){
  var frm = document.forms[0];
  var result = true;
  var ctrl_qty = "";
  var qtyname = "";
  var qtyvalue = "";
  var ii = 0;
  var numctrls = frm.elements.length;
  var done = false;
  var numhousing = 0;
  var errmsg = "";

  // Get the number of persons
  GetNumPersons();

  // Verify each Attendance Qty is <= number of persons
  for (ii=0; ii < numctrls; ii++){
    ctrl_qty = frm.elements[ii];
    if (ctrl_qty.type == "text"){
        qtyname = ctrl_qty.name;
       if (qtyname.substring(0,8) == "num_aqty") {
        qtyvalue = StringTrim(ctrl_qty.value);
        if (isnumeric(qtyvalue) && qtyvalue > 0) {
          if ( qtyvalue > g_numpersons ) {
            ctrl_qty.className = "olr-err";
            result = false;
          } // if qtyvalue > g_numpersons
        } // if numeric > 0
      } // if num_qty
    } // if text
  } // for each control

  // Verify each housing is <= number of persons
  var qty_housing_total = -1;
  for (ii=0; ii < numctrls; ii++){
    ctrl_qty = frm.elements[ii];
    if (ctrl_qty.type == "text"){
      qtyname = ctrl_qty.name;
      if (qtyname.substring(0,12) == "num_housing_") {
        qtyvalue = StringTrim(ctrl_qty.value);
        if (isnumeric(qtyvalue) && qtyvalue > 0) {
          if (qty_housing_total < 0) { qty_housing_total = 0;}
            qty_housing_total += qtyvalue * 1.0;
            if ( qtyvalue > g_numpersons ) {
             ctrl_qty.className = "olr-err";
             result = false;
          } // if qtyvalue > g_numpersons
        } // if numeric > 0
      } // if num_housing_
    } // if text
  } // for each control

  // Check num_hosp_req_num <= g_numpersons
  ctrl_qty = frm.num_hosp_req_num;
  if ( ctrl_qty ){
      qtyvalue = StringTrim(ctrl_qty.value);
    if (isnumeric(qtyvalue) && qtyvalue > 0) {
       if ( qtyvalue > g_numpersons ) {
         ctrl_qty.className = "olr-err";
         result = false;
      } // if qtyvalue > g_numpersons
    } // if numeric > 0
  } // if qtrl_qty
  
  // Check num_member_single <= g_numpersons
  ctrl_qty = frm.num_member_single;
  if ( ctrl_qty ){
    qtyvalue = StringTrim(ctrl_qty.value);
    if (isnumeric(qtyvalue) && qtyvalue > 0) {
       if ( qtyvalue > g_numpersons ) {
         ctrl_qty.className = "olr-err";
         result = false;
      } // if qtyvalue > g_numpersons
    } // if numeric > 0
  } // if qtrl_qty

  // Check Men Gender Balance <= g_numpersons
  var qty_dancer_total = 0;
  var qty_men_dancer = 0;
   ctrl_qty = frm["num_men_dancers"];
   if ( ctrl_qty ) {
     qtyvalue = StringTrim(ctrl_qty.value);
    if (! isblank(qtyvalue) ){
         if (! isnumeric(qtyvalue)) {
           result = false;
         ctrl_qty.className = "olr-err";
         } else if ( qtyvalue > g_numpersons ) {
         ctrl_qty.className = "olr-err";
         result = false;
      } else {  qty_men_dancer = qtyvalue; }
    } // if qtyvalue not blank
  } // if qtrl_qty

  // Check Women Gender Balance <= g_numpersons
  var qty_women_dancer = 0;
   ctrl_qty = frm["num_women_dancers"];
   if ( ctrl_qty ) {
     qtyvalue = StringTrim(ctrl_qty.value);
    if (! isblank(qtyvalue) ){
         if (! isnumeric(qtyvalue)) {
           result = false;
         ctrl_qty.className = "olr-err";
         } else if ( qtyvalue > g_numpersons ) {
         ctrl_qty.className = "olr-err";
         result = false;
      } else {  qty_women_dancer = qtyvalue; }
    } // if qtyvalue not blank
  } // if qtrl_qty

  if ( ! result ){ errmsg = "Qty > Num Persons (" + g_numpersons + ").\n"}
  
  // Check total Gender Balance <= g_numpersons
  qty_dancer_total = (1.0 * qty_men_dancer) + (1.0 * qty_women_dancer);
  if ( qty_dancer_total  > g_numpersons ){
      errmsg += "Num Dancers [" + 
   qty_dancer_total + "] > Num  Persons [" + g_numpersons + "].\n";}

  // Check housing (cabin) total = g_numpersons
  if ( qty_housing_total > -1 && qty_housing_total != g_numpersons ){
    errmsg += "Housing count [" + qty_housing_total + 
    "] not equal to Num  Persons [" + g_numpersons + "].\n";
  }

   // Check total number of registrations > 0
  if ( g_numregs <= 0 ){errmsg += "No Attendance Options selected.\n";}

   // Check total number of registrations equals number of persons
  if (g_maxpersons > 1 || ! g_attendance_multi_select){
    if ( g_numregs != g_numpersons ){errmsg += "Total Registrations [" + 
     g_numregs + "] not equal to Num  Persons [" + g_numpersons + "].\n";}
  } // if g_attendance_multi_select

  // Check for negative balance due
  ctrl_qty = document.getElementById("price_bal_due")
  if (ctrl_qty){
    if ((ctrl_qty.innerHTML * 1.0) < 0){
      errmsg += "Balance Due is Negative.\n"
    } // if balance due < 0
  } // if balance due

  return(errmsg);
}  // ValidateQtys

function ValidateMusCallers(which_type){
   var frm = document.forms[0];
   var ctrlname = "txt_mus_name";
   var ctrlnamelen = 0;
   var ctrl = null;
   var ctrlvalue = "";
   var result = true;
   var found = false;
   
   if (which_type == "callers"){ctrlname = "txt_call_name";}
   // Loop through all musician or caller name controls
   for (ii = 1; ii <= g_maxpersons; ii++){
      ctrl = frm[ctrlname + ii];
      if ( ctrl ) {
         ctrlvalue = StringTrim(ctrl.value).toLowerCase();
         if (ctrlvalue != "" ){
            // Check if first name matches a registered person first name
            found = false;
            for (jj = 0; jj < g_fnames.length; jj++){
               if (g_fnames[jj] == ctrlvalue){
                  found = true;
               }
            } // for jj
            // If no first name match found, mark control as error
            if (!found){
               ctrl.className = "olr-err";
               result = false;
            }
         } // if not blank
      } // if ctrl
   } // for ii
   return result;
} // ValidateMusCallers

function ValidateControls(){
  var success = true;
  var ii = 0;
  var frm = document.forms[0];
  var numctrls = frm.elements.length;
  var ctrl = "";
  var ctrlname = "";
  var ctrlvalue = "";
  var numbad = 0;
  var errs = "";
  
  // Remove red outlines
  for (ii=0; ii < numctrls; ii++){
    ctrl = frm.elements[ii];
    ctrl.className = "";
  }

  if ( ! ValidateRequired() ) { errs += "Missing required data.\n";}
  if ( ! UpdateTotal(false) ) { errs += "Invalid Quantity.\n";}
  errs += ValidateQtys();
  if ( ! ValidateMusCallers("musicians") ) { errs += "Invalid Musician First Name.\n";}
  if ( ! ValidateMusCallers("callers") ) { errs += "Invalid Caller First Name.\n";}

  // Validate all text controls.
  // name prefix "txt_" means it should contain text.
  // name prefix "num_" means it should be a positive number.

  for (ii=0; ii < numctrls; ii++){
    ctrl = frm.elements[ii];
    if (ctrl.type == "text"){
      ctrlname = ctrl.name;
      ctrlvalue = ctrl.value;
      if (! ValidateText(ctrlname, ctrlvalue)) { 
        ctrl.className = "olr-err";
        numbad += 1;
      }
     }
  }
  if (! ValidateGender()) { numbad += 1; }
  if ( numbad > 0) {
    errs += "Invalid data.\n";
  }
  if ( errs != "" ) {
    errs += "Scroll up and look for data fields with a red border.\n";
    alert(errs);
    success = false;
  }
  return success;
} // ValidateControls

function ValidateRadio($ctrlname){
  var success = true;
  var rclass = "";
  var ctrl = null;
  var checked = false;
  var rlist = document.getElementsByName($ctrlname);

  for (jj = 0; jj < rlist.length; jj++){
    ctrl = rlist[jj];
    rclass = ctrl.parentNode.className;
    ctrl.parentNode.className = "olr-required"; // clear error
    if (ctrl.checked){ checked = true; }
  } // for each radio button
  if (!checked){
    success = false;
    ctrl = rlist[0];
    ctrl.parentNode.className = "olr-required olr-err";
  } // if nothing checked
  return success;
} // ValidateRadio

function ValidateRequired(){
  var success = true;
  var frm = document.forms[0];
  var reqnames = frm["required_fields"].value;
  var aryreqname = reqnames.split(",");
  var arylen = aryreqname.length;
  var ii = 0;
  var ctrlname = "";
  var ctrl = "";
  var radio = false;

  for (ii = 0; ii < arylen; ii++){
    ctrlname = aryreqname[ii];
    ctrl = frm[ctrlname];
    if (ctrl){
      if (ctrlname.substring(0,4) == "rad_"){
        if (!ValidateRadio(ctrlname)){success = false;}
      } else {
        ctrl.className = "";
        if (isblank(ctrl.value)) { 
          success = false;
          ctrl.className = "olr-err";
        } // if blank value
      } // else not radio
    } // if ctrl
  } // for each control
  return success;
} // ValidateRequired


function ValidateGender(){
// If gender is used, it is required for each non-empty first name.
  if (!g_use_gender_data){ return true; }

  var success = true;
  var frm = document.forms[0];

  for (ii = 1; ii <= g_maxpersons; ii++){
    ctrl1 = frm["txt_firstname" + ii];
    ctrl2 = frm["rad_gender" + ii];
    glist = document.getElementsByName("rad_gender" + ii);
    checked = false;
    if ( ctrl1 && glist.length > 0) {
      ctrlvalue1 = StringTrim(ctrl1.value);
      for (jj = 0; jj < glist.length; jj++){
        ctrl2 = glist[jj];
        ctrl2.parentNode.className = "olr-required";
        if (ctrlvalue1 != "" || ii == 1){
          if (ctrl2.checked){ checked = true; }
        } // if not blank
      } // for each gender
      if ( (ctrlvalue1 != "" || ii == 1 ) && !checked){
        ctrl2 = glist[0];
        ctrl2.parentNode.className = "olr-required olr-err";
        success = false;
      } // if nothing checked
    } // if ctrls
  } // for each person
  return success;
} // ValidateGender


function ValidatePage() {
  var ii = 0;
  var frm = document.forms[0];
  var firstname2 = "";
  var lastname1 = "";
  var lastname2 = "";
  var temp = "";

  // Check for invalid values.
  if (! ValidateControls() ) { return false;}
  temp = frm["hid_total"].value;
  if ( temp == "" || temp == "0" || temp == "0.00") {
    alert("Total is $0. Please select an Attendance option.\n"); 
    return false;
  }
  return true;
}

function submit_form(){
	var result = false;
	if ( ValidatePage() ) {
		if (confirm("Are you sure ?")) {
			result = true;
		};
	}
	return(result);
}

function submit_form_closed_zz(){
  alert("Online Registration is now Closed")
  return false;
}
