<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车机设备车类型
 */
abstract class TerminalCarType
{
    //1北汽EC200，2EQ1_OLD小蚂蚁，3奇瑞EQ，4东方汽车，5众泰100
    public static $CarTypeBQEC200 = ['code' => 1, 'name' => '北汽EC200', 'value' => 'FD21'];
    public static $CarTypeQREQ1 = ['code' => 2, 'name' => 'EQ1_OLD小蚂蚁', 'value' => 'FD22'];
    public static $CarTypeQRQE = ['code' => 3, 'name' => '奇瑞EQ', 'value' => 'FD26'];
    public static $CarTypeDFQC = ['code' => 4, 'name' => '东方汽车', 'value' => 'FD27'];
    public static $CarTypeZT100 = ['code' => 5, 'name' => '众泰100', 'value' => 'FD28'];
    public static $CarTypeYQQ1 = ['code' => 6, 'name' => '云雀Q1', 'value' => 'FD29'];
    public static $CarTypeZT100S = ['code' => 7, 'name' => '众泰100S', 'value' => 'FD2A'];
    public static $CARDEVICETYPE_CODE = [
        1 => ['name' => '北汽EC200', 'value' => 'FD21'],
        2 => ['name' => 'EQ1_OLD小蚂蚁', 'value' => 'FD22'],
        3 => ['name' => '奇瑞EQ', 'value' => 'FD26'],
        4 => ['name' => '东方汽车', 'value' => 'FD27'],
        5 => ['name' => '众泰100', 'value' => 'FD28'],
        6 => ['name' => '云雀Q1', 'value' => 'FD29'],
        7 => ['name' => '众泰100S', 'value' => 'FD2A'],
    ];
}