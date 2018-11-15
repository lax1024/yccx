<?php
/**
 * Created by PhpStorm.
 * User:LongAnxiang
 * Date: 2018/3/13
 * Time: 19:59
 */

namespace tool;

use think\Cache;
use think\Config;
use think\Session;

/**
 * 微信相关功能操作
 * Class WecharTool
 * @package tool
 */
abstract class WecharTool
{
    /**
     * 获取二维码 参数
     * @param $terminal_str 参数
     * @return string
     */
    public static function getQRcode($terminal_str)
    {
        $access_token = WecharTool::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $access_token;
        //{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}
        $post_data = [
            "action_name" => "QR_LIMIT_STR_SCENE",
            "action_info" => [
                "scene" => [
                    "scene_str" => $terminal_str
                ]
            ],
        ];
        $out_data = send_post_json($url, $post_data);
        return $out_data;
    }

    /**
     * 创建微信菜单
     * @param $menu
     * @return string
     */
    public static function creatMenu($menu)
    {
        $menu = [
            "button" => [
                [
                    "name" => "我的",
                    "sub_button" => [
                        [
                            'type' => "view",
                            'name' => "个人中心",
                            'url' => "http://www.youchedongli.cn/mobile/index/user_center.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "我的订单",
                            'url' => "http://www.youchedongli.cn/mobile/index/order_mine.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "查找充电站",
                            'url' => "http://wechat.xcharger.net/arranging/gps?oasourceid=gh_c3debd706b25&dealer=871976061741834240"
                        ],
                    ]
                ],
                [
                    'type' => "view",
                    "name" => "立即用车",
                    "url" => "http://www.youchedongli.cn/mobile/index/map_choose_car.html",
                ],
                [
                    "name" => "其他",
                    "sub_button" => [
                        [
                            'type' => "view",
                            'name' => "常规租车",
                            'url' => "http://www.youchedongli.cn/mobile/index/index_tradition.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "我要充电",
                            'url' => "http://www.youchedongli.cn/pile/index/index.html"
                        ],
                        [
                            'type' => "view",
                            "name" => "关于我们",
                            "url" => "http://www.youchedongli.cn/mobile/index/ewm.html"
                        ],
                        [
                            'type' => "view",
                            "name" => "用车协议",
                            "url" => "http://www.youchedongli.cn/mobile/index/user_protocol.html"
                        ]
                    ]
                ]
            ]
        ];
        $access_token = WecharTool::getAccessToken();
        $url = " https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
        //{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}
        $result = https_post($url, $menu);
        print_r($result);
        $out_data = json_decode($result,true);
        return $out_data;
    }

    /**
     * 获取微信菜单
     * @return bool|string
     */
    public static function getMenu()
    {
        $access_token = WecharTool::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=" . $access_token;
        $out_data = file_get_contents($url);
        return $out_data;
    }

    /**
     * 删除微信菜单
     * @return bool|string
     */
    public static function delMenu()
    {
        $access_token = WecharTool::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . $access_token;
        $out_data = file_get_contents($url);
        return $out_data;
    }

    /**
     *根据openid 获取微信用户信息
     * @param string $openid
     * @return mixed
     */
    public static function getUser($openid = '')
    {
        $access_token = WecharTool::getAccessToken();
        $get_userinfo = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res = file_get_contents($get_userinfo);
        //解析json
        $user_obj = json_decode($res, true);
        return $user_obj;
    }

    /**
     *根据openid 获取微信用户信息
     * @param string $openid
     * @return mixed
     */
    public static function getUserInfo($openid = '')
    {
        $access_token = WecharTool::getAccessToken();
        $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
//        file_put_contents("getUserInfo.txt",$get_user_info_url);
        $res = file_get_contents($get_user_info_url);
        //解析json
        $user_obj = json_decode($res, true);
        return $user_obj;
    }

    /**
     * 获取$access_token
     * @param bool $is_update true 强制制刷新$access_token
     * @return bool|string
     */
    public static function getAccessToken($is_update = false)
    {
        $wxconfig = Config::get('wxconfig');
        $appid = $wxconfig['APPID'];
        $secret = $wxconfig['APPSECRET'];
        $get_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;
        if ($is_update) {
            $res = file_get_contents($get_token_url);
            $json_objt = json_decode($res, true);
            $access_token = $json_objt['access_token'];
            Cache::set('temp_token_temp', $access_token, 7000);
            return $access_token;
        }
        $access_token = Cache::get('temp_token_temp');
        if (empty($access_token)) {
            $res = file_get_contents($get_token_url);
            $json_objt = json_decode($res, true);
            $access_token = $json_objt['access_token'];
            Cache::set('temp_token_temp', $access_token, 7000);
        }
        return $access_token;
    }

