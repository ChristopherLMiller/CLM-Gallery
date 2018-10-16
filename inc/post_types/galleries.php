<?php
/**
*
* Register Gallery Post type
*
* This function will register the gallery post type to wordpress
*
* @type function
* @date 8/5/17
* @since 0.1.0
*
* @param N/A
* @return N/A
*/
function clm_gallery_register_post_type_gallery() {
  // Register post type 'gallery'
  register_post_type('gallery', array(
    'labels'              => array(
      'name'                => __('Galleries', 'clm-gallery'),
      'singular_name'       => __('Gallery', 'clm-gallery'),
      'add_new'             => __('Add New', 'clm-gallery'),
      'add_ne_item'         => __('Add New Gallery', 'clm-gallery'),
      'edit_item'           => __('Edit Gallery', 'clm-gallery'),
      'new_item'            => __('New Gallery', 'clm-gallery'),
      'view_item'           => __('View Gallery', 'clm-gallery'),
      'search_items'        => __('Search Galleries', 'clm-gallery'),
      'not_found'           => __('No Galleries found', 'clm-gallery'),
      'not_found_in_trash'  => __('No Galleries found in Trash', 'clm-gallery'),
    ),
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'query_var'           => true,
    'menu_icon'           => 'dashicons-format-gallery',
    'rewrite'             => array(
      'slug'          => 'gallery',
      'with_front'    => false,
      'hierarchical'  => true,
    ),
    'capability_type'     => 'post',
    'hierarchical'        => true,
    'menu_position'       => null,
    'supports'            => array('title', 'editor', 'thumbnail'),
    'taxonomies'          => array('post_tag')
  ));

  register_taxonomy('gallery_category', 'gallery', array(
    'hierarchical'        => true,
    'show_ui'             => true,
    'rewrite'             => array( 'slug' => 'Category'),
    'label'               => __('Category'),
  ));
}
add_action('init', 'clm_gallery_register_post_type_gallery');

/**
 * clm_gallery_change_title
 * 
 * Changes the title on the form to reflect post type
 * 
 * @param $title - title to use
 */
function clm_gallery_change_title_field_gallery( $title ){
     $screen = get_current_screen();
 
     if  ( 'gallery' == $screen->post_type ) {
          $title = 'Enter Gallery Name';
     }
 
     return $title;
}
 
add_filter( 'enter_title_here', 'clm_gallery_change_title_field_gallery' );


/**
*
* Register Gallery Meta Boxes
*
* This function will register meta boxes for custom Gallery Post type
* @type function
* @date 8/5/17
* @since 0.1.0
*
* @param N/A
* @return N/A
*/
function clm_gallery_register_meta_boxes_gallery() {
  // Register meta boxes for 'gallery' type
  add_meta_box('gallery_visibility', __('Gallery Visibility', 'clm-gallery'), 'clm_gallery_visibility_callback', 'gallery', 'side', 'low');
  add_meta_box('gallery_users_visibility', __('Gallery Users Visibility', 'clm-gallery'), 'clm_gallery_users_visibility_callback', 'gallery', 'side', 'low');
}
add_action('add_meta_boxes', 'clm_gallery_register_meta_boxes_gallery');

/**
*
* Gallery Metabox Callback
*
* Displays the metabox
*
* @param $post
* @return void
*/
function clm_gallery_visibility_callback( $post ) {
  $values = get_post_meta($post->ID);
  $selected = isset($values['gallery_visibility'][0]) ? $values['gallery_visibility'][0] : "public";
  wp_nonce_field('my_meta_box_nonce', 'meta_box_nonce'); ?>
  <p>
    <div class="prfx-row-content">
      <label for="gallery_visibility">
        <?php _e('Visbiility', 'clm-gallery'); ?>
        <select id="gallery_visibility" name="gallery_visibility" selected="<?= $selected ?>">
          <option value="public" <?php selected($selected, "public"); ?>>Public</option>
          <option value="private" <?php selected($selected, "private"); ?>>Private</option>
          <option value="protected" <?php selected($selected, "protected"); ?>>Protected</option>
        </select>
      </label>
    </div>
  </p>
  <?php
}

/**
 * 
 * Gallery metabox users visibility callback
 * 
 * Displays the users visibility metabox
 * 
 * @param $post
 * @return void
 */
function clm_gallery_users_visibility_callback( $post ) {
  $values = get_post_meta($post->ID);
  $selected = isset($values['gallery_user_visibility']) ? maybe_unserialize($values['gallery_user_visibility'][0]) : array();
  wp_nonce_field('my_meta_box_nonce', 'meta_box_nonce'); ?>
  <p>
    <div class="prfx-row-content">
      <?php foreach (get_users() as $user) : ?>
      <label for="user_checkbox">
        <input type="checkbox" name="user_checkbox[]" id="user_checkbox" <?php echo ($selected && in_array($user->ID, $selected)) ? "checked" : ""; ?> value="<?= $user->ID; ?>" />
        <?php _e($user->display_name, 'clm-gallery'); ?>
      </label>
      <br/>
      <?php endforeach; ?>
    </div>
  </p>
  <?php
}

/**
* Gallery Metabox save
*
* Saves the current state of the meta box information
*
* @param $post_id
* @return N/A
*/
function clm_gallery_metaboxes_save($post_id) {
  // Bail if we're doing an auto save
  if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

  // if our nonce isn't there, or we can't verify it, bail
  if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;

  // if our current user can't edit this post, bail
  if( !current_user_can( 'edit_post' ) ) return;

  // verify and save our data
  update_post_meta($post_id, 'gallery_visibility', $_REQUEST['gallery_visibility']);
  update_post_meta($post_id, 'gallery_user_visibility', $_REQUEST['user_checkbox']);
}
add_action( 'save_post', 'clm_gallery_metaboxes_save' );

function clm_gallery_columns_head($defaults) {
  $defaults['gallery_visibility'] = 'Gallery Visibility';
  $defaults['image_count'] = 'Images';
  return $defaults;
}
add_filter('manage_gallery_posts_columns', 'clm_gallery_columns_head');

function clm_gallery_columns_content($column_name, $post_ID) {
  if ($column_name == "gallery_visibility") {
    echo clm_gallery::get_instance()->get_visibility($post_ID);
  } else if ($column_name == "image_count") {
    echo clm_gallery::get_instance()->get_num_images($post_ID);
  }
}
add_action('manage_gallery_posts_custom_column', 'clm_gallery_columns_content', 10, 2);

/* Output the current screen for testing purposes */
function this_screen() {
  $current_screen = get_current_screen();

  if ($current_screen->id === "gallery") {
    require_once __DIR__ . '/../WP_List_Table.php';
    $wp_list_table = new GalleryImagesList();
    $wp_list_table->prepare_items();
    $wp_list_table->display();
  }
}
add_action('current_screen', 'this_screen');