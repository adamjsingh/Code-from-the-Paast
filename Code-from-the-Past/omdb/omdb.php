<?php
/**
 * Plugin Name: OMDB
 * Plugin URI: localhost/omdb
 * Description: Searches OMDB by title
 * Version: 1.0
 * Author: Adam Singh
 * Author URI: localhost
 */

//Function that retrieve search results from the API
//[movie title="Good Will Hunting"]
function omdb_search($atts)
{
  //Setting attributes from the short code
  $a = shortcode_atts(array('title' => "Good Will Hunting"), $atts);

  $your_key = "a61b2946"; //Key from OMDB
  $url = "http://www.omdbapi.com/?apikey=".$your_key."&t=".$a['title'];
  $response = wp_remote_get($url); //Calling OMDB for data

  //Catching an error if it occurs
  if(is_wp_error($response))
  {
    die("Error occred when retrieving data.");
  }

  //Decoding the json
  $data = json_decode($response['body'], true);

  //Creating the begining of the table
  $table = "<table style=\"width:100%\">";

  //for loop that traverses through the data
  foreach($data as $key => $value)
  {
    if($key == "Ratings") //Rating found
    {
      //for loop for traversing through the ratings
      foreach($value as $rating)
      {
        $table .= "<tr>";
        $table .= "<td>Source</td>";
        $table .= "<td>".$rating['Source']."</td>";
        $table .= "</tr>";
      }//End of foreach($value as $rating)
    }//End of if($key == "Ratings")

    else //Ratings not found
    {
      $table .= "<tr>";
      $table .= "<td>".$key."</td>";
      $table .= "<td>".$value."</td>";
      $table .= "</tr>";
    }//End of else
  }//End of foreach($data as $key => $value)

  //End of the table
  $table .= "</table>";
  echo $table; //Printing table
}//End of omdb_search

add_shortcode('movie', 'omdb_search'); //Creating OMDB search shortcode
?>
