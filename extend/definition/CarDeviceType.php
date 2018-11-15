<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车机设备类型
 */
abstract class CarDeviceType
{
    //1常规汽车 2纯电动车
    public static $CarDeviceTypeDefault = ['code' => 1, 'name' => '常规汽车'];
    public static $CarDeviceTypeElectricity = ['code' => 2, 'name' => '纯电动车'];

    public static $CARDEVICETYPE_CODE = [
        1 => '常规汽车',
        2 => '纯电动车'
    ];
}