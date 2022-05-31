<?php
/**
 * Extend kemampuan rest api untuk theme houzez post type houzez_invoces
 */
add_filter('register_post_type_args', 'add_meta_to_houzez_packages', 10, 2);

function add_meta_to_houzez_packages($args, $post_type){
    /**
     * Meta fields
     * key => dipakai untuk field endpoint POST/UPDATE/DELETE
     * type => untuk menentukan tipe field saat posting data
     * out => dipake untuk field endpoint GET
     */
    $meta_fields = [
        array('key' => 'fave_billing_time_unit', 'type' => 'string', 'out' => 'billing_unit'),
        array('key' => 'fave_unlimited_listings', 'type' => 'string', 'out' => 'unlimited_listings'),
        array('key' => 'fave_package_price', 'type' => 'integer', 'out' => 'package_price'),
        array('key' => 'fave_package_stripe_id', 'type' => 'string', 'out' => 'package_stripe_id'),
        array('key' => 'fave_package_visible', 'type' => 'string', 'out' => 'package_visible'),
        array('key' => 'fave_unlimited_images', 'type' => 'string', 'out' => 'unlimited_images'),
        array('key' => 'fave_package_popular', 'type' => 'string', 'out' => 'package_popular'),
        array('key' => 'fave_billing_unit', 'type' => 'string', 'out' => 'billing_time_unit'),
        array('key' => 'fave_package_listings', 'type' => 'string', 'out' => 'package_listings'),
        array('key' => 'fave_package_featured_listings', 'type' => 'string', 'out' => 'package_featured_listings')
    ];
    foreach ($meta_fields as $f) {
        $field = $f['key'];
        $fd = isset($f['out']) ? $f['out'] : $f['key'];
        $tp = $f['type'];
        if ( 'houzez_packages' === $post_type ) {
            register_rest_field( $post_type, $field, array(
                'update_callback' => function ($value, $object) use ($field){
                    update_post_meta( $object->ID, $field, $value );
                },
                'schema'          => array(
                    'type'        => $tp,
                    'arg_options' => array(
                        'sanitize_callback' => function ( $value ) {
                            // Make the value safe for storage.
                            return sanitize_text_field( $value );
                        }
                    ),
                ),
            ));

            register_rest_field( $post_type, $fd, array(
                'get_callback' => function ( $data ) use ($field) {
                    return get_post_meta( $data['id'], $field, true );
                }
            ));
        }
    }
   
   return $args;
}