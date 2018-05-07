<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 18:06
 */

$app_id = 6468226;
$access_token = "";

ini_set('memory_limit', '-1');

require_once('../src/VK/VK.php');
require_once('../src/VK/VKException.php');

use VK\VK;

function getMembers25k($group_id, $membersGroups, $len, $vk)
{
    require_once('../src/VK/VK.php');
    require_once('../src/VK/VKException.php');

    $code = 'var members = API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.74", "count": "1000", "offset": ' . count($membersGroups) . '}).items;'
        . 'var offset = 1000;'
        . 'while (offset < 25000 && (offset + ' . count($membersGroups) . ') < ' . $len . ')'
        . '{'
        . 'members = members + "," + API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.74", "count": "1000", "offset": (' . count($membersGroups) . ' + offset)}).items;'
        . 'offset = offset + 1000;'
        . '};'
        . 'return members;';

    $data = $vk->api("execute", array('code' => $code));

    if ($data['response']) {
        $array = explode(',', $data['response']);
        $membersGroups = array_merge($membersGroups, $array);

        if ($len > count($membersGroups)) {
            sleep(rand(0, 1));
            getMembers25k($group_id, $membersGroups, $len, $vk);
        } else {
            return $membersGroups;
        }
    }

    echo "Error in request\n";
    return array();
}

function save_to_file($matrix, $filename)
{
    $fp = fopen($filename, 'w');

    foreach ($matrix as $fields) {
        fputcsv($fp, $fields);
    }
}

function get_group_members_by_offset($group_id, $offset, $limit)
{
    global $access_token;
    $request_params = [
        'group_id' => $group_id,
        'offset' => $offset,
        'count' => $limit,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.getMembers?' . http_build_query($request_params);

    $repeat = true;
    $attempts_cnt = 0;

    do {
        $members_str = file_get_contents($url);
        $members = json_decode($members_str, true);

        $error = $members["error"];

        if (is_null($error)) {
            $repeat = false;
        } else {
            sleep(1);
        }

        $attempts_cnt += 1;

    } while ($repeat == true and $attempts_cnt < 5);

    if ($attempts_cnt == 5) {
        echo "Warning: attempts count is $attempts_cnt, group_id = $group_id skipped!!!\n";
    }

    return $members;
}

function get_all_group_members($group_id)
{
    $page = 0;
    $limit = 1000;
    $group_members = array();

    do {
        $offset = $page * $limit;
        $offset_members = get_group_members_by_offset($group_id, $offset, $limit);
        $offset_users = $offset_members["response"]["users"];
        array_push($group_members, ...$offset_users);

        $page++;

        // значение 8 выкидыват error-6 не очень часто, подобрано эмпирически, но когда кидает, то repeat флаг в get_group_members это улавливает
        if ($page % 8 == 0) {
            sleep(1);
        }

    } while ($offset_members["response"]["count"] > $offset + $limit and $page < 10);

    return $group_members;
}


$search_keys = range('a', 'b');
$user_item_matrix = array();

$vk_config = array(
    'app_id' => $app_id,
    'access_token' => $access_token
);

try {
    $vk = new VK($vk_config['app_id'], $vk_config['api_secret'], $vk_config['access_token']);
} catch (\VK\VKException $e) {
    echo "Exception in init VK object!\n";
}

$vk->setApiVersion('5.74');

echo "VK object created\n";

//$membersGroups = array();
//$group_id = '1';
//$membersGroups = getMembers25k($group_id, $membersGroups, 20000, $vk);
//print_r($membersGroups);

foreach ($search_keys as $key) {
    $start = microtime(true);
    echo "\nSearch key: $key\n";

    $request_params = [
        'q' => $key,
        'type' => 'group',
        'offset' => 0,
        'count' => 2,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $search_result = file_get_contents($url);

    $result_obj = json_decode($search_result, true);

    foreach ($result_obj['response'] as $group_obj) {

        $group_id = $group_obj['gid'];

        echo "$group_id\n";
        $group_ids = array_column($user_item_matrix, '0');

        if (!in_array($group_id, $group_ids)) {

            if (!is_null($group_id)) {
                $membersGroups = array();
                $members = getMembers25k($group_id, $membersGroups, 20000, $vk);

                if (!is_null($members)) {

                    array_unshift($members, $group_id, "<-g_id-users->");

                    array_push($user_item_matrix, $members);
                }
            }
        }
    }

    $time_elapsed_secs = microtime(true) - $start;

    echo "Execution time of key $key is equals to $time_elapsed_secs sec\n";
}


print_r($user_item_matrix);

//$filename = '../data/group_id_users_offset_a_b.csv';
//save_to_file($user_item_matrix, $filename);