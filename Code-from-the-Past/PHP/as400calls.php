<?php
// CODE FOR WEB SERVICE CALLS AND DATABASE QUERIES
// FILE CREATED BY BY ASINGH - 11/28/2018
// EDITED FOR AD LOGIN BY ASINGH - 12/07/2018
// CLEANED UP AND EDITED FOR MINI ORANGE AUTHENTICATION BY ASINGH - 12/10/2018

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
 * should be placed right before get_footer();
 */

//Path for Web Service Calls, Data Table Names and Login Redirect
define('HC_PATH', '');
define('MILL_CLAIMS_TABLE', "");
define('HC_USERS', "");
define('HC_REDIRECT', "Location:  ");

//Credential Functions//////////////////////////////////////////////////////////////////////////////////////////////////////
// These functions need the user's credentials to operate

//WP User Retrieval
//Function Accesses the user through cookies
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
            return $matches[0];
        }
    }//End of foreach($_COOKIE as $key => $value)

    header(HC_REDIRECT); /* Redirect browser */
    die("User is not logged into wordpress.");
}//End of function wp_user_retrieval()

//Database Connect function
function open_db()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); // Create MySQL connection

    // Check connection for error
    if($conn->connect_error)
    {
        header(HC_REDIRECT); /* Redirect browser */
        //die("Connection failed: " . $conn->connect_error); //Debugging Test
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
        close_db($conn); //Closing Database
        header(HC_REDIRECT); /* Redirect browser */
        //die("Could not query"); // Debugging Test
    }

    return $result;
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

        return "";  //Return an empty string if there is nosession key in the array.
    }//End of if(is_array($json))

    return null;  //returns null if there was an array was not present when needed.
}//End of function extractSessionID($json)

//Login function
function portal_login()
{
    //$username = wp_user_retrieval(); //Retrieving wordpress user name
    $username = "portalae";
    //echo $username."<br>"; //Debugging statement

    $conn = open_db(); // Create connection
    $query = "SELECT * FROM `".HC_USERS."` WHERE `Username` = \"".$username."\""; //Create query to retrieve Dancik Creendtials
    $result = run_query($conn, $query);

    //Retrieve results of sucessful Dancik Credentials query and store all retrieved credentials in the session
    $dancik = $result->fetch_assoc();
    $_SESSION['dancik_user'] = $dancik['DancikUsername'];
    $_SESSION['dancik_pass'] = $dancik['DancikPassword'];
    $_SESSION['role'] = $dancik['RoleID'];
    $_SESSION['username'] = $username;

    //Making Login Web Service Call to Dancik and decoding the JSON
    $json_str = file_get_contents(HC_PATH."/login/?d24user=".$_SESSION['dancik_user']."&d24pwd=".$_SESSION['dancik_pass']);
    $hold = json_decode($json_str, true);

    if(isset($hold['errors'])) // If JSON returned is an error message conditional
    {
        close_db($conn); //Closing Database
        //Location will need to be changed when code is pushed.
        header(HC_REDIRECT); /* Redirect browser */
        exit();
        //die("Fails to log into Dancik."); // Debugging Test
    }//End of if(isset($hold['errors']))

    else //No error is returned conditional
    {
        //Storing Dancik Session ID and removing password.
        $_SESSION['sesid'] = extractSessionID($hold);
        unset($_SESSION['dancik_pass']);

        //Dancik Call to get account ID and store in the session
        $json_str = file_get_contents(HC_PATH."/getAccountInfo?d24user=".$_SESSION['dancik_user']."&d24sesid=".$_SESSION['sesid']);
        $hold = json_decode($json_str, true);
        $accountInfo = $hold['acct'];
        $_SESSION['acctid'] = $accountInfo['accountid'];

        close_db($conn); //Closing Database connection
    }//End of no error is returned conditional
}//End of function portal_login()

 //Logout Function
function portal_logout()
{
     // Function destroys the session, to log out user
     // Unsetting the session attributes that I created, session destroy does not unset them
     if(isset($_SESSION['username'])) unset($_SESSION['username']);
     if(isset($_SESSION['sesid'])) unset($_SESSION['sesid']);
     if(isset($_SESSION['acctid'])) unset($_SESSION['acctid']);
     if(isset($_SESSION['dancik_user'])) unset($_SESSION['dancik_user']);
     if(isset($_SESSION['dancik_pass'])) unset($_SESSION['dancik_pass']);
     if(isset($_SESSION['role'])) unset($_SESSION['role']);

     $_SESSION = array();
     session_destroy();
}//End of function portal_logout()
//End of Credential Functions///////////////////////////////////////////////////////////////////////////////////////////////////

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
            //$first = false;
        }
    }

    //Retrieving query results
    printMillClaim(run_query($conn, $query)); //Printing query results table

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
