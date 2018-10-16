<?php
/**
 * Register Gallery Images Post Type
 * 
 * Function will register the gallery images post type to wordpress
 * 
 * @type function
 * @date 10/30/2017
 * @since 0.1.0
 * 
 */
function clm_gallery_register_post_type_gallery_images() {
  //register post type 'images'
  register_post_type('gallery_images', array(
    'labels'              => array(
      'name'                => __('Gallery Images', 'clm-gallery'),
      'singular_name'       => __('Gallery Image', 'clm-gallery'),
      'add_new'             => __('Add New', 'clm-gallery'),
      'add_ne_item'         => __('Add New Gallery Image', 'clm-gallery'),
      'edit_item'           => __('Edit Gallery Image', 'clm-gallery'),
      'new_item'            => __('New Gallery Image', 'clm-gallery'),
      'view_item'           => __('View Gallery Image', 'clm-gallery'),
      'search_items'        => __('Search Gallery Images', 'clm-gallery'),
      'not_found'           => __('No Gallery Images found', 'clm-gallery'),
      'not_found_in_trash'  => __('No Gallery Images found in Trash', 'clm-gallery'),
    ),
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'query_var'           => true,
    'menu_icon'           => 'dashicons-format-image',
    'rewrite'             => array(
      'slug'          => 'gallery-image',
      'with_front'    => false,
      'hierarchical'  => true,
    ),
    'capability_type'     => 'post',
    'hierarchical'        => true,
    'menu_position'       => null,
    'supports'            => array('title', 'editor', 'thumbnail'),
    'taxonomies' => array('post_tag')
  ));
}
add_action('init', 'clm_gallery_register_post_type_gallery_images');

function clm_gallery_register_meta_boxes_gallery_images() {
  add_meta_box('galleries', __('Galleries', 'clm-gallery'), 'clm_gallery_list_callback', 'gallery_images', 'side', 'low');
}
add_action('add_meta_boxes', 'clm_gallery_register_meta_boxes_gallery_images');

function clm_gallery_list_callback( $post ) {
  $values = get_post_meta($post->ID);
  $selected = isset($values['galleries']) ? maybe_unserialize($values['galleries'][0]) : array();
  wp_nonce_field('gallery_image_list_nonce', 'gallery_image_list_nonce_field'); ?>
  <p>
    <div class="prfx-row-content">
      <?php foreach (clm_gallery::get_instance()->get_galleries() as $gallery) : ?>
      <label for="user_checkbox">
        <input type="checkbox" name="gallery_checkbox[]" id="gallery_checkbox" value="<?= $gallery->ID; ?>" <?php echo in_array($gallery->ID, $selected) ? "checked" : ""; ?> />
        <?php _e($gallery->post_title, 'clm-gallery'); ?>
      </label>
      <br/>
      <?php endforeach; ?>
    </div>
  </p>
  <?php
}

function clm_gallery_images_metaboxes_save( $post_id ) {
  // Bail if doing autosave
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

  // bail if nonce isn't there or can't be verified
  if ( !isset($_POST['gallery_image_list_nonce_field']) || !wp_verify_nonce($_POST['gallery_image_list_nonce_field'], 'gallery_image_list_nonce') ) return;

  // bail if user can't edit post
  if (!current_user_can('edit_post')) return;

  if (isset($_POST['gallery_checkbox'])) {
    if (is_array($_POST['gallery_checkbox'])) {
      $result = [];
      foreach ($_POST['gallery_checkbox'] as $item) {
        $result[] = intval($item);
      }
      update_post_meta($post_id, 'galleries', $result);
    }
  }
}
add_action('save_post', 'clm_gallery_images_metaboxes_save');


function clm_gallery_images_columns_head($defaults) {
  $defaults['image_galleries'] = 'Galleries';
  return $defaults;
}

function clm_gallery_images_columns_content($column_name, $post_ID) {
  if ($column_name == "image_galleries") {
    echo clm_gallery::get_instance()->get_galleries_in($post_ID, true);
  }
}

add_filter('manage_gallery_images_posts_columns', 'clm_gallery_images_columns_head');
add_action('manage_gallery_images_posts_custom_column', 'clm_gallery_images_columns_content', 10, 2);