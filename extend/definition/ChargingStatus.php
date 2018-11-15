<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *充电桩状态分类
 */
abstract class ChargingStatus
{
    //1审核中 2维修中 3已下线 4正常上线 5使用中
    public static $ChargingStatusCheck = ['code' => 1, 'name' => '审核中'];
    public static $ChargingStatusMaintain = ['code' => 2, 'name' => '维修中'];
    public static $ChargingStatusLogoff = ['code' => 3, 'name' => '已下线'];
    public static $ChargingStatusNormal = ['code' => 4, 'name' => '正常上线'];
    public static $ChargingStatusInuse = ['code' => 5, 'name' => '使用中'];

    public static $CHARGINGSTATUS_CODE = [
        1 => '审核中',
        2 => '维修中',
        3 => '已下线',
        4 => '正常上线',
        5 => '使用中'
    ];
}