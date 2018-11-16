Skip to main contentSkip to toolbar

Dashboard
Home
Updates 3

Avada
Welcome
Registration
Support
FAQ
Demos
Plugins
System Status
Fusion Patcher
Theme Options

Fusion Builder
Welcome
Support
FAQ
Settings
Library
Add-ons

Fusion Slider
Add or Edit Slides
Add or Edit Sliders
Export / Import

Posts
All Posts
Add New
Categories
Tags

Media
Library
Add New

Forms
Forms
New Form
Entries
User Registration
Settings
Import/Export
Add-Ons
System Status
Help

Pages
All Pages
Add New

Comments

PHP snippets
Snippets
+ Add snippet
Tags
Import/Export
Settings

Portfolio
Portfolio
Add New
Portfolio Categories
Skills
Tags

FAQs
FAQs
Add New
FAQ Categories

Appearance
Themes
Customize
Widgets
Menus
Editor

Plugins 1
Installed Plugins
Add New
Editor

Users
All Users
Add New
Your Profile
User Role Editor

Tools
Available Tools
Import
Export
Export Personal Data
Erase Personal Data

All-in-One WP Migration
Export
Import
Backups

Settings
General
Writing
Reading
Discussion
Media
Permalinks
Privacy
Duplicate Post
UpdraftPlus Backups
User Role Editor
SSL

Landing Pages
All Landing Pages
Add New
Subscribers
Settings
Upgrade to Premium

Akeeba Backup

miniOrange SAML 2.0 SSO

WP File Manager
WP File Manager
Settings
Root Directory
System Properties
Shortcode – PRO
Collapse menu
About WordPress
JJ Haines
31 Plugin Update, 1 Theme Update, Translation Updates
00 comments awaiting moderation
New
Forms
UpdraftPlus
Howdy, Adam Singh
Log Out
Screen Options
Edit snippet + Add snippet
PHP snippets updated.
Dismiss this notice.
Enter title here
Login Call
 Toggle panel: Publish
Publish
 Published on: Nov 12, 2018 @ 12:16 Edit Edit date and time
Move to TrashUpdate
Toggle panel: Tags
Tags
Add New Tag

Add

Separate Tags with commas
Choose from the most used Tags
Toggle panel: Meet with Clearfy plugin!
MEET WITH CLEARFY PLUGIN!
Do you use snippets to disable unused WordPress features?
We can offer you a simpler and more reliable solution - our popular plugin for WordPress optimization Clearfy.

Just click toggles to turn on or off unused WordPress functions.

- No snippets
- Do not waste time
- Do not worry about security
- It's free
Download for free
Toggle panel: Base options
Base options
Enter the code for your snippet

1
//LDAP Server connection
2
/* $adServer = "ldap://bal-dc02.jjhaines.com";
3
​
4
    $ldap = ldap_connect($adServer);
5
    $username = "";
6
    $password = "";
7
​
8
    $ldaprdn = 'jjhnt' . "\\" . $username;
9
​
10
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
11
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
12
​
13
    if ($ldap)
14
    {
15
      echo "<hr />Connection ok: $ldap<hr />";
16
​
17
      $bind = ldap_bind($ldap, $ldaprdn, $password);
18
​
19
      if ($bind)
20
      {
21
        $filter="(sAMAccountName=$username)";
22
        $result = ldap_search($ldap,"dc=JJHAINES,dc=COM",$filter);
23
        ldap_sort($ldap,$result,"sn");
24
        $info = ldap_get_entries($ldap, $result);
25
​
26
        for( $i=0; $i < $info["count"]; $i++ )
27
        {
28
            if($info['count'] > 1) break;
29
            echo "<p>You are accessing <strong> ". $info[$i]["sn"][0] .", " . $info[$i]["givenname"][0] ."</strong><br /> (" . $info[$i]["samaccountname"][0] .")</p>\n";
30
            echo '<pre>';
31
            var_dump($info);
32
            echo '</pre>';
33
            $userDn = $info[$i]["distinguishedname"][0];
34
        }//End of for( $i=0; $i < $info["count"]; $i++ )
35
      }// End of if ($bind)
36
      else
37
      {
38
        echo "Invalid username / password<hr />".$login_form;
39
      }
40
      @ldap_close($ldap);
41
    }//End of if ($ldap)
42
    else
43
    {
44
      echo "Failed connection<hr />".$login_form;
45
    }*/
