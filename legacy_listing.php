<?php
/*
Plugin Name: Hunsting Listing
Description: Leasing Hunting Listing
Author: Alexander Trejo
Version: 1.0
*/

class lgcyListing{
  function __construct(){
    add_shortcode('lgcy-listing', array($this, 'output'));
  }

  function output(){
    global $wp_query, $wpdb;

    wp_enqueue_script('jquery-ui-slider');

    //Get Max and Min price
    $qry = "SELECT MIN(CONVERT(pm.meta_value, UNSIGNED INTEGER)) min_price, MAX(CONVERT(pm.meta_value, UNSIGNED INTEGER)) max_price
            FROM $wpdb->posts p, $wpdb->postmeta pm
            WHERE p.post_type = 'hunting_lease'
            AND p.post_status='publish'
            AND p.ID = pm.post_id
            AND pm.meta_key = '_hunting_lease_price'
            AND pm.meta_value <> ''";
    $row = $wpdb->get_row($qry);
    $price_range = array('min' => $this->min_range($row->min_price),
                         'max' => $this->max_range($row->max_price),
                         'vmin' => $_GET['price_min'] ? $_GET['price_min'] : $this->min_range($row->min_price),
                         'vmax' => $_GET['price_max'] ? $_GET['price_max'] : $this->max_range($row->max_price),
                   );

    //Get Max and Min acre (size)
    $qry = "SELECT MIN(CONVERT(pm.meta_value, UNSIGNED INTEGER)) min_acre, MAX(CONVERT(pm.meta_value, UNSIGNED INTEGER)) max_acre
            FROM $wpdb->posts p, $wpdb->postmeta pm
            WHERE p.post_type = 'hunting_lease'
            AND p.post_status='publish'
            AND p.ID = pm.post_id
            AND pm.meta_key = '_hunting_lease_size'
            AND pm.meta_value <> ''";
    $row = $wpdb->get_row($qry);
    $acre_range = array('min' => $this->min_range($row->min_acre),
                        'max' => $this->max_range($row->max_acre),
                        'vmin' => $_GET['acre_min'] ? $_GET['acre_min'] : $this->min_range($row->min_acre),
                        'vmax' => $_GET['acre_max'] ? $_GET['acre_max'] : $this->max_range($row->max_acre),
                  );

    //Get States
    $st = lgcyStates::get_states();

    //Get Counties options
    $state = esc_html($_GET['state']);
    if($state){
      $counties = lgcyStates::get_counties($state);
      $counties = array_merge(array('' => 'Any'), $counties);
    }else{
      $counties = array('' => 'Any');
    }

    //Get Property types
    $terms = get_terms( array(
        'taxonomy' => 'property_type',
        'hide_empty' => false,
    ));
    $property_type = array();
    foreach($terms as $t) $property_type[] = $t->name;

    //Default WP_QUERY arguments
    $args = array('post_type'=>'hunting_lease', 'post_status'=>'publish', 'orderby' => 'date', 'order'   => 'DESC');

    //Filters
    if($_GET['price_min'] && $_GET['price_max']){
      $args['meta_query'] = array(
        array(
          'key'     => '_hunting_lease_price',
          'value'   => $_GET['price_min'],
          'type'    => 'numeric',
          'compare' => '>=',
        ),
        array(
          'key'     => '_hunting_lease_price',
          'value'   => $_GET['price_max'],
          'type'    => 'numeric',
          'compare' => '<=',
        ),
      );
    }

    if($_GET['availability']){
      $args['meta_query'][] = array(
              'key'     => '_hunting_lease_availability',
              'value'   => $_GET['availability'],
            );
    }
    if($_GET['state']){
      $args['meta_query'][] = array(
              'key'     => '_hunting_lease_state',
              'value'   => $_GET['state'],
            );
    }
    if($_GET['county'] && $_GET['county'] != 'Any'){
      $args['meta_query'][] = array(
              'key'     => '_hunting_lease_county',
              'value'   => $_GET['county'],
            );
    }
    if($_GET['type']){
      $args['tax_query'][] = array(
              'taxonomy' => 'property_type',
              'field'    => 'name',
              'terms'    => $_GET['type'],
            );
    }
    if($_GET['keyword']){
        $args['s'] = $_GET['keyword'];
    }

    //add paged value to query
    $args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
    $leases = new WP_Query($args);

    //$pagination = get_posts_nav_link();
    $big = 999999999; // need an unlikely integer

    $pagination = paginate_links( array(
     'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
     'format' => '?paged=%#%',
     'current' => max( 1, get_query_var('paged') ),
     'total' => $leases->max_num_pages
    ) );

    // Pagination fix
    $temp_query = $wp_query;
    $wp_query   = NULL;
    $wp_query   = $leases;

    $lease = array();
    if($leases->have_posts()){
        while($leases->have_posts()){
          $leases->the_post();
          $id = get_the_ID();
          $meta = get_post_meta($id);
          $lease[] = array( 'id' => $id,
                            'title' => get_the_title(),
                            'image' => has_post_thumbnail() ? get_the_post_thumbnail_url('','lease-thumbnail') : plugins_url( 'images/placeholder.jpg', __FILE__ ),
                            'excerpt' => wp_trim_words( get_the_excerpt(), 20, '...' ),
                            'link' => get_the_permalink($id),
                            'city' => $meta['_hunting_lease_city'][0],
                            'state' =>$meta['_hunting_lease_state'][0],
                            'size' => $meta['_hunting_lease_size'][0],
                            'size_type' => $meta['_hunting_lease_size_type'][0],
                            'county' => $meta['_hunting_lease_county'][0],
                            'price' => $meta['_hunting_lease_price'][0],
                            'availability' => $meta['_hunting_lease_availability'][0],
                            'lat' => $meta['_hunting_lease_lat'][0],
                            'lng' => $meta['_hunting_lease_lng'][0],
          );
        }
     }

     // Reset main query object
     $wp_query = NULL;
     $wp_query = $temp_query;

     return mvcView::render('lease_listing.php', array('properties' => $lease, 'center' => $this->center_coord($lease), 'pagination' => $pagination, 'price_range' => $price_range, 'acre_range' => $acre_range, 'states' => $st, 'counties' => $counties, 'loading' => lgcyStates::get_loading() ));
  }

