<?php
// CODE FOR WEB SERVICE CALLS AND DATABASE QUERIES
// FILE CREATED BY BY ASINGH - 11/28/2018

/* There are three other php  files that must have PHP code for this file to work
 * 
 * 
 * functions.php – This file is found in www/wp-content/themes/whatever_theme_we_are_using.  
 * The last line of the file should have the following line of code: include("whatever_path_you_choose/as400calls.php"); 
 * The path to as400calls.php should go to where it is stored.  
 * Also make sure there are no closing php tags at the end of functions.php.  
 * They may cause "Headers already sent" issues. 
 * 
 * Header.php – This file should be in the same directory as the functions.php file.  
 * At the beginning of the file include the following line of code: if(!isset($_SESSION)) session_start(); 
 * This is to ensure there is an active session for the php.  
 * This will be needed since the user’s credentials for Dancik and MySQL will be stored as session values.  
 * This is so the credentials can be used from page to page. 
 * 
 * Footer.php - This file should be in the same directory as the functions.php file.  
 * At the end of the file include the followingline of code: <?php exit(); ?> 
 * This is to ensure the session variables will not be unset when going from page to page.
 */

//MySQL Sever, MySQL Database, Path for Web Service Calls and Login Redirect
define('HC_HOST', "wordpressstackbuild005-rds-zqc3a8-databasecluster-8d6sph6bk7ti.cluster-cxuflrtukmtq.us-east-1.rds.amazonaws.com");
//define('HC_HOST', 'localhost');
define('HC_DATABASE', 'HainesConnect');
define('HC_PATH', 'http://jjh400.jjhaines.com/danciko/dancik-ows/d24/');
//define('HC_REDIRECT', "Location:  https://localhost/jjhaines-net/JJH-GLB-IT-Dev_AWS-NVA-CC_LAMP-WPv040900_Rep-0001_www-JJHaines-com/test-login");
define('HC_REDIRECT', "Location:  https://jjhaines.net/test-login/");

//Extract Session ID's from the JSON
function extractSessionID($json)
{
    if(is_array($json)) //Is the paramater an array?
    {
        //for loop that prints key and value
        foreach($json as $key => $value)
        {
            //If the key is session key, return the session key
            if(preg_match("/sesid/", $key))
                return $value;
                
            //If the value is an array, make a recursive call.
            // I shouldn't really need this else if but hey, just to be safe.
            else if(is_array($value))
                return extractSessionID($value);
            
        }//End of foreach($json as $key => $value)
        
        return "";  //Return an empty string if there is nosession key in the array.
    }//End of if(is_array($json))
    
    return null;  //returns null if there was an array was not present when needed.
}

//PRINTING FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////////////
// These function write tables that display the data to the web page.
//print invoices
function print_invoices($invoices)
{
    //Creating the table
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    
    //Creating head row
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">Reference</th>";
    $table .= "<th align=\"left\">Amount</th>";
    $table .= "<th align=\"left\">Amount Due</th>";
    $table .= "<th align=\"left\">Due Date</th>";
    $table .= "<th align=\"left\">Discount</th>";
    $table .= "<th align=\"left\">Invoice ID</th>";
    $table .= "<th align=\"left\">Amount Paid</th>";
    $table .= "<th align=\"left\">Invoice Date</th>";
    $table .= "<th align=\"left\">PO</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>"; //Start Data Rows
    
    //For Loop going through each invoice
    foreach($invoices as $invoice)
    {
        $table .= "<tr>"; //Start new row
        //For Loop for each individual invoice
        foreach($invoice as $key => $value)
        {
            //Print the table row
            $table .= "<td align=\"left\">".$value."</td>";
        }//End of foreach($invoice as $value)
        $table .= "</tr>"; //Finishing the row
    }//End of foreach($invoices as $invoice)
    
    // Finishing the table
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div>";
    
    echo $table;
}//End of Print Invoices

//Print Items
function printItems($items)
{
    //Creating table and header
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">ID</th>";
    $table .= "<th align=\"left\">Color</th>";
    $table .= "<th align=\"left\">Pattern</th>";
    $table .= "<th align=\"left\">Description</th>";
    $table .= "<th align=\"left\">Available Inventory</th>";
    //$table .= "<th align=\"left\">Need to Order</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    
    //For Loop going through each invoice
    foreach($items as $item)
    {
        $table .= "<tr>";
        $table .= "<td align=\"left\">".$item['id_dsp']."</td>";
        $table .= "<td align=\"left\">".$item['color']."</td>";
        $table .= "<td align=\"left\">".$item['patterndescription_dsp']."</td>";
        $table .= "<td align=\"left\">".$item['description1']." ".$item['description2']."</td>";
        $table .= "<td align=\"left\">".$item['available_inventory']."</td>";
        //$table .= "<td align=\"left\">".$item['insufficient_inventory_flag']."</td>";
        $table .= "</tr>";
    }//End of foreach($items as $item)
    
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div>";
    
    echo $table;
}//End of Print Items
 
