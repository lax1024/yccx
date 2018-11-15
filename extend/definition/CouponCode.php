<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *代金券获取方式代码
 */
abstract class CouponCode
{
    //支付方式名称代码 platformp 平台发放 activity 活动获取 merchant 商户发放
    public static $CouponCodePlatformp = ['code' => 'platformp', 'name' => '平台发放'];
    public static $CouponCodeActivity = ['code' => 'activity', 'name' => '活动获取'];
    public static $CouponCodeMerchant = ['code' => 'merchant', 'name' => '商户发放'];

    public static $COUPONCODE_CODE = [
        'platformp' => '平台发放',
        'activity' => '活动获取',
        'merchant' => '商户发放',
    ];
}