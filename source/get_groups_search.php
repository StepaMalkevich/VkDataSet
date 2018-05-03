<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 18:06
 */

require_once __DIR__ . '/../vendor/autoload.php';

$access_token = "";

function get_group_members($group_id)
{
    global $access_token;
    $request_params = [
        'group_id' => $group_id,
        'offset' => 0,
        'count' => 2,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.getMembers?' . http_build_query($request_params);

    $result = file_get_contents($url);

    $result_obj = json_decode($result, true);

    $users = $result_obj["response"]["users"];

    return $users;
}

$alphas = range('a', 'b');
$global_matrix = array();

foreach ($alphas as &$value) {
    echo "\n Ключ: $value \n";

    $request_params = [
        'q' => $value,
        'type' => 'group',
        'offset' => 0,
        'count' => 2,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $result = file_get_contents($url);

    $result_obj = json_decode($result, true);

    foreach ($result_obj['response'] as $group_obj) {
        $group_id = $group_obj['gid'];
        if (!is_null($group_id)) {
            $users = get_group_members($group_id);
            array_unshift($users, $group_id);

            array_push($global_matrix, $users);
        }
    }

    unset($value);
}

$fp = fopen('file.csv', 'w');
$header = array("group_id, users");
fputcsv($fp, $header);


foreach ($global_matrix as $fields) {
    fputcsv($fp, $fields);
}