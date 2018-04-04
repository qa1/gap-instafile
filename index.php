<?php

define('API_KEY','XXX');
define('DEV',true);

function errorLog($d){
    if(DEV)
        error_log(print_r($d,true));
}


function runCommandFile($api_key, $method,$datas=[]){
    $url = "https://api.gap.im/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'token: '.$api_key,
    ));
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        errorLog(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

function runCommand($api_key, $method, $datas=[]){
    $url = "https://api.gap.im/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'token: '.$api_key,
    ));
    curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($datas));
    $res = curl_exec($ch);
    if(DEV)
        errorLog($res);
    if(curl_error($ch)){
        errorLog(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

$chat_id = $_POST['chat_id'];

if($_POST['type'] == 'joint'){
    $d = runCommand(API_KEY, 'sendMessage',[
        'chat_id'=>$chat_id,
        'data'=>'به ربات دانلود خوش آمدید
        
چه کاری براتون انجام بدم ؟',
        'type'=>'text',
        'reply_keyboard'=>json_encode(
            [
                'keyboard'=>[
                        [
                            ['profile'=>'دانلود پروفایل'],
                            ['post'=>'دانلود پست'],
                        ],
                        [
                            ['help'=>'راهنما استفاده']
                        ]
                ]
            ]
        )
    ]);
//    errorLog($d);
}

if($_POST['type'] == 'text'){
    $text = $_POST['data'];

    if($text == 'help'){
        runCommand(API_KEY, 'sendMessage',[
            'chat_id'=>$chat_id,
            'data'=>'🛠 خوش اومدید

برای استفاده از این سرویس روی دکمه مربوطه کلیک کنید و سپس آدرس پروفایل یا پست مد نظر رو ارسال کنید .

➖ توجه کنید که برای ارسال پست نباید طرف مقابل Private باشد .',
            'type'=>'text',
            'reply_keyboard'=>json_encode(
                [
                    'keyboard'=>[
                        [
                            ['profile'=>'دانلود پروفایل'],
                            ['post'=>'دانلود پست'],
                        ],
                        [
                            ['help'=>'راهنما استفاده']
                        ]
                    ]
                ]
            )
        ]);
        die;
    }

    if($text == 'cancel'){
        runCommand(API_KEY, 'sendMessage',[
            'chat_id'=>$chat_id,
            'data'=>'حله .
            
چه کاری براتون انجام بدم ؟',
            'type'=>'text',
            'reply_keyboard'=>json_encode(
                [
                    'keyboard'=>[
                        [
                            ['profile'=>'دانلود پروفایل'],

                        ],
                        [
                            ['help'=>'راهنما استفاده']
                        ]
                    ]
                ]
            )
        ]);
        apcu_store($chat_id.'-location','home');
        die;
    }


    if($text == 'profile'){
        runCommand(API_KEY, 'sendMessage',[
            'chat_id'=>$chat_id,
            'data'=>'لطفا آدرس پیج اینستاگرام را ارسال کنید :',
            'type'=>'text',
            'reply_keyboard'=>json_encode(
                [
                    'keyboard'=>[

                        [
                            ['cancel'=>'انصراف']
                        ]
                    ]
                ]
            )
        ]);
        apcu_store($chat_id.'-location','profile');
        die;
    }


    switch (apcu_fetch($chat_id.'-location')){
        case 'profile':{
            $url = trim($text);
            $url = $url.'/?__a=1';
            if($url = str_replace(["https://","http://"],["",""],$url)){
                $url = "https://".$url;
            }
            $json = file_get_contents($url);
            $json = json_decode($json);
            if($json){
                $profile = $json->graphql->user ->profile_pic_url_hd;
                $filename = explode("/",$profile);
                $filename = end($filename);
                $localFile = __DIR__.'/files/'.$filename;
                copy($profile, $localFile);
                $r = runCommandFile(API_KEY,'upload',[
                    'image'=>new CURLFile($localFile)
                ]);
                $r->desc = $text;
                errorLog($r);
                $r = runCommand(API_KEY, 'sendMessage',
                    [
                        'type'=>'image',
                        'data'=>json_encode($r),
                        'chat_id'=>$chat_id,
                    ]);
                errorLog($r);

            }else{
                runCommand(API_KEY, 'sendMessage',[
                    'chat_id'=>$chat_id,
                    'data'=>'آدرس اشتباه است .

آدرس پیج باید مشابه https://instagram.com/pageusername باشد',
                    'type'=>'text',
                    'reply_keyboard'=>json_encode(
                        [
                            'keyboard'=>[

                                [
                                    ['cancel'=>'انصراف']
                                ]
                            ]
                        ]
                    )
                ]);
            }
        }break;

        default:{
            runCommand(API_KEY, 'sendMessage',[
                'chat_id'=>$chat_id,
                'data'=>'متوجه نشدم
                
چه کاری براتون انجام بدم ؟',
                'type'=>'text',
                'reply_keyboard'=>json_encode(
                    [
                        'keyboard'=>[
                            [
                                ['profile'=>'دانلود پروفایل'],

                            ],
                            [
                                ['help'=>'راهنما استفاده']
                            ]
                        ]
                    ]
                )
            ]);
        }break;
    }




//    errorLog($d);
}
