<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 18:06
 */

$access_token = "5335a4a9d393ac965a8f473fc8584873dcbd21c316e09b397427a87ec98a488f82372f28aff51044623f7";

function get_group_members($group_id)
{
    global $access_token;
    $request_params = [
        'group_id' => $group_id,
        'offset' => 0,
        'count' => 1000,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.getMembers?' . http_build_query($request_params);

    $repeat = true;
    $attempts_cnt = 0;

    do {
        $result = file_get_contents($url);
        $result_obj = json_decode($result, true);

        $users = $result_obj["response"]["users"];

        if (!is_null($users)) {
            $repeat = false;
        } else {
            sleep(1);
        }

        $attempts_cnt += 1;

    } while ($repeat == true and $attempts_cnt < 10);

    if ($attempts_cnt == 10){
        echo "attempts is $attempts_cnt !!!\n";
    }

    return $users;
}

$alphas = range('a', 'f');
$user_item_matrix = array();

foreach ($alphas as &$value) {
    echo "\n Ключ поиска: $value \n";

    $request_params = [
        'q' => $value,
        'type' => 'group',
        'offset' => 0,
        'count' => 1000,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $result = file_get_contents($url);

    $result_obj = json_decode($result, true);

    $q_cnt = 0;
    foreach ($result_obj['response'] as $group_obj) {
        $group_id = $group_obj['gid'];
        $group_ids = array_column($user_item_matrix, '0');

        if (!array_key_exists($group_id,  $group_ids)) {
            $hashset[$group_id] = true;

            if (!is_null($group_id)) {
                $users = get_group_members($group_id);

                array_unshift($users, $group_id, "<-g_id->users");

                array_push($user_item_matrix, $users);

                if ($q_cnt % 8 == 0) {
                    sleep(1);
                }
            }
        }

        $q_cnt += 1;
    }

    unset($value);
}


$fp = fopen('../data/group_id_users.csv', 'w');

foreach ($user_item_matrix as $fields) {
    fputcsv($fp, $fields);
}