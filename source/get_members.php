<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 17:54
 */

$access_token = "";

$request_params = [
    'group_id' => 'matobes546',
    'sort' => 'id_asc',
    'offset' => 0,
    'count' => 30,
    'access_token' => $access_token,
    'version' => '5.74'
];

$url = 'https://api.vk.com/method/groups.getMembers?'.http_build_query($request_params);

$result = file_get_contents($url);

echo $result;