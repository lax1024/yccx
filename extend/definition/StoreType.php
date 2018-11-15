<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *店铺类型
 */
abstract class StoreType
{
    public static $StoreTypeCar = ['code' => 1, 'name' => '常规汽车站点'];
    public static $StoreTypeElectrocar = ['code' => 2, 'name' => '新能源站点'];
    public static $StoreTypeCharging = ['code' => 3, 'name' => '充电桩站点'];
    public static $STORETYPE_CODE = [
        1 => '常规汽车站点',
        2 => '新能源站点',
        3 => '充电桩站点'
    ];
}