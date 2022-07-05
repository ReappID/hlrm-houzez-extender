<?php

require_once ABSPATH . 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$client = new Aws\S3\S3Client([
    'endpoint' => 'sgp1.digitaloceanspaces.com',
    'version' => 'latest',
    'region' => 'ap-southeast-1',
    'credentials' => [
        'key'    => 'JJSYNEJXJHPS7KI5ZRSR',
        'secret' => 'Vl7L4YlYtxFRMfFRm9l6XSSHVoQE/RLGsv/q+58cI4k',
    ],
]);

// $objects = $client->listObjects([
//     'Bucket' => 'halorumah.sgp1.digitaloceanspaces.com/wp-content',
// ]);
// foreach ($objects['Contents'] as $obj){
//     echo $obj['Key']."\n";
// }

// exit;

