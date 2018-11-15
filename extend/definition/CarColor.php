<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车辆颜色定义
 */
abstract class CarColor
{
    //1审核中 2维修中 3已下线 4正常上线 5使用中
    public static $CarColorBlue = ['code' => 1, 'name' => '蓝色'];
    public static $CarColorYellow = ['code' => 2, 'name' => '黄色'];
    public static $CarColorBack = ['code' => 3, 'name' => '黑色'];
    public static $CarColorWi = ['code' => 4, 'name' => '白色'];
    public static $CarColorOther = ['code' => 9, 'name' => '其他'];

    public static $CARCOLOR_CODE = [
        1 => '蓝色',
        2 => '黄色',
        3 => '黑色',
        4 => '白色',
        9 => '其他'
    ];
}