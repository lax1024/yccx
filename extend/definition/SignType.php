<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *标记类型
 */
abstract class SignType
{
    public static $GoodsTypeCar = ['code' => 1, 'name' => '充电桩站点'];
    public static $GoodsTypeElectrocar = ['code' => 2, 'name' => '取还车站点'];
    public static $GOODSTYPE_CODE = [
        1 => '充电桩站点',
        2 => '取还车站点'
    ];
}