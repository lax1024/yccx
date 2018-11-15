<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *注册类型
 */
abstract class RegistType
{
    public static $RegistTypeAPP = ['code' => 1, 'name' => 'APP'];
    public static $RegistTypePC = ['code' => 2, 'name' => 'PC'];
    public static $RegistTypeWeChar = ['code' => 3, 'name' => 'WeChar'];
    public static $RegistTypeQQ = ['code' => 4, 'name' => 'QQ'];
    public static $RegistTypeAdmin = ['code' => 5, 'name' => 'Admin'];
    public static $REGISTTYPE_CODE = [
        1 => 'APP',
        2 => 'PC',
        3 => 'WeChar',
        4 => 'QQ',
        5 => 'Admin'
    ];
}