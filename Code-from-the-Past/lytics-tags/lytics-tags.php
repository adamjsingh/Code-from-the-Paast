<?php
/*
Plugin Name: Lytics Tags
Description: Allows the creation of Lytics tags for posts
Version:     1.0.1
Author:      Money Morning Dev Team
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

//This function is used to setup and register the taxonomy of Lytics Aspects
function register_lyticsCategories_taxonomy()
{
    $category_args = array('hierarchical' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_posts',
            'edit_terms' => 'edit_posts',
            'delete_terms' => 'edit_posts',
            'assign_terms' => 'edit_posts',
        ),
        'labels' => array (
            'name' => 'Lytics Categories',
            'singular_name' => 'Lytics Category',
            'edit_item' => 'Edit Lytics Category',
            'update_item' => 'Update Lytics Category',
            'add_new_item' => 'Add New Lytics Category',
            'new_item_name' => 'New Lytics Category Name',
            'all_items' => 'All Lytics Categories',
            'popular_items' => 'Popular Lytics Categories',
            'add_or_remove_items' => 'Add or remove Lytics Categories',
        ),
        'show_ui' => true,
        'show_in_menus' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_in_quick_edit' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_admin_column' => true,
    );

    register_taxonomy('lytics_categories', array('post'), $category_args);
}

//This function is used to setup and register the taxonomy of Lytics Topics
function register_lyticsTopics_taxonomy()
{
    $category_args = array('hierarchical' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_posts',
            'edit_terms' => 'edit_posts',
            'delete_terms' => 'edit_posts',
            'assign_terms' => 'edit_posts',
        ),
        'labels' => array (
            'name' => 'Lytics Topics',
            'singular_name' => 'Lytics Topics',
            'edit_item' => 'Edit Lytics Topics',
            'update_item' => 'Update Lytics Topics',
            'add_new_item' => 'Add New Lytics Topic',
            'new_item_name' => 'New Lytics Topic Name',
            'all_items' => 'All Lytics Topics',
            'popular_items' => 'Popular Lytics Topics',
            'add_or_remove_items' => 'Add or remove Lytics Topics',
        ),
        'show_ui' => true,
        'show_in_menus' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_in_quick_edit' => true,
        'public' => true,
        'publicly_queryable' => true,
        'show_admin_column' => true,
    );

    register_taxonomy('lytics_topics', array('post'), $category_args);
}

//Getting the tag slug
function mm_get_lytics_ID($lytics)
{
    if(isset($lytics->slug)) {
        return $lytics->slug;
    }
    return false;
}

//Collecting all of the tags of the Lytics Categories taxonomy
function mm_get_lytics_categories($postID)
{
    return  wp_get_object_terms($postID, 'lytics_categories', array('fields' => 'all'));
}

//Collecting all of the tags of the Lytics Topics taxonomy
function mm_get_lytics_topics($postID)
{
    return  wp_get_object_terms($postID, 'lytics_topics', array('fields' => 'all'));
}

//Function creates and places the Lytics meta tag in the post head
function set_lytics_meta()
{
    $output = '';

    //Getting tyhe post and the aspects for the post
    $postID = get_queried_object_id();
    $post_type = (get_post_type($postID))?get_post_type($postID):"";
    $output .= '<meta name="lytics:post_type" content="'.$post_type.'"> ';
    $output .= '<meta name="lytics:author" content="'.get_the_author().'"> ';
    $lyticsCategories = mm_get_lytics_categories($postID);
    $lyticsTopics = mm_get_lytics_topics($postID);

    //If there are aspects, collect all the ones for the post and create the
    //meta tag
    if($lyticsCategories)
    {
        foreach ($lyticsCategories as $lyticsCategory)
        {
            $lyticsCategory->lyticsID = mm_get_lytics_ID($lyticsCategory);
        }

        $categoryIDs = array_map(function ($cat){return $cat->lyticsID;}, $lyticsCategories);
        //$catNames = array_map(function ($cat){return $cat->name;}, $lyticsCategories);
        //$catTagVals = array_map(function ($cat) {return $cat->tagVal;}, $lyticsCategories);
        $output .= '<meta name="lytics:category" content="'. implode(',', $categoryIDs) .'">';
        //$output .= '<meta name="paTagNames" id="paTagNames​" content="'. implode(',', $catNames) .'">';
        //$output .= '<meta name="paTagValues" id="paTagValues" content="'. implode(',', $catTagVals) .'">';
    }

    //If there are Topics, collect all the ones for the post and create the
    //meta tag
    if($lyticsTopics)
    {
        foreach ($lyticsTopics as $lyticsTopic)
        {
            $lyticsTopic->lyticsID = mm_get_lytics_ID($lyticsTopic);
        }

        $topicIDs = array_map(function ($cat){return $cat->lyticsID;}, $lyticsTopics);
        //$catNames = array_map(function ($cat){return $cat->name;}, $lyticsCategories);
        //$catTagVals = array_map(function ($cat) {return $cat->tagVal;}, $lyticsCategories);
        $output .= '<meta name="lytics:topics" content="'. implode(',', $topicIDs) .'">';
        //$output .= '<meta name="paTagNames" id="paTagNames​" content="'. implode(',', $catNames) .'">';
        //$output .= '<meta name="paTagValues" id="paTagValues" content="'. implode(',', $catTagVals) .'">';
    }

    echo $output;
}

//Register the taxonomy on site load
add_action( 'init', 'register_lyticsCategories_taxonomy');
add_action( 'init', 'register_lyticsTopics_taxonomy');

//Create meta tags in the head
add_action( 'wp_head', 'set_lytics_meta' );
?>
