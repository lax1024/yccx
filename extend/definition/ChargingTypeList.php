<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *充电桩设备具体类型
 */
abstract class ChargingTypeList
{
    //1常规充电桩 2定制型充电桩
    public static $ChargingTypeList0 = ['code' => 0, 'name' => '落地式交流充电桩'];
    public static $ChargingTypeList1 = ['code' => 1, 'name' => '壁挂式交流充电桩'];
    public static $ChargingTypeList2 = ['code' => 2, 'name' => '单枪分体式充电机'];
    public static $ChargingTypeList3 = ['code' => 3, 'name' => '双枪分体式充电机'];
    public static $ChargingTypeList4 = ['code' => 4, 'name' => '单枪一体式充电机'];
    public static $ChargingTypeList5 = ['code' => 5, 'name' => '双枪一体式充电机'];
    public static $ChargingTypeList6 = ['code' => 6, 'name' => '四枪一体式充电机'];
    public static $ChargingTypeList7 = ['code' => 7, 'name' => '单枪箱式终端'];
    public static $ChargingTypeList8 = ['code' => 8, 'name' => '双枪箱式终端'];
    public static $CHARGINGTYPELIST_CODE = [
        0 => '落地式交流充电桩',
        1 => '壁挂式交流充电桩',
        2 => '单枪分体式充电机',
        3 => '双枪分体式充电机',
        4 => '单枪一体式充电机',
        5 => '双枪一体式充电机',
        6 => '四枪一体式充电机',
        7 => '单枪箱式终端',
        8 => '双枪箱式终端'
    ];
}