<?php

namespace tool;

/**
 *表数据复制分析 数据模型
 */

use app\common\model\Order;
use definition\OrderStatus;
use think\Db;

class TableTool
{
    /**
     * 创建表
     * @param $end_date
     * @param $m
     * @return bool
     */
    public function copyTable($end_date, $m = 0)
    {
        $sql_str = file_get_contents('./public/sql/ycdl_order_analyze.sql');
        $end_date_in = str_replace("-", '', $end_date);
        $_arr = explode(';', $sql_str);
        foreach ($_arr as $num => $v) {
            $v = trim($v);
            if (!empty($v)) {
                $v = str_ireplace('_201808', '_' . $end_date_in . "_" . $m, $v);
                $ret = Db::execute($v);
                if (!empty($ret)) {
                    return false;
                }
            }
        }
        $this->copyData($end_date);
        return true;
    }

    public function copyData($end_date, $m = 0)
    {
        $map = [
            'acquire_month' => $end_date,
            'order_status' => OrderStatus::$OrderStatusFinish['code']
        ];
        $end_date_int = str_replace("-", '', $end_date);
        $order_model = new Order();
        $field = "store_id,store_name,customer_id,customer_phone,customer_name";
        $field .= ",payment_code,payment_time,order_amount,goods_id,goods_device";
        $field .= ",goods_name,goods_type,goods_licence_plate,acquire_time,acquire_month";
        $field .= ",acquire_date,acquire_hour,acquire_week,acquire_address,acquire_store_id";
        $field .= ",acquire_store_name,return_time,return_month,return_date,return_hour";
        $field .= ",return_week,all_mileage,reality_return_time,return_address,return_store_id";
        $field .= ",return_store_name,order_status,channel_uid,store_key_id,store_key_name";
        $order_data = $order_model->where($map)->field($field)->select();
        foreach ($order_data as $v) {
            $in_data = json_decode(json_encode($v), true);
            $in_data['year_week'] = date("Y-W",strtotime($in_data['return_date']." 00:00:00"));
            Db::table("ycdl_order_analyze_" . $end_date_int . "_" . $m)->insert($in_data);
        }
    }

    /**
     * 店铺取车热力数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function acquireStore($date_int, $m = 0)
    {
        $sql = "SELECT acquire_address,acquire_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY acquire_store_id;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['acquire_address'] = unserialize($value['acquire_address']);
            $value['acquire_address']['name'] = $value['acquire_store_name'];
            $value['acquire_address']['count'] = $value['count'];
            $order_data_arr[] = $value['acquire_address'];
        }
        return $order_data_arr;
    }

    /**
     * 店铺还车热力数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function returnStore($date_int, $m = 0)
    {
        $sql = "SELECT return_address,return_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY return_store_id;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['return_address'] = unserialize($value['return_address']);
            $value['return_address']['name'] = $value['return_store_name'];
            $value['return_address']['count'] = $value['count'];
            $order_data_arr[] = $value['return_address'];
        }
        return $order_data_arr;
    }

    /**
     * 按照星期分布取车数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function weekAcquireData($date_int, $m = 0)
    {
        $sql = "SELECT acquire_address,acquire_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY acquire_week;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['acquire_address'] = unserialize($value['acquire_address']);
            $value['acquire_address']['name'] = $value['acquire_store_name'];
            $value['acquire_address']['count'] = $value['count'];
            $order_data_arr[] = $value['acquire_address'];
        }
        return $order_data_arr;
    }

    /**
     * 按照星期分布还车车数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function weekReturnData($date_int, $m = 0)
    {
        $sql = "SELECT return_address,return_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY return_week;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['return_address'] = unserialize($value['return_address']);
            $value['return_address']['name'] = $value['return_store_name'];
            $value['return_address']['count'] = $value['count'];
            $order_data_arr[] = $value['return_address'];
        }
        return $order_data_arr;
    }

    /**
     * 取车时间节点与站点关系数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function hourAcquireData($date_int, $m = 0)
    {
        $sql = "SELECT acquire_address,acquire_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY acquire_hour;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['acquire_address'] = unserialize($value['acquire_address']);
            $value['acquire_address']['name'] = $value['acquire_store_name'];
            $value['acquire_address']['count'] = $value['count'];
            $order_data_arr[] = $value['acquire_address'];
        }
        return $order_data_arr;
    }

    /**
     * 还车时间节点与站点关系数据
     * @param $date_int
     * @param $m
     * @return array
     */
    public function hourReturnData($date_int, $m = 0)
    {
        $sql = "SELECT return_address,return_store_name,COUNT(id) as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY return_hour,return_store_id;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['return_address'] = unserialize($value['return_address']);
            $value['return_address']['name'] = $value['return_store_name'];
            $value['return_address']['count'] = $value['count'];
            $order_data_arr[] = $value['return_address'];
        }
        return $order_data_arr;
    }

    /**
     * 取车站点金额统计
     * @param $date_int
     * @param int $m
     * @return array
     */
    public function acquireAmountStore($date_int, $m = 0)
    {
        $sql = "SELECT acquire_address,acquire_store_name,SUM(order_amount)as `count` FROM " . " ycdl_order_analyze_" . $date_int . "_" . $m . " GROUP BY acquire_store_id;";
        $order_data = Db::query($sql);
        $order_data_arr = [];
        foreach ($order_data as &$value) {
            $value['acquire_address'] = unserialize($value['acquire_address']);
            $value['acquire_address']['name'] = $value['acquire_store_name'];
            $value['acquire_address']['count'] = $value['count'];
            $order_data_arr[] = $value['acquire_address'];
        }
        return $order_data_arr;
    }
}