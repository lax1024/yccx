<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *车辆等级分类
 */
abstract class CarGrade
{
    public static $CarGradeHot = ['code' => 1, 'name' => '热销'];
    public static $CarGradeEconomy = ['code' => 2, 'name' => '经济型'];
    public static $CarGradeComfortable = ['code' => 3, 'name' => '舒适型'];
    public static $CarGradeBusiness = ['code' => 4, 'name' => '商务车'];
    public static $CARGRADE_CODE = [
        1 => '热销',
        2 => '经济型',
        3 => '舒适型',
        4 => '商务车'
    ];
}