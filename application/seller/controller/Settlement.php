<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use app\common\model\Order as OrderModel;
use app\common\model\Store as StoreModel;
use app\common\model\Remittance as RemittanceModel;
use definition\OrderStatus;
use think\Config;

/**
 * 结算系统
 * Class System
 * @package app\jybz\controller
 */
class Settlement extends SellerAdminBase
{
    protected $order_model;
    protected $store_model;
    protected $remittance_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->order_model = new OrderModel();
        $this->store_model = new StoreModel();
        $this->remittance_model = new RemittanceModel();
    }

    /**
     * 订单管理
     * @param int $store_key_id
     * @param int $is_all
     * @param int $page
     * @param string $end_time
     * @return mixed
     */
    public function index($store_key_id = 0, $end_time = '', $is_all = 0, $page = 1)
    {
        if (empty($end_time)) {
            $end_time = time();
        } else {
            $end_time = urldecode($end_time);
            $end_time = strtotime($end_time);
        }
        $map['store_key_id'] = $store_key_id;
        $map['order_status'] = OrderStatus::$OrderStatusFinish['code'];
        if (intval($is_all) == 0) {
            $map['is_settlement'] = 0;
        }
        $map['payment_time'] = array('elt', $end_time);
        $page_config = ['page' => $page, 'query' => ['store_key_id' => $store_key_id, 'end_time' => $end_time, 'is_all' => $is_all]];
        $order_list = $this->order_model->getPagelist($map, 'id DESC', $page_config, 15, $this->web_info['is_super_admin'], $store_key_id);
//        $order_list = $this->order_model->where($map)->order('id DESC')->paginate(15, false, ['page' => $page]);
        $count = $this->order_model->where($map)->count();
        $order_amount = $this->order_model->where($map)->sum('order_amount');
        $store_data = $this->store_model->where(array('id' => $store_key_id))->find();
        if (intval($is_all) == 1) {
            $map['is_settlement'] = 0;
            $order_amount_sett = $this->order_model->where($map)->sum('order_amount');
        } else {
            $order_amount_sett = $order_amount;
        }
        if (!empty($store_data)) {
            $store_data['order_amount_sett'] = $order_amount_sett;
            $store_data['order_amount'] = floatval($order_amount_sett) - (floatval($order_amount_sett) * floatval($store_data['commission']));
        } else {
            $store_data['store_name'] = "系统平台";
            $store_data['store_principal'] = "系统平台";
            $store_data['store_tel'] = "系统平台";
            $store_data['commission_str'] = "平台自行结算";
            $store_data['commission'] = 0;
            $store_data['order_amount_sett'] = $order_amount_sett;
            $store_data['order_amount'] = floatval($order_amount_sett) - (floatval($order_amount_sett) * floatval($store_data['commission']));
        }
        foreach ($order_list as &$value) {
            $this->order_model->formatx($value);
        }
        return $this->fetch('index', ['order_list' => $order_list, 'seller' => $store_data, 'is_all' => $is_all, 'count' => $count, 'order_amount' => $order_amount, 'store_key_id' => $store_key_id, 'end_time' => date('Y-m-d H:i:s', $end_time)]);
    }

    /**
     * 显示订单
     * @param $id
     * @return mixed
     */
    public function view($id)
    {
        $order = $this->order_model->find($id);
        $this->order_model->formatx($order);
        return $this->fetch('view', ['order' => $order]);
    }

    public function sett($channel_uid = 0, $end_time = '')
    {
        if (empty($end_time)) {
            $end_time = time();
        } else {
            $end_time = urldecode($end_time);
            $end_time = strtotime($end_time);
            if (intval($end_time) > time()) {
                $end_time = time();
            }
        }
        $map['channel_uid'] = $channel_uid;
        $map['order_state'] = 50;
        $map['is_settlement'] = 0;
        $map['payment_time'] = array('elt', $end_time);
        $order_amount = $this->order_model->where($map)->sum('order_amount');
        $tojoin = $this->tojoin_model->where(array('uid' => $channel_uid))->find();
        $tojoin_join_grade = 0;
        if (empty($tojoin)) {
            $tojoin_data['commission_str'] = "10%";
            $tojoin_data['commission'] = 0.1;
            $tojoin_data['unit_name'] = "无单位";
            $tojoin_data['join_grade_str'] = "普通推广";
            $tojoin_data['order_amount'] = floatval($order_amount) * floatval($tojoin_data['commission']);
        } else {
            $tojoin_join_grade = $tojoin['join_grade'];
            $join_grade = Config::get('join_grade');
            $join_grade_data = $join_grade[intval($tojoin['join_grade'])];
            $tojoin_data['commission_str'] = $join_grade_data['commission_str'];
            $tojoin_data['commission'] = $join_grade_data['commission'];
            $tojoin_data['unit_name'] = $tojoin['unit_name'];
            $tojoin_data['join_grade_str'] = $join_grade_data['name'];
            $tojoin_data['order_amount'] = floatval($order_amount) * floatval($tojoin_data['commission']);
        }
        if (floatval($tojoin_data['order_amount']) <= 0) {
            $this->error('结算失败');
        }
        $settlement_data = array(
            'uid' => $channel_uid,
            'unit_name' => $tojoin_data['unit_name'],
            'join_grade' => $tojoin_join_grade,
            'money' => $tojoin_data['order_amount'],
            'order_amount' => $order_amount,
            'settlement_time' => date("Y-m-d H:i:s", $end_time),
        );
        if ($this->remittance_model->addSettlement($settlement_data)) {
            if ($this->order_model->where($map)->setField('is_settlement', 1)) {
//                echo $this->order_model->getLastSql();
                $this->success('结算成功');
            } else {
                $this->order_model->where($map)->setField('is_settlement', 1);
                $this->success('结算成功');
            }
        }
        $this->error('结算失败');
    }
}