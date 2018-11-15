<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 * 车辆维护类型
 */
abstract class CarOperationType
{
    //故障类型 1续航低于90km  2车机掉线  3小电瓶电压过低 6停车费用 7平台任务
    public static $CarOperationTypeEOL = ['code' => 1, 'name' => '续航低于设定'];
    public static $CarOperationTypeLost = ['code' => 2, 'name' => '车机掉线'];
    public static $CarOperationTypeLow = ['code' => 3, 'name' => '小电瓶电压过低'];
    public static $CarOperationTypePark = ['code' => 6, 'name' => '停车费用'];
    public static $CarOperationTypePla = ['code' => 7, 'name' => '平台任务'];
    public static $CAROPERATIONTYPE_CODE = [
        1 => '续航低于设定',
        2 => '车机掉线',
        3 => '小电瓶电压过低',
        6 => '停车费用',
        7 => '平台任务',
    ];
}