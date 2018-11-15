<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *用户状态
 */
abstract class CustomerStatus
{
    //用户状态 0账号待审核 1账号正常 2账号锁定 3账号审核失败 4账号审核中
    public static $CustomerStatusWait = ['code' => 0, 'name' => '待提交实名制'];
    public static $CustomerStatusNormal = ['code' => 1, 'name' => '账号正常'];
    public static $CustomerStatusLock = ['code' => 2, 'name' => '账号锁定'];
    public static $CustomerStatusFailure = ['code' => 3, 'name' => '账号审核失败'];
    public static $CustomerStatusCheck = ['code' => 4, 'name' => '账号审核中'];
    public static $CustomerStatusDriving = ['code' => 5, 'name' => '驾驶证分数扣完'];

    public static $CUSTOMERSTATUS_CODE = [
        0 => '待提交',
        1 => '账号正常',
        2 => '账号锁定',
        3 => '账号审核失败',
        4 => '账号审核中',
        5 => '驾驶证分数扣完'
    ];
}