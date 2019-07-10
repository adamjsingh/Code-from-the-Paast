<?php

//This will replace the Parse.ly widget that displays links to related content
//to what is being viewed
class Lytics_Widget extends WP_Widget
{
  //Constructor, uses MM translations
  public function __construct()
  {
    parent::__construct('lytics_widget', __('Lytics Topic Trending', MONEYMORNING_ID),
        array('description' => __('Displays the top shared posts.', MONEYMORNING_ID))
    );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */

  //Main widget function
  public function widget($args, $instance)
  {
    //Final code will be
    //$tags = get_meta_tags(home_url($wp->request));
    //echo "<p>".$stage_url."</p>";

    //Looking for content of our defined Lytics topic meta tags and putting them
    //together for the topics value in the api
    /*
    $topic_tags = array('paTagIds', 'categories', 'tags');
    $topics = "";

    foreach($topics_tags as $topic_tag)
    {
      $topics .= $tags[$topic_tag].",";
    }

    $topics = trim($topics, ",");
    */

    //There is also /api/content/recommend/segment/{segId}?{limit,contentsegment,shuffle,rank}
    //In segments, topics are in the global array an their Segment QL identifier is "global"

    //Temporary work around for testing on staging, get_meta_tags does not have
    //access to the staging sites.
    global $wp;
    $stage_url = home_url($wp->request);
    $stage_url = preg_replace('/stage\./', '', $stage_url);
    //echo "<p>".$stage_url."</p>";

    //API URL
    $url = "https://api.lytics.io/api/content/recommend/user/email/cchamber@moneymappress.com?";
    $url .= "url=".$stage_url."&rank=affinity&limit=5&access_token=Pdp5cy4ZgAaogFe1B97kDwxx";

    $response = wp_safe_remote_get($url); //Wordpress API Call
    //$result = file_get_contents($url);

    //If there are no call errors
    if(!is_wp_error($response))
    {
      //Retrieving the body of the response as JSON
      $body = $response['body'];
      $json = json_decode($body, true);
      //$json = json_decode($result, true);

      //Status is okay
      if($json['status'] == 200)
      {
        $data = $json['data']; //Data of the content

        //This flag was created so if there was no valid content to display,
        //The widget would not display at all.
        $first_print = true;

        //For loop to create links to the content retrieved by the API
        foreach($data as $page)
        {
          //If it is not the same article being displayed and
          //it is on moneymorning.com and it is an article.
          //I also trimmed the last '/' on the URL to get desired strcmp result
          if(strcmp(trim("https://".$page['url'], "/"), trim($stage_url, "/")) != 0 &&
             preg_match("/moneymorning/", $page['url']) &&
             in_array("article", $page['aspects'])
             /*preg_match("/\/search\//", $page['url']) == 0*/)
          {
            if($first_print)//If the first link, print the beginging
            {
              echo $args['before_widget'];
              echo $args['before_title'].'Lytics Topic Trending'.$args['after_title'];
              $first_print = false;
            }

            //Debugging echos
            //echo "<p>Stage URL: ".$stage_url."</p>";
            //echo "<p>Retrieved URL: https://".$page['url']."</p>";

            //Printing title as a link
            $title = "<p><a href='https://".$page['url']."'>";
            $title .= $page['title']."</a></p>";
            echo $title;

            //Checking Lytics for primary image link
            //if(isset($page['primary_image']))
            //stage. will need to be removed for production
            $display_post_id = url_to_postid("https://stage.".$page['url']);
            if(has_post_thumbnail($display_post_id))
            {
              $image = wp_get_attachment_image_src(get_post_thumbnail_id($display_post_id), 'thumbnail' );

              $image_link = "<p><a href='https://".$page['url']."'>";
              $image_link .= "<img src='".$image[0]."'></a></p>";
              //echo "<p>Post URL: ".$page['url']."</p>";
              //echo "<p>Current Post: ".get_the_ID()." Display Post: ".$display_post_id."</p>";
              echo $image_link;

              //Creating and using a curl object to see if the primary image link
              //is broken
              /*
              $ch = curl_init($page['primary_image']);
              $status = curl_getinfo($ch);

              if($status["http_code"] == 200)
              {
                $image = "<p><a href='https://".$page['url']."'>";
                $image .= "<img src='".$page['primary_image']."'></a></p>'";
                echo $image;
              }

              curl_close($ch);
              */
            }//End of if(has_post_thumbnail($display_post_id))
            echo "<br>"; //breaking tag for space between links
          }//End of if('https://'.$page['url'] != $stage_url)
        }//End of foreach($data as $page)

        if(!$first_print)//If there was a link, print end
        {
          echo $args['after_widget'];
        }
      }//End of if($json['status'] == 200)

      else
      {
        echo "<p>could not find data!</p>";
      }
    }//End of if(!is_wp_error($response))

    else
    {
      echo "<p>Did not connect!</p>";
    }
  }//End of Widget Function

  //Defining unsused form function
  public function form($instance)
  {
    ?>
    <br />
    <p class="description"><?php echo "This widget contains no options."; ?></p>
    <?php
  }

  //Defining unsued update function
  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['title'] = (! empty($new_instance['title']) ? strip_tags($new_instance['title']) : '');

    return $instance;
  }

}//End of LyticsWidget

// Register the widget
function register_lytics_widget()
{
    register_widget('Lytics_Widget');
}

//Hooking Lytics Widget to widget intialize
add_action('widgets_init', 'register_lytics_widget');
?>
