<?php
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if (!function_exists('sql_logger')) {
    function sql_logger()
    {
        global $wpdb;
        $log_file = fopen(ABSPATH . '/sql_log.txt', 'a');
        fwrite($log_file, "//////////////////////////////////////////\n\n" . date("F j, Y, g:i:s a") . "\n");
        foreach ($wpdb->queries as $q) {
            fwrite($log_file, $q[0] . " - ($q[1] s)" . "\n\n");
        }
        fclose($log_file);
    }
}

if (WP_DEBUG == true || WP_DEBUG == 1 && function_exists('sql_logger')) {
    add_action('shutdown', 'sql_logger');
}

function send_bg($url,  $post_parameters,$method, $headers = false)
{
    // print_r($headers);exit;
    $log_file = ABSPATH . 'wp-content/data-mirrors.log';
    $tipe = isset($headers['Content-Type']) ? $headers['Content-Type'] : false;
    if ($method == 'GET' || $tipe != 'application/json') {
        if (is_array($post_parameters)) {
            $params = "";
            foreach ($post_parameters as $key => $value) {
                $params .= $key . "=" . urlencode($value) . '&';
            }
            $params = rtrim($params, "&");
        } else {
            $params = $post_parameters;
        }
    } else if ($method == 'POST' || $tipe == 'application/json') {
        if (is_array($post_parameters)) {
            $params = json_encode($post_parameters);
        } else {
            $params = $post_parameters;
        }
    }

    // $headers = "";
    if (is_array($headers)) {
        $res = "";
        foreach ($headers as $k => $v) {
            $res .= ' -H "' . $k . ':' . $v . '"';
        }
        $headers = $res;
    }

    $command = "curl -X '" . $method . "'" . $headers . " -d '" . $params . "' --url '" . $url . "' >> /dev/shm/request.log 2> /dev/null &";
    // return $command;
    $out = null;
    $retval = null;
    exec($command, $out, $retval);

    if ($out != null && $out != '') {
        // print_r($out);
        // exit;
        return $out;
    }
    return $command;
    return false;
}

function send($url, $post_parameters,$method = 'POST',  $headers = false)
{
    $curl = curl_init();
    $params = null;
    $tipe = isset($headers['Content-Type']) ? $headers['Content-Type'] : false;

    if (is_array($post_parameters) && $tipe == 'application/json') {
        $params = json_encode($post_parameters);
    } else {
        $params = $post_parameters;
    }

    if (is_array($headers)) {
        $res = [];
        foreach ($headers as $k => $v) {
            $res[] = "$k:$v";
        }
        $headers = $res;
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    // echo $response;
}

function formattedWANumber($number)
{
	if(str_starts_with($number, '0')){
		ltrim($number,'0');
		return "62".$number;
	}

	if(str_starts_with($number, "62")){
		return $number;
	}

	if(str_starts_with($number, '+')){
		ltrim($number,'+');
	}

	return $number;

	
}
