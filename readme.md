# INSTAGRAM API InstaLite [![License][packagist-license]][license-url]
> **easy-to-use class for working with instagram, minimal number of features**

[![Downloads][packagist-downloads]][packagist-url]
[![Telegram][Telegram-image]][Telegram-url]

- [Installation](#Installation)
- [Example](#Example)
- Method Api
    - [Search User](#Search-User)
    - [Upload Photo](#Upload-Photo)
    - [Send Message](#Send-Message)
- [Use Proxy](#Use-Proxy)

## Installation
**Using Composer:**
```
composer require tioffs/instalite
```
## Example
```php
require_once __DIR__ . '/vendor/autoload.php';
use InstaLite;
$instagram = new InstaLite("username", "password", "proxy");
/** search user (return array standart instagram) **/
$user = $instagram->searchUser('alex')->id();
/** search user (return array user id [1,2,3]) **/
$user = $instagram->searchUser('alex')->all();
/** send photo **/
$instagram->uploadPhoto(__DIR__ . '/img.jpg', 'text #hashtag');
/** send message direct **/
$instagram->sendMessage('text message', [1233, 1233, 1223]);
```
# Method
## Search User
Search user instagram, nickname, username, last name, first name
```php
$key = "search first name or username ...";
$user = $instagram->searchUser($key);
 
$user->id();
/** return array user id **/
[1, 2, 3, 4, 5, 6]

$user->all();
/** return array user standart formate instagram **/
[
    [
        pk: ""
        username: ""
        full_name: ""
        is_private: false
        profile_pic_url: ""
        profile_pic_id: ""
        is_verified: false
        has_anonymous_profile_picture: false
        mutual_followers_count: 0
        social_context: ""
        search_social_context: ""
        friendship_status: {}
        latest_reel_media: 1580484486
        seen: 0
    ],[],[]
]

```
## Upload Photo
Send photo, upload instagram
```php
/** file photo mimetype JPEG **/
$photo = __DIR__ . '/image.jpg';
/** Message text and Hashtag **/
$message = 'Hello InstaGram';
$upload = $instagram->uploadPhoto($photo, $message);
/** Result **/
if($upload) {
    /** upload photo sussecc **/
    echo $upload;
    /** media id **/
}
```
## Send Message
Send message direct instagram
```php
/** Array user id **/
$user = [12356456, 45645465];
/** Message text **/
$message = 'Hello InstaGram';
$send = $instagram->sendMessage($message, $user);
/** Result **/
if($send) {
    /** message send sussecc **/
}
```
## Use Proxy
supports socks5 and http/https
```php
/** socks5 **/
$instagram = new InstaLite("username", "password", "socks5://login:password@ip:port");
/** http/https **/
$instagram = new InstaLite("username", "password", "http://login:password@ip:port");
```

----

Made with &#9829; from the [@tioffs][tioffs-url]

[tioffs-url]: https://timlab.ru/
[license-url]: https://github.com/tioffs/instalite/blob/master/LICENSE

[telegram-url]: https://t.me/joinchat/C9JmzQ-fc3SKXI0D-9h-uw
[telegram-image]: https://img.shields.io/badge/Telegram-Join%20Chat-blue.svg?style=flat

[packagist-url]: https://packagist.org/packages/tioffs/instalite
[packagist-license]: https://img.shields.io/github/license/tioffs/instalite
[packagist-downloads]: https://img.shields.io/packagist/dt/tioffs/instalite