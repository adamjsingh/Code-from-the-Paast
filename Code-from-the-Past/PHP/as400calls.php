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
 * 
 * 
 */

require '/var/www/wordpress/jjhaines/awsphp/aws-autoloader.php';
use Aws\Kms\KmsClient as KMSClient;
use Aws\Exception\AwsException;


//Path for Web Service Calls, Data Table Names and Login Redirect
define('HC_PATH', 'http://jjh400.jjhaines.com/danciko/dancik-ows/d24/');
define('SP_PATH', "http://jjh400.jjhaines.com/danciko/dancik-sws/");
define('SP_CALL', "http://jjh400.jjhaines.com/danciko/dancik-sws/rest/sales-portal/");
define('MILL_CLAIMS_TABLE', "jjhc_MillClaims");
define('HC_USERS', "jjhc_Users");
define('HC_REDIRECT', "Location:  https://jjhaines.net");
define('AD_SERVER', "ldap://bal-dc02.jjhaines.com");

//START OF UTILIY FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////////
//These functions are helper functions used to retrieve and calculate data, and any other things.

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

//Percentage Function
//Caluclate percentage for progress
function percentage($amount, $max)
{
    if($max == 0)
    {
        return 0;
    }
    
    else
    {
        return ($amount/$max)*100;
    }
}//End of function percentage($amount, $max)

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
//END OF UTILIY FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////

//START OF DATABASE FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////
//These functions interface with the MySQL Database for the Wordpress site

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

//Prepare and Excute Function Function
function prepare_execute($conn, $query)
{
    //If the query fails, close the connection and redirect to the user to the login page
    if(!$stmt = $conn->prepare($query))
    {
        close_db($conn);
        death("Could not query.");
    }//End of if(!$result = $conn->query($query))
    
    return $stmt->execute(); //return successful query
}//End of function prepare_execute($conn, $query)
//END OF DATABASE FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////////


//START OF CREDENTIAL FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////
// These functions use the user's credentials to obtain Dancik sesson keys and Active Directory authentication.
// They also store, check and clear the session data, as well.

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
       //Logging out previous session and starting a new.
       portal_logout();
       if(!isset($_SESSION)) session_start();
       
       // Create a KMSClient
       $key = "arn:aws:kms:us-east-1:086480065013:key/915f0862-29f5-4c56-b377-e3f1ff2d4b98";
       $kmsClient = new Aws\Kms\KmsClient([
        'profile' => 'default',
        'version' => '2014-11-01',
        'region'  => 'us-east-1'
       ]);
       
       //All the code below will go here when ready.
   }//End of if(!isset($_SESSION['username']) || $_SESSION['username'] != $username)
   */
   
   //Temporary until user table is setup
   $_SESSION['username'] = "portalae"; 
   $_SESSION['sales_user'] = "asingh";
   $sales_password = "asingh";
   
   //$_SESSION['username'] = $username();
   
   $conn = open_db(); // Create connection
   //Create query to retrieve Dancik Creendtials
   // Commneted out until ready 
   //$query = "SELECT `DancikUsername`, AES_DECRYPT(`DancikPassword`, ".$some_key."), `Sales_User`, AES_DECRYPT(`Sales_Password`, ".$some_key."),"
   //$query .= " `RoleID` FROM `".HC_USERS."` WHERE UPPER(`Username`) = \"".strtoupper($_SESSION['username'])."\"";
   $query = "SELECT `DancikUsername`, `DancikPassword`, `RoleID` FROM `".HC_USERS."` WHERE UPPER(`Username`) = \"".strtoupper($_SESSION['username'])."\""; 
   $result = run_query($conn, $query);
    
   //Retrieve results of sucessful Dancik Credentials query and store all retrieved credentials in the session
   $dancik = $result->fetch_assoc();
   $_SESSION['dancik_user'] = $dancik['DancikUsername'];
   //Comment out until ready $_SESSION['sales_user'] = $dancik['Sales_User'];
   $_SESSION['role'] = $dancik['RoleID'];
   close_db($conn); //Closing Database connection

   /*
   //Decrypting passwords
   //Use $d24['Plaintext'] and $sales['Plaintext'] as passwords for the login APIs
   $d24 = $kmsClient->decrypt([
       'CipphertextBlob' => $dancik['DancikPassword']       
   ]);
   
   $sales = $kmsClient->decrypt([
       'CipphertextBlob' => $dancik['SalesPassword']
   ]);
   */
   
   //Making Login Web Service Call to D24, decoding the JSON and removing password
   $json_str = file_get_contents(HC_PATH."login/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$dancik['DancikPassword']);
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
 
   //Making Login Web Service Call to Sales Portal, decoding the JSON and removing password
   $json_str = file_get_contents(SP_PATH."signon?user=".$_SESSION['sales_user']."&pwd=".$sales_password);
   //echo $json_str;
   $hold = json_decode($json_str, true);
   //unset($dancik['Sales_Password']);
   
   if(isset($hold['errors'])) // If JSON returned is an error message conditional
   {
       death("Fails to log into Dancik Sales Portal.");
   }//End of if(isset($hold['errors']))
       
   else //No error is returned conditional
   {
       //Extracting Sales Portal Session
       $_SESSION['sales_session'] = extractSessionID($hold);
       //$call = SP_CALL."getAccount".get_sales_credentials();
       //$json_str = file_get_contents($call);
       //echo $json_str;
   }//End of no error is returned conditional 
}//End of function portal_login()
 
 //Logout Function
 // Function destroys the session, to log out user
