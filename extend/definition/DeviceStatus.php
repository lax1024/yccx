<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *设备状态
 */
abstract class DeviceStatus
{
    //1审核中 2维修中 3未绑定 4已绑定 5已锁定
    public static $DeviceCheck = ['code' => 1, 'name' => '审核中'];
    public static $DeviceMaintain = ['code' => 2, 'name' => '维修中'];
    public static $DeviceOnBind = ['code' => 3, 'name' => '已审核'];
    public static $DeviceBind = ['code' => 4, 'name' => '已绑定'];
    public static $DeviceLock = ['code' => 5, 'name' => '已锁定'];
    public static $DEVICESTATUS_CODE = [
        1 => '审核中',
        2 => '维修中',
        3 => '已审核',
        4 => '已绑定',
        5 => '已锁定'
    ];
}