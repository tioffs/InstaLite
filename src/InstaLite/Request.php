<?php
/**
 * INSTAGRAM LITE API
 * @version 1.0.0
 * @author tioffs <github.com/tioffs>
 */
namespace InstaLite;

class Request
{
    private static $class;
    private static $curl_param;
    private static $instaLite;
    private static $setHeadet = [];
    private static $setPostData = [];

    /**
     * sessionGenerate
     *
     * @param object $instaLite
     * @return object
     */
    public static function sessionGenerate(object &$instaLite): object
    {
        self::$class = new Request;
        self::$instaLite = &$instaLite;
        return self::$class;
    }

    /**
     * addHead
     *
     * @param string $value
     * @param [type] $key
     * @return object
     */
    public function addHead(string $value, $key): object
    {
        self::$setHeadet[$value] = $key;
        return self::$class;
    }

    /**
     * addFile
     *
     * @param string $value
     * @param [type] $key
     * @return object
     */
    public function addFile(string $filePatch): object
    {
        self::$setPostData = file_get_contents(realpath($filePatch));
        return self::$class;
    }

    /**
     * addParam
     *
     * @param string $value
     * @param [type] $key
     * @return object
     */
    public function addParam(string $value, $key): object
    {
        self::$setPostData[$value] = $key;
        return self::$class;
    }

    /**
     * __cookie
     *
     * @return void
     */
    private static function __cookie()
    {
        $temp = '';
        foreach (self::$instaLite->session['cookie'] as $k => $v) {
            $temp .= "{$k}={$v}; ";
        }
        return $temp;
    }

    /**
     * __headers
     *
     * @return void
     */
    private static function __headers()
    {
        $temp = [];
        $headers = array_merge(self::$instaLite->session['header'], self::$setHeadet);
        foreach ($headers as $k => $v) {
            $temp[] = "{$k}:{$v}";
        }
        $temp[] = 'cookie:' . self::__cookie();
        return $temp;
    }

    /**
     * __default
     *
     * @return void
     */
    private static function __default()
    {
        self::$curl_param[CURLOPT_HEADER]          = true;
        self::$curl_param[CURLOPT_TIMEOUT]         = 65;
        self::$curl_param[CURLOPT_FOLLOWLOCATION]  = false;
        self::$curl_param[CURLOPT_SSL_VERIFYHOST]  = false;
        self::$curl_param[CURLOPT_SSL_VERIFYPEER]  = false;
        self::$curl_param[CURLOPT_RETURNTRANSFER]  = true;
        self::$curl_param[CURLOPT_AUTOREFERER]     = false;
        self::$curl_param[CURLINFO_HEADER_OUT]     = true;
        self::$curl_param[CURLOPT_HTTPHEADER]      = self::__headers();
        if (self::$instaLite->proxy) {
            self::$curl_param[CURLOPT_PROXY]       = self::$instaLite->proxy;
        }
        if (isset(self::$curl_param[CURLOPT_POST]) && self::$curl_param[CURLOPT_POST]) {
            $type = self::$setHeadet['content-type'] ?? '';
            switch ($type) {
                case 'application/x-www-form-urlencoded':
                    self::$setPostData = http_build_query(self::$setPostData);
                    break;
            }
            self::$curl_param[CURLOPT_POSTFIELDS]  = self::$setPostData;
        }
    }

    /**
     * post
     *
     * @param string $url
     * @return object
     */
    public static function post(string $url): object
    {
        self::$curl_param[CURLOPT_URL] = $url;
        self::$curl_param[CURLOPT_POST] = true;
        return self::$class;
    }

    /**
     * get
     *
     * @param string $url
     * @return object
     */
    public static function get(string $url): object
    {
        self::$curl_param[CURLOPT_URL] = $url;
        return self::$class;
    }

    /**
     * __send
     *
     * @return void
     */
    private static function __send()
    {
        self::__default();
        $curl = curl_init();
        curl_setopt_array($curl, self::$curl_param);
        $response = curl_exec($curl);
        $headers  = curl_getinfo($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $header_content = substr($response, 0, $headers['header_size']);
        $response = trim(str_replace($header_content, '', $response));
        preg_match_all("/Set-Cookie:\s*(?<cookie>[^=]+=[^;]+)/mi", $header_content, $matches);
        foreach ($matches['cookie'] as $c) {
            if ($c = str_replace(['sessionid=""', 'target=""'], '', $c)) {
                $c = explode('=', $c);
                self::$instaLite->session['cookie'] = array_merge(self::$instaLite->session['cookie'], [trim($c[0]) => trim($c[1])]);
            }
        }
        self::$instaLite->session['header']['x-csrftoken'] = self::$instaLite->session['cookie']['csrftoken'] ?? self::$instaLite->session['header']['x-csrftoken'] ?? '';
        self::$setHeadet  = [];
        self::$curl_param = [];
        self::$setPostData = [];
        return [$response, $httpCode, $matches['cookie']];
    }

    /**
     * json
     *
     * @param boolean $array true - return object, false - return array
     * @return array|object|null
     */
    public static function json(bool $array = false)
    {
        list($response, $httpCode, $coockie) = self::__send();
        return \json_decode($response, $array);
    }

    /**
     * body
     *
     * @return array|null [body=>[], code=>int, cookie=>[]]
     */
    public static function body(): ?array
    {
        list($response, $httpCode, $coockie) = self::__send();
        return [
            'body'   => $response,
            'code'   => $httpCode,
            'cookie' => $coockie
        ];
    }
}