function portal_logout()
{
    // Iteration counters to stop loops so not to hang page.
    $d24_count = 0;
    $sp_count = 0;
        
    // Unsetting the session attributes that I created, session destroy does not unset them
    
    //Session Key checks and log off loops
    if(isset($_SESSION['sesid']))
    {
        do //Do While loopto keep loggin off until sucessful, within five tries.
        {
            $json_str = file_get_contents(HC_PATH."logoff/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$_SESSION['sesid']);
            $d24 = json_decode($json_str, true);
            $d24_count++;
        }while(!isset($d24['success']) || $d24_count < 5); //Keep trying to logoff until successful
        
        if(!isset($d24['success'])) echo "Failure to logoff D24 Web Service Calls.  Contact JJ Haines IT support.<br>";
        unset($_SESSION['sesid']);
    }//End of if(isset($_SESSION['sesid']))
    
    if(isset($_SESSION['sales_session']))
    {
        do //Do While loopto keep loggin off until sucessful, within five tries.
        {
            $json_str = file_get_contents(SP_PATH."signoff?dancik-session-user=".$_SESSION['dancik_user']."&dancik-sessionid=".$_SESSION['sales_session']);
            $sp = json_decode($json_str, true);
            $sp_count++;
        }while(!isset($sp['success']) || $sp_count < 5); //Keep trying to logoff until successful
        
        if(!isset($sp['success'])) echo "Failure to logoff Sales Portal Web Service Calls.  Contact JJ Haines IT support.<br>";
        unset($_SESSION['sales_session']);
    }//End of if(isset($_SESSION['sales_session']))
    
    if(isset($_SESSION['username'])) unset($_SESSION['username']);
    if(isset($_SESSION['acctid'])) unset($_SESSION['acctid']);
    if(isset($_SESSION['dancik_user'])) unset($_SESSION['dancik_user']);
    if(isset($_SESSION['role'])) unset($_SESSION['role']);
    if(isset($_SESSION['sales_user'])) unset($_SESSION['sales_user']);
    
    $_SESSION = array();
    session_destroy();
}//End of function portal_logout()

//Death Function
//Kills page and session if something goes wrong and redirects user to the home page
function death($message)
{
    portal_logout(); //Clearing Session and logging out of rest calls
    header(HC_REDIRECT); /* Redirect browser */
    die($message);
}//End of function death($message)
//END OF CREDENTIAL FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////

//START OF CREDENTIAL RETRIEVAL FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////
//These Functions return the strings that the DANCIK API uses to check username and session ids.

//Get D24 Credentials String
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
//END OF CREDENTIAL RETRIEVAL FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////

//TESTING FOR EXTERNAL LINKS WITH DANCIK CREDENTIALS////////////////////////////////////////////////////////////////////////////
// Orders and inventory function
function orders_inventory()
{
    $button = "<button onclick=\"orders_inventory_redirect()\"";
    $button .= "class = \"fusion-button button-flat fusion-button-square button-large button-default button-1\"";
    $button .= ">Orders and Inventory</button>";
    $button .= "<script>";
    $button .= "function orders_inventory_redirect(){";
    $button .= "window.open('http://jjh400.jjhaines.com/danciko/d24/main/".get_credentials()."', '_blank')}";
    $button .= "</script>";
    echo $button;
}// End of function orders_inventory()
//END OF TESTING FOR EXTERNAL LINKS WITH DANCIK CREDENTIALS/////////////////////////////////////////////////////////////////////

//SEARCHING AND PRINTING FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////////////
// These functions make web service calls to Dancik or send queries to the MySQL database.
// Then the search function calls the appropriate print function

//START OF D24 PRODUCT FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////
//Product Search Function
function productSearch()
{
   $json_str = file_get_contents(HC_PATH."getItemsForAccount".get_credentials());
   $products = json_decode($json_str, true);
   if(isset($products['errors'])) death("Service call returned an error."); //If the service call is bad, call death.
   printItems($products['items']);
}//End of productSearch()

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
//END OF D24 PRODUCT FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////

//START OF D24 PRICE FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////////////////
//Price Search Function
// It used to go here http://jjh400:8000/price-catalogs
function priceSearch()
{
    $json_str = file_get_contents(HC_PATH."getPriceList".get_credentials());
    $prices = json_decode($json_str, true);
    if(isset($prices['errors'])) death("Service call returned an error."); //If the service call is bad, call death.
    printBestPrices($prices['best_price']);
}//End of productSearch()

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
//END OF D24 PRICE FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////


//START OF MILL CLAIM FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////
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
//END OF MILL CLAIM FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////

//START OF SALES PORTAL CUSTOMER FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////
// Customer Search Function
function customer_search()
{
    //Creating search form
    $form = "<form action=\"\" method=\"post\">Search Term:<input name=\"keyword\" type=\"text\" />";
    $form .= "<input name=\"start\" type=\"hidden\" value=\"1\" />";
    $form .= "<input class=\"fusion-button button-flat fusion-button-square button-large button-default button-1\"";
    $form .= " type=\"submit\" value=\"Search\" /></form><br><br>";
    echo $form;
    
    //Conditional to check to see if a key word was entered
    if(isset($_POST['keyword']) && trim($_POST['keyword']) != "")
    {
        //Making webservice calls
        $call = SP_CALL."getCustomers".get_sales_credentials()."&keyword=".$_POST['keyword']."&startingRecord=".$_POST['start'];
        $json_str = file_get_contents($call);
        //echo $json_str;
        $hold = json_decode($json_str, true);
        $records = $hold['records'];
        $query_size = $hold['info']['querysize'];
        printCustomers($records, $query_size, $_POST['keyword']);  //Printing results
    }//End of if(isset($_POST['keyword']))
}//End of function customer_search()

//Print Customers
function printCustomers($customers, $size, $keyword)
{
    $i = 1; //Iterator for forms
    //Creating a new table
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    $table .= "<tbody>";
    
    //For Loop for each customer
    foreach($customers as $customer)
    {        
        $table .= "<tr>"; //Starting a row
        $table .= "<td style=\"text-align:left;\">";
        $table .= "<form action=\"../customer-metrics/\" method=\"post\" id=\"customer".($i)."\" name=\"customer".($i)."\">";
        $table .= "<input name=\"cust_name\" type=\"hidden\" value=\"".$customer['name']."\" />";
        $table .= "<input name=\"acct\" type=\"hidden\" value=\"".$customer['acct']."\" />";
        $table .= "<input name=\"addr1\" type=\"hidden\" value=\"".$customer['addr1']."\" />";
        $table .= "<input name=\"addr2\" type=\"hidden\" value=\"".$customer['addr2']."\" />";
        $table .= "<input name=\"city\" type=\"hidden\" value=\"".$customer['city']."\" />";
        $table .= "<input name=\"state\" type=\"hidden\" value=\"".$customer['state']."\" />";
        $table .= "<input name=\"zip\" type=\"hidden\" value=\"".$customer['zip']."\" />";
        $table .= "<input name=\"phone\" type=\"hidden\" value=\"".$customer['phone']."\" />";
        $table .= "<input name=\"keyword\" type=\"hidden\" value=\"".$keyword."\" />";
        $table .= "<input name=\"start\" type=\"hidden\" value=\"".($_POST['start'])."\" />";
        $table .= "</form>";
        $table .= "<button ";
        $table .= "style=\"background:none!important; ";
        $table .= "color:inherit; ";
        $table .= "text-align:left; ";
        $table .= "border:none; ";
        $table .= "padding:0!important; ";
        $table .= "font:inherit; ";
        $table .= "cursor:pointer;";
        $table .= "\"";
        $table .= "type=\"submit\" form=\"customer".($i)."\" value=\"Submit\">";
        
        //Customer Information displayed in a one-cell row
        $table .= "<strong>";
        $table .= $customer['name']." (".$customer['acct'].")";
        $table .= "</strong></br>";
        $table .= $customer['addr1']."<br>";
        if(trim($customer['addr2']) != "") $table .= $customer['addr2']."<br>";
        $table .= $customer['city'].", ".$customer['state']." ".$customer['zip']."<br>";
        $table .= $customer['phone']."<br>";
        
        $table .= "</button>";
        $table .= "</td>";
        $table .= "</tr>"; //Ending the row
        $i++;
    }//End of foreach($customers as $customer)
    
    //Finishing table
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div><br><br>";
    
    echo $table;
    
    //Div to align the previous and next buttons
    echo "<div style=\"display:flex;\">";
    
    //Starting past the first record conditional
    if($_POST['start'] > 1)
    {
        //Creating the previous button
        $prev = "<form method=\"post\" align=\"left\">";
        $prev .= "<input type=\"hidden\" name=\"keyword\" value=\"".$keyword."\" />";
        $prev .= "<input name=\"start\" type=\"hidden\" value=\"".($_POST['start'] - 25)."\" />";
        $prev .= "<input type=\"submit\" class=\"";
        $prev .= "fusion-button button-flat fusion-button-square button-large button-default button-1\" ";
        $prev .= "name=\"prev\" id=\"prev\" value=\"Previous 25\" /></form>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo $prev;
    }//End of if($_POST['start'] > 1)
    
    //Not starting within the last 25 records conditional
    if($_POST['start'] < $size - 25)
    {
        //Creating the next button
        $next = "<form method=\"post\" align=\"right\">";
        $next .= "<input type=\"hidden\" name=\"keyword\" value=\"".$keyword."\" />";
        $next .= "<input name=\"start\" type=\"hidden\" value=\"".($_POST['start'] + 25)."\" />";
        $next .= "<input type=\"submit\" class=\"";
        $next .= "fusion-button button-flat fusion-button-square button-large button-default button-1\" ";
        $next .= "name=\"next\" id=\"next\" value=\"Next 25\" /></form>";
        echo $next;
    }//End of if($_POST['start'] < $size - 25)
    
    echo "</div><br><br>"; //End of dive that aligns the previous and next buttons
}//End of function printCustomers($customers, $size, $keyword)

//Customer Metrics Function
function customer_metrics()
{
    $display = "<p>";
    $display .= "<strong>";
    $display .= $_POST['cust_name']." (".$_POST['acct'].")";
    $display .= "</strong><br>";
    $display .= "<a href=\"https://maps.google.com/?q=";
    $display .= $_POST['addr1'].", ";
    if(trim($_POST['addr2']) != "") $display .= $_POST['addr2'].", ";
    $display .= $_POST['city'].", ".$_POST['state']." ".$_POST['zip']."\" target=\"_blank\">";
    $display .= $_POST['addr1']."<br>";
    if(trim($_POST['addr2']) != "") $display .= $_POST['addr2']."<br>";
    $display .= $_POST['city'].", ".$_POST['state']." ".$_POST['zip']."</a><br><br>";
    $display .= "<a href=\"tel:".$_POST['phone']."\" target=\"_blank\">".$_POST['phone']."</a><br>";
    $display .= "</p>";
    echo $display;
   
    $call  = SP_CALL."getSalesMonthbyMonth".get_sales_credentials()."&comp=0&slsm=112";
    $json_str = file_get_contents($call);
    //echo $json_str;
    $hold = json_decode($json_str, true);
    $metrics = $hold['past12mm'];
    
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">Gross Sale</th>";
    $table .= "<th align=\"left\">Gross Percentage</th>";
    $table .= "<th align=\"left\">Average Order</th>";
    $table .= "<th align=\"left\">Month</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    
    foreach($metrics as $metric)
    {
        $table .= "<tr>";
        $table .= "<td><strong>".$metric['gross_sale']."</strong></td>";
        $table .= "<td>".$metric['gross_pct']."</td>";
        $table .= "<td>".$metric['avg_order']."</td>";
        $table .= "<td>".$metric['month_year']."</td>";
        $table .= "</tr>";
    }
    
    $table .= "</tbody></table></div></br>";
    echo $table;
    
    open_orders($_POST['acct']);
    
    $button = "<form action=\"https://jjhaines.net/customer-search/\" method=\"post\">";
    $button .= "<input type=\"hidden\" value=\"".$_POST['keyword']."\" name=\"keyword\" />";
    $button .= "<input type=\"hidden\" value=\"".$_POST['start']."\" name=\"start\" />";
    $button .= "<input type=\"submit\" class=\"fusion-button button-flat ";
    $button .= "fusion-button-square button-large button-default button-1\" name=\"cs\" id=\"cs\" ";
    $button .= "value=\"Return to Customer Search\" /><br/></form></br></br>";
    echo $button;
   
}//End of function customer_metrics()

//Open Orders Function
//Displays open orders for the customer
function open_orders($account)
{
    //$json_str = file_get_contents(SP_CALL."getOpenOrders".get_sales_credentials()."&comp=0&acct=".$account);
    //echo $json_str;
    
    $json_str = file_get_contents(HC_PATH."getOpenOrders".get_credentials());
    $hold = json_decode($json_str, true);
    $open_orders = $hold['openorders'];
    
    //Creating table header
    $table = "<div class=\"table-2\">";
    $table .= "<table width=\"100%\">";
    $table .= "<thead>";
    $table .= "<tr>";
    $table .= "<th align=\"left\">Order Number</th>";
    $table .= "<th align=\"left\">Purchasing Order Number</th>";
    $table .= "<th align=\"left\">Reference Number</th>";
    $table .= "<th align=\"left\">Ship Date</th>";
    $table .= "<th align=\"left\">Order Date</th>";
    $table .= "</tr>";
    $table .= "</thead>";
    $table .= "<tbody>";
    
    //Loop that goes through each row of the query
    foreach($open_orders as $open_order)
    {
        $table .= "<tr>";
        $table .= "<td align=\"left\">{$open_order["order"]}</td>";
        $table .= "<td align=\"left\">{$open_order["po"]}</td>";
        $table .= "<td align=\"left\">{$open_order["reference"]}</td>";
        $table .= "<td align=\"left\">{$open_order["shipdate"]}</td>";
        $table .= "<td align=\"left\">{$open_order["orderdate"]}</td>";
        $table .= "</tr>";
    }//End of while($row = $result->fetch_assoc())
    
    //Finishing the Table
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div></br></br>";
    
    echo $table; //Printing table
}//End of function open_orders()

//END OF SALES PORTAL CUSTOMER FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////

//START OF SALES PORTAL ITEMS FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////
//Sales Portal Items Search
function itemsSearch()
{
    //Creating search form
    $form = "<form action=\"\" method=\"post\">Search Term:<input name=\"keyword\" type=\"text\" />";
    $form .= "<input name=\"start\" type=\"hidden\" value=\"1\" />";
    $form .= "<input class=\"fusion-button button-flat fusion-button-square button-large button-default button-1\"";
    $form .= " type=\"submit\" value=\"Search\" /></form><br><br>";
    echo $form;
    
    //Making REST API and printing results
    $call = SP_CALL."getItems".get_sales_credentials()."&startingRecord=";
    
    //Conditional to see if start is set in post, for initial load of the page
    if(isset($_POST['start']))
    {
        $call .= $_POST['start'];
    }//End of if(isset($_POST['start']))
    
    //Conditional to see if start is not set in POST, for initial load of the page
    else
    {
        $call .= "1";
    }//End of else
    
    if(isset($_POST['keyword'])) $call .= "&keyword=".$_POST['keyword'];
    $json_str = file_get_contents($call);
    $hold = json_decode($json_str, true);
    $records = $hold['records'];
    $query_size = $hold['info']['querysize'];
    
    //Conditional to see if keyword is set in POST, for initial load of the page
    if(isset($_POST['keyword']))
    {
        $keyword = $_POST['keyword'];
    }//End of if(isset($_POST['keyword']))
        
    //Conditional to see if keyword is not set in POST, for initial load of the page
    else
    {
        $keyword = "";
    }//End of else
        
    printSalesItems($records, $query_size, $keyword);
}//End of itemsSearch()

//Print Sales Items
function printSalesItems($items, $size, $keyword)
{
    //Creating a new table
    $table = "<div class=\"table-1\">";
    $table .= "<table width=\"100%\">";
    $table .= "<tbody>";
    
    //For Loop for each customer
    foreach($items as $item)
    {
        //Product Information displayed in a two-cell row
        $table .= "<tr>"; //Starting a row
        $table .= "<td style=\"border-right-style:none; text-align:left;\">";
        $table .= "<strong>".$item['item']."</strong><br>";
        $table .= $item['desc1']."<br>";
        $table .= $item['desc2']."<br>";
        if(strcasecmp($item['discontinued'], "Y") == 0)
        {
            $table .= "<div style=\"font-weight:bold; color:#c90000;\">Discontinued</div>";
        }
        $table .= "</td>";
        
        $table .= "<td style=\"border-left-style:none; text-align:right; vertical-align:top;\">";
        $table .= $item['inv']."&nbsp;&nbsp".$item['uom']."<br>";
        $table .= preg_replace("/\d$/", "", $item['price1'])."&nbsp;&nbsp".$item['uom']."<br>";
        $table .= "</td>";
        $table .= "</tr>"; //Ending the row
    }//End of foreach($customers as $customer)
    
    //Finishing table
    $table .= "</tbody>";
    $table .= "</table>";
    $table .= "</div><br><br>";
    
    echo $table;
    
    //Div to align the previous and next buttons
    echo "<div style=\"display:flex;\">";
    
    //Conditional to check to see if the previous button should be created
    if(isset($_POST['start']) && $_POST['start'] > 1)
    {
        //Creating the previous button
        $prev = "<form method=\"post\" align=\"left\ target=\"gform_ajax_frame_1\">";
        $prev .= "<input type=\"hidden\" name=\"keyword\" value=\"".$keyword."\" />";
        $prev .= "<input name=\"start\" type=\"hidden\" value=\"".($_POST['start'] - 25)."\" />";
        $prev .= "<input type=\"submit\" class=\"";
        $prev .= "fusion-button button-flat fusion-button-square button-large button-default button-1\" ";
        $prev .= "name=\"prev\" id=\"prev\" value=\"Previous 25\" /></form>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo $prev;
    }//End of if(isset($_POST['start']) && $_POST['start'] > 1)
    
    //Conditional to see if the next button should be created
    if(!isset($_POST['start']) || $_POST['start'] < $size - 25)
    {
        //If the post doesn't have a start location, start at 1
        if(!isset($_POST['start']))
        {
            $start = 1;
        }
        
        //Otherwise, start with what is stored in post
        else
        {
            $start = $_POST['start'];
        }
        
        //Creating the next button
        $next = "<form method=\"post\" align=\"right\" target=\"gform_ajax_frame_1\">";
        $next .= "<input type=\"hidden\" name=\"keyword\" value=\"".$keyword."\" />";
        $next .= "<input name=\"start\" type=\"hidden\" value=\"".($start + 25)."\" />";
        $next .= "<input type=\"submit\" class=\"";
        $next .= "fusion-button button-flat fusion-button-square button-large button-default button-1\" ";
        $next .= "name=\"next\" id=\"next\" value=\"Next 25\" /></form>";
        echo $next;
    }//End of if(!isset($_POST['start']) || $_POST['start'] < $size - 25)
    
    echo "</div><br><br>"; //End of dive that aligns the previous and next buttons
}//End of function printSalesItems($items, $size, $keyword)
//END OF SALES PORTAL ITEMS FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////

//START OF AWS KMS FUNCTIONS/////////////////////////////////////////////////////////////////////////////////////////////////////
// Checks to see which keys are valid
function checkKeys()
{
    //Create a KMSClient
    $client = new KMSClient([
        'profile' => 'default',
        'version' => '2014-11-01',
        'region'  => 'us-east-1'
    ]);
    
    $result = $client->listKeys();
    $keys = $result->get("Keys");
    $test = "Hoy's my boy!";
    
    //For Loop for the keys
    foreach($keys as $key)
    {
        try //Trying to encrypt and decrypt
        {
            $cipher = $client->encrypt([
                'KeyId' => $key['KeyArn'],
                'Plaintext' => $test,
            ]);
            
            $plain = $client->decrypt([
                'CiphertextBlob' => $cipher['CiphertextBlob'],
            ]);
            
            echo "Key ID: ".$key['KeyId']."</br>";
            echo "Key ARN: ".$key['KeyArn']."</br>";
            echo "Test: ".$test."</br>";
            echo "Encrypted: ".$cipher['CiphertextBlob']."</br>";
            echo "Plain: ".$plain['Plaintext']."</br><br>";
        }//End of try
        
        catch(Exception $e)
        {
            echo "Failed: ".$e->getMessage()."</br><br>";
        }
        
    }//End of foreach($keys as $key)
}//End of function checkKeys()
//END OF AWS KMS FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////

//END OF SEARCHING AND PRINTING FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////

//START OF DATA TABLE FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////////////////
// Function that creates ae survey form and stores results in the database
function new_ae_survey()
{
    //If there Is a date in the POST, insett or update
    if(isset($_POST['date']))
    {
        $conn = open_db(); //Open Daabase connection
        $query = "SELECT * FROM WHERE";
        run_query($conn, $query);
        $query = "INSERT INTO WHERE";
        run_query($conn, $query);
        close_db($conn); //Close Database coonection
    }//End of if(isset($_POST['date']))
    
    //Creating the form
    $form = "<form method=\"post\" enctype=\"multipart/formdata\" ";
    $form .= "target=\"gform_ajax_frame_1\">";
    $form .= "</form>";
    echo $form;
}//End of function new_ae_survey()
//END OF DATA TABLE FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////////
/*
//START OF DATA ENTRY FUNCTIONS//////////////////////////////////////////////////////////////////////////////////////////////////
// Function used to add new user to jjhc_Users
function create_new_user()
{
    $conn = open_db();
    // Create a KMSClient
    $key = "arn:aws:kms:us-east-1:086480065013:key/915f0862-29f5-4c56-b377-e3f1ff2d4b98";
    $kmsClient = new Aws\Kms\KmsClient([
        'profile' => 'default',
        'version' => '2014-11-01',
        'region'  => 'us-east-1'
    ]);
    
    //Encrypting passwords
    $result = $KmsClient->encrypt([
        'KeyId' => $key,
        'Plaintext' => mysqli_real_escape_string($conn, $_POST['d24_password']),
    ]);
    
    $d24_password = $result['CiphertextBlob'];
    unset($_POST['d24_password']);
    
    $result = $KmsClient->encrypt([
        'KeyId' => $key,
        'Plaintext' => mysqli_real_escape_string($conn, $_POST['sales_password']),
    ]);
    
    $sales_password = $result['CiphertextBlob'];
    unset($_POST['sales_password']);
    
    $ad_user = mysqli_real_escape_string($conn, $_POST['ad_user']);
    $d24_user = mysqli_real_escape_string($conn, $_POST['d24_user']);
    $sales_user = mysqli_real_escape_string($conn, $_POST['sales_user']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $query = "INSERT INTO `jjhc_Users` (ad_user, d24_user, d24_password, sales_user, sales_password, role) ";
    $query .= "VALUES (\"".$ad_user."\", \"".$d24_user."\", \"".$d24_password."\", \"".$sales_user."\", \"".$sales_password;
    $query .= "\", \".$role.\")";
    
    if(prepare_execute($conn, $query))
        echo "User successfully added.\n";
    
    else
        echo "Failed to add user.\n";
    
    close_db($conn);
}//End of function create_new_user
//END OF DATA ENTRY FUNCTIONS////////////////////////////////////////////////////////////////////////////////////////////////////
*/
// END OF CODE FOR WEB SERVICE CALLS
?>