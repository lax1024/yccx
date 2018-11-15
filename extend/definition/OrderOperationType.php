<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车辆状态分类
 */
abstract class OrderOperationType
{
    //1补电 2保洁 3掉线
    public static $OrderTypeRecharge = ['code' => 1, 'name' => '补电+小保洁'];
    public static $OrderTypeClean = ['code' => 2, 'name' => '大保洁'];
    public static $OrderTypeDispatch = ['code' => 3, 'name' => '调度'];
    public static $OrderTypeLost = ['code' => 6, 'name' => '小电瓶+掉线'];
    public static $OrderTypeMove = ['code' => 7, 'name' => '移车'];

    public static $ORDEROPERATIONTYPE_CODE = [
        1 => '补电+小保洁',
        2 => '大保洁',
        3 => '调度',
        6 => '小电瓶+掉线',
        7 => '移车',
    ];
}