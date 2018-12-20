<?php
// CODE FOR WEB SERVICE CALLS AND DATABASE QUERIES
// FILE CREATED BY BY ASINGH - 11/28/2018
// EDITED FOR AD LOGIN BY ASINGH - 12/07/2018
// CLEANED UP AND EDITED FOR MINI ORANGE AUTHENTICATION BY ASINGH - 12/10/2018
// CLEANED UP AND EDITED SO IT RETRIEVES CREDENTIALS ON USER CHANGE BUT NOT ON EVERY PAGE LOAD - 12/12/2018

/* There are three lines of code that need to be put into the haines-portal-100-width.php file
 * 
 * The first two lines of code 
 * 
 * if(!isset($_SESSION)) session_start();
 * include("as400calls.php");
 * 
 * Should be within PHP tags after 
 * 
 * if ( ! defined( 'ABSPATH' ) ) {exit( 'Direct script access denied.' );}
 * 
 * and before
 * 
 * <?php get_header(); ?>
 * 
 * The third line of code 
 * 
 * exit();
 * 
 * should be placed right after get_footer();
 */

//Path for Web Service Calls, Data Table Names and Login Redirect
define('HC_PATH', 'http://jjh400.jjhaines.com/danciko/dancik-ows/d24/');
define('SP_PATH', "http://jjh400.jjhaines.com/danciko/dancik-sws/");
define('MILL_CLAIMS_TABLE', "jjhc_MillClaims");
define('HC_USERS', "jjhc_Users");
define('HC_REDIRECT', "Location:  https://jjhaines.net");
define('AD_SERVER', "ldap://bal-dc02.jjhaines.com");

//Credential Functions//////////////////////////////////////////////////////////////////////////////////////////////////////
// These functions use or change the user's credentials in the session.

//WP User Retrieval
//Function accesses the username from the cookies
function wp_user_retrieval()
{
    //For Loop for Cookies
    foreach($_COOKIE as $key => $value)
    {
        //Check for the right key
        if(preg_match("/^wordpress_logged_in/", $key))
        {
            $matches = array();
            preg_match("/^[^\|]*/", $value, $matches);
            return trim($matches[0]);
        }//End of if(preg_match("/^wordpress_logged_in/", $key))
    }//End of foreach($_COOKIE as $key => $value)
        
   death("User is not logged into wordpress.");
}//End of function wp_user_retrieval()

//Database Connect function
function open_db()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); // Create MySQL connection
    
    // Check connection for error
    if($message = $conn->connect_error)
    {
        close_db($conn);
        death("Connection failed: ".$message);
    }
    
    return $conn; //Return the connecton
}//End of function connect_db()

//Database Close function
function close_db($conn)
{
    //If there is a connecion to he database, close it.
    if(isset($conn)) mysqli_close($conn);
}//End of function close_db($conn)

//Run Query Function
function run_query($conn, $query)
{
    //If the query fails, close the connection and redirect to the user to the login page
    if(!$result = $conn->query($query))
    {
        close_db($conn);
        death("Could not query.");
    }//End of if(!$result = $conn->query($query))
    
    return $result; //return successful query
}//End of function run_query($conn, $query)

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
        
        return "";  //Return an empty string if there is no session key in the array.
    }//End of if(is_array($json))
    
    return null;  //returns null if an array was not pasted in as a paramater.
}//End of function extractSessionID($json)