//Print Best Prices
function printBestPrices($best_prices)
{
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">Date</th>";
    $table .= "<th align=\"left\">Name</th>";
    $table .= "<th align=\"left\">Best Price</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    
    //For Loop going through each invoice
    foreach($best_prices as $price)
    {
        $table .= "<tr>";
        //preg_replace("/(\d\d\/\d\d\/\d\d)\s+\d\d:\d\d/", , $price['date']);
        $table .= "<td align=\"left\">".$price['date']."</td>";
        $table .= "<td align=\"left\">".$price['name']."</td>";
        $table .= "<td align=\"left\">$".number_format((float)$price['best_price'], 2, '.' , ',')."</td>";
        $table .= "</tr>";
    }//End of foreach($items as $item)
    
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div>";
    
    echo $table;
}//End of Print Best Prices
 
 //Function to Print Account
function printAccount($accountInfo)
{
    //Creating table and header
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">User</th>";
    $table .= "<th align=\"left\">Account Name</th>";
    $table .= "<th align=\"left\">Account ID</th>";
    $table .= "<th align=\"left\">Warehouse ID</th>";
    $table .= "<th align=\"left\">Address</th>";
    $table .= "<th align=\"left\">City</th>";
    $table .= "<th align=\"left\">State</th>";
    $table .= "<th align=\"left\">Zip Code</th>";
    $table .= "<th align=\"left\">Phone</th>";
    $table .= "<th align=\"left\">Fax</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    $table .= "<tr>";
    $table .= "<td align=\"left\">{$accountInfo['userid']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['account_name']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['accountid']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['warehouseid']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['address1']}, {$accountInfo['address2']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['city']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['state']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['zip']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['phone']}</td>";
    $table .= "<td align=\"left\">{$accountInfo['fax']}</td>";
    $table .= "</tr>";
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div><br><br>";
    
    echo $table;
}//End of Print Account

//Print Mill Claim function
function printMillClaim($result)
{
    //Creating table header
    $table = "<div class=\"table-2\">";
    $table .= "<table width=\"100%\">";
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">ID</th>";
    $table .= "<th align=\"left\">Claim Number</th>";
    $table .= "<th align=\"left\">Claim Status</th>";
    $table .= "<th align=\"left\">Customer Account</th>";
    //$table .= "<th align=\"left\">Account Executive</th>";
    $table .= "<th align=\"left\">Consumer</th>";
    $table .= "<th align=\"left\">Customer</th>";
    $table .= "<th align=\"left\">Manufacturer</th>";
    $table .= "<th align=\"left\">Item</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    
    //Loop that goes through each row of the query
    while($row = $result->fetch_assoc())
    {
        $table .= "<tr>";
        $table .= "<td align=\"left\">{$row["ID"]}</td>";
        $table .= "<td align=\"left\">{$row["Claim Number"]}</td>";
        $table .= "<td align=\"left\">{$row["Claim Status"]}</td>";
        $table .= "<td align=\"left\">{$row["Customer Account Number"]}</td>";
        //$table .= "<td align=\"left\">{$row["Account Executive Last Name"]}</td>";
        $table .= "<td align=\"left\">{$row["Consumer Name"]}</td>";
        $table .= "<td align=\"left\">{$row["Customer Name"]}</td>";
        $table .= "<td align=\"left\">{$row["Manufacturer Name"]}</td>";
        $table .= "<td align=\"left\">{$row["Item Number"]}</td>";
        $table .= "</tr>";
    }//End of while($row = $result->fetch_assoc())
    
    //Finishing the Table
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div>";
    
    echo $table; //Printing table
}//End of Print Mill Claim
//END OF PRINTING FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////

//SEARCHING FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////
// These functions make web service calls to Dancik or send queries to the MySQL database.
// Then the search function calls the appropriate print function
//Product Search Function
function productSearch()
{
    $json_str = file_get_contents(HC_PATH."getItemsForAccount?d24user=".$_SESSION['username']."&d24sesid=".$_SESSION['sesid']."&d24_acctid=".$_SESSION['acctid']);
    $products = json_decode($json_str, true);
    $items = $products['items'];
    printItems($items);
}//End of productSearch()
 
