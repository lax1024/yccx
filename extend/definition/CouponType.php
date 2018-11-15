<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *代金券面值
 */
abstract class CouponType
{
    //支付方式名称代码 platformp 平台发放 activity 活动获取 merchant 商户发放
    public static $CouponTypePrice1 = ['code' => 1, 'name' => '1元代金券'];
    public static $CouponTypePrice2 = ['code' => 2, 'name' => '2元代金券'];
    public static $CouponTypePrice5 = ['code' => 5, 'name' => '5元代金券'];
    public static $CouponTypePrice10 = ['code' => 10, 'name' => '10元代金券'];
    public static $CouponTypePrice15 = ['code' => 15, 'name' => '15元代金券'];
    public static $CouponTypePrice20 = ['code' => 20, 'name' => '20元代金券'];
    public static $CouponTypePrice30 = ['code' => 30, 'name' => '30元代金券'];
    public static $CouponTypePrice40 = ['code' => 40, 'name' => '40元代金券'];
    public static $CouponTypePrice50 = ['code' => 50, 'name' => '50元代金券'];
    public static $CouponTypePrice100 = ['code' => 100, 'name' => '100元代金券'];
    public static $PAY_CODE = [
        1 => '1元代金券',
        2 => '2元代金券',
        5 => '5元代金券',
        10 => '10元代金券',
        15 => '15元代金券',
        20 => '20元代金券',
        30 => '30元代金券',
        40 => '40元代金券',
        50 => '50元代金券',
        100 => '100元代金券'
    ];
}