// Active Directory Login from Sengeta Menon
// Just in case we need it
function ad_login()
{
    //Form Sengeta created
    $login_form = <<<EOT
    <form action="#" method="POST">
        <p><label for="username">Username: </label><input id="username" type="text" name="username" /> </p>
        <p><label for="password">Password: </label><input id="password" type="password" name="password" /> </p>
        <p align="right"><input type="submit" name="submit" value="Submit" /></p>
    </form>
EOT;
    
    //If data AD login data was entered into the form conditional
    if(isset($_POST['username']) && isset($_POST['password']))
    {
        $adServer = AD_SERVER; 
        
        //Connecting to ldap server and collecting login credentials
        $ldap = ldap_connect($adServer);
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        //Clearing the post
        unset($_POST['username']);
        unset($_POST['password']);
        
        $ldaprdn = 'jjhnt' . "\\" . $username; //Creating RDN
        
        //Setting LDAP options
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        
        //Successful ldap connection conditional
        if ($ldap)
        {
            echo "<hr />Connection ok: $ldap<hr />";
            
            //binding distiguished names and password to connection
            $bind = ldap_bind($ldap, $ldaprdn, $password);
            
            //Successful login conditional
            if($bind)
            {
                //Filtering and sorting results returned from login
                $filter="(sAMAccountName=$username)";
                $result = ldap_search($ldap,"dc=JJHAINES,dc=COM",$filter);
                ldap_sort($ldap,$result,"sn");
                $info = ldap_get_entries($ldap, $result);
                
                //Loop that goes through all of the entries from the successful result
                for( $i=0; $i < $info["count"]; $i++ )
                {
                    if($info['count'] > 1) break;
                    echo "<p>You are accessing <strong> ". $info[$i]["sn"][0] .", " . $info[$i]["givenname"][0] ."</strong><br /> (" . $info[$i]["samaccountname"][0] .")</p>\n";
                    echo '<pre>';
                    var_dump($info);
                    echo '</pre>';
                    $userDn = $info[$i]["distinguishedname"][0];
                }//End of for( $i=0; $i < $info["count"]; $i++ )
            }//End of if($bind)
                
            //Login failure conditional
            else
            {
                echo "Invalid username / password<hr />".$login_form;
            }//End of else
            
            @ldap_close($ldap); //Closing connection
        }//End of if ($ldap)
        
        //Ldap connection failed
        else
        {
            echo "Failed connection<hr />".$login_form; //Print login form on failed connection
        }//End of else
    }//End of if(isset($_POST['username']) && isset($_POST['password']))
    
    //If AD login data was not entered into the form conditional
    else
    {
        echo $login_form; //Print login form
    }//End of else  
}//End of function ad_login()

//Check Session Function
//Checks for data in session to see if it is active.
//Calls portal_login to restore the session if need be.
function check_session()
{
    if(!isset($_SESSION['username']) ||
       !isset($_SESSION['dancik_user']) || 
       !isset($_SESSION['sales_user']) ||
       !isset($_SESSION['role']) ||
       !isset($_SESSION['sesid']) ||
       !isset($_SESSION['sales_session']) ||
       !isset($_SESSION['acctid']))
    {
        portal_login();
    }
}//End of function check_session()

