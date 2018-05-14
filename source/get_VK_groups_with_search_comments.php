<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 18:06
 */

$app_id = 6468226;
$access_token = "Enter your token";

/**
 * Сначала отдельно запускается функция для получения access_token-а приложения
 */
function get_access_token()
{
    $permissions = [
        'photos', 'audio', 'video', 'docs', 'notes', 'pages', 'status', 'offers', 'questions',
        'wall', 'groups', 'messages', 'email', 'notifications', 'stats', 'ads', 'offline'
    ];

    $request_params = [
        'client_id' => 6468226,
        'redirect_uri' => 'https://oauth.vk.com/blank.html',
        'response_type' => 'token',
        'display' => 'page',
        'scope' => implode(',', $permissions)
    ];

    $url = 'https://oauth.vk.com/authorize?' . http_build_query($request_params);

    echo $url;

}

ini_set('memory_limit', '-1');

require_once('../src/VK/VK.php');
require_once('../src/VK/VKException.php');

use VK\VK;

/**
 * Будем вызывать метод Vk api search с рандомными ключами
 */
function generate_search_keys($key_num)
{
    $search_keys = array();

    foreach (range(0, $key_num) as $v) {
        array_push($search_keys, getRandomString());
    }

    return $search_keys;
}

function getRandomString($length = 2) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $string;
}

/**
 * Будем собирать $len пользователей группы. Потом в python коде проверять, подходит ли собранный датасет с $len нашим требованиям
 * Здесь именно специальный параметр $len, потому что неизвестно, сколько в итоге нам нужно брать пользователей из каждой группы, чтобы удовлетворить тербования к датасету
 */
function getMembersWithExecute($group_id, &$membersGroups, $len, $vk)
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

        if (array_key_exists('response', $data)) {
            $array = explode(',', $data['response']);


            if (count($array) < 100) {
                /**
                 * Иногда ВК отдает мало записей, не будем ждать, а будем сразу скипать такое
                 */
                return $membersGroups;
            }

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

    } while ($repeat == true and $attempts_cnt < 3);

    if ($attempts_cnt == 3) {
        echo "Warning: attempts count is $attempts_cnt, skipp this\n";
    }

    return $membersGroups;
}

$search_keys = generate_search_keys(50);

/**
 * Учет "обработанных" групп
 */
$group_ids = array();

$filename = '../data/group_id_to_users_arr.csv';
$result_file = fopen($filename, 'w');

$vk_config = array(
    'app_id' => $app_id,
    'access_token' => $access_token
);

$vk = new VK($vk_config['app_id'], $vk_config['api_secret'], $vk_config['access_token']);

$vk->setApiVersion('5.74');

echo "VK object created\n";

foreach ($search_keys as $key) {
    echo "\nSearch key: $key\n";

    $request_params = [
        'q' => $key,
        'type' => 'group',
        'offset' => 0,
        'count' => 1000,
        'access_token' => $access_token,
        'v' => '5.74'
    ];

    /**
     * Воспользуемся стандарным VK api для получения групп по ключу $key
     */
    $url = 'https://api.vk.com/method/groups.search?' . http_build_query($request_params);

    $search_result = file_get_contents($url);

    $result_obj = json_decode($search_result, true);

    $q = 0;
    $start = microtime(true);

    foreach ($result_obj['response'] as $group_obj) {

        $group_id = $group_obj['gid'];

        if (!in_array($group_id, $group_ids)) {

            if (!is_null($group_id)) {
                $membersGroups = array();

                $members = getMembersWithExecute($group_id, $membersGroups, 25000, $vk);

                /** Будем хранить результат в формате:
                 * 12345 // group id
                 * <-g_id-users-> // разделитель
                 * 123123 // id пользователей
                 * 123123
                 * 123123
                 *
                 * Запишем в файл именно такие строки. Разделитель для проверки.
                 */
                array_unshift($members, $group_id, "<-g_id-users->");

                // Сразу сохраним строку в файл
                fputcsv($result_file, $members);

                array_push($group_ids, $group_id);
            }
        }

        if ($q % 100 == 0) {
            $time_elapsed_secs = microtime(true) - $start;
            $start = microtime(true);
            echo "Execution time of key $key for $q is equals to $time_elapsed_secs sec\n";
        }

        $q += 1;

        unset($members);
    }
}