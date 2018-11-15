<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *订单状态定义
 */
abstract class OrderStatus
{
    public static $OrderStatusCanceled = ['code' => 0, 'name' => '已取消'];
    public static $OrderStatusNopayment = ['code' => 10, 'name' => '未付款'];
    public static $OrderStatusPayment = ['code' => 20, 'name' => '已付款'];
    public static $OrderStatusAcquire = ['code' => 30, 'name' => '使用中'];
    public static $OrderStatusReturn = ['code' => 40, 'name' => '已归还'];
    public static $OrderStatusFinish = ['code' => 50, 'name' => '订单完成'];
    public static $ORDER_STATUS_CODE = [
        0 => '已取消',
        10 => '未付款',
        20 => '已付款',
        30 => '使用中',
        40 => '已归还',
        50 => '订单完成'
    ];
    //0(已取消)10(默认):已接单;30:已取商品;40:归还商品;50完成
    public static $ORDER_OP_STATUS_CODE = [
        0 => '已取消',
        10 => '已接单',
        20 => '未知',
        30 => '维护中',
        40 => '已归还',
        50 => '任务完成'
    ];
}