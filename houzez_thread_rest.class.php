<?php

// require_once 'payment.class.php';
// require_once 'fn.php';


class Houzez_Thread_Rest extends WP_REST_Controller
{
    public function register_routes()
    {
        $version = '1';
        $namespace = 'houzez/v' . $version;
        $base = 'thread';
        register_rest_route($namespace, '/' . $base . '/create', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'createThread'),
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ));
    }

    public function createThread($request)
    {
        global $current_user;

        $current_user = wp_get_current_user();

        // echo json_encode(
        //     array(
        //         'success' => true,
        //         'msg' => esc_html__("Message sent successfullyss!", 'houzez'),
        //         'whatsapp' => $current_user
        //     )
        // );wp_die();

        if (!is_user_logged_in()) {
            return new WP_Error('cant-create', __('message', 'text-domain'), array('status' => 401));
        }

        if (isset($_POST['property_id']) && !empty($_POST['property_id']) && isset($_POST['message']) && !empty($_POST['message'])) {

            $message = $_POST['message'];
            $thread_id = apply_filters('houzez_start_thread', $_POST);
            $message_id = apply_filters('houzez_thread_message', $thread_id, $message, array());


            if ($message_id) {
                do_action('houzez_property_agent_contact', $_POST);
                $agent_phone = get_user_meta($_POST['agent_id'], 'fave_author_whatsapp', true);
                if (!$agent_phone || $agent_phone == '') {
                    $agent_phone = get_user_meta($_POST['agent_id'], 'fave_author_mobile', true);
                }
                if (str_starts_with($agent_phone, '0')) {
                    $agent_phone = ltrim($agent_phone, '0');
                    $agent_phone = "62" . $agent_phone;
                } else if (str_starts_with($agent_phone, '+62')) {
                    $agent_phone = ltrim($agent_phone, '+');
                    // $wanumber = "62". $wanumber;
                } else if (str_starts_with($agent_phone, '620')) {
                    $agent_phone = ltrim($agent_phone, '620');
                    $agent_phone = "62" . $agent_phone;
                }
                $link_wa = "";
                if ($agent_phone != '') {
                    $link_wa = "https://api.whatsapp.com/send?phone=" . $agent_phone . "&text=" . urlencode($message);
                }
                echo json_encode(
                    array(
                        'success' => true,
                        'msg' => esc_html__("Message sent successfullyss!", 'houzez'),
                        'whatsapp' => $link_wa
                    )
                );

                wp_die();
            }
        }

        echo json_encode(
            array(
                'success' => false,
                'msg' => esc_html__("Some errors occurred! Please try again.", 'houzez')
            )
        );

        wp_die();
    }
}
