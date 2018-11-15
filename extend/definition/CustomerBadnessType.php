<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *用户不良记录类型
 */
abstract class CustomerBadnessType
{
    //1挡住他人车辆。2停放于违停区域。3车辆被损坏。4其他
    public static $CustomerBadnessBlock = ['code' => 1, 'name' => '挡住他人车辆'];
    public static $CustomerBadnessPark = ['code' => 2, 'name' => '停放于违停区域'];
    public static $CustomerBadnessDestroy = ['code' => 3, 'name' => '车辆被损坏'];
    public static $CustomerBadnessOther = ['code' => 4, 'name' => '其他'];

    public static $CUSTOMERSTATUS_CODE = [
        1 => '挡住他人车辆',
        2 => '停放于违停区域',
        3 => '车辆被损坏',
        4 => '其他',
    ];
}