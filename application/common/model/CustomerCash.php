<?php

namespace app\common\model;

use app\common\model\Customer as CustomerModel;
use app\common\model\CustomerCashLog as CustomerCashLogModel;
use definition\OrderStatus;
use think\Config;
use think\Model;

class CustomerCash extends Model
{
    private $customer_model;//用户模型
    private $customer_cash_log_model;//支付日志模型
    protected $insert = ['start_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new CustomerModel();
        $this->customer_cash_log_model = new CustomerCashLogModel();
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

        if (!empty($data['start_time'])) {
            $data['start_time_str'] = date('Y-m-d H:i:s', $data['start_time']);
        } else {
            $data['start_time_str'] = "无";
        }
        if (!empty($data['payment_time'])) {
            $data['payment_time_str'] = date('Y-m-d H:i:s', $data['payment_time']);
        } else {
            $data['payment_time_str'] = "无";
        }
        if (!empty($data['deduct_time'])) {
            $data['deduct_time_str'] = date('Y-m-d H:i:s', $data['deduct_time']);
        } else {
            $data['deduct_time_str'] = "无";
        }
        if (!empty($data['payment_code'])) {
            $pay_code = Config::get('pay_code');
            $data['payment_code_str'] = $pay_code[$data['payment_code']];
        } else {
            $data['payment_code_str'] = "未支付";
        }
        if (!empty($data['state'])) {
            $data['state_str'] = "待付款";
            if (intval($data['state']) == 20) {
                $data['state_str'] = "已付款";
            } else if (intval($data['state']) == 30) {
                $data['state_str'] = "已退款";
            } else if (intval($data['state']) == 40) {
                $data['state_str'] = "后台扣除";
            }
        } else {
            $data['state_str'] = "无";
        }
        $customer_data = $this->customer_model->where(['id' => $data['customer_id']])->field('mobile_phone,customer_name')->find();
        $data['customer_name'] = $customer_data['customer_name'];
    }

