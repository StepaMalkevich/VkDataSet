<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 18:06
 */

$access_token = "";

function get_group_members($group_id)
{
    global $access_token;
    $request_params = [
        'group_id' => $group_id,
        'offset' => 0,
        'count' => 3,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.getMembers?' . http_build_query($request_params);

    $repeat = true;

    do {
        $result = file_get_contents($url);
        $result_obj = json_decode($result, true);

        $users = $result_obj["response"]["users"];

        if (!is_null($users)) {
            $repeat = false;
        } else {
            sleep(1);
        }

    } while ($repeat == true);

    if (is_null($users)) {
        echo "never";
        print_r($result);
    }
    return $users;
}

$alphas = range('a', 'b');
$global_matrix = array();
$hashset = array();

foreach ($alphas as &$value) {
    echo "\n Ключ: $value \n";

    $request_params = [
        'q' => $value,
        'type' => 'group',
        'offset' => 0,
        'count' => 3,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $result = file_get_contents($url);

    $result_obj = json_decode($result, true);

    $i = 0;
    $null_cnt = 0;
    foreach ($result_obj['response'] as $group_obj) {
        if ($i % 100 == 0) {
            echo "i = $i \n";
        }

        $group_id = $group_obj['gid'];

        if (!array_key_exists($group_id, $hashset)) {
            $hashset[$group_id] = true;

            if (!is_null($group_id)) {
                $users = get_group_members($group_id);

                array_unshift($users, $group_id, "<-g_id->users");

                array_push($global_matrix, $users);

                if ($i % 8 == 0) {
                    sleep(1);
                }
            }
        }

        $i += 1;
    }

    unset($value);
}


$fp = fopen('../data/group_id_users.csv', 'w');

foreach ($global_matrix as $fields) {
    fputcsv($fp, $fields);
}