<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

/* Ratings PHP Shortcodes.
--------------------------------------------------------------------------------------------*/
function rw_get_post_rating($postID = false, $class = 'blog-post', $schema = false)
{
    $postID = (false === $postID) ? get_the_ID() : $postID;
    return ratingwidget()->EmbedRatingByPost(get_post($postID), $class, $schema);
}

function rw_the_post_rating($postID = false, $class = 'blog-post', $schema = false)
{
    echo rw_get_post_rating($postID, $class, $schema);
}

/**
* Return rating metadata.
* 
* @param mixed $postID Post id. Defaults to current loop post id.
* @param mixed $accuracy The number of digits after floating point.
*/
function rw_get_post_rating_data($postID = false, $accuracy = false)
{
    $rwp = ratingwidget();
    
    $postID = (false === $postID) ? get_the_ID() : $postID;
    
    return $rwp->GetRatingDataByRatingID($rwp->_getPostRatingGuid($postID), $accuracy);
}

function rw_get_user_rating($userID = false)
{
    $userID = (false === $userID) ? get_current_user_id() : $userID;
    return ratingwidget()->EmbedRatingByUser(get_user_by('id', $userID));
}

function rw_the_user_rating($userID = false)
{
    echo rw_get_user_rating($userID);
}

/* Post inline Shortcodes.
--------------------------------------------------------------------------------------------*/
function rw_the_post_shortcode($atts)
{
    extract(shortcode_atts(array(
          'post_id' => 1,
          'type' => 'blog-post',
          'add_schema' => false,
       ), $atts));
    
    return rw_get_post_rating($post_id, $type, $add_schema);
}  
?>
