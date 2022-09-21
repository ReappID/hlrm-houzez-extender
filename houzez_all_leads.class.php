<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Leads_List extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => __('Lead', 'sp'), //singular name of the listed records
            'plural' => __('Leads', 'sp'), //plural name of the listed records
            'ajax' => false //should this table support ajax?

        ]);
    }

    public function get_leads($per_page = 10, $page_number = 1)
    {
        global $wpdb;
        //     if (isset($_POST['page']) && isset($_POST['s'])) {
        //         $this->users_data = $this->get_users_data($_POST['s']);
        //   } else {
        //         $this->users_data = $this->get_users_data();
        //   }
        $search = (isset($_REQUEST['s'])) ? $_REQUEST['s'] : false;
        $do_search = ($search) ? $wpdb->prepare(" WHERE source_link LIKE '%%%s%%' OR `email` LIKE '%%%s%%'", $search, $search) : '';

        $sql = "SELECT `user_id`, `display_name`, `email`, `mobile`, `type`, `source_link`, `enquiry_to` as 'agent', `message`, `time`, `enquiry_user_type` FROM {$wpdb->prefix}houzez_crm_leads $do_search";

        $order = "";

        if (!empty($_REQUEST['orderby'])) {
            $order = ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $order .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }else if(empty($_REQUEST['orderby'])){
            $order = ' ORDER BY `time` DESC';
            // $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= $order." LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    public function no_items()
    {
        _e('No leads avaliable.', 'sp');
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'agent':
            case 'source_link':
            default:
                return $item[$column_name]; //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'display_name' => __('Nama', 'sp'),
            'email' => __('Email', 'sp'),
            'mobile' => __('Phone', 'sp'),
            'type' => __('Tipe User', 'sp'),
            'source_link' => __('Property', 'sp'),
            'agent' => __('Agent/Owner', 'sp'),
            'message' => __('Pesan', 'sp'),
            'time' => __('Waktu', 'sp')
        ];

        return $columns;
    }
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'source_link' => array('source_link', true),
            'time' => array('time', false)
        );

        return $sortable_columns;
    }
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}houzez_crm_leads";

        return $wpdb->get_var($sql);
    }

    public function prepare_items()
    {

        // $this->_column_headers = $this->get_column_info();
        $this->_column_headers = [
            $this->get_columns(), // all the columns
            [], // hidden columns
            [] // sortable columns
        ];
        /** Process bulk action */
        // $this->process_bulk_action();

        $per_page = $this->get_items_per_page('leads_per_page', 5);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();
        // self::class

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);

        $this->items = self::get_leads($per_page, $current_page);
    }
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['user_id']
        );
    }

    function column_source_link($item)
    {
        # code...
        return "<a href='" . $item['source_link'] . "'>" . $item['source_link'] . "</a>";
    }

    function column_mobile($item)
    {
        $agent_phone = $item['mobile'];
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

        $link_wa = "https://api.whatsapp.com/send?phone=" . $agent_phone;
        return $agent_phone . "&nbsp;<a class='button' href='" . $link_wa . "' target='_BLANK'>Hubungi ke Whatsapp</a>";
    }

    function column_agent($item)
    {
        global $wpdb;
        $user = false;

        if ($item['enquiry_user_type'] == 'author_info') {
            $user = get_user_by('ID', absint($item['agent']));
        } else if ($item['enquiry_user_type'] == 'agent_info') {
            $agent_sql = "SELECT user_id from {$wpdb->prefix}usermeta WHERE meta_key='fave_author_agent_id' AND meta_value='" . absint($item['agent']) . "' LIMIT 1;";
            $agent = $wpdb->get_results($agent_sql);
            if (count($agent) > 0) {
                $user = get_user_by('ID', $agent[0]['user_id']);
            }
            // $sql = "SELECT ID, user_email FROM {$wpdb->prefix}users WHERE ID=" . absint($item['agent']) . " LIMIT 1";
        } else if ($item['enquiry_user_type'] == 'agency_info') {
            $agency_sql = "SELECT user_id from {$wpdb->prefix}usermeta WHERE meta_key='fave_agency_agent_id' AND meta_value='" . absint($item['agent']) . "' LIMIT 1;";
            $agency = $wpdb->get_results($agency_sql);
            if (count($agency) > 0) {
                $user = get_user_by('ID', $agency[0]['user_id']);
            }
        }
        // $result = $wpdb->get_results($sql);

        // return json_encode($result);
        if ($user) {
            return $user->user_email;
        }
        return $item['agent'];
        // return $title . $this->row_actions($actions);
    }
}