    /**
     * 获取列表
     * @param array $map
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getlist($map = array(), $order = '', $page = 0, $limit = 8)
    {
        $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }

    /**
     * 获取列表 框架分页
     * @param array $map
     * @param string $order
     * @param $config_page
     * @param int $limit
     * @param bool $is_auto
     * @return array|\think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPagelist($map = array(), $order = '', $config_page, $limit = 8, $is_auto = false)
    {
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if ($is_auto) {
            $order_list_out = [];
            if (!empty($order_list)) {
                foreach ($order_list as $value) {
                    $order_model = new Order();
                    $count = $order_model->where(['customer_id' => $value['customer_id'], 'order_status' => OrderStatus::$OrderStatusFinish['code']])->count();
                    if ($count > 0) {
                        $this->formatx($value);
                        $order_list_out[] = $value;
                    }
                }
            }
            return $order_list_out;
        } else {
            if (!empty($order_list)) {
                foreach ($order_list as &$value) {
                    $this->formatx($value);
                }
            }
            return $order_list;
        }
    }

    //支付押金订单
    public function pay_cash($pay_info, $payment_code)
    {
        $order = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 10))->find();
        if (empty($order)) {
            return false;
        }
        $money = array(
            'cash' => $pay_info['money'],// 单位 元
            'pay_sn' => $pay_info['pay_sn'],// 订单号
            'channel' => $pay_info['channel'],// 押金类型  1支付宝 2微信支付 3银行冻结
            'customer_id' => $order['customer_id'],//用户id
            'remark' => $pay_info['remark']//备注信息
        );
        if ($this->customer_model->addCash($money)) {
            $ret = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 10))->update(array('state' => 20, 'payment_code' => $payment_code, 'payment_time' => time()));
            if (!$ret) {
                return false;
            }
        }
        return true;
    }

    //退还押金订单
    public function return_cash($pay_info, $payment_code)
    {
        $order = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 20))->find();
        if (empty($order)) {
            return false;
        }
        if (intval($order['lock']) == 1) {
            return false;
        }
        $money = array(
            'cash' => $pay_info['money'],// 单位 元
            'pay_sn' => $pay_info['pay_sn'],// 订单号
            'refund_sn' => $pay_info['refund_sn'],// 退款号
            'channel' => $pay_info['channel'],// 押金类型  1支付宝 2微信支付 3银行冻结
            'customer_id' => $order['customer_id'],//用户id
            'remark' => $pay_info['remark']//备注信息
        );
        if ($this->customer_model->subCash($money)) {
            $ret = $this->where(array('pay_sn' => $pay_info['pay_sn'], 'state' => 20))->update(array('refund_sn' => $pay_info['refund_sn'], 'state' => 30, 'payment_code' => $payment_code, 'payment_time' => time()));
            if (!$ret) {
                return false;
            }
        }
        return true;
    }

    //添加押金订单
    public function add_cash($cash_info)
    {
        $map = array(
            'customer_id' => $cash_info['customer_id'],
            'state' => 10
        );
        $pay_sn = $this->_create_pay_sn();
        $cash_data = $this->where($map)->find();
        if (empty($cash_data)) {
            $map['state'] = 20;
            $cash_temp_data = $this->where($map)->find();
            if (!empty($cash_temp_data)) {
                $pay_info = array(
                    'code' => 30,
                    'info' => "已缴纳",
                    'out_trade_no' => '',
                    'body' => "优车出行 押金缴纳",
                    'total_price' => ''
                );
                return $pay_info;
            }
            $cash_info['pay_sn'] = $pay_sn;
            $cash_info['start_time'] = time();
            if ($this->save($cash_info)) {
                $pay_info = array(
                    'code' => 0,
                    'info' => "提交成功",
                    'out_trade_no' => $pay_sn,
                    'body' => "优车出行 押金缴纳",
                    'total_price' => $cash_info['cash']
                );
                return $pay_info;
            }
        } else {
            $cash_data->pay_sn = $pay_sn;
            $cash_data->cash = $cash_info['cash'];
            $cash_data->start_time = time();
            if ($cash_data->save()) {
                $pay_info = array(
                    'code' => 0,
                    'info' => "提交成功",
                    'out_trade_no' => $pay_sn,
                    'body' => "优车出行 押金缴纳",
                    'total_price' => $cash_info['cash']
                );
                return $pay_info;
            }
        }
        $pay_info = array(
            'code' => 21,
            'info' => "提交失败",
            'out_trade_no' => '',
            'body' => "优车出行 押金缴纳",
            'total_price' => ''
        );
        return $pay_info;
    }

    /**
     * 锁定
     * @param $pay_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lock_cash($pay_sn)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误',
        ];
        if (empty($pay_sn)) {
            return $out_data;
        }
        $cash_data = $this->where(['pay_sn' => $pay_sn, 'state' => 20])->find();
        if (empty($cash_data)) {
            $out_data['code'] = 101;
            $out_data['info'] = "数据不存在";
            return $out_data;
        }
        $cash_data->lock = 1;
        $ret = $cash_data->save();
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "锁定成功";
            return $out_data;
        }
        $out_data['code'] = 102;
        $out_data['info'] = "锁定失败";
        return $out_data;
    }

    /**
     * 解除锁定
     * @param $pay_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del_lock_cash($pay_sn)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误',
        ];
        if (empty($pay_sn)) {
            return $out_data;
        }
        $cash_data = $this->where(['pay_sn' => $pay_sn, 'state' => 20])->find();
        if (empty($cash_data)) {
            $out_data['code'] = 101;
            $out_data['info'] = "数据不存在";
            return $out_data;
        }
        $cash_data->lock = 0;
        $ret = $cash_data->save();
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "解除成功";
            return $out_data;
        }
        $out_data['code'] = 102;
        $out_data['info'] = "解除失败";
        return $out_data;
    }

    /**
     * 扣除押金
     * @param $pay_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deduct_cash($pay_sn)
    {
        $retunr_data = array(
            'code' => 101,
            'info' => "参数有误"
        );
        $order = $this->where(array('pay_sn' => $pay_sn, 'state' => 20))->find();
        if (empty($order)) {
            return $retunr_data;
        }
        $money = array(
            'cash' => $order['cash'],// 单位 元
            'pay_sn' => $order['pay_sn'],// 订单号
            'refund_sn' => "",// 退款号
            'channel' => $order['payment_code'],// 押金类型  1支付宝 2微信支付 3银行冻结
            'customer_id' => $order['customer_id'],//用户id
            'remark' => $order['remark']//备注信息
        );
        if ($this->customer_model->deductCash($money) > 0) {
            $ret = $this->where(array('pay_sn' => $pay_sn, 'state' => 20))->update(array('state' => 40, 'deduct_time' => time()));
            if ($ret) {
                $retunr_data = array(
                    'code' => 0,
                    'info' => "后台扣除成功"
                );
                return $retunr_data;
            }
        }
        $retunr_data = array(
            'code' => 102,
            'info' => "后台扣除失败"
        );
        return $retunr_data;
    }

    /**
     * 判断是否可以退还订单
     * @param $pay_sn
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function is_return_cash($pay_sn)
    {
        $cash_data = $this->where(array('pay_sn' => $pay_sn, 'state' => 20))->field('customer_id,lock')->find();
        if (empty($cash_data)) {
            $out_data = array(
                'code' => 20,
                'info' => '退款信息不存在'
            );
            return $out_data;
        }
        if (intval($cash_data['lock']) == 1) {
            $out_data = array(
                'code' => 30,
                'info' => '押金已锁定，请联系客服'
            );
            return $out_data;
        }
        $order_model = new Order();
        $out_data = $order_model->IsReturnCash($cash_data['customer_id']);
        return $out_data;
    }

    /**
     * 获取充值订单号
     * @return string
     */
    private function _create_pay_sn()
    {
        $sn = "cash_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

}