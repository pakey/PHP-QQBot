<?php
include __DIR__ . '/lib/init.php';
//
//PT::curl('https://ui.ptlogin2.qq.com/cgi-bin/login?daid=164&target=self&style=16&mibao_css=m_webqq&appid=501004106&enable_qlogin=0&no_verifyimg=1&s_url=http%3A%2F%2Fw.qq.com%2Fproxy.html&f_url=loginerroralert&strong_login=1&login_state=10&t=20131024001', [], 'GET', [
//    'cookie' => [
//        'RK'       => 'OfeLBai4FB',
//        'ptcz'     => 'ad3bf14f9da2738e09e498bfeb93dd9da7540dea2b7a71acfb97ed4d3da4e277',
//        'pgv_pvi'  => '911366144',
//        'ptisp'    => 'ctc',
//        'pgv_info' => 'ssid=s5714472750',
//        'pgv_pvid' => '1051433466',
//        'qrsig'    => 'hJ9GvNx*oIvLjP5I5dQ19KPa3zwxNI62eALLO*g2JLbKPYsZIRsnbJIxNe74NzQQ',
//    ],
//]);

$url = PT::cache()->delete('webqq_sigurl');

$con = PT::curl('https://ssl.ptlogin2.qq.com/ptqrshow', [
    'appid' => 501004106,
    'e'     => 0,
    'l'     => 'M',
    's'     => 5,
    'd'     => 72,
    'v'     => 4,
    't'     => $_SERVER['REQUEST_TIME'],
], 'GET', [
        'referer' => 'https://ui.ptlogin2.qq.com/cgi-bin/login?daid=164&target=self&style=16&mibao_css=m_webqq&appid=501004106&enable_qlogin=0&no_verifyimg=1&s_url=http%3A%2F%2Fw.qq.com%2Fproxy.html&f_url=loginerroralert&strong_login=1&login_state=10&t=20131024001',
        'cookie'=>[],
    ]
);
header('content-type:image/png');
echo($con);