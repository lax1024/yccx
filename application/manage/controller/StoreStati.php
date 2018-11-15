<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\Order as OrderModel;
use app\common\model\Statistics;
use app\common\model\StoreLeisure;

/**
 * 站点统计情况
 * Class Slide
 * @package app\manage\controller
 */
class StoreStati extends AdminBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 站点统计情况
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $order_model = new OrderModel();
        $day = date("Y-m-d", intval(time() - 3600 * 24));
        $acquire_data_temp = $order_model->where(['acquire_date' => $day])->field('acquire_store_id,acquire_store_name,count(id) count')->group('acquire_store_id')->select();
        $acquire_data_temp = json_encode($acquire_data_temp);
        $acquire_data_temp = json_decode($acquire_data_temp, true);
        $acquire_data_temp = two_sort($acquire_data_temp, 'count', SORT_DESC);
        $day_acquire_data = [];
        $day_acquire_store = [];
        for ($i = 0; $i < 15; $i++) {
            $day_acquire_data[] = $acquire_data_temp[$i]['count'];
            $day_acquire_store[] = $acquire_data_temp[$i]['acquire_store_name'];
        }
        $week = date("Y-") . str_pad(date("W") - 1, 2, "0", STR_PAD_LEFT);
        $acquire_data_temp = $order_model->where(['acquire_year_week' => $week])->field('acquire_store_id,acquire_store_name,count(id) count')->group('acquire_store_id')->select();
        $acquire_data_temp = json_encode($acquire_data_temp);
        $acquire_data_temp = json_decode($acquire_data_temp, true);
        $acquire_data_temp = two_sort($acquire_data_temp, 'count', SORT_DESC);
        $week_acquire_data = [];
        $week_acquire_store = [];
        for ($i = 0; $i < 15; $i++) {
            $week_acquire_data[] = $acquire_data_temp[$i]['count'];
            $week_acquire_store[] = $acquire_data_temp[$i]['acquire_store_name'];
        }
        $month = date("Y-m", strtotime("last month"));
        $acquire_data_temp = $order_model->where(['acquire_month' => $month])->field('acquire_store_id,acquire_store_name,count(id) count')->group('acquire_store_id')->select();
        $acquire_data_temp = json_encode($acquire_data_temp);
        $acquire_data_temp = json_decode($acquire_data_temp, true);
        $acquire_data_temp = two_sort($acquire_data_temp, 'count', SORT_DESC);
        $month_acquire_data = [];
        $month_acquire_store = [];
        for ($i = 0; $i < 15; $i++) {
            $month_acquire_data[] = $acquire_data_temp[$i]['count'];
            $month_acquire_store[] = $acquire_data_temp[$i]['acquire_store_name'];
        }
        $acquire_data = [
            'day' => json_encode($day_acquire_data),
            'week' => json_encode($week_acquire_data),
            'month' => json_encode($month_acquire_data)
        ];
        $acquire_store = [
            'day' => json_encode($day_acquire_store),
            'week' => json_encode($week_acquire_store),
            'month' => json_encode($month_acquire_store)
        ];
        return $this->fetch('index', ['acquire_data' => $acquire_data, 'acquire_store' => $acquire_store]);

    }

    /**
     * 站点统计情况
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function creturn()
    {
        $order_model = new OrderModel();
        $day = date("Y-m-d", intval(time() - 3600 * 24));
        $return_data_temp = $order_model->where(['return_date' => $day])->field('return_store_id,return_store_name,count(id) count')->group('return_store_id')->select();
        $return_data_temp = json_encode($return_data_temp);
        $return_data_temp = json_decode($return_data_temp, true);
        $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
        $day_return_data = [];
        $day_return_store = [];
        for ($i = 0; $i < 15; $i++) {
            $day_return_data[] = $return_data_temp[$i]['count'];
            $day_return_store[] = $return_data_temp[$i]['return_store_name'];
        }
        $week = date("Y-") . str_pad(date("W") - 1, 2, "0", STR_PAD_LEFT);
        $return_data_temp = $order_model->where(['return_year_week' => $week])->field('return_store_id,return_store_name,count(id) count')->group('return_store_id')->select();
        $return_data_temp = json_encode($return_data_temp);
        $return_data_temp = json_decode($return_data_temp, true);
        $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
        $week_return_data = [];
        $week_return_store = [];
        for ($i = 0; $i < 15; $i++) {
            $week_return_data[] = $return_data_temp[$i]['count'];
            $week_return_store[] = $return_data_temp[$i]['return_store_name'];
        }
        $month = date("Y-m", strtotime("last month"));
        $return_data_temp = $order_model->where(['return_month' => $month])->field('return_store_id,return_store_name,count(id) count')->group('return_store_id')->select();
        $return_data_temp = json_encode($return_data_temp);
        $return_data_temp = json_decode($return_data_temp, true);
        $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
        $month_return_data = [];
        $month_return_store = [];
        for ($i = 0; $i < 15; $i++) {
            $month_return_data[] = $return_data_temp[$i]['count'];
            $month_return_store[] = $return_data_temp[$i]['return_store_name'];
        }
        $return_data = [
            'day' => json_encode($day_return_data),
            'week' => json_encode($week_return_data),
            'month' => json_encode($month_return_data)
        ];
        $return_store = [
            'day' => json_encode($day_return_store),
            'week' => json_encode($week_return_store),
            'month' => json_encode($month_return_store)
        ];
        return $this->fetch('creturn', ['return_data' => $return_data, 'return_store' => $return_store]);
    }

    /**
     * 站点统计情况
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function stand()
    {
        $store_model = new StoreLeisure();
        $day = date("Y-m-d", intval(time() - 3600 * 24));
        $return_data_temp = $store_model->where(['date_e' => $day])->field('store_id,store_name,sum(hour) count')->group('store_id')->select();
        $return_data_temp = json_encode($return_data_temp);
        $return_data_temp = json_decode($return_data_temp, true);
        $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
        $day_return_data = [];
        $day_return_store = [];
        for ($i = 0; $i < 15; $i++) {
            $day_return_data[] = $return_data_temp[$i]['count'];
            $day_return_store[] = $return_data_temp[$i]['store_name'];
        }
        $week = date("Y-") . str_pad(date("W") - 1, 2, "0", STR_PAD_LEFT);
        $return_data_temp = $store_model->where(['date_e_year_week' => $week])->field('store_id,store_name,sum(hour) count')->group('store_id')->select();
        $return_data_temp = json_encode($return_data_temp);
        $return_data_temp = json_decode($return_data_temp, true);
        $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
        $week_return_data = [];
        $week_return_store = [];
        for ($i = 0; $i < 15; $i++) {
            $week_return_data[] = $return_data_temp[$i]['count'];
            $week_return_store[] = $return_data_temp[$i]['store_name'];
        }
        $month = date("Y-m", strtotime("last month"));
        $return_data_temp = $store_model->where(['date_e_month' => $month])->field('store_id,store_name,sum(hour) count')->group('store_id')->select();
        if (!empty($return_data_temp)) {
            $return_data_temp = json_encode($return_data_temp);
            $return_data_temp = json_decode($return_data_temp, true);
            $return_data_temp = two_sort($return_data_temp, 'count', SORT_DESC);
            $month_return_data = [];
            $month_return_store = [];
            for ($i = 0; $i < 15; $i++) {
                $month_return_data[] = $return_data_temp[$i]['count'];
                $month_return_store[] = $return_data_temp[$i]['store_name'];
            }
        } else {
            $month_return_data = [];
            $month_return_store = [];
        }
        $return_data = [
            'day' => json_encode($day_return_data),
            'week' => json_encode($week_return_data),
            'month' => json_encode($month_return_data)
        ];
        $return_store = [
            'day' => json_encode($day_return_store),
            'week' => json_encode($week_return_store),
            'month' => json_encode($month_return_store)
        ];
        return $this->fetch('stand', ['return_data' => $return_data, 'return_store' => $return_store]);
    }

    /**
     * 订单数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order_list()
    {
        $order_day = [];
        $order_data = [];
        $statistics_model = new Statistics();
//        $out_order_data = $statistics_model->where(['type'=>1,'d_type'=>1])->order('day ASC')->limit(15)->select();
        $map = ['type' => 1, 'd_type' => 1];
        $out_order_data = $statistics_model->getList($map, 'day DESC', 0, 15, 153);
        foreach ($out_order_data as $value) {
            $order_day[] = substr($value['day'], 5, 5)." 周".week_str($value['day']);
            $order_data[] = $value['data_log']['order_data']['income'];
        }
        $order_day = array_reverse($order_day);
        $order_data = array_reverse($order_data);
        return $this->fetch('order_list', ['order_day' => json_encode($order_day), 'order_data' => json_encode($order_data)]);
    }
}