<?php

include __DIR__ . '/lib/init.php';


$url=PT::cache()->get('webqq_sigurl');
if(!$url){
    $con = PT::curl(
        'https://ssl.ptlogin2.qq.com/ptqrlogin'
        ,
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
    $data=explode(",",substr($con,7,-4));
    if(isset($data['4'])){

        $data=array_map(function($v){
            return trim($v,"' ");
        },$data);
        if($data['0']=="0"){
            PT::cache()->set('webqq_sigurl',$data['2']);
            $url=$data['2'];
        }else{
            var_dump($data['4']);
        }
    }else{
        echo $con;
    }
}
var_dump($url);
var_dump(PT::cache()->get('webqq_cookie'));

