<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 17:41
 */

namespace wxapi;


use app\common\model\Customer as CustomerModel;
use app\common\model\OrderCharging;
use definition\SceneType;
use tool\WecharTool;

/**
 * 微信公众平台
 * Class WxApi
 * @package wxapi
 */
class WxApi
{
    //验证签名
    public function valid($get_data)
    {
        $echoStr = $get_data["echostr"];
        $signature = $get_data["signature"];
        $timestamp = $get_data["timestamp"];
        $nonce = $get_data["nonce"];
        $token = WxApiConfig::$token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        }
        return false;
    }

    //响应消息
    public function responseMsg($postStr)
    {
        file_put_contents('responseMsg.txt', "WX" . serialize($postStr));
        if (!empty($postStr)) {
            $this->logger("R \r\n" . $postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
//            if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe")) {
//                //过滤关注和取消关注事件
//            } else {
//
//            }
            $openid = $postObj->FromUserName;
            $user_info = WecharTool::getUserInfo($openid);
//            file_put_contents("getUserInfo.txt",$user_info['nickname']."|".$user_info['headimgurl']);
            $unionid = '';
            if (!empty($user_info['unionid'])) {
                $unionid = $user_info['unionid'];
            }
            $in_customer = [
                'customer_nickname' => $user_info['nickname'],
                'wechar_nickname' => $user_info['nickname'],
                'wechar_openid' => $user_info['openid'],
                'wechar_unionid' => $unionid,
                'wechar_headimgurl' => $user_info['headimgurl'],
                'channel_uid' => 0
            ];
            $customer_model = new CustomerModel();
//            file_put_contents("customer_data_up.txt",json_encode($in_customer));
            $customer_data = $customer_model->addWecharCustomer($in_customer);
            $customer_id = $customer_data['id'];
            $customer_time = $customer_data['time'];
//            file_put_contents("customer_data.txt",json_encode($customer_data));
            //消息类型分离
            switch ($RX_TYPE) {
                case "event":
                    $result = $this->receiveEvent($postObj, $customer_id);
                    break;
                case "text":
                    if (strstr($postObj->Content, "第三方")) {
//                        $result = $this->relayPart3("http://www.youchedongli.cn/mobile/index/user_center" . '?' . $_SERVER['QUERY_STRING'], $postStr);
                        $result = $this->receiveText($postObj);
                    } else if (strstr($postObj->Content, "抽奖")) {
                        if (time() - $customer_time <= 3600 * 12) {
                            $customer_model = new CustomerModel();
                            $customer_model->updateChannel($customer_id, 'jltg_cj', '抽奖');
                        }
                        $result = $this->receiveTextLottery($postObj);
                    } else {
                        $result = $this->receiveText($postObj);
                    }
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: " . $RX_TYPE;
                    break;
            }
            $this->logger("T \r\n" . $result);
            echo $result;
        } else {
            echo "";
            exit;
        }
    }

    //接收事件消息
    private function receiveEvent($object, $customer_id = '')
    {
        $scene = "";
        switch ($object->Event) {
            case "subscribe":
                $content = "欢迎关注优车出行";
//                $content .= (!empty($object->EventKey)) ? ("\n来自二维码场景 " . str_replace("qrscene_", "", $object->EventKey)) : "";
                $scene = str_replace("qrscene_", "", $object->EventKey);
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "CLICK":
                switch ($object->EventKey) {
                    case "COMPANY":
                        $content = array();
                        $content[] = array("Title" => "优车出行", "Description" => "", "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
                        break;
                    default:
                        $content = "点击菜单：" . $object->EventKey;
                        break;
                }
                break;
            case "VIEW":
                $content = "跳转链接 " . $object->EventKey;
                break;
            case "SCAN":
//                $content = "扫描场景 " . $object->EventKey;
                $scene = $object->EventKey;
                break;
            case "LOCATION":
//                $content = "上传位置：纬度 " . $object->Latitude . ";经度 " . $object->Longitude;
                $customer_model = new CustomerModel();
                $customer_model->update_customer_wechar($customer_id, $object->Longitude, $object->Latitude);
                break;
            case "scancode_waitmsg":
                if ($object->ScanCodeInfo->ScanType == "qrcode") {
                    $content = "扫码带提示：类型 二维码 结果：" . $object->ScanCodeInfo->ScanResult;
                } else if ($object->ScanCodeInfo->ScanType == "barcode") {
                    $codeinfo = explode(",", strval($object->ScanCodeInfo->ScanResult));
                    $codeValue = $codeinfo[1];
                    $content = "扫码带提示：类型 条形码 结果：" . $codeValue;
                } else {
                    $content = "扫码带提示：类型 " . $object->ScanCodeInfo->ScanType . " 结果：" . $object->ScanCodeInfo->ScanResult;
                }
                break;
            case "scancode_push":
                $content = "扫码推事件";
                break;
            case "pic_sysphoto":
                $content = "系统拍照";
                break;
            case "pic_weixin":
                $content = "相册发图：数量 " . $object->SendPicsInfo->Count;
                break;
            case "pic_photo_or_album":
                $content = "拍照或者相册：数量 " . $object->SendPicsInfo->Count;
                break;
            case "location_select":
                $content = "发送位置：标签 " . $object->SendLocationInfo->Label;
                break;
            default:
                $content = "receive a new event: " . $object->Event;
                break;
        }

        if (empty($scene)) {
            if (is_array($content)) {
                if (isset($content[0]['PicUrl'])) {
                    $result = $this->transmitNews($object, $content);
                } else if (isset($content['MusicUrl'])) {
                    $result = $this->transmitMusic($object, $content);
                }
            } else {
                $result = $this->transmitText($object, $content);
            }
        } else {
            $scene_data_out = WecharTool::disposeScene($scene);
            //判断是不是自定义类型
            if (is_array($scene_data_out['data'])) {
                //判断是不是自定义类型中的充电桩
                if ($scene_data_out['data']['type'] == SceneType::$SceneTypeEle['code']) {
                    $customer_model = new CustomerModel();
                    $out_data = $customer_model->isCharging($customer_id);
                    if (empty($out_data['code'])) {
                        $charging_id = $scene_data_out['data']['data'];
                        $order_charging_model = new OrderCharging();
                        $order_charging_model->addOrder($charging_id, $customer_id, '');
                    } else {
                        if ($out_data['code'] == 101) {
                            $result = $this->transmitOrder($object, 0, "", 4, 0);
                        } else if ($out_data['code'] == 102) {
                            $result = $this->transmitOrder($object, 0, "", 5, 0);
                        }
                    }
                } else if ($scene_data_out['data']['type'] == SceneType::$SceneTypeTG['code']) {
                    $customer_model = new CustomerModel();
                    $customer_model->updateChannel($customer_id, $scene_data_out['data']['data'], '抽奖');
                    $content = "【1】优车出行“锦鲤”特别奖一个：包括:
1.现金奖励1000元
2.优车出行共享小汽车1个月使用权（价值1200元）
3.银花电商提供：魅可MAC口红1支（价值170元）
4.中医销巴超市100元无门槛购物券（价值100元）
5.贵安新区VR小镇门票2张（价值176元）
6.吉源驾校1400元抵用券1张
7.华硕顽石五代F/A580UR（京东价4980）1000元抵用券1张
8.全网视频会员年卡1张（价值268元）
奖品总价值： 5214元
  
【2】小锦鲤奖
一等奖 优车出行小汽车一个月使用权1个（价值1200元/辆）
二等奖：优车出行小汽车半个月使用权4个（价 值600元/辆）
三等奖：银花电商魅可口红14支（价值170元/支）
四等奖：贵安新区华侨城VR小镇门票10张（携程价88元/张）
五等奖：中医销巴超市50元无门槛购物券8张
六等奖：华硕顽石五代F/A580UR（京东价4980）1000元抵用券30张
七等奖：视频会员卡年卡20张（价值268元/张）
幸运奖：吉源驾校1400优惠券100张
所有奖品总价值：154420元
中奖总人数：188人
「 邀请你来填写金数据表单《优车绿色出行大使抽选活动报名》https://jinshuju.net/f/SggtRk 」";
                    $result = $this->transmitText($object, $content);
                }
            }
        }
        return $result;
    }

    /**
     * @param $object
     * @param $id
     * @param $name
     * @param $type
     * @param int $fee
     * @return bool|string
     */
    private function transmitOrder($object, $id, $name, $type, $fee = 0)
    {
        $content = "";
        switch (intval($type)) {
            case 1:
                $text = $name . "号桩,于" . date("Y-m-d H:i:s") . "开始为您的爱车充电。";
                $content = "【开始充电】
$text

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
                break;
            case 2:
                $text = $name . "号桩,于" . date("Y-m-d H:i:s") . "已结束为您的爱车充电。本次订单费用总计" . $fee . "元";
                $content = "【充电完成】
$text
<a href='http://www.youchedongli.cn/pile/index/order_pay.html?id=$id'>立即支付</a>

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
                break;
            case 3:
                $text = "本次订单已于" . date("Y-m-d H:i:s") . "支付完成，感谢您的使用！";
                $content = "【支付完成】
$text

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
                break;
            case 4:
                $text = "还未绑定手机号码，请您先绑定手机号码且充值大于30元才能进行充电。";
                $content = "【账号提示】
$text

<a href='http://www.youchedongli.cn/mobile/index/user_balance.html?type=$id'>立即绑定</a>";
                break;
            case 5:
                $text = "您的余额不足30元，无法启动充电桩，请您充值大于30元后进行扫描充电。";
                $content = "【充值提示】
$text

<a href='http://www.youchedongli.cn/mobile/index/user_balance.html?type=$id'>立即充值</a>";
                break;
        }
        $result = false;
        if (!empty($content)) {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        //多客服人工回复模式
        if (strstr($keyword, "请问在吗") || strstr($keyword, "在线客服")) {
            $result = $this->transmitService($object);
            return $result;
        }
        //自动回复模式
        if (strstr($keyword, "文本")) {
            $content = "这是个文本消息";
        } else if (strstr($keyword, "运维")) {
            $content = "<a href='http://www.youchedongli.cn/mobile/task/index.html'>运维管理</a>";
        }else if (strstr($keyword, "表情")) {
            $content = " " . $this->bytes_to_emoji(0x1F1E8) . $this->bytes_to_emoji(0x1F1F3) . "\n优车出行：" . $this->bytes_to_emoji(0x1F335);
        } else if (strstr($keyword, "单图文")) {
            $content = array();
            $content[] = array("Title" => "优车出行", "Description" => "优车出行", "PicUrl" => "http://img.youchedongli.cn/public/static/mobile/images/seller_logo.png", "Url" => "http://www.youchedongli.cn/mobile/index/user_center");
        } else if (strstr($keyword, "图文") || strstr($keyword, "多图文")) {
            $content = array();
            $content[] = array("Title" => "优车出行1", "Description" => "", "PicUrl" => "http://img.youchedongli.cn/public/static/mobile/images/seller_logo.png", "Url" => "http://www.youchedongli.cn/mobile/index/user_center");
            $content[] = array("Title" => "优车出行2", "Description" => "", "PicUrl" => "http://img.youchedongli.cn/public/static/mobile/images/seller_logo.png", "Url" => "http://www.youchedongli.cn/mobile/index/user_center");
            $content[] = array("Title" => "优车出行3", "Description" => "", "PicUrl" => "http://img.youchedongli.cn/public/static/mobile/images/seller_logo.png", "Url" => "http://www.youchedongli.cn/mobile/index/user_center");
        } else if (strstr($keyword, "音乐")) {
            $content = array();
            $content[] = array("Title" => "优车出行1", "Description" => "", "PicUrl" => "http://img.youchedongli.cn/public/static/mobile/images/seller_logo.png", "Url" => "http://www.youchedongli.cn/mobile/index/user_center");
        } else {
//            $content = date("Y-m-d H:i:s", time()) . "\nOpenID：" . $object->FromUserName . "\n技术支持 优车出行";
            $content = date("Y-m-d H:i:s", time()) . "\n技术支持 优车出行";
        }

        if (is_array($content)) {
            if (isset($content[0])) {
                $result = $this->transmitNews($object, $content);
            } else if (isset($content['MusicUrl'])) {
                $result = $this->transmitMusic($object, $content);
            }
        } else {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    //接收文本消息（自定义抽奖）
    private function receiveTextLottery($object)
    {
        $keyword = trim($object->Content);
        //多客服人工回复模式
        if (strstr($keyword, "抽奖")) {
            $content = "【1】优车出行“锦鲤”特别奖一个：包括:
1.现金奖励1000元
2.优车出行共享小汽车1个月使用权（价值1200元）
3.银花电商提供：魅可MAC口红1支（价值170元）
4.中医销巴超市100元无门槛购物券（价值100元）
5.贵安新区VR小镇门票2张（价值176元）
6.吉源驾校1400元抵用券1张
7.华硕顽石五代F/A580UR（京东价4980）1000元抵用券1张
8.全网视频会员年卡1张（价值268元）
奖品总价值： 5214元
  
【2】小锦鲤奖
一等奖 优车出行小汽车一个月使用权1个（价值1200元/辆）
二等奖：优车出行小汽车半个月使用权4个（价 值600元/辆）
三等奖：银花电商魅可口红14支（价值170元/支）
四等奖：贵安新区华侨城VR小镇门票10张（携程价88元/张）
五等奖：中医销巴超市50元无门槛购物券8张
六等奖：华硕顽石五代F/A580UR（京东价4980）1000元抵用券30张
七等奖：视频会员卡年卡20张（价值268元/张）
幸运奖：吉源驾校1400优惠券100张
所有奖品总价值：154420元
中奖总人数：188人
「 邀请你来填写金数据表单《优车绿色出行大使抽选活动报名》https://jinshuju.net/f/SggtRk 」";
            $result = $this->transmitText($object, $content);
            return $result;
        }
    }

    //接收图片消息
    private function receiveImage($object)
    {
        $content = array("MediaId" => $object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object)
    {
        $content = "你发送的是位置，经度为：" . $object->Location_Y . "；纬度为：" . $object->Location_X . "；缩放级别为：" . $object->Scale . "；位置为：" . $object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition)) {
            $content = "你刚才说的是：" . $object->Recognition;
            $result = $this->transmitText($object, $content);
        } else {
            $content = array("MediaId" => $object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    //接收视频消息
    private function receiveVideo($object)
    {
        $content = array("MediaId" => $object->MediaId, "ThumbMediaId" => $object->ThumbMediaId, "Title" => "", "Description" => "");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：" . $object->Title . "；内容为：" . $object->Description . "；链接地址为：" . $object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)) {
            return "";
        }

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if (!is_array($newsArray)) {
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
        $item_str = "";
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>
$item_str    </Articles>
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray)
    {
        if (!is_array($musicArray)) {
            return "";
        }
        $itemTpl = "<Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[music]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
        <MediaId><![CDATA[%s]]></MediaId>
    </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[image]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "<Voice>
        <MediaId><![CDATA[%s]]></MediaId>
    </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[voice]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
    </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[video]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复多客服消息
    private function transmitService($object)
    {
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复第三方接口消息
    private function relayPart3($url, $rawData)
    {
        $headers = array("Content-Type: text/xml; charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //字节转Emoji表情
    function bytes_to_emoji($cp)
    {
        if ($cp > 0x10000) {       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)) . chr(0x80 | (($cp & 0x3F000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x800) {   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x80) {    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else {                    # 1 byte
            return chr($cp);
        }
    }

    //日志记录
    private function logger($log_content)
    {
        if (isset($_SERVER['HTTP_APPNAME'])) {   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        } else if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1") { //LOCAL
            $max_size = 1000000;
            $log_filename = "log.xml";
            if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
                unlink($log_filename);
            }
            file_put_contents($log_filename, date('Y-m-d H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
        }
    }

}

?>