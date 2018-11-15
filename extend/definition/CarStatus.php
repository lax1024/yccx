<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车辆状态分类
 */
abstract class CarStatus
{
    //1审核中 2维修中 3已下线 4正常上线 5使用中 6维护中 7长租用
    public static $CarStatusCheck = ['code' => 1, 'name' => '审核中'];
    public static $CarStatusMaintain = ['code' => 2, 'name' => '维修中'];
    public static $CarStatusLogoff = ['code' => 3, 'name' => '已下线'];
    public static $CarStatusNormal = ['code' => 4, 'name' => '正常上线'];
    public static $CarStatusInuse = ['code' => 5, 'name' => '使用中'];
    public static $CarStatusVindicate = ['code' => 6, 'name' => '维护中'];
    public static $CarStatusRent = ['code' => 7, 'name' => '长租用'];

    public static $CARSTATUS_CODE = [
        1 => '审核中',
        2 => '维修中',
        3 => '已下线',
        4 => '正常上线',
        5 => '使用中',
        6 => '维护中',
        7 => '长租用',
    ];
}