<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 07.05.2018
 * Time: 17:47
 */

require_once('../src/VK/VK.php');
require_once('../src/VK/VKException.php');

use VK\VK;

function ex()
{
    $vk_config = array(
        'app_id' => 6468226,
        'access_token' => ""
    );

    try {
        // создание объекта с последующей авторизацией
        $vk = new VK($vk_config['app_id'], $vk_config['api_secret'], $vk_config['access_token']);
    } catch (\VK\VKException $e) {

    }


    $group_id = '1'; // пишеи сюда ID сообщества
    $membersGroups = array(); // массив участников группы
    $info_group = $vk->api('groups.getById', array( // вызов запроса на информацию о сообществе и получения количества участников и фотографии 200х200 px
        'group_id' => $group_id,
        'fields' => 'photo_200,members_count',
        'v' => '5.74'
    ));

    if ($info_group['response']) { // проверка на успешный запрос
        print_r('<img src="' . $info_group['response'][0]['photo_200'] . '">'); // вывод информации
        print_r('<br> Участников: ' . $info_group['response'][0]['members_count']);
    }
}

function getMembers25k($group_id, $membersGroups, $len, $vk)
{
    require_once('../src/VK/VK.php');
    require_once('../src/VK/VKException.php');
    $code = 'var members = API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.74", "sort": "id_asc", "count": "1000", "offset": ' . count($membersGroups) . '}).items;' // делаем первый запрос и создаем массив
        . 'var offset = 1000;' // это сдвиг по участникам группы
        . 'while (offset < 25000 && (offset + ' . count($membersGroups) . ') < ' . $len . ')' // пока не получили 20000 и не прошлись по всем участникам
        . '{'
        . 'members = members + "," + API.groups.getMembers({"group_id": ' . $group_id . ', "v": "5.27", "sort": "id_asc", "count": "1000", "offset": (' . count($membersGroups) . ' + offset)}).items;' // сдвиг участников на offset + мощность массива
        . 'offset = offset + 1000;' // увеличиваем сдвиг на 1000
        . '};'
        . 'return members;';
    //print_r($code); die("asdasdasdasd");
    $data = $vk->api("execute", array('code' => $code));
    if ($data['response']) {
        //print_r($data); die("123123132");
//                $membersGroups = $membersGroups.concat(JSON.parse("[" + data.response + "]")); // запишем это в массив
        $array = explode(',', $data['response']);
        //print_r($array); die();
        $membersGroups = array_merge($membersGroups, $array); // запишем это в массив
//                $('.member_ids').html('Загрузка: ' + membersGroups.length + '/' + members_count);
        if ($len > count($membersGroups)) {// если еще не всех участников получили
            sleep(rand(0, 1));
            getMembers25k($group_id, $membersGroups, $len, $vk); // задержка [0,1]  с. после чего запустим еще раз
        } else { // если конец то
            print_r("Готово");
            print_r($membersGroups);
        }
    } else {
        print_r($data); // в случае ошибки выведем её
    }
    die();
}

$vk_config = array(
    'app_id' => 6468226,
    'access_token' => "",
);

try {
    // создание объекта с последующей авторизацией
    $vk = new VK($vk_config['app_id'], $vk_config['api_secret'], $vk_config['access_token']);
} catch (\VK\VKException $e) {

}
$vk -> setApiVersion('5.74');

$membersGroups = array();
$group_id = '1';
getMembers25k($group_id, $membersGroups, 50000, $vk);