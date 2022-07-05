<?php
// require_once ABSPATH . 'vendor/autoload.php';

class Mirrordata
{
    public function __construct()
    {
    }

    public function init_table_mirror()
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'mirror_history';

        $sql = "CREATE TABLE $tablename (
            id bigint NOT NULL AUTO_INCREMENT,
            last_mirror_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            table_name tinytext NOT NULL,
            post_type tinytext NULL,
            local_data_id int(11) NOT NULL,
            mirror_data_id varchar(255) NULL,
            mirror_status tinyint default 0 NOT NULL,
            post_url text NULL,
            PRIMARY KEY  (id)
        );";
        try {
            dbDelta($sql);
        } catch (\Throwable $th) {
            print_r($th);exit;
        }
    }

    public function doMirror($payload, $url, $method = 'POST')
    {
        $result = background_curl_request($url, $method, $payload);
        if($result && $result != ''){
            $this->logIt($result);
        }
    }

    private function logIt($data)
    {
        global $wpdb;
        print_r($data);exit;
    }

}
