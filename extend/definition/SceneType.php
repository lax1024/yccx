<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *公众号自定义二维码类型
 */
abstract class SceneType
{
    //1审核中 2维修中 3已下线 4正常上线 5使用中
    public static $SceneTypeCar = ['code' => 'c', 'name' => '共享汽车'];
    public static $SceneTypeEle = ['code' => 'e', 'name' => '充电桩'];
    public static $SceneTypeTG = ['code' => 't', 'name' => '渠道推广'];

    public static $CARCOLOR_CODE = [
        'c' => '共享汽车',
        'e' => '充电桩',
        't' => '渠道推广',
    ];
}