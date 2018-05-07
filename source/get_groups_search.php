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

function getMembersWithExecute($group_id, $membersGroups, $len, $vk)
{
    $code = 'var members = API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.74", "count": "1000", "offset": ' . count($membersGroups) . '}).items;'
        . 'var offset = 1000;'
        . 'while (offset < 25000 && (offset + ' . count($membersGroups) . ') < ' . $len . ')'
        . '{'
        . 'members = members + "," + API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.74", "count": "1000", "offset": (' . count($membersGroups) . ' + offset)}).items;'
        . 'offset = offset + 1000;'
        . '};'
        . 'return members;';

    $repeat = true;
    $attempts_cnt = 0;
    do {
        $data = $vk->api("execute", array('code' => $code));

        if ($data['response']) {
            $array = explode(',', $data['response']);
            $membersGroups = array_merge($membersGroups, $array);

            if ($len > count($membersGroups)) {
                sleep(1);
                getMembersWithExecute($group_id, $membersGroups, $len, $vk);
            } else {
                return $membersGroups;
            }

            $repeat = false;
        } else {
            sleep(1);
        }

        $attempts_cnt += 1;

    } while ($repeat == true and $attempts_cnt < 5);

    echo "Error in request with attempts_cnt $attempts_cnt\n";

    return $membersGroups;
}

function save_to_file($matrix, $filename)
{
    $fp = fopen($filename, 'w');

    foreach ($matrix as $fields) {
        fputcsv($fp, $fields);
    }
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

foreach ($search_keys as $key) {
    $start = microtime(true);
    echo "\nSearch key: $key\n";

    $request_params = [
        'q' => $key,
        'type' => 'group',
        'offset' => 0,
        'count' => 100,
        'access_token' => $access_token,
        'version' => '5.74'
    ];

    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $search_result = file_get_contents($url);

    $result_obj = json_decode($search_result, true);

    foreach ($result_obj['response'] as $group_obj) {

        $group_id = $group_obj['gid'];

        $group_ids = array_column($user_item_matrix, '0');

        if (!in_array($group_id, $group_ids)) {

            if (!is_null($group_id)) {
                $membersGroups = array();
                $members = getMembersWithExecute($group_id, $membersGroups, 2000, $vk);

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

$filename = '../data/group_id_users_execute_a_b.csv';
save_to_file($user_item_matrix, $filename);