  //Find center of group of coordinates
  function center_coord($data){
    if (!sizeof($data)) return array('lat' => 37.590270, 'lng' => -97.236156); //No Coordinates, return USA center coordinates

    $num_coords = count($data);

    $X = 0.0;
    $Y = 0.0;
    $Z = 0.0;


    $found = false;
    foreach ($data as $coord){
      if(isset($coord['lat']) && isset($coord['lng'])){
        $lat = $coord['lat'] * pi() / 180;
        $lon = $coord['lng'] * pi() / 180;

        $a = cos($lat) * cos($lon);
        $b = cos($lat) * sin($lon);
        $c = sin($lat);

        $X += $a;
        $Y += $b;
        $Z += $c;

        $found = true;
      }
    }

    if (!$found) return array('lat' => 37.590270, 'lng' => -97.236156); //No Coordinates, return USA center coordinates

    $X /= $num_coords;
    $Y /= $num_coords;
    $Z /= $num_coords;

    $lon = atan2($Y, $X);
    $hyp = sqrt($X * $X + $Y * $Y);
    $lat = atan2($Z, $hyp);

    return array('lat' => $lat * 180 / pi(), 'lng' => $lon * 180 / pi());
  }

  //Round min to 100 multiples (100, 200, 300, 400, etc)
  function min_range($num){
    $whole = floor($num /100);
    $fraction = (($num / 100) - $whole) * 100;

    if($fraction == 0){
      return $num;
    }else{
      return $whole * 100;
    }
  }

  //Round max to 100 multiples (100, 200, 300, 400, etc)
  function max_range($num){
    $whole = floor($num /100);
    $fraction = (($num / 100) - $whole) * 100;

    if($fraction == 0){
      return $num;
    }else{
      return ($whole * 100) + 100;
    }
  }
}

$lgcyListing = new lgcyListing();
