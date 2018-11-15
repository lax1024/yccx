<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *支付代码
 */
abstract class PayCode
{
    public static $PayCodeBalance = ['code' => 'balance', 'name' => '余额支付'];
    public static $PayCodeWeixin = ['code' => 'weixin', 'name' => '微信支付'];
    public static $PayCodeAlipay = ['code' => 'alipay', 'name' => '支付宝支付'];
    public static $PayCodeUnionpay = ['code' => 'unionpay', 'name' => '银联支付'];
    public static $PayCodeBody = "优车动力在线支付";

    public static $PAY_CODE = [
        'balance' => '余额支付',
        'weixin' => '微信支付',
        'alipay' => '支付宝支付',
        'unionpay' => '银联支付'
    ];
}