//Price Search Function
// It used to go here http://jjh400:8000/price-catalogs
function priceSearch()
{
    $json_str = file_get_contents(HC_PATH."getPriceList?d24user=".$_SESSION['username']."&d24sesid=".$_SESSION['sesid']."&d24_acctid=".$_SESSION['acctid']);
    $prices = json_decode($json_str, true);
    $best_prices = $prices['best_price'];
    printBestPrices($best_prices);
}//End of productSearch()

// Mill Claim Search
function mill_claim_search()
{
    // Create connection
    $conn = connect_db();
    if(!isset($conn) || is_null($conn)) die("No Connection.");
    
    //Creating query
    $conditions = array();
    
    //Checking to see if the user entered information on Claim Number
    //Stored in the conditions and clears the POST
    if(isset($_POST['claim_number']))
    {
       $conditions['Claim Number'] = $_POST['claim_number'];
       unset($_POST['claim_number']);
    }
    
    //Checking to see if the user entered information on Status
    //Stored in the conditions and clears the POST
    if(isset($_POST['claim_status']))
    {
       $conditions['Claim Status'] = $_POST['claim_status'];
       unset($_POST['claim_status']);
    }
    
    //Checking to see if the user entered information on Manufacturer
    //Stored in the conditions and clears the POST
    if(isset($_POST['manufacturer']))
    {
       $conditions['Manufacturer Name'] = $_POST['manufacturer'];
       unset($_POST['manufacturer']);
    }
    
    //Checking to see if the user entered information on Consumer Name
    //Stored in the conditions and clears the POST
    if(isset($_POST['consumer']))
    {
       $conditions['Consumer Name'] = $_POST['consumer'];
       unset($_POST['consumer']);
    }
    
    //Creating query to get Mill Claims.  This is searching for AE Carter now
    //The query condition for AE Carter will need to be replaced for the condition of the user.
    $query = "SELECT * FROM jjhc_MillClaims WHERE `Account Executive Last Name` = \"Carter\"";
    
    //if($_SESSION['role'] == 2) $query .= " `Account Executive Last Name` = \"Carter\"";
    //$first = true;
    foreach($conditions as $key => $value)
    { 
       if(isset($value) && !is_null($value) && $value != "")
       {
            /*if(!$first)
            {
                $query .= " AND ";
            }*/
        
           $query .= " AND `".$key."` = \"".$value."\"";
           //$first = false;
       }
    }
       
    $result = $conn->query($query); // This is does not work in the conditional. ???
    
    // Conditional to check query and if the user entered search data
    if((isset($conditions['Claim Number']) || isset($conditions['Claim Status']) || 
       isset($conditions['Manufacturer Name']) || isset($conditions['Consumer Name'])) &&
       $result->num_rows)
    {
        printMillClaim($result);
    }//End of if for conditional to check query and if the user entered search data
      
    //This else is for debugging
    else 
    {
        $query = "SELECT * FROM jjhc_MillClaims WHERE `Account Executive Last Name` = \"Carter\"";
        $result = $conn->query($query);
        printMillClaim($result);
    }
    
    
    //Unsetting the elements of the condtions array.
    foreach($conditions as $condition)
    {
        unset($condition);
    }
    $conditions = array();
    close_db($conn); // Closing Mill Claim DB connection
}//End of function mill_claim_search()
//END OF SEARCHING FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////
 
