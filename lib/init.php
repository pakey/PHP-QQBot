<?php

define('ROOT', dirname(__DIR__));

define('MEMCACHE_HOST', '127.0.0.1');
define('MEMCACHE_PORT', 11211);

/**
 * @Author: 杰少Pakey
 * @Email : Pakey@qq.com
 * @File  : init.php
 */
class PT
{

    public static function success($info, $data = [])
    {
        $data = [
            'status' => 1,
            'info'   => $info,
            'data'   => $data,
        ];
        self::json($data);
        exit();
    }

    public static function error($info, $data = [])
    {
        $data = [
            'status' => 0,
            'info'   => $info,
            'data'   => $data,
        ];
        self::json($data);
        exit();
    }

    public static function json($data)
    {
        if (!headers_sent()) {
            //设置系统的输出字符为utf-8
            header("Content-Type: application/json; charset=utf-8");
            //支持页面回跳
            header("Cache-control: private");
            //版权标识
            header("X-Powered-By: PTcms Studio (www.ptcms.com)");
            // 跨域
            //header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Origin:*');
            header('Access-Control-Allow-Headers: accept, x-requested-with');
        }
        if (defined('JSON_PRETTY_PRINT')) {
            $body = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        echo $body;
    }

    public static function curl($url, $params = [], $method = 'GET', $option = [])
    {
        $opts = [
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36 QQBrowser/4.0.4035.400',
            CURLOPT_REFERER        => 'http://gu.qq.com/i/',
            CURLOPT_NOSIGNAL       => 1,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        $cookie = [];
        if (isset($option['cookie'])) {
            if (is_array($option['cookie'])) {
                $cookie = $option['cookie'];
            }
            unset($option['cookie']);
        } else {
            $cookie = self::cache()->get('webqq_cookie');;
            if (!$cookie) {
                $cookie = [];
            }
        }

        $opts[CURLOPT_COOKIE] = self::joinCookie($cookie);

        if (isset($option['useragent'])) {
            $opts[CURLOPT_USERAGENT] = $option['useragent'];
            unset($option['useragent']);
        }
        
        
        if (isset($option['referer'])) {
            $opts[CURLOPT_REFERER] = $option['referer'];
            unset($option['referer']);
        }


        if (!empty($option['header'])) {
            $opts[CURLOPT_HTTPHEADER] = $option['header'];
        }

        //补充配置
        foreach ($option as $k => $v) {
            $opts[$k] = $v;
        }
        
        //安全模式
        if (ini_get("safe_mode") || ini_get('open_basedir')) {
            unset($opts[CURLOPT_FOLLOWLOCATION]);
        }
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                break;
            case 'POST':
                //判断是否传输文件
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                exit('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $content = curl_exec($ch);
        curl_close($ch);
        //检测header 处理头部cookie
        list($header, $body) = explode("\r\n\r\n", $content, 2);
        $headers = explode("\n", $header);
        rsort($headers);
        foreach ($headers as $v) {
            if (strpos($v, ':') === false) continue;
            list($key, $value) = explode(':', $v, 2);
            $key = strtolower($key);
            if ($key == 'set-cookie') {
                $value   = str_replace('; ', ';', $value);
                $cookies = explode(';', trim($value), 3);
                list($cookie_key, $cookie_v) = explode('=', $cookies['0'], 2);
                if (strtotime(str_replace('expires=', '', $cookies['1'])) < time() && isset($param['cookie'][$cookie_key])) {
                    unset($cookie[$cookie_key]);
                } else {
                    $cookie[$cookie_key] = $cookie_v;
                }
            }
        }
        self::cache()->set('webqq_cookie',$cookie);
        return $body;
    }
    
    public static function joinCookie($arr)
    {
        $str = [];
        foreach ($arr as $k => $v) {
            $str[] = $k . '=' . $v;
        }
        return implode('; ', $str);
    }
    
    public static function cache()
    {
        static $cache;
        if (!$cache) {
            $cache = new Memcache();
            $cache->connect(MEMCACHE_HOST, MEMCACHE_PORT);
        }
        return $cache;
    }
}


function parseJsonp($con)
{
    if ($con) {
        $t1 = explode('(', substr($con, 0, -12), 2);
        if (empty($t1['1'])) {
            global $param;
            var_dump($param);
            var_dump($con);
            return [];
        } else {
            if (strpos($t1['1'], ')')) {
                $t2      = explode(')', $t1['1']);
                $t1['1'] = $t2['0'];
            }
            return json_decode($t1['1'], true);
        }
    }
    return [];
}



