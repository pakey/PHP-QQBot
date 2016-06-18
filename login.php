<?php

include __DIR__ . '/lib/init.php';


$url = PT::cache()->get('webqq_sigurl');
if (!$url) {
    $con  = PT::curl(
        'https://ssl.ptlogin2.qq.com/ptqrlogin',
        [
            'webqq_type'   => 10,
            'remember_uin' => 1,
            'login2qq'     => 1,
            'aid'          => 501004106,
            'u1'           => 'http://w.qq.com/proxy.html?login2qq=1&webqq_type=10',
            'ptredirect'   => 0,
            'ptlang'       => 2052,
            'daid'         => 164,
            'from_ui'      => 1,
            'pttype'       => 1,
            'dumy'         => '',
            'fp'           => 'loginerroralert',
            'action'       => '0-0-61691',
            'mibao_css'    => 'm_webqq',
            't'            => 1,
            'g'            => 1,
            'js_type'      => 0,
            'js_ver'       => 10164,
            'login_sig'    => '',
            'pt_randsalt'  => 2,
        ],
        'GET',
        [
        ]
    );
    $data = explode(",", substr($con, 7, -4));
    if (isset($data['4'])) {
        $data = array_map(function ($v) {
            return trim($v, "' ");
        }, $data);
        if ($data['0'] == "0") {
            PT::cache()->set('webqq_sigurl', $data['2']);
            $url = $data['2'];
            //种cookie
            PT::curl($url, [], 'GET', [
                'referer' => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
            ]);

            $cookie = PT::cache()->get('webqq_cookie');
            PT::cache()->set('webqq_ptwebqq', $cookie['ptwebqq']);
            // 获取vfwebqq
            $con     = PT::curl('http://s.web2.qq.com/api/getvfwebqq', [
                'ptwebqq'    => $cookie['ptwebqq'],
                'clientid'   => CLIENT_ID,
                'psessionid' => '',
                't'          => $_SERVER['REQUEST_TIME_FLOAT'],
            ], 'GET', [
                'header'  => ['Origin:http://d1.web2.qq.com'],
                'referer' => 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1',
            ]);
            $res     = json_decode($con, true);
            $vfwebqq = $res['result']['vfwebqq'];
            //获取psessionid
            PT::cache()->set('webqq_vfwebqq', $res['result']['vfwebqq']);
            $con = PT::curl('http://d1.web2.qq.com/channel/login2', http_build_query(['r' => json_encode([
                'ptwebqq'    => $cookie['ptwebqq'],
                'clientid'   => CLIENT_ID,
                'psessionid' => '',
                'status'     => 'online',
            ])]), 'POST', [
                'header'  => ['Origin: http://d1.web2.qq.com'],
                'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2',
            ]);
            $res = json_decode($con, true);
            if ($res['retcode'] == 0) {
                $psessionid = $res['result']['psessionid'];
                $uin        = $res['result']['uin'];
                PT::cache()->set('webqq_psessionid', $res['result']['psessionid']);
                PT::cache()->set('webqq_uin', $res['result']['uin']);
            } else {
                var_dump('登陆失败', $res);
            }


        } else {
            var_dump($data['4']);
            exit;
        }
    } else {
        echo $con;
    }
}

//$online=API::get_online_friend_list();
//var_dump($online);
//$userlist=API::get_friend_list();
//var_dump($userlist);
$list=API::get_group_name_list();
var_dump($list);
//$list=API::get_discus_list();
//var_dump($list);
//$list=API::get_friend_info('2147467410');
//var_dump($list);
//$list=API::get_friend_account('2147467410');
//var_dump($list);
//$list=API::get_group_info('2272921323');
//var_dump($list);
//$list=API::get_msg_poll();
//print_r($list);
//$list=API::send_msg_friend('3693441404',date('Y-m-d H:i:s'));
//var_dump($list);
$list=API::send_msg_group('2272921323','123');
var_dump($list);
//$list=API::send_msg_discuss('3346936378','123');
//var_dump($list);
//239245970


