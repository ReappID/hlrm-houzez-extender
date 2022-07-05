<?php
// require_once ABSPATH . 'vendor/autoload.php';

class Formz
{
    public function __construct()
    {
    }

    public function init_table()
    {
        global $wpdb;
        $tablename_master = $wpdb->prefix . 'hlrm_formz';
        $tablename_meta = $wpdb->prefix . 'hlrm_formzmeta';
        $sql1 = "CREATE TABLE $tablename_master (
            id bigint NOT NULL AUTO_INCREMENT,
            created timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
            names varchar(100) NOT NULL,
            email varchar(150) NOT NULL,
            phone varchar(20) NOT NULL,
            messages text,
            referrer varchar(512) NOT NULL,
            PRIMARY KEY  (id)
        );";
        $sql2 = "CREATE TABLE $tablename_meta (
            meta_id bigint NOT NULL AUTO_INCREMENT,
            formz_id bigint not null,
            meta_key varchar(100) not null,
            meta_value varchar(255),
            PRIMARY KEY  (meta_id)
        );";
        try {
            dbDelta([$sql1, $sql2]);
        } catch (\Throwable $th) {
            print_r($th);exit;
        }
    }

}
