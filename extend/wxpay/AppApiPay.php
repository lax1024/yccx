<?php

namespace wxpay;

use wxpay\database\WxPayAppApiPay;

/**
 * APP支付实现类
 * 该类实现了从微信公众平台获取code、通过code获取openid和access_token、
 * 生成jsapi支付js接口所需的参数、生成获取共享收货地址所需的参数
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发
 *
 * Class AppApiPay
 * @package wxpay
 * @author goldeagle
 */
class AppApiPay
{
    public $data = null;
    public $curl_timeout = 5;

    /**
     *
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws WxPayException
     *
     * @return string json数据，可直接填入js函数作为参数
     */
    /**
     * @param $UnifiedOrderResult
     * @return array
     * @throws WxPayException
     */
    public function getAppParameters($UnifiedOrderResult)
    {
        if (!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == ""
        ) {
            throw new WxPayException("参数错误");
        }
        $appapi = new WxPayAppApiPay();
        $appapi->setAppid($UnifiedOrderResult["appid"]);
        $appapi->setMchId($UnifiedOrderResult["mch_id"]);
        $appapi->setPrepayid($UnifiedOrderResult["prepay_id"]);
        $appapi->setPackage("Sign=WXPay");
        $appapi->setNonceStr(WxPayApi::getNonceStr());
        $appapi->setTimeStamp(time());
        $appapi->setSign($appapi->makeSign());
        $parameters = $appapi->getValues();
        return $parameters;
    }
}