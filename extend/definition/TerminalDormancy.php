<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车机设备休眠时间 对于表
 */
abstract class TerminalDormancy
{
    //T_1MIN		0x2EE0		//(T_1S*60)
//T_2MIN		0x5DC0		//(T_1S*60)
//T_3MIN		0x8CA0		//(T_1MIN*5)
//T_4MIN		0xBB80		//(T_1MIN*5)
//T_5MIN		0xEA60		//(T_1MIN*5)
//T_6MIN		0x11940		//(T_1MIN*5)
//T_7MIN		0x14820		//(T_1MIN*5)
//T_8MIN		0x17700		//(T_1MIN*5)
//T_10MIN		0x1D4C0		//(T_1MIN*10)
//T_12MIN		0x23280		//(T_1MIN*10)
//T_15MIN		0x2BF20		//(T_1MIN*10)
    public static $TerminalDormancy1 = ['code' => 1, 'name' => '1分钟', 'value' => '00002EE0'];
    public static $TerminalDormancy2 = ['code' => 2, 'name' => '2分钟', 'value' => '00005DC0'];
    public static $TerminalDormancy3 = ['code' => 3, 'name' => '3分钟', 'value' => '00008CA0'];
    public static $TerminalDormancy4 = ['code' => 4, 'name' => '4分钟', 'value' => '0000BB80'];
    public static $TerminalDormancy5 = ['code' => 5, 'name' => '5分钟', 'value' => '0000EA60'];
    public static $TerminalDormancy6 = ['code' => 6, 'name' => '6分钟', 'value' => '00011940'];
    public static $TerminalDormancy7 = ['code' => 7, 'name' => '7分钟', 'value' => '00014820'];
    public static $TerminalDormancy8 = ['code' => 7, 'name' => '8分钟', 'value' => '00017700'];
    public static $TerminalDormancy10 = ['code' => 10, 'name' => '10分钟', 'value' => '0001D4C0'];
    public static $TerminalDormancy12 = ['code' => 12, 'name' => '12分钟', 'value' => '00023280'];
    public static $TerminalDormancy15 = ['code' => 15, 'name' => '15分钟', 'value' => '0002BF20'];
    public static $TERMiNAlDORMANCY_CODE = [
        1 => ['name' => '1分钟', 'value' => '00002EE0'],
        2 => ['name' => '2分钟', 'value' => '00005DC0'],
        3 => ['name' => '3分钟', 'value' => '00008CA0'],
        4 => ['name' => '4分钟', 'value' => '0000BB80'],
        5 => ['name' => '5分钟', 'value' => '0000EA60'],
        6 => ['name' => '6分钟', 'value' => '00011940'],
        7 => ['name' => '7分钟', 'value' => '00014820'],
        8 => ['name' => '8分钟', 'value' => '00017700'],
        10 => ['name' => '10分钟', 'value' => '0001D4C0'],
        12 => ['name' => '12分钟', 'value' => '00023280'],
        15 => ['name' => '15分钟', 'value' => '0002BF20'],
    ];
}