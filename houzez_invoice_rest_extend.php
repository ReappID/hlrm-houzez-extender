<?php
add_filter( 'register_post_type_args', 'invoice_rest', 10, 2 );
function invoice_rest( $args, $post_type ) {
 
    if ( 'houzez_invoice' === $post_type ) {
        $args['show_in_rest'] = true;
 
        // Optionally customize the rest_base or rest_controller_class
        $args['rest_base']             = 'invoices';
        
        
    //     add_action('rest_api_init', function () {           
    //         $rest_class = new Houzez_Invoice_Rest();
    //         $rest_class->register_routes();
    //    });
       
    }
 
    return $args;
}