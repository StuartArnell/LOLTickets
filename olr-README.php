<?php
// GM Online Registration System version 1.3.4  November 5, 2016
// 
// The separate files listed here are included in the zip file.
// The separate files are provided so you can read about the system
// without downloading the zip file.
// 
// This is an online registration system for any event.
// It is distributed as open source freeware under the 
// Open Software License v. 3.0.
// 
// It uses PayPal to collect the money, so requires a 
// PayPal seller account linked to a bank account.
// 
// It can be installed in any existing web site that supports
// PHP scripts and MySQL databases.
// 
// You create a MySQL Database, User, and Password.
// You edit one configuration file,
// then click one button to create the database table,
// then click another button to create the registration page.
// 
// The generated registration page is fully functional,
// but not very pretty. It is assumed you will want to 
// re-format it and modify the text.
// 
// ----- FEATURES
// 
// Registration for multiple people
// Can specify to not share personal information with others
// Options to indicate Gender, Child, and Student
// Hospitality options: Need, Offer, Smoking, Cats, Dogs, Car Pooler
// As many custom data fields as needed, in various formats 
//    (number, text, radio buttons, selection lists)
// Optional use of multiple phone numbers, with number type (home, cell, etc.)
// As many Attendance/Price options as needed
// Optional sales of merchandise (T-Shirts, etc.)
// Optional discount for Early Registration or fee for Late Registration 
//    (date is handled automatically)
// Optional discount for other purposes (organization membership, etc.)
// Optional donation
// Optional payment of deposit instead of entire amount
// Optional payment of PayPal fee
// Optional use of custom PayPal Payment pages
// Automatic updating of registration status for PayPal Refunds
// Optional reference to this Online Registration System in registration page footer
// Confirmation email is sent to user and to registrar for
//    Registrations and Refunds
// If hospitality is filled in, email is sent to hospitality coordinator
// Error checking for invalid or missing data
// Setting of PHP error options
// Restricting web access to sensitive files
// Logging of all registrations and paypal transactions
// Separate page to view the names of the Paid registrants
// Separate page (password protected) to display all registrations 
//    and download in Excel CSV format
// Separate page (password protected) to edit registrations
// Separate page (password protected) to generate database table 
//   and HTML registration page via one-button clicks
// 
// ----- REQUIREMENTS
// 
// PayPal Seller account, linked to a bank account
// Web server with PHP scripting (version 5.4.x, 5.5.x, or 5.6.x, NOT 7.x.x)
//    and MySQL database
// Someone with enough HTML and web server knowledge to create and 
//    modify files in your web site, including creating a MySQL database.
// 
// ----- INSTALLATION
// 
// For installation instructions, see the User's Guide
// Online-Registration-Guide.pdf
// 
// To update a version 1.3.3 installation to version 1.3.4:
// 
// 1. Copy the following files in the new version's "website" folder 
//    to the old installation folder, overwriting the old files:
//    olr-README.php, olr-build-reg-page.php, olr-validate.js.
// 
// 2. Update the version number in the old olr-config.php file
//    from 1.3.3 to 1.3.4, in TWO places: 
//    in the comment at the start of the file,
//    and in the definition of $c_olr_version near the end of the file.
//    Optional: move the line with the definiton of $c_olr_version
//    from near the end of the file to immediately after the line
// class Online_Reg_Config_Class{
// 
// 3. If your system uses a Late Registration Fee and is already running,
//    the late registration date is probably missing from the olr-init.js file.
//    If the following line is NOT in the olr-init.js file, 
//    add it before the line that contains "UpdateTotal(false);":
// CheckLateRegDate('MMM DD, YYYY', 0);
//    where MMM DD, YYYY is the late registration start date
//    defined in the olr-config.php file, like:
// var $c_early_late_reg_cutoff_date = "December 2, 2016";
//    If your system is not running yet, building the registration page 
//    will automatically update the olr-init.js file, so you don't
//    need to do anything about this.
// 
// 4. If you have a running online registration page and it has the 
//    Online Registration System version number in the footer, 
//    update that version number from 1.3.3 to 1.3.4.
//    Do NOT re-build the registration page. That will wipe out all 
//    current registrations.
// 
// Home Page: http://www.glennman.com/online-reg/home.html
// Download Site: https://sourceforge.net/projects/gmonlineregistration/
// Developer: Glenn Manuel, gemdancer@fastem.com
?>
