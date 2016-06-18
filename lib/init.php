<?php

define('ROOT', dirname(__DIR__));

define('MEMCACHE_HOST', '127.0.0.1');
define('MEMCACHE_PORT', 11211);
define('CLIENT_ID', 53999199);

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
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36 QQBrowser/4.0.4035.400',
            CURLOPT_REFERER        => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
            CURLOPT_NOSIGNAL       => 1,
            CURLOPT_ENCODING       => 'gzip, deflate, sdch',
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
            unset($option['header']);
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
                if ($params) {
                    $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);
                }
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
        //var_dump($url);
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $content = curl_exec($ch);
        //检测header 处理头部cookie
        if (!strpos($content, "\r\n\r\n")) {
            var_dump($content, curl_error($ch), curl_getinfo($ch));
            return false;
        }
        curl_close($ch);
        //var_dump($content);
        list($header, $body) = explode("\r\n\r\n", $content, 2);
        $headers = explode("\n", $header);
        sort($headers);
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
        //var_dump($header);
        self::cache()->set('webqq_cookie', $cookie);
        return $body;
    }
    
    public static function joinCookie($arr)
    {
        $str = [];
        foreach ($arr as $k => $v) {
            $str[] = $k . '=' . $v;
        }
        return implode('; ', $str) . ';';
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


class API
{

    
    public static function send_msg_group($id, $message, $font = [])
    {
        if ($font == []) {
            $font = [
                'name'  => '微软雅黑',
                'size'  => 13,
                'style' => [0, 0, 0],
                'color' => 'FF0000',
            ];
        }
        $content = json_encode([$message, ['font', $font]]);
        $data=['r' => json_encode([
            'group_uin'         => (int)$id,
            'content'    => $content,
            'face'       => 213,
            'msg_id'     => (int)(substr($_SERVER['REQUEST_TIME'], 4).rand(100,999)),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
        ])];
        $con     = PT::curl('http://d1.web2.qq.com/channel/send_qun_msg2', http_build_query($data), 'POST', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res     = json_decode($con, true);
        return $res;
    }
    
    public static function send_msg_friend($id, $message, $font = [])
    {
        if ($font == []) {
            $font = [
                'name'  => '微软雅黑',
                'size'  => 13,
                'style' => [0, 0, 0],
                'color' => 'FF0000',
            ];
        }
        $content = json_encode([$message, ['font', $font]]);
        $data=['r' => json_encode([
            'to'         => (int)$id,
            'content'    => $content,
            'face'       => 112,
            'msg_id'     => (int)(substr($_SERVER['REQUEST_TIME'], 4).rand(100,999)),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
        ])];
        $con     = PT::curl('http://d1.web2.qq.com/channel/send_buddy_msg2', http_build_query($data), 'POST', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res     = json_decode($con, true);
        return $res;
    }
    
    public static function send_msg_discuss($id, $message, $font = [])
    {
        if ($font == []) {
            $font = [
                'name'  => '微软雅黑',
                'size'  => 13,
                'style' => [0, 0, 0],
                'color' => 'FF0000',
            ];
        }
        $content = json_encode([$message, ['font', $font]]);
        $data=['r' => json_encode([
            'did'         => (int)$id,
            'content'    => $content,
            'face'       => 112,
            'msg_id'     => (int)(substr($_SERVER['REQUEST_TIME'], 4).rand(100,999)),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
        ])];
        $con     = PT::curl('http://d1.web2.qq.com/channel/send_discu_msg2', http_build_query($data), 'POST', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res     = json_decode($con, true);
        return $res;
    }

    public static function get_msg_poll()
    {
        $con = PT::curl('http://d1.web2.qq.com/channel/poll2', http_build_query(['r' => json_encode([
            'ptwebqq'    => PT::cache()->get('webqq_ptwebqq'),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
            't'          => $_SERVER['REQUEST_TIME'],
        ])]), 'POST', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }
    
    /**
     * 在线好友
     */
    public static function get_online_friend_list()
    {
        $con = PT::curl('http://d1.web2.qq.com/channel/get_online_buddies2', [
            'vfwebqq'    => PT::cache()->get('webqq_vfwebqq'),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
            't'          => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }
    
    /**
     * 在线好友
     */
    public static function get_discus_list()
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_discus_list', [
            'vfwebqq'    => PT::cache()->get('webqq_vfwebqq'),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
            't'          => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }

    public static function get_friend_info($tuin)
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_friend_info2', [
            'tuin'       => $tuin,
            'vfwebqq'    => PT::cache()->get('webqq_vfwebqq'),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
            't'          => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'header'  => ['Origin: http://d1.web2.qq.com'],
            'referer' => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }
    
    public static function get_friend_account($tuin, $type = 1)
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_friend_uin2', [
            'tuin'    => $tuin,
            'type'    => $type,
            'vfwebqq' => PT::cache()->get('webqq_vfwebqq'),
            't'       => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }

    public static function get_group_info($gcode)
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_group_info_ext2', [
            'gcode'   => $gcode,
            'vfwebqq' => PT::cache()->get('webqq_vfwebqq'),
            't'       => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }

    public static function get_discu_info($did)
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_discu_info', [
            'gcode'      => $did,
            'vfwebqq'    => PT::cache()->get('webqq_vfwebqq'),
            'clientid'   => CLIENT_ID,
            'psessionid' => PT::cache()->get('webqq_psessionid'),
            't'          => $_SERVER['REQUEST_TIME'],
        ], 'GET', [
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }
    
    
    /**
     * 所有好友
     */
    public static function get_friend_list()
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_user_friends2', http_build_query(['r' => json_encode([
            'vfwebqq' => PT::cache()->get('webqq_vfwebqq'),
            'hash'    => self::hash(PT::cache()->get('webqq_uin'), PT::cache()->get('webqq_cookie')['ptwebqq']),
        ])]), 'POST', [
            'header'  => ['Origin: http://s.web2.qq.com'],
            'referer' => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }
    
    public static function get_group_name_list()
    {
        $con = PT::curl('http://s.web2.qq.com/api/get_group_name_list_mask2', http_build_query(['r' => json_encode([
            'vfwebqq' => PT::cache()->get('webqq_vfwebqq'),
            'hash'    => self::hash(PT::cache()->get('webqq_uin'), PT::cache()->get('webqq_cookie')['ptwebqq']),
        ])]), 'POST', [
            'header'  => ['Origin: http://s.web2.qq.com'],
            'referer' => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
        ]);
        $res = json_decode($con, true);
        if (isset($res['retcode']) && $res['retcode'] == 0) {
            return $res['result'];
        } else {
            var_dump($con);
            exit;
        }
    }


    //hash 加密

    public static function hash($uin, $ptwebqq)
    {
        $n   = [0, 0, 0, 0];
        $len = strlen($ptwebqq);
        for ($i = 0; $i < $len; $i++) {
            $n[$i % 4] ^= ord($ptwebqq{$i});
        }
        $u    = ["EC", "OK"];
        $v    = [];
        $v[0] = $uin >> 24 & 255 ^ ord($u['0']['0']);
        $v[1] = $uin >> 16 & 255 ^ ord($u['0']['1']);
        $v[2] = $uin >> 8 & 255 ^ ord($u['1']['0']);
        $v[3] = $uin & 255 ^ ord($u['1']['1']);
        $N    = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"];
        $V    = "";
        for ($i = 0; $i < 8; $i++) {
            $t = $i % 2 == 0 ? $n[$i >> 1] : $v[$i >> 1];
            $V .= $N[$t >> 4 & 15];
            $V .= $N[$t & 15];
        }
        return $V;
    }
}