//Credential Functions//////////////////////////////////////////////////////////////////////////////////////////////////////
// These functions log the user in and out and chaeck user credentials when changing pages.
//Login function
function portal_login()
{
    // Create connection
    //$conn = new mysqli(HOST, $_POST['username'], $_POST['password'], DATABASE);
    $conn = new mysqli(HOST, DB_USER, DB_PASSWORD, DATABASE);
    
    // Check connection for errors
    //If so, clear the POST, close the connection and redirect to the user to the login page
    if ($conn->connect_error) 
    {
        unset($_POST['username']);
        unset($_POST['password']);
        $conn->close();
        header(HC_REDIRECT); /* Redirect browser */
        exit();
    } 
    
    //Create query to retrieve Dancik Creendtials
    $query = "SELECT DancikUsername, DancikPassword, RoleID FROM jjhc_Users WHERE Username = \"".$_POST['username']."\" AND Password = \"".$_POST['password']."\"";
    
    //If the query fails, clear the POST, close the connection and redirect to the user to the login page
    if(!$result = $conn->query($query))
    {
        unset($_POST['username']);
        unset($_POST['password']);
        $conn->close();
        header(HC_REDIRECT); /* Redirect browser */
        exit();
    }
    
    //Retrieve results of sucessful Dancik Credentials query and store all retrieved credentials in the session
    $dancik = $result->fetch_assoc();
    $_SESSION['dancik_user'] = $dancik['DancikUsername'];
    $_SESSION['dancik_pass'] = $dancik['DancikPassword'];
    $_SESSION['role'] = $dancik['RoleID'];
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['password'] = $_POST['password'];
    
    //Making Login Web Service Call to Dancik and decoding the JSON
    $json_str = file_get_contents(HC_PATH."/login/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$_SESSION['dancik_pass']);
    $hold = json_decode($json_str, true);
    
    if(isset($hold['errors'])) // If JSON returned is an error message conditional
    {
        //Go back to the login page and clear the post
        unset($_POST['username']);
        unset($_POST['password']);
        $conn->close();
        //Location will need to be changed when code is pushed.
        header(HC_REDIRECT); /* Redirect browser */
        exit();
    }//End of if(isset($hold['errors']))
    
    else //No error is returned conditional
    {
        //Storing Dancik Session ID and removing password.
        $_SESSION['sesid'] = extractSessionID($hold);
        unset($_SESSION['dancik_pass']);
        
        //Dancik Call to get account ID and store in the session
        $json_str = file_get_contents(HC_PATH."/getAccountInfo?d24user=".$_SESSION['username']."&d24sesid=".$_SESSION['sesid']);
        $hold = json_decode($json_str, true);
        $accountInfo = $hold['acct'];
        $_SESSION['acctid'] = $accountInfo['accountid'];
         
        //Removing username and password from post after they have been stored in the session
        unset($_POST['username']);
        unset($_POST['password']);
        $conn->close(); //Closing Database connection
    }//End of no error is returned conditional   
}//End of function portal_login()
 
 //Logout Function
function portal_logout()
{
     // Function destroys the session, to log out user
     // Unsetting the session attributes that I created, session destroy does not unset them
     if(isset($_SESSION['username'])) unset($_SESSION['username']);
     if(isset($_SESSION['password'])) unset($_SESSION['password']);
     if(isset($_SESSION['sesid'])) unset($_SESSION['sesid']);
     if(isset($_SESSION['acctid'])) unset($_SESSION['acctid']);
     if(isset($_SESSION['dancik_user'])) unset($_SESSION['dancik_user']);
     if(isset($_SESSION['dancik_pass'])) unset($_SESSION['dancik_pass']);
     if(isset($_SESSION['role'])) unset($_SESSION['role']);
     session_destroy();
     //header("Location:  https://localhost/jjhaines-net/JJH-GLB-IT-Dev_AWS-NVA-CC_LAMP-WPv040900_Rep-0001_www-JJHaines-com/test-login"); /* Redirect browser *
}//End of function portal_logout()

//Logged in checking function
function check_login()
{
    //Condtional to see if someone entered an username and password
    if(isset($_POST['username']) && isset($_POST['password'])) 
    {
        portal_login(); //Call login function
    }
    
    //Redirect to login page if no one logged in
    else if(!isset($_SESSION['username']) || !isset($_SESSION['password']) ||
            !isset($_SESSION['sesid']) || !isset($_SESSION['acctid']) ||
            !isset($_SESSION['dancik_user']) || !isset($_SESSION['role']))
    {
        //Location will need to be changed when code is pushed.
        header(HC_REDIRECT); /* Redirect browser */
        exit();
    }
}//End of function check_post()

//Database Connect function
function connect_db()
{
    // Create connection
    $conn = new mysqli(HC_HOST, $_SESSION['username'], $_SESSION['password'], HC_DATABASE);
    
    // Check connection for error
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
    
    else //No error, return the connection
        return $conn;
}//End of function connect_db()

//Database Close function
function close_db($conn)
{
    //If there is a connecion to he database, close it.
    if(!is_null($conn)) mysqli_close($conn);
}//End of function close_db($conn)

 
 //Debugging function
function print_cookies()
{
    foreach($_COOKIE as $key => $value)
    {
        echo $key." => ";
         
         if(is_array($value))
             echo "array<br>";
             
         else
             echo $value."<br>";
     }
}
 
//Debugging function
function print_session()
{
    foreach($_SESSION as $key => $value)
    {
        echo $key." => ";
         
        if(is_array($value))
            echo "array<br>";
         
        else
            echo $value."<br>";
    }
}
 // END OF CODE FOR WEB SERVICE CALLS
 ?>