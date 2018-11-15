<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车机设备状态
 */
abstract class CarDeviceStatus
{
    //1审核中 2维修中 3未绑定 4已绑定 5已锁定
    public static $CarDeviceCheck = ['code' => 1, 'name' => '审核中'];
    public static $CarDeviceMaintain = ['code' => 2, 'name' => '维修中'];
    public static $CarDeviceOnBind = ['code' => 3, 'name' => '未绑定'];
    public static $CarDeviceBind = ['code' => 4, 'name' => '已绑定'];
    public static $CarDeviceLock = ['code' => 5, 'name' => '已锁定'];
    public static $CARDEVICESTATUS_CODE = [
        1 => '审核中',
        2 => '维修中',
        3 => '未绑定',
        4 => '已绑定',
        5 => '已锁定'
    ];
}