//Login function
//Collects all of user's credentials and stores them in the session if there is a new user
function portal_login()
{
    /*
   $username =  wp_user_retrieval(); //Retrieving user
   
   //Checking username is not in the session
   if(!isset($_SESSION['username']) || $_SESSION['username'] != $username)
   {
       //All the code below will go here when ready.
   }//End of if(!isset($_SESSION['username']) || $_SESSION['username'] != $username)
   */
   
   $_SESSION['username'] = "portalae"; //Temporary until user table is setup
   //$_SESSION['username'] = wp_user_retrieval();
   
   $conn = open_db(); // Create connection
   //Create query to retrieve Dancik Creendtials
   // Commneted out until ready 
   //$query = "SELECT `DancikUsername`, `DancikPassword`, `Sales_User`, `Sales_Password`, `RoleID` FROM `".HC_USERS."` WHERE UPPER(`Username`) = \"".strtoupper($_SESSION['username'])."\"";
   $query = "SELECT `DancikUsername`, `DancikPassword`, `RoleID` FROM `".HC_USERS."` WHERE UPPER(`Username`) = \"".strtoupper($_SESSION['username'])."\""; 
   $result = run_query($conn, $query);
    
   //Retrieve results of sucessful Dancik Credentials query and store all retrieved credentials in the session
   $dancik = $result->fetch_assoc();
   $_SESSION['dancik_user'] = $dancik['DancikUsername'];
   //Comment out until ready $_SESSION['sales_user'] = $dancik['Sales_User'];
   $_SESSION['role'] = $dancik['RoleID'];
   close_db($conn); //Closing Database connection

   //Making Login Web Service Call to D24, decoding the JSON and removing password
   $json_str = file_get_contents(HC_PATH."/login/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$dancik['DancikPassword']);
   $hold = json_decode($json_str, true);
   unset($dancik['DancikPassword']);
  
   if(isset($hold['errors'])) // If JSON returned is an error message conditional
   {
       death("Fails to log into D24.");
   }//End of if(isset($hold['errors']))
       
   else //No error is returned conditional
   {
       //Extracting D24 Session ID and Account ID
       $_SESSION['sesid'] = extractSessionID($hold);
       $json_str = file_get_contents(HC_PATH."/getAccountInfo?d24user=".$_SESSION['dancik_user']."&d24sesid=".$_SESSION['sesid']);
       $hold = json_decode($json_str, true);
       $accountInfo = $hold['acct'];
       $_SESSION['acctid'] = $accountInfo['accountid'];
   }//End of no error is returned conditional   
 
   //Comment out until ready
   /*
   //Making Login Web Service Call to Sales Portal, decoding the JSON and removing password
   $json_str = file_get_contents(SP_PATH."signon?user=".$_SESSION['sales_user']."&pwd=".$dancik['Sales_Password']);
   $hold = json_decode($json_str, true);
   unset($dancik['Sales_Password']);
   
   if(isset($hold['errors'])) // If JSON returned is an error message conditional
   {
       death("Fails to log into Dancik Sales Portal.");
   }//End of if(isset($hold['errors']))
       
   else //No error is returned conditional
   {
       //Extracting Sales Portal Session
       $_SESSION['sales_session'] = extractSessionID($hold);
   }//End of no error is returned conditional 
   */
}//End of function portal_login()
 
 //Logout Function
function portal_logout()
{
    // Iteration counters to stop loops so not to hang page.
    $d24_count = 0;
    //Comment out until ready $sp_count = 0;
    
    // Loging off of D24 and Sales Portal loops.
    // These are loops so that if there is a failure, it will try to logoff again.
    // There are a limited number of iterations in case there is an issue with the Rest servers, therefore
    // the user cannot logout and loop would be virtually infinite and may crash the system.
    do
    {
        $json_str = file_get_contents(HC_PATH."/logoff/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$_SESSION['sesid']);
        $d24 = json_decode($json_str, true);
        $d24_count++;
    }while(!isset($d24['success']) || $d24_count < 5); //Keep trying to logoff until successful
    
    //Comment out until ready
    /*
    do
    {
        $json_str = file_get_contents(SP_PATH."signoff?dancik-session-user=".$_SESSION['dancik_user']."&dancik-sessionid=".$_SESSION['sales_session']);
        $sp = json_decode($json_str, true);
        $sp_count++;
    }while(!isset($sp['success']) || $sp_count < 5); //Keep trying to logoff until successful
    //End of D24 and Sales Portal loops
    */
    
    //Logoff failure notifications
    //There is no call to die because death calls portal_logout and die.
    if(!isset($d24['success'])) echo "Failure to logoff D24 Web Service Calls.  Contact JJ Haines IT support.<br>";
    //Comment out until ready
    //if(!isset($sp['success'])) echo "Failure to logoff Sales Portal Web Service Calls.  Contact JJ Haines IT support.<br>";
    
    // Function destroys the session, to log out user
    // Unsetting the session attributes that I created, session destroy does not unset them
    if(isset($_SESSION['username'])) unset($_SESSION['username']);
    if(isset($_SESSION['sesid'])) unset($_SESSION['sesid']);
    if(isset($_SESSION['acctid'])) unset($_SESSION['acctid']);
    if(isset($_SESSION['dancik_user'])) unset($_SESSION['dancik_user']);
    if(isset($_SESSION['role'])) unset($_SESSION['role']);
    //Comment out until ready
    //if(isset($_SESSION['sales_user'])) unset($_SESSION['sales_user']);
    //if(isset($_SESSION['sales_session'])) unset($_SESSION['sales_session']);
    
    $_SESSION = array();
    session_destroy();
}//End of function portal_logout()

//Get Credentials String
//Returns a string for entering credentials for Dancik RESTful Calls
function get_credentials()
{
    return "?d24user=".$_SESSION['username']."&d24sesid=".$_SESSION['sesid']."&d24_acctid=".$_SESSION['acctid'];
}//End of function get_credentials()

//Get Sales Credentials String
//Returns a string for entering credentials for the Sales Portal Calls
function get_sales_credentials()
{
    return "?dancik-session-user=".$_SESSION['sales_user']."&dancik-sessionid=".$_SESSION['sales_session'];
}

//Death Function
//Kills page and session if something goes wrong and redirects user to the home page
function death($message)
{
    portal_logout(); //Clearing Session and logging out of rest calls
    header(HC_REDIRECT); /* Redirect browser */
    die($message);
}//End of function death($message)
//End of Credential Functions///////////////////////////////////////////////////////////////////////////////////////////////////

//TESTING FOR EXTERNAL LINKS WITH DANCIK CREDENTIALS////////////////////////////////////////////////////////////////////////////
// Orders and inventory function
function orders_inventory()
{
    header("Location:  http://jjh400.jjhaines.com/danciko/d24/main/".get_credentials(), false);
}// End of function orders_inventory()

// Sales Portal Function
function sales_portal()
{
    /*
    $cookie_jar = array(
        "sales-login" => "%7B%22user%22%3A%22asingh%22%2C%22pwd%22%3A%22asingh%22%7D",
        "JSESSIONID" => "C5C19175E8CE3603ADACA38B813E9464",
        "NAVIGATORSID" => "0000Ke5ncSjkV6YHgOuFL8JI_En:3d55f87a-feca-4a95-bd5e-259296123e5b",       
    );
    $sales_login = "%7B%22user%22%3A%22asingh%22%2C%22pwd%22%3A%22asingh%22%7D";
    setcookie("sales-login", $sales_login, 0, "/", "jjh400.jjhaines.com", true);
    $_POST['user'] = "asingh";
    $_POST['pwd'] = "asingh";
    */
    header("Locaion:  http://jjh400.jjhaines.com/danciko/sales/app-sales/index.jsp#show_dashboard");
}
//END OF TESTING FOR EXTERNAL LINKS WITH DANCIK CREDENTIALS/////////////////////////////////////////////////////////////////////

//SEARCHING FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////////
// These functions make web service calls to Dancik or send queries to the MySQL database.
// Then the search function calls the appropriate print function
//Product Search Function
function productSearch()
{
    $json_str = file_get_contents(HC_PATH."getItemsForAccount".get_credentials());
    $products = json_decode($json_str, true);
    if(isset($products['errors'])) death("Service call returned an error."); //If the service call is bad, call death.
    printItems($products['items']);
}//End of productSearch()

//Price Search Function
// It used to go here http://jjh400:8000/price-catalogs
function priceSearch()
{
    $json_str = file_get_contents(HC_PATH."getPriceList".get_credentials());
    $prices = json_decode($json_str, true);
    if(isset($prices['errors'])) death("Service call returned an error."); //If the service call is bad, call death.
    printBestPrices($prices['best_price']);
}//End of productSearch()

// Mill Claim Search
function mill_claim_search()
{
    // Create connection
    $conn = open_db();
    
    //Creating query conditions array
    $conditions = array();
    
    //Checking to see if the user entered information on Claim Number
    //Stored in the conditions and clears the POST
    if(isset($_POST['claim_number']))
    {
        //echo $_POST['claim_number'];
        $conditions['Claim Number'] = $_POST['claim_number'];
        unset($_POST['claim_number']);
    }
    
    //Checking to see if the user entered information on Claim Status
    //Stored in the conditions and clears the POST
    if(isset($_POST['claim_status']))
    {
        //echo $_POST['claim_status'];
        $conditions['Claim Status'] = $_POST['claim_status'];
        unset($_POST['claim_status']);
    }
    
    //Checking to see if the user entered information on Manufacturer
    //Stored in the conditions and clears the POST
    if(isset($_POST['manufacturer']))
    {
        //echo $_POST['manufacturer'];
        $conditions['Manufacturer Name'] = $_POST['manufacturer'];
        unset($_POST['manufacturer']);
    }
    
    //Checking to see if the user entered information on Consumer Name
    //Stored in the conditions and clears the POST
    if(isset($_POST['consumer']))
    {
        //echo $_POST['consumer'];
        $conditions['Consumer Name'] = $_POST['consumer'];
        unset($_POST['consumer']);
    }
    
    //Creating query to get Mill Claims.  This is searching for AE Carter now
    //The query condition for AE Carter will need to be replaced for the condition of the user.
    $query = "SELECT * FROM `".MILL_CLAIMS_TABLE."` WHERE `Account Executive Last Name` = \"Carter\"";
    
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
            //$query .= " AND `".$key."` LIKE \"%".$value."%\"";
            //$first = false;
        }
    }
    
    //Retrieving and printing query results table
    printMillClaim(run_query($conn, $query));
    
    // Conditional to check query and if the user entered search data
    /*if((isset($conditions['Claim Number']) || isset($conditions['Claim Status']) ||
     isset($conditions['Manufacturer Name']) || isset($conditions['Consumer Name'])))
     {
     printMillClaim($result);
     }//End of if for conditional to check query and if the user entered search data
     
     //This else is for debugging
     else
     {
     $query = "SELECT * FROM ".MILL_CLAIMS_TABLE." WHERE `Account Executive Last Name` = \"Carter\"";
     $result = $conn->query($query);
     printMillClaim($result);
     }*/
    
    
    //Unsetting the elements of the condtions array.
    //$conditions = array();
    
    close_db($conn); // Closing Mill Claim DB connection
}//End of function mill_claim_search()
//END OF SEARCHING FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////

//PRINTING FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////////
// These function write tables that display the data to the web page.
//Print Invoices
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
        foreach($invoice as $value)
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
        $table .= "<td align=\"left\">". preg_replace("/reducer/i", 'EDUCER', $item['patterndescription_dsp'])."</td>";
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
    //Creating Table Head
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
    
    //Finishing Table
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
    
    //Creating table body
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
    
    //Finishing table
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
//END OF PRINTING FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////////////////
// END OF CODE FOR WEB SERVICE CALLS
?>