<?php

require_once __DIR__ . '/vendor/autoload.php';
use InstaLite\InstaLite;
$instagram = new InstaLite("username", "password", "proxy");
/** search user (return array standart instagram) **/
$user = $instagram->searchUser('alex')->id();
/** search user (return array user id [1,2,3]) **/
$user = $instagram->searchUser('alex')->all();
/** send photo **/
$instagram->uploadPhoto(__DIR__ . '/img.jpg', 'text #hashtag');
/** send message direct **/
$instagram->sendMessage('text message', [1233, 1233, 1223]);
