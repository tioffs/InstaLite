<?php

/**
 * INSTAGRAM LITE API
 * @version 1.0.0
 * @author tioffs <github.com/tioffs>
 */

namespace InstaLite;

use InstaLite\Exception;
use InstaLite\Request;

class InstaLite
{
    /** username instagram */
    public $username;
    /** password instagram */
    public $password;
    /** array user Authorization */
    public $user;
    /** proxy socks5://login:password@ip:port */
    public $proxy;
    /** debug, console message default false */
    public $debug;
    /** default session array */
    public $session = [
        'header' => [
            'user-agent' => 'Instagram 10.3.2 Android (18/4.3; 320dpi; 720x1280; Huawei; HWEVA; EVA-L19; qcom; en_US)',
            'x-ig-app-id' => 1217981644879628
        ],
        'cookie' => []
    ];
    /** url web instagram */
    protected $web = 'https://www.instagram.com/';
    /** url api instagram */
    protected $api = 'https://i.instagram.com/';
    /** patch session folder */
    private $sessionPatch = __DIR__ . '/session/';
    /** Search temp user array */
    private $userList;

    /**
     * __construct
     *
     * @param string $username
     * @param string $password
     * @param string $proxy
     * @param boolean $debug defaul false (console message)
     */
    public function __construct(string $username, string $password, string $proxy = null, bool $debug = false)
    {
        $this->username    = $username;
        $this->password    = $password;
        $this->proxy       = $proxy;
        $this->debug       = $debug;
        Request::sessionGenerate($this);
        if (!$this->__restoreSession()) {
            $this->__login();
        }
        if ($this->proxy != $proxy) {
            $this->proxy   = $proxy;
        }
    }

    /**
     * __login
     * - Authorization web instagram
     * @return void
     */
    private function __login()
    {
        Request::get('https://www.instagram.com')->body();
        $response = Request::post($this->web . 'accounts/login/ajax/')
            ->addParam('username', $this->username)
            ->addParam('password', $this->password)
            ->addParam('queryParams', '{}')
            ->addParam('optIntoOneTap', false)
            ->addHead('content-type', 'application/x-www-form-urlencoded')
            ->json(true);
        if (isset($response['authenticated'], $response['userId']) && $response['authenticated']) {
            $this->user = $response;
            $this->__updateSession();
            return $this->__log('user login success, user id: ' . $response['userId']);
        }
        throw new Exception("Error Authorization");
    }

    /**
     * __checkAuth
     * - check Authorization
     * @return bool
     */
    private function __checkAuth(): bool
    {
        $user = Request::get($this->web . 'accounts/edit/?__a=1')
            ->addHead('user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36')
            ->json(true);
        if (!isset($user['form_data']['username'])) {
            unlink($this->sessionPatch . $this->username);
            $this->__log('Fail session: ' . $this->username);
            return false;
        }
        $this->__log('user login success, user id: ' . $this->user['userId']);
        $this->__updateSession();
        return true;
    }

    /**
     * __restoreSession
     * - load session, search file name username 
     * @return bool
     */
    private function __restoreSession(): bool
    {
        if (file_exists($this->sessionPatch . $this->username)) {
            $session = json_decode(file_get_contents($this->sessionPatch . $this->username), true);
            $this->session  = $session['session'];
            $this->proxy    = $session['proxy'];
            $this->username = $session['username'];
            $this->password = $session['password'];
            $this->user     = $session['user'];
            return $this->__checkAuth();
        }
        return false;
    }

    /**
     * __updateSession
     *  - Save session and update
     * @return void
     */
    private function __updateSession()
    {
        file_put_contents($this->sessionPatch . $this->username, json_encode([
            'session'   => $this->session,
            'proxy'     => $this->proxy,
            'username'  => $this->username,
            'user'      => $this->user,
            'password'  => $this->password
        ]));
        $this->__log('update session file');
    }

