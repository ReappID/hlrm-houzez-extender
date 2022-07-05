<?php
// require_once('spaces.php');

require_once ABSPATH . 'vendor/autoload.php';
// require_once ABSPATH . 'vendor/aws/aws-sdk-php/src/S3/S3Client.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\Credentials;
// global $client;


add_action('after_setup_theme', 'extend_houzez_upload_images', 0);

function extend_houzez_upload_images()
{
    remove_action('wp_ajax_houzez_property_img_upload', 'houzez_property_img_upload');
    remove_action('wp_ajax_nopriv_houzez_property_img_upload', 'houzez_property_img_upload');

    remove_action('wp_ajax_houzez_remove_property_thumbnail', 'houzez_remove_property_thumbnail');
    remove_action('wp_ajax_nopriv_houzez_remove_property_thumbnail', 'houzez_remove_property_thumbnail');

    add_action('wp_ajax_houzez_property_img_upload', 'hlrm_houzez_property_img_upload');    // only for logged in user
    add_action('wp_ajax_nopriv_houzez_property_img_upload', 'hlrm_houzez_property_img_upload');

    add_action('wp_ajax_houzez_remove_property_thumbnail', 'hlrm_houzez_remove_property_thumbnail');
    add_action('wp_ajax_nopriv_houzez_remove_property_thumbnail', 'hlrm_houzez_remove_property_thumbnail');
}

if (!function_exists('hlrm_houzez_property_img_upload')) {
    function hlrm_houzez_property_img_upload()
    {
        // $credentials = new Aws\Credentials\Credentials('UOEUETHNMIW2A5AHWPER', 'INH5QN/QKL1wmrbp8nYnaS39zkxEL9MYfOsFIbtq0/g');
        $secret = 'INH5QN/QKL1wmrbp8nYnaS39zkxEL9MYfOsFIbtq0/g';
        $key = 'UOEUETHNMIW2A5AHWPER';
        $policy = base64_encode(json_encode(array(
            // ISO 8601 - date('c'); generates uncompatible date, so better do it manually
            'expiration' => date('Y-m-d\TH:i:s.000\Z', strtotime('+1 day')), 
            'conditions' => array(
              array('bucket' => 'halorumah'),
              array('acl' => 'public-read'),
              array('starts-with', '$key', ''),
              array('starts-with', '$Content-Type', ''), // accept all files
              // Plupload internally adds name field, so we need to mention it here
              array('starts-with', '$name', ''), 
              // One more field to take into account: Filename - gets silently sent by FileReference.upload() in Flash
              // http://docs.amazonwebservices.com/AmazonS3/latest/dev/HTTPPOSTFlash.html
              array('starts-with', '$Filename', ''),
            )
          )));
          $signature = base64_encode(hash_hmac('sha1', $policy, $secret, true));

        $client = new Aws\S3\S3Client([
            'endpoint' => 'https://halorumah.sgp1.digitaloceanspaces.com',
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => [
                'key'    => $key,
                'secret' => $secret
            ],
            'validate' => true,
            
        ]);

        $verify_nonce = $_REQUEST['verify_nonce'];
        if (!wp_verify_nonce($verify_nonce, 'verify_gallery_nonce')) {
            echo json_encode(array('success' => false, 'reason' => 'Invalid nonce!'));
            die;
        }

        $submitted_file = $_FILES['property_upload_file'];
        if(!isset($submitted_file['tmp_name'])){
            $ajax_response = array('success' => false, 'reason' => 'Image upload failed!');
            echo json_encode($ajax_response);
            die;
        }
        // print_r($submitted_file);exit;
        try {
            $uploaded_image = $client->putObject([
                'Bucket' => 'wp-content/uploads/sample/',
                'Key'    => time(),
                'SourceFile'   => $submitted_file['tmp_name'],
                'ContentType' => $submitted_file['type'],
                'ContentLength' => $submitted_file['length']
            ]);
            print_r($uploaded_image);
            exit;
        } catch (Aws\S3\Exception\S3Exception $e) {
            echo $e->getMessage();
        }

        if (isset($uploaded_image['ObjectURL'])) {
            $file_name          =   basename($submitted_file['name']);
            $file_type          =   wp_check_filetype($uploaded_image['ObjectURL']);

            // Prepare an array of post data for the attachment.
            $attachment_details = array(
                'guid'           => $uploaded_image['ObjectURL'],
                'post_mime_type' => $file_type['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_name)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id      =   wp_insert_attachment($attachment_details, $uploaded_image['file']);
            $attach_data    =   wp_generate_attachment_metadata($attach_id, $uploaded_image['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $thumbnail_url = wp_get_attachment_image_src($attach_id, 'houzez-item-image-1');

            $feat_image_url = wp_get_attachment_url($attach_id);

            $ajax_response = array(
                'success'   => true,
                'url' => $thumbnail_url[0],
                'attachment_id'    => $attach_id,
                'full_image'    => $feat_image_url
            );

            echo json_encode($ajax_response);
            die;
        } else {
            $ajax_response = array('success' => false, 'reason' => 'Image upload failed!');
            echo json_encode($ajax_response);
            die;
        }
    }
}


if (!function_exists('hlrm_houzez_remove_property_thumbnail')) {
    function hlrm_houzez_remove_property_thumbnail()
    {

        $nonce = $_POST['removeNonce'];
        $remove_attachment = false;
        if (!wp_verify_nonce($nonce, 'verify_gallery_nonce')) {

            echo json_encode(array(
                'remove_attachment' => false,
                'reason' => esc_html__('Invalid Nonce', 'houzez')
            ));
            wp_die();
        }

        if (isset($_POST['thumb_id']) && isset($_POST['prop_id'])) {
            $thumb_id = intval($_POST['thumb_id']);
            $prop_id = intval($_POST['prop_id']);

            $property_status = get_post_status($prop_id);

            if ($thumb_id > 0 && $prop_id > 0 && $property_status != "draft") {
                delete_post_meta($prop_id, 'fave_property_images', $thumb_id);
                $remove_attachment = wp_delete_attachment($thumb_id);
            } elseif ($thumb_id > 0 && $prop_id > 0 && $property_status == "draft") {
                delete_post_meta($prop_id, 'fave_property_images', $thumb_id);
                $remove_attachment = true;
            } elseif ($thumb_id > 0) {
                if (false == wp_delete_attachment($thumb_id)) {
                    $remove_attachment = false;
                } else {
                    $remove_attachment = true;
                }
            }
        }

        echo json_encode(array(
            'remove_attachment' => $remove_attachment,
        ));
        wp_die();
    }
}
