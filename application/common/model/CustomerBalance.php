<?php

namespace app\common\model;

use app\common\model\Customer as CustomerModel;
use app\common\model\CustomerBalanceLog as CustomerBalanceLogModel;
use think\Model;

class CustomerBalance extends Model
{
    private $customer_model;//用户模型
    private $customer_balance_log_model;//支付日志模型
    protected $insert = ['start_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new CustomerModel();
        $this->customer_balance_log_model = new CustomerBalanceLogModel();
    }

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return time();
    }

    public function formatx(&$data)
    {
        if ($data['state'] == 10) {
            $data['time_str'] = date('Y-m-d H:i:s', $data['start_time']);
        } else {
            $data['time_str'] = date('Y-m-d H:i:s', $data['payment_time']);
        }
    }

    public function getlist($map = array(), $order = '', $page = 1, $limit = 8)
    {
        $order_list = $this->where($map)->order($order)->paginate($limit, false, ['page' => $page]);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }

    /**
     * 支付余额订单
     * @param $pay_info
     * @param $payment_code
     * @return bool
     */
    public function pay_balance($pay_info, $payment_code)
    {
        $order = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 10))->find();
        if (empty($order)) {
            return false;
        }
        $balance = array(
            'balance' => $pay_info['money'],// 单位 元
            'pay_sn' => $pay_info['pay_sn'],// 订单号
            'customer_id' => $order['customer_id'],//用户id
            'remark' => $pay_info['remark']//备注信息
        );
        if ($this->customer_model->addBalance($balance)) {
            $ret = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 10))->update(array('state' => 20, 'payment_code' => $payment_code, 'payment_time' => time()));
            if (!$ret) {
                return false;
            }
        }
        return true;
    }

    /**
     * 添加余额订单
     * @param $balance_info
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_balance($balance_info)
    {
        $map = array(
            'customer_id' => $balance_info['customer_id'],
            'state' => 10
        );
        $pay_sn = $this->_create_pay_sn();
        $balance_data = $this->where($map)->find();
        if (empty($balance_data)) {
            $balance_info['pay_sn'] = $pay_sn;
            $balance_info['start_time'] = time();
            if ($this->save($balance_info)) {
                $pay_info = array(
                    'code' => 0,
                    'info' => "提交成功",
                    'out_trade_no' => $pay_sn,
                    'body' => "优车出行 余额充值",
                    'total_price' => $balance_info['balance']
                );
                return $pay_info;
            }
        } else {
            $balance_data->pay_sn = $pay_sn;
            $balance_data->balance = $balance_info['balance'];
            $balance_data->start_time = time();
            if ($balance_data->save()) {
                $pay_info = array(
                    'code' => 0,
                    'info' => "提交成功",
                    'out_trade_no' => $pay_sn,
                    'body' => "优车出行 余额充值",
                    'total_price' => $balance_info['balance']
                );
                return $pay_info;
            }
        }
        $pay_info = array(
            'code' => 21,
            'info' => "提交失败",
            'out_trade_no' => '',
            'body' => "优车出行 余额充值",
            'total_price' => ''
        );
        return $pay_info;
    }

    /**
     * 获取充值订单号
     * @return string
     */
    private function _create_pay_sn()
    {
        $sn = "balance_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }
}