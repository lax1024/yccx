<?php
namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *客户余额类型 定义
 */
abstract class BalanceType
{
    public static $BalanceTypeADD = ['code' => 'ADD', 'name' => '获得'];
    public static $BalanceTypeSUB = ['code' => 'SUB', 'name' => '减少'];
    public static $BalanceTypePAY = ['code' => 'PAY', 'name' => '支付'];
    public static $BalanceTypeREFUND = ['code' => 'REFUND', 'name' => '退款'];
    public static $LOG_TYPE_CODE = [
        'ADD' => '获得',
        'SUB' => '减少',
        'PAY' => '支付',
        'REFUND' => '退款'
    ];
}