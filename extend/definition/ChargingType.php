<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *充电桩设备类型
 */
abstract class ChargingType
{
    //1常规充电桩 2定制型充电桩
    public static $ChargingTypeDefault = ['code' => 1, 'name' => '慢充电桩'];
    public static $ChargingTypeFast = ['code' => 2, 'name' => '快充电桩'];
    public static $ChargingTypeIndiv = ['code' => 3, 'name' => '定制型充电桩'];
    public static $CHARGINGTYPE_CODE = [
        1 => '慢充电桩',
        2 => '快充电桩',
        3 => '定制型充电桩'
    ];
}