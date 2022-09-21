<?php

// require_once 'payment.class.php';
// require_once 'fn.php';


class Houzez_Property_Sync_Rest extends WP_REST_Controller
{
    public $list_meta = [
        'fave_property_price' => 'property_price',
        'fave_property_sec_price' => 'property_second_price', 
        'fave_property_size' => 'property_size', 
        'fave_property_bedrooms' => 'bedrooms_count',
        'fave_property_bathrooms' => 'bathroom_count',
        'fave_property_garage' => 'garage_count',
        'fave_property_garage_size' => 'garage_size',
        'fave_property_year' => 'build_year',
        'fave_property_id' => 'property_id',
        'fave_property_country' => 'property_country',
        'fave_property_map_address' => 'property_map_address',
        'fave_property_address' => 'property_address',
        'fave_property_location' => 'property_coordinates',
        'fave_currency_info' => 'currency',
        'fave_agents' => 'agent_id',
        'additional_features' => 'additional_features',
        'fave_property_zip' => 'property_zip',
        'fave_video_url' => 'video_url',
        'fave_featured' => 'is_featured',
        'fave_display_agent_option' => 'display_agent',
        'fave_geolocation_lat' => 'latitude',
        'fave_geolocation_lng' => 'longitude',
        'fave_virtual_tour' => 'virtual_tour',
        'fave_property_land' => 'property_land_size',
        'fave_jumlah-lantai' => 'floor_count',
        'fave_sertifikat' => 'certificate',
        '_thumbnail_id' => 'thumbnail_id',
        'fave_property_images' => 'property_images'
    ];
    public function register_routes()
    {
        $version = '1';
        $namespace = 'houzez/v' . $version;
        $base = 'properties-sync';
        register_rest_route($namespace, '/' . $base, array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'getProperties'),
            'permission_callback' => function () {
                return true;
            }
        ));
        register_rest_route($namespace, '/' . $base . '/indexed', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'isIndexed'),
            'permission_callback' => function () {
                return true;
            }
        ));
        register_rest_route($namespace, '/'. $base . '/synced', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array($this, 'set_sync_status'),
            'permission_callback' => function () {
                return true;
            }
        ));
    }

    public function isIndexed($request)
    {
        $id = $request->ids;
        $ids = "";
        if(is_array($id)){
            $ids = implode(",", $id);
        }
        try {
            global $wpdb;
            $prefix = $wpdb->prefix;
            $sql = "UPDATE ".$prefix."posts SET ".$prefix."posts.is_indexed = 1 WHERE ".$prefix."posts.ID IN ($ids)";
            $res = $wpdb->query($sql);
    
            if($res){
                return new WP_REST_Response(array('message' => 'success', 'data' => []), 200);
                exit;
            }
        } catch (\Throwable $th) {
            //throw $th;
            return new WP_REST_Response(array('message' => 'error', 'data' => []), 200);
                exit;
        }
       
    }

    public function set_sync_status($request)
    {
        $ids = $_POST['ids'];

        // if(is_string($ids)){
        //     $ids = explode(",", $ids);
        // }

        global $wpdb;
        $prefix = $wpdb->prefix;

        $sql = "UPDATE ".$prefix."posts set ".$prefix."posts.is_sync=1 WHERE ".$prefix."posts.ID IN (".$ids.")";
        try {
            if($wpdb->query($sql)){
                return new WP_REST_Response(array('message' => 'success', 'data' => array('ids' => $ids)), 200);
                exit;
            }
        } catch (\Throwable $th) {
            //throw $th;
            return new WP_REST_Response(array('message' => 'error', 'data' => []), 200);
                exit;
        }
        
    }

    public function getProperties($request)
    {
        $limit = is_numeric($_GET['per_page']) ? $_GET['per_page'] : 100;
        $offset = is_numeric($_GET['page']) ? $_GET['page'] - 1 : 0;
        $format = $_GET['format'];
        if($format == null){
            $format = 'json';
        }

        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = "SELECT
        " . $prefix . "terms.name as 'city',
        " . $prefix . "posts.*, " . $prefix . "posts.ID as post_id,
        " . $prefix . "users.user_nicename, " . $prefix . "users.user_email, " . $prefix . "users.display_name, " . $prefix . "users.ID as user_id
      from
        " . $prefix . "term_taxonomy
        INNER JOIN " . $prefix . "terms ON " . $prefix . "term_taxonomy.term_id = " . $prefix . "terms.term_id
        INNER JOIN " . $prefix . "term_relationships ON " . $prefix . "term_taxonomy.term_taxonomy_id = " . $prefix . "term_relationships.term_taxonomy_id
        INNER JOIN " . $prefix . "posts ON " . $prefix . "term_relationships.object_id = " . $prefix . "posts.ID
        INNER JOIN " . $prefix . "users ON " . $prefix . "posts.post_author = " . $prefix . "users.ID
      where
        post_type = 'property'
        AND post_status = 'publish'
        AND 
          taxonomy = 'property_city' AND is_sync = 0
          ORDER BY " . $prefix . "posts.post_date DESC
          LIMIT " . $limit . " OFFSET " . $offset;

        $res = $wpdb->get_results($sql, ARRAY_A);
        $result = [];
        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                foreach($this->list_meta as $meta => $val){
                    if($meta == 'fave_property_images'){
                        $images_id = get_post_meta($res[$i]['ID'], $meta, false);
                        $images = $this->getFile($images_id);
                        if(count($images) > 0){
                            $res[$i]['property_images'] = $images;
                        }else{
                            $res[$i]['property_images'] = null;
                        }
                    }else if($meta == '_thumbnail_id'){
                        $img_id = get_post_meta($res[$i]['ID'], $meta, false);
                        $res[$i][$val] = $this->getFile($img_id);
                    }
                    else{
                        $res[$i][$val] =  get_post_meta($res[$i]['ID'], $meta, true);
                    }
                }
                $result[] = $res[$i];
            }
        }



        return new WP_REST_Response(array('message' => 'success', 'data' => $result), 200);
        exit;
    }

    private function getFile($images)
    {
        global $wpdb;
        $result = [];
        try {
            if(is_array($images) && count($images) > 0){
                foreach($images as $img){
                    $sql = "SELECT * FROM ".$wpdb->prefix."as3cf_items WHERE source_id = ".$img;
                    $file = $wpdb->get_row($sql);
                    if($file && $file != null && property_exists($file,'provider')){
                        $prov = "";
                        switch ($file->provider) {
                            case 'do':
                                $prov = 'digitaloceanspaces.com';
                                break;
                            
                            default:
                                # code...
                                break;
                        }
                        $result[] = $file->bucket.".".$file->region.".".$prov."/".$file->path;
                    }
                }
            }else if(is_numeric($images)){
                $sql = "SELECT * FROM ".$wpdb->prefix."as3cf_items WHERE source_id = ".$images;
                    $file = $wpdb->get_row($sql);
                    if($file && $file != null && property_exists($file,'provider')){
                        $prov = "";
                        switch ($file->provider) {
                            case 'do':
                                $prov = 'digitaloceanspaces.com';
                                break;
                            
                            default:
                                # code...
                                break;
                        }
                        return $file->bucket.".".$file->region.".".$prov."/".$file->path;
                    }
            }
        } catch (\Throwable $th) {
            $result = [];
            return $result;
        }
        

        return $result;
    }


}
