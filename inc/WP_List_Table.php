<?php

/* WP_List_Table for galleries */

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class GalleryImagesList extends WP_List_table {
  public function __construct() {
    parent::__construct([
      'singular'  => __('Gallery Image', 'CLM'),
      'plural'    => __('Gallery Images', 'CLM'),
      'ajax'      => false,
    ]);
  }

  /**
   * Define the columns that are gong to be used in the table
   * @return array $columns, the array of columns to use with the table
   */
  function get_columns() {
    return $columns = array(
        'col_link_id'   => __('ID'),
        'col_link_name' => __('Name'),
    );
  }

  /**
   * Define which columns to actiavte the sorting functionaility on
   * @return array $sortable, the array of columns that can be sorted by the user
   */
  public function get_sortable_columns() {
    return $sortable = array(
      'col_link_id'   => 'link_id',
      'col_link_name' => 'link_name',
    );
  }

  /**
   * Prepare the table with different parameters, pagination, columns and table elements
   */
  function prepare_items() {
    global $wpdb, $_wp_column_headers;
    $screen = get_current_screen();

    $query = "SELECT * FROM $wpdb->links";
    $columns = $this->get_columns();
    $_wp_column_headers[$screen->id]=$columns;

    $this->items = $wpdb->get_results($query);
  }

  /**
 * Display the rows of records in the table
 * @return string, echo the markup of the rows
 */
function display_rows() {

   //Get the records registered in the prepare_items method
   $records = $this->items;

   //Get the columns registered in the get_columns and get_sortable_columns methods
   list( $columns, $hidden ) = $this->get_column_info();

   //Loop for each record
   if(!empty($records)){foreach($records as $rec){

      //Open the line
        echo '< tr id="record_'.$rec->link_id.'">';
      foreach ( $columns as $column_name => $column_display_name ) {

         //Style attributes for each col
         $class = "class='$column_name column-$column_name'";
         $style = "";
         if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
         $attributes = $class . $style;

         //edit link
         $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->link_id;

         //Display the cell
         switch ( $column_name ) {
            case "col_link_id":  echo '< td '.$attributes.'>'.stripslashes($rec->link_id).'< /td>';   break;
            case "col_link_name": echo '< td '.$attributes.'>'.stripslashes($rec->link_name).'< /td>'; break;
            case "col_link_url": echo '< td '.$attributes.'>'.stripslashes($rec->link_url).'< /td>'; break;
            case "col_link_description": echo '< td '.$attributes.'>'.$rec->link_description.'< /td>'; break;
            case "col_link_visible": echo '< td '.$attributes.'>'.$rec->link_visible.'< /td>'; break;
         }
      }

      //Close the line
      echo'< /tr>';
   }}
}
}
