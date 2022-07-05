<?php
/*** Sort and Filter Users ***/
add_action('restrict_manage_users', 'filter_by_source');

function filter_by_source($which)
{
 // template for filtering
 $st = '<select name="source_%s" style="float:none;margin-left:10px;">
    <option value="">%s</option>%s</select>';

 // generate options
 $options = '<option value="landing">Landing Page</option>
    <option value="">Sales</option>';
 
 // combine template and options
 $select = sprintf( $st, $which, __( 'Source...' ), $options );

 // output <select> and submit button
 echo $select;
 submit_button(__( 'Filter' ), null, $which, false);
}

add_filter('pre_get_users', 'filter_users_by_source_section');

function filter_users_by_source_section($query)
{
 global $pagenow;
 if (is_admin() && 'users.php' == $pagenow) {
  // figure out which button was clicked. The $which in filter_by_job_role()
  $top = @$_GET['source_top'] ? @$_GET['source_top'] : null;
  $bottom = @$_GET['source_bottom'] ? @$_GET['source_bottom'] : null;
  if (!empty($top) OR !empty($bottom))
  {
   $section = !empty($top) ? $top : $bottom;
   
   // change the meta query based on which option was chosen
   $meta_query = array (array (
      'key' => 'source',
      'value' => $section,
      'compare' => 'LIKE'
   ));
   $query->set('meta_query', $meta_query);
  }
 }
}