    /**
     * uploadPhoto Web interface
     *
     * @param string $photo  file patch __DIR__ . '/photo.jpg';
     * @param string $message  text message, hashtag
     * @return string|null return media id
     */
    public function uploadPhoto(string $photo, string $message): ?string
    {
        if (!file_exists($photo)) {
            throw new Exception("File [$photo] not found");
        }
        $photo_id = round(microtime(true) * 1000);
        $file_temp = __DIR__ . '/' . $this->uuid4();
        list($width, $height, $image_type) = getimagesize(realpath($photo));
        $srcImage = ImageCreateFromJPEG($photo);
        $resImage = ImageCreateTrueColor($width, $height);
        ImageCopyResampled($resImage, $srcImage, 0, 0, 0, 0, $width, $height, $width, $height);
        ImageJPEG($srcImage, $file_temp, 100);
        ImageDestroy($srcImage);

        $response = Request::post($this->web . 'rupload_igphoto/fb_uploader_' . $photo_id)
            ->addHead('content-type', 'image/jpg')
            ->addHead('x-entity-name', 'fb_uploader_' . $photo_id)
            ->addHead('offset', 0)
            ->addHead('user-agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_4_1 like Mac OS X; ru-RU) AppleWebKit/537.36 (KHTML, like Gecko)  Version/11.4.1 Mobile/15G77 Safari/537.36 Puffin/5.2.2IP')
            ->addHead('x-entity-length', filesize($file_temp))
            ->addHead('x-instagram-rupload-params', '{"media_type":1,"upload_id":"' . $photo_id . '","upload_media_height":' . $height . ',"upload_media_width":' . $width . '}')
            ->addFile($file_temp)
            ->json(true);
        unlink($file_temp);
        if (!isset($response['upload_id'], $response['status']) && $response['status'] != 'ok') {
            throw new Exception("Error upload file: " . \json_encode($response));
        }
        $this->__log('upload file success: ' . \json_encode($response));
        $response = Request::post($this->web . 'create/configure/')
            ->addHead('content-type', 'application/x-www-form-urlencoded')
            ->addHead('user-agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_4_1 like Mac OS X; ru-RU) AppleWebKit/537.36 (KHTML, like Gecko)  Version/11.4.1 Mobile/15G77 Safari/537.36 Puffin/5.2.2IP')
            ->addParam('upload_id', $response['upload_id'])
            ->addParam('caption', $message)
            ->addParam('usertags', '')
            ->addParam('custom_accessibility_caption', '')
            ->addParam('retry_timeout', '')
            ->json(true);
        if (!isset($response['media']['id']) && $response['status'] != 'ok') {
            throw new Exception("Error save post: " . \json_encode($response));
        }
        $this->__log('save post success: ' . \json_encode($response));
        return $response['media']['id'];
    }

    /**
     * searchUser
     *
     * @param string $searchKey user name, first name, last name, nick name
     * @return object
     * @example searchUser
     *  - `searchUser('alex')->all() - return array all users standart formate instagram`
     *  - `searchUser('alex')->id() - return array [id,id,id,id] all users`
     */
    public function searchUser(string $searchKey): object
    {
        $query = [
            'context'       => 'blended',
            'query'         => $searchKey,
            'rank_token'    => '0.89805833269' . rand(10000, 99999),
            'include_reel'  => true
        ];
        $response = Request::get('https://www.instagram.com/web/search/topsearch/?' . http_build_query($query))
            ->json(true)['users'] ?? [];
        $this->userList = [];
        foreach ($response as $user) {
            $this->userList[] = $user['user'];
        }
        return $this;
    }

    /**
     * all
     * return array user
     * @return array|null
     */
    public function all(): ?array
    {
        return $this->userList;
    }

    /**
     * id
     * return array user id
     * @return array|null
     */
    public function id(): ?array
    {
        $userList = [];
        foreach ($this->userList as $user) {
            $userList[] = $user['pk'];
        }
        return $userList;
    }

    /**
     * Send Message
     *
     * @param string $message - message text
     * @param array $user - array user id [1,2,3,4,5]
     * @return boolean|null
     */
    public function sendMessage(string $message, array $user): ?bool
    {
        $response = Request::post($this->api . 'api/v1/direct_v2/threads/broadcast/text/')
            ->addParam('text', $message)
            ->addParam('_uuid', '')
            ->addParam('_csrftoken', $this->session['cookie']['csrftoken'])
            ->addParam('recipient_users', "[[". implode(',', $user) ."]]")
            ->addParam('_uid', $this->user['userId'])
            ->addParam('action', 'send_item')
            ->addParam('thread_ids', ["0"])
            ->addParam('client_context', str_replace('-', '', $this->uuid4()))
            ->addHead('content-type', 'application/x-www-form-urlencoded')
            ->json(true);
        if ($response['status'] == 'ok' && isset($response['payload']['item_id'])) {
            $this->__log('message send: ' . \json_encode($response));
            return true;
        }
        $this->__log('sendMessage error: ' . \json_encode($response));
        return false;
    }

    /**
     * UUID v4 generate
     *
     * @return string
     */
    private function uuid4(): ?string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Console log
     *
     * @param string $message
     * @param boolean $error
     * @return void
     */
    private function __log(string $message, bool $error = false)
    {
        if ($this->debug) {
            print PHP_EOL . $message . PHP_EOL;
        }
    }
}
