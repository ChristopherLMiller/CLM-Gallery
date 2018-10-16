<?php
/**
 * Plugin Name: CLM.me Gallery
 * Plugin URI: 
 * Description: Simple to use gallery
 * Version 0.1.0
 * Author: Chris Miller
 * Author URI: http://christopherleemiller.me/
 * Copyright: Chris Miller
 * Text Domain: clm_gallery
 * Domain path: /lang
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('clm_gallery')) :
class clm_gallery {

  private static $instance;

  /**
  *
  * __construct
  *
  * Class Constructor
  */
  private function __construct() {
    // get custom post types
    require_once 'inc/post_types/galleries.php';
    require_once 'inc/post_types/gallery_images.php';
}

  /**
   * get_instance
   * 
   * Return the singleton instance
   * 
   * @return object
   */
  public static function get_instance() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * get_galleries
   * 
   * Returns array of all galleries
   * 
   * @return array - array of galleries
   */
  public function get_galleries() {
    $args = array(
      'post_type' => 'gallery',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    );

    $galleries_filtered = [];
    foreach (get_posts($args) as $gallery) {
      // get the post meta to check for visibility
      $gallery_meta = get_post_meta($gallery->ID, 'gallery_visibility', true);
      $gallery_user_visibility = get_post_meta($gallery->ID, 'gallery_user_visibility', true);

      if ($gallery_meta == "public") {
        // gallery totally public no need to check further
        $galleries_filtered[] = $gallery;
      } elseif ($gallery_meta == "protected") {
        if (in_array('administrator', wp_get_current_user()->roles)) {
          // admin auto granted all
          $galleries_filtered[] = $gallery;
        } elseif (in_array(wp_get_current_user()->ID, $gallery_user_visibility)) {
          // person has been given access to this gallery
          $galleries_filtered[] = $gallery;
        }
      } elseif ($gallery_meta == "private") {
        // allow admin users
        if (in_array('administrator', wp_get_current_user()->roles)) {
          $galleries_filtered[] = $gallery;
        }
      }
    }

    return $galleries_filtered;
  }

  /**
   * get_galleries_in_category
   * 
   * Return all galleries in specified category
   * 
   * @param int - category
   * @return array All matching galleries
   */
  public function get_galleries_in_category($category) {
    $galleries_raw = $this->galleries();

    $galleries_filtered = [];
    foreach($galleries_raw as $gallery) {
      $terms = get_the_terms($gallery->ID, 'gallery_category');
      if ($terms) {
        foreach( $terms as $term) {
          if (strtolower($term->name) == strtolower($category)) {
            $galleries_filtered[] = $gallery;
          }
        }
      }
    }

    return $galleries_filtered;
  }

  /**
   * get_images
   * 
   * Returns array of all images linked to the gallery
   * 
   * @param ID - id of the gallery
   * @return array - array of all images
   */
  public function get_images( $gallery_ID ) {
    // first iteration, grab all posts of type gallery_images that are published
    $args = array(
      'post_type'       => 'gallery_images',
      'posts_per_page'  => -1,
      'post_status'     => 'publish',
      'order'           => 'ASC'
    );
    $posts = get_posts($args);

    // step 2 - iterate all posts extracting out ones that belong to ID provided
    $posts_filtered = array();
    foreach ($posts as $post) {
      $post_meta = get_post_meta($post->ID, 'galleries');
      if (isset($post_meta[0]) && in_array($gallery_ID, $post_meta[0])) {
        $posts_filtered[] = $post;
      }
    }
    return $posts_filtered;
  }

  /**
  * get_num_images
  * 
  * Returns count of the number of images in the gallery
  * 
  * @param ID - id of the gallery
  * @return int - number of images
  */
  public function get_num_images( $gallery_ID ) {
    return count($this->get_images($gallery_ID));
  }

  /**
   * get_random_gallery_image
   * 
   * Returns a random image from the gallery
   * 
   * @param ID - id of the gallery
   * @return url - full url of the image
   */
  public function get_random_gallery_image( $gallery_ID ) {
    $images = $this->get_images($gallery_ID);

    // check if there are any posts remaining, return one random one if so, if not return the placeholder
    if (count($images)) {
      $rand = rand(0, count($images) - 1);
      return get_the_post_thumbnail_url($images[$rand]->ID, 'large');
    } else {
      return get_site_url() . '/wp-content/plugins/clm-gallery/imgs/placeholder.png';
    }
  }

  /**
  * 
  * get_featured_gallery_image
  * 
  * Return the featured image of the gallery
  * 
  * @param int ID of the gallery
  * @param bool return random image if featured not set
  * @return url full path of the image
  */
  public function get_featured_gallery_image( $gallery_ID, $random = true) {
    if ( get_the_post_thumbnail_url( $gallery_ID, 'large' ) ) {
      return get_the_post_thumbnail_url( $gallery_ID, 'large' );
    } else {
      return $this->get_random_gallery_image($gallery_ID);
    }
  }

  /**
   * get_visibility
   * 
   * Return the visibility of the gallery
   * 
   * @param ID - ID of the gallery
   * @return string - visibility
   */
  public function get_visibility( $gallery_ID) {
    return get_post_meta($gallery_ID, 'gallery_visibility', true);
  }

  /**
   * get_user_visibility
   * 
   * Return list of users able to view the gallery
   * 
   * @param ID - ID of the gallery
   * @return array - users
   */
  public function get_user_visibility( $gallery_ID ) {
    return get_post_meta($gallery_ID, 'gallery_user_visibility', true);
  }

  /**
   * user_can_view_gallery
   * 
   * Current user is able to view the gallery provided, returns true if viewable
   * 
   * @param ID - ID of the gallery
   * @return bool - able to view
   */
  public function user_can_view_gallery( $gallery_ID ) {
    $visibility = $this->get_visibility($gallery_ID);

    if ($visibility == "public") {
      return true;
    } else if ($visibility == "protected") {
      $gallery_user_visibility = get_post_meta($gallery_ID, 'gallery_user_visibility', true);
      if ( in_array('administrator', wp_get_current_user()->roles) || in_array(wp_get_current_user()->ID, $gallery_user_visibility) ) {
          // admin auto granted all
        return true;
      }
    } else if ($visibility == "private") {
      // allow admin users
        if (in_array('administrator', wp_get_current_user()->roles)) {
          return true;
        }
    }

    // not able to view
    return false;
  }

  /**
   * get_gallery_name
   * 
   * Returns the name of the gallery
   * 
   * @param ID - ID of the gallery
   * @return string - name of the gallery
   */
  public function get_gallery_name( $gallery_ID) {
    return get_post($gallery_ID)->post_title;
  }

  /**
   * get_gallery_guid
   * 
   * Return the guid of the gallery
   * 
   * @param int ID of the gallery
   * @return string guid
   */
  public function get_gallery_guid( $gallery_ID ) {
    return get_post($gallery_ID)->guid;
  }

  /**
   * get_galleries_in
   * 
   * Return array of galleries image belongs to
   * 
   * @param int ID - ID of the image
   * @param bool Whether to link to gallery
   * @return array - array of galleries
   */
  public function get_galleries_in( $image_ID, $link = false) {
    $galleries = get_post_meta($image_ID, 'galleries')[0];
    
    // iterate these galleries, and fetch the name of the gallery
    $named = [];
    foreach ($galleries as $gallery) {
      if ($link) {
        $named[] = '<a href="http://christopherleemiller.me/wp-admin/post.php?post=' . $gallery . '&action=edit">' . $this->get_gallery_name($gallery) .'</a>';
      } else {
        $named[] = $this->get_gallery_name($gallery);
      }
    }
    return implode(', ', $named);
  }

  /**
   * get_gallery_categories
   * 
   * Return array of all the gallery categories
   * 
   * @return array - Array of categories
   */
  public function get_all_gallery_categories() {
    return get_terms(['taxonomy' => 'gallery_category', 'hide_empty' => false]);
  }

  /**
   * get_gallery_categories
   * 
   * Return array of categories for a gallery
   * 
   * @param int ID of the gallery
   * @param bool Stringify the list
   * @return array Array of categories
   */
  public function get_gallery_categories( $gallery_ID, $stringify = true) {
    $categories = get_the_terms( $gallery_ID, 'gallery_category');

    // loop each of the terms and extract the name out
    $category_names = [];
    foreach ($categories as $category) {
      array_push($category_names, $category->name);
    }

    if ($stringify) {
      return implode(" ", $category_names);
    } else {
      return $category_names;
    }
  }
}
endif;

add_action('plugins_loaded', function() {
  clm_gallery::get_instance();
});