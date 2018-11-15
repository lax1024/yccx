<?php

namespace definition;
/**
 * Created by PhpStorm.
 * User: LongAnxiang
 * Date: 2018/3/13
 * Time: 21:29
 *商品类型
 */
abstract class GoodsType
{
    public static $GoodsTypeCar = ['code' => 1, 'name' => '普通汽车'];
    public static $GoodsTypeElectrocar = ['code' => 2, 'name' => '电动车'];
    public static $GoodsTypeCharging  = ['code' => 3, 'name' => '充电桩'];
    public static $GOODSTYPE_CODE = [
        1 => '普通汽车',
        2 => '电动车',
        3 => '充电桩'
    ];
}