    /**
     * 获取$access_token $ticket
     * @param bool $is_update 强制制刷新$access_token
     * @return string
     */
    public static function getTicket($is_update = false)
    {
        $access_token = WecharTool::getAccessToken($is_update);
        $get_jsapi_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $access_token . "&type=jsapi";
        $res = file_get_contents($get_jsapi_url);
        $json_obj = json_decode($res, true);
        if (intval($json_obj['errcode']) == 0) {
            $ticket = $json_obj['ticket'];
        } else {
            $access_token = WecharTool::getAccessToken(true);
            $get_jsapi_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $access_token . "&type=jsapi";
            $res = file_get_contents($get_jsapi_url);
            $json_obj = json_decode($res, true);
            if (intval($json_obj['errcode']) == 0) {
                $ticket = $json_obj['ticket'];
            } else {
                $ticket = '';
            }
        }
        return $ticket;
    }

    /**
     * 获取语音保存到服务器
     * @param $serverId
     * @param $type
     * @param $path
     * @param bool $is_stream 是否返回数据流
     * @return string
     */
    public static function getVoiceUrl($serverId, $type, $path, $is_stream = false)
    {
        $access_token = WecharTool::getAccessToken();
        $openid = Session::get('wechar_openid');
        $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res = file_get_contents($get_user_info_url);
        //解析json
        $user_objs = json_decode($res, true);
        if (!empty($user_objs['errcode'])) {
            $access_token = WecharTool::getAccessToken(true);
        }
//    $serverId = "bfp4KWed89AKId3nGDa6gZoBD1j-FK1awWUOoeUmyQFyKnAPKiE7NEOz5iKV3FcJ";
//    $access_token = "aG7P6P8_8G9jNVNaq25ocNY8Vn1dfgDE-b2NVO2Qr5hRS1KNsY_ISHT70ZfXMwKRiaYYAoMeKW5aOO8Lv-T6tffOaf_DuZy-Xts5igEGpAwSh98BdFhfIkwiAN1cyVxbBTUiAGAFXY";
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $serverId;
//        file_put_contents('serverId.temp', $serverId);
        $temp = file_get_contents($url);
        //如果需要 返回数据流
        if ($is_stream) {
            return $temp;
        }
        $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads/' . $path);
        $time_path = date('Ymd');
        $save_path = 'public/uploads/' . $path . '/' . $time_path;
        $file_name = md5(date('YmdHis') . rand(1000, 9999));
        if (!file_exists($upload_path . "/" . $time_path)) {
            mkdir($upload_path . "/" . $time_path, 0777, true);
        }
        $targetName = $upload_path . "/" . $time_path . '/' . $file_name . "." . $type;
        file_put_contents($targetName, $temp);
        $url = $save_path . '/' . $file_name . "." . $type;
        $url_file = str_replace('/', '_', $url);
        oss_move($url_file);
        return "/" . $url;
    }

    /**
     * 获取图片保存到服务器
     * @param $serverId
     * @param $type
     * @param $path
     * @param bool $is_stream 是否返回数据流
     * @return string|array
     */
    public static function getImgUrl($serverId, $type, $path, $is_stream = false)
    {
        $access_token = WecharTool::getAccessToken();
        $openid = Session::get('wechar_openid');
        $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res = file_get_contents($get_user_info_url);
        //解析json
        $user_objs = json_decode($res, true);
        if (!empty($user_objs['errcode'])) {
            $access_token = WecharTool::getAccessToken(true);
        }
//    $serverId = "bfp4KWed89AKId3nGDa6gZoBD1j-FK1awWUOoeUmyQFyKnAPKiE7NEOz5iKV3FcJ";
//    $access_token = "aG7P6P8_8G9jNVNaq25ocNY8Vn1dfgDE-b2NVO2Qr5hRS1KNsY_ISHT70ZfXMwKRiaYYAoMeKW5aOO8Lv-T6tffOaf_DuZy-Xts5igEGpAwSh98BdFhfIkwiAN1cyVxbBTUiAGAFXY";
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $serverId;
        file_put_contents('serverId.temp', $serverId);
        $temp = file_get_contents($url);
        $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads/' . $path);
        $time_path = date('Ymd');
        $save_path = 'public/uploads/' . $path . '/' . $time_path;
        $file_name = md5(date('YmdHis') . rand(1000, 9999));
        if (!file_exists($upload_path . "/" . $time_path)) {
            mkdir($upload_path . "/" . $time_path, 0777, true);
        }
        $targetName = $upload_path . "/" . $time_path . '/' . $file_name . "." . $type;
        file_put_contents($targetName, $temp);
        $url = $save_path . '/' . $file_name . "." . $type;
        $url_file = str_replace('/', '_', $url);
        oss_move($url_file);
        //如果需要 返回数据流
        if ($is_stream) {
            $out_data = array(
                'data' => $temp,
                'url' => $url,
            );
            return $out_data;
        }
        return "/" . $url;
    }

    /**
     * 处理二维码参数
     * @param $scene_str
     * @return array
     */
    public static function disposeScene($scene_str)
    {
        $scene_data = [
            'code' => 0,
            'info' => "解析成功"
        ];
        $pos = strpos($scene_str, '_');
        if ($pos === false) {
            $scene_data['data'] = $scene_str;
        } else {
            $scene = explode('_', $scene_str);
            $scene_temp['type'] = $scene[0];
            $scene_temp['data'] = str_replace("*", "_", $scene[1]);
            $scene_data['data'] = $scene_temp;
        }
        return $scene_data;
    }
}