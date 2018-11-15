<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 15:25
 */

namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Request;
use wxapi\WxApi;
use wxapi\WxApiConfig;

class Wecharapi extends HomeBase
{
    private $encodingAesKey;
    private $token;
    private $appId;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->encodingAesKey = WxApiConfig::$encodingAesKey;
        $this->token = WxApiConfig::$token;
        $this->appId = WxApiConfig::$appId;
    }

    public function index()
    {
//        $get_data = $this->request->get(['echostr', 'signature', 'timestamp', 'nonce']);
        $WxApi = new WxApi();
        $postStr = file_get_contents("php://input");
        $WxApi->responseMsg($postStr);
//        if ($WxApi->valid($get_data)) {
//            $postStr = file_get_contents("php://input");
//            $WxApi->responseMsg($postStr);
//        }
    }
}