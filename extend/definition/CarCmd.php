<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车辆控制
 */
abstract class CarCmd
{
    //1寻车 2开门 3关门 4点火 5熄火 6取车 7还车
    public static $CarCmdFind = ['code' => 1, 'name' => '寻车'];
    public static $CarCmdOpenDoor = ['code' => 2, 'name' => '开门'];
    public static $CarCmdCloseDoor = ['code' => 3, 'name' => '关门'];
    public static $CarCmdIgnite = ['code' => 4, 'name' => '点火'];
    public static $CarCmdFlameout = ['code' => 5, 'name' => '熄火'];
    public static $CarCmdAcquire = ['code' => 6, 'name' => '取车'];
    public static $CarCmdReturn = ['code' => 7, 'name' => '还车'];

    public static $CARCMD_CODE = [
        1 => '寻车',
        2 => '开门',
        3 => '关门',
        4 => '点火',
        5 => '熄火',
        6 => '取车',
        7 => '还车',
    ];
}