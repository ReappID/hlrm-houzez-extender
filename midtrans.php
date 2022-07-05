<?php
function add_midtrans()
{   
    if(MIDTRANS_CLIENT_KEY != ''){
        $snapUrl = MIDTRANS_IS_PRODUCTION == 1 ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";
        echo '<script type="text/javascript" src="'.$snapUrl.'" data-client-key="' . MIDTRANS_CLIENT_KEY . '"></script>';
    }
    
}
add_action('wp_enqueue_scripts', 'add_midtrans');