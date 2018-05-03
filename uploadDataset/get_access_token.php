<?php
/**
 * Created by PhpStorm.
 * User: Stepan
 * Date: 03.05.2018
 * Time: 17:08
 */

$permissions = [
    'photos','audio','video','docs','notes','pages','status','offers','questions',
    'wall','groups','messages','email','notifications','stats','ads','offline'
];

$request_params = [
    'client_id' => 6468226,
    'redirect_uri' => 'https://oauth.vk.com/blank.html',
    'response_type' => 'token',
    'display' => 'page',
    'scope' => implode(',', $permissions)
];

$url = 'https://oauth.vk.com/authorize?'.http_build_query($request_params);

echo $url;