<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *押金渠道类型 定义
 */
abstract class ChannelType
{
    //1支付宝 2微信支付 3银行冻结
    public static $ChannelTypeAlipay = ['code' => 1, 'name' => '支付宝'];
    public static $ChannelTypeWechar = ['code' => 2, 'name' => '微信支付'];
    public static $ChannelTypeBank = ['code' => 3, 'name' => '银行冻结'];
    public static $CHANNEl_TYPE_CODE = [
        1 => '支付宝',
        2 => '微信支付',
        3 => '银行冻结'
    ];
}