46
​
47
// Path definitions
48
define('PUBLIC_HTML', 'C:/Program Files (x86)/Ampps/www/jjhaines-net/JJH-GLB-IT-Dev_AWS-NVA-CC_LAMP-WPv040900_Rep-0001_www-JJHaines-com/HainesConnect');
49
define('APP_PATH', 'C:/Program Files (x86)/Ampps/www/jjhaines-net/JJH-GLB-IT-Dev_AWS-NVA-CC_LAMP-WPv040900_Rep-0001_www-JJHaines-com/HainesConnect/AE');
50
define('SALT', 'Fig Newton: The force required to accelerate a fig 39.37 inches per sec.');
51
​
52
//Print Function for arrays
53
function arrayPrint($json)
54
{
55
  if(is_array($json)) //Is the paramater an array?
56
  {
57
    //for loop that prints key and value
58
    foreach($json as $key => $value)
59
    {
60
      echo $key.": ";
61

62
      if(is_array($value))// if that checks to see if the value is an array
63
      {
64
        //If so, recursive call to print the new array
65
        arrayPrint($value);
66
        echo str_repeat("<br>",1);
67
      }//End of if(is_array($value))
68

69
      else// value is not an array
70
      {
71
         echo $value.str_repeat("<br>",3);
72
      }
73
    }//End of foreach($json as $key => $value)
74
  }//End ofif(is_array($json))
75
}
76
​
77
​
78
​
79
//Extract Session ID's from the JSON
80
function extractSessionID($json)
81
{
82
  if(is_array($json)) //Is the paramater an array?
83
  {
84
    //for loop that prints key and value
85
    foreach($json as $key => $value)
86
    {
87
      //If the key is session key, return the session key
88
      if(preg_match("/sesid/", $key))
89
        return $value;
90

91
      //If the value is an array, makea recursive call.
92
      else if(is_array($value))
93
        return extractSessionID($value);
94
    }//End of foreach($json as $key => $value)
95

96
    return "";  //Return an empty string if there is nosession key in the array.
97
  }//End of if(is_array($json))
98

99
  return null;  //returns null if there was an array was not present when needed.
100
}
101
​
102
//Extract Account ID's from the JSON
103
function extractAccountID($json)
104
{
105
  if(is_array($json)) //Is the paramater an array?
106
  {
107
    //for loop that prints key and value
108
    foreach($json as $key => $value)
109
    {
110
      //If the key is session key, return the session key
111
      if(preg_match("/accountid/", $key))
112
      {
113
        echo "Found it!<br>";
114
        return $value;
115
      }
116

117
      //If the value is an array, makea recursive call.
118
      else if(is_array($value))
119
        return extractAccountID($value);
120
    }//End of foreach($json as $key => $value)
121

122
    return "";  //Return an empty string if there is nosession key in the array.
123
  }//End of if(is_array($json))
124

125
  return null;  //returns null if there was an array was not present when needed.
126
}
127
​
128
$csrf_token_login = md5( SALT . time() );
129
// portalae Cookies01 works
130
$d24User = "portalae";
131
$d24Password = "Cookies01";
132
​
133
//Dancik Production Login
134
$dancikUser = "dancikprod";
135
$dancikPassword = "HAL2001SAL";
136
​
137
//Sharepoint Login
138
$SPuser = 'smenon';
139
$SPpass = 'Football01';
140
​
141
//DB Setting
142
$db_hostname="localhost";
143
$db_name="HainesConnect";
144
$db_username="webdevs";
145
$db_password="Cookies01";
146
​
147
//Get D24Data
148
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-ows/d24/login/?d24user=".$d24User."&d24pwd=".$d24Password);
149
$d24key = extractSessionID(json_decode($json_str, true));
150
echo "Printing D24 Data.<br>";
151
//echo $d24key.str_repeat("<br>",1);
152
//echo $json_str.str_repeat("<br>",1);
153
arrayPrint(json_decode($json_str, true));
154
​
155
//Get D24 JJ Data
156
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-ows/d24-jjh/login/?d24user=".$dancikUser."&d24pwd=".$dancikPassword);
157
$dancikKey = extractSessionID(json_decode($json_str, true));
158
echo "Printing D24 JJ Data.<br>";
159
//echo $dancikKey.str_repeat("<br>",1);
160
//echo $json_str.str_repeat("<br>",1);
161
arrayPrint(json_decode($json_str, true));
162
​
163
//Get Sharepoint Data
164
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-sws/signon?user=$SPuser&pwd=$SPpass");
165
$SPkey = extractSessionID(json_decode($json_str, true));
166
echo "Printing Sharepoint Data.<br>";
167
//echo $SPkey.str_repeat("<br>",1);
168
//echo $json_str.str_repeat("<br>",1);
169
arrayPrint(json_decode($json_str, true));
170
​
171
//Get Sales Portal Data
172
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-sws/rest/sales-portal/?d24user=".$d24User."&d24sesid=".$d24key);
173
echo "Sales Portal Data.<br>";
174
//echo $json_str.str_repeat("<br>",1);
175
arrayPrint(json_decode($json_str, true));
176
​
177
//Get Credit Management Data
178
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-sws/rest/creditmgr/?d24user=".$d24User."&d24sesid=".$d24key);
179
echo "Printing Credit Management Data.<br>";
180
//echo $json_str.str_repeat("<br>",1);
181
arrayPrint(json_decode($json_str, true));
182
​
183
//Get Account Info
184
$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-ows/d24/getAccountInfo?d24user=".$d24User."&d24sesid=".$d24key);
185
echo "Get Account Info.<br>";
186
echo $json_str."<br>";
187
arrayPrint(json_decode($json_str, true));
188
$hold = json_decode($json_str, true);
189
$accountInfo = $hold['acct'];
190
if(is_array($accountInfo))
191
{
192
  foreach($accountInfo as $key => $value)
193
    echo $key." : ".$value."<br>";
194
}
195
​
196
$table = <<<EOT
197
<div class="table-1">
198
<table width="100%">
199
<thead>
200
<tr>
201
<th align="left">User</th>
202
<th align="left">Account Name</th>
203
<th align="left">Account ID</th>
204
<th align="left">Warehouse ID</th>
205
</tr>
206
</thead>
207
<tbody>
208
<tr>
209
<td align="left">{$accountInfo['userid']}</td>
210
<td align="left">{$accountInfo['account_name']}</td>
211
<td align="left">{$accountInfo['accountid']}</td>
212
<td align="left">{$accountInfo['warehouseid']}</td>
213
</tr>
214
</tbody>
215
</table>
216
</div>
217
EOT;
218
​
219
echo $table;
220
​
221
//$accountID = "019199";
222
//$accountID = extractAccountID(json_decode($json_str, true));
223
​
224
//Account ID Test
225
/*$json_str = file_get_contents("http://jjh400.jjhaines.com/danciko/dancik-ows/d24/getAccountInfo?".$accountID."&d24_acctid=".$accountID."d24user=".$d24User."&d24sesid=".$d24key);
226
echo "Testing Account ID.<br>";
227
//echo $json_str.str_repeat("<br>",1);
228
echo "This is the account ID ".$accountID."<br>";
229
arrayPrint(json_decode($json_str, true));
230
$data = (json_decode($json_str, true));*/
Enter the PHP code, without opening and closing tags.
-If you want to put the html code in the snippet, put the closing php tag before the html code. Example: ?><div>my html code</div>
-You can get the values of the variables from the shortcode attributes. For example, if you set the my_type attribute for the shortcode [wbcr_php_snippet id="2864" my_type="button"], you can get the value of the my_type attribute in the snippet by calling $my_type var.
Where to execute the code?
Run everywhereWhere there is a shortcode
If you select the "Run everywhere" option, after activating the widget, the php code will be launched on all pages of your site. Another option works only where you have set a shortcode snippet (widgets, post).
Description
You can write a short note so that you can always remember why this code or your colleague was able to apply this code in his works.

Thank you for creating with WordPress. Version 4.9.8
