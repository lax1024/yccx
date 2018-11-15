<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\CustomerCash as CustomerCashModel;
use app\common\model\CustomerCashLog;

/**
 * 押金管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CustomerCash extends AdminBase
{
    protected $customer_cash_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_cash_model = new CustomerCashModel();
    }

    /**
     * 用户押金管理
     * @param string $keyword
     * @param string $state
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $state = '-1', $page = 1)
    {
        $map = [];
        if (!empty($keyword)) {
            $map['customer_id|mobile_phone|pay_sn|refund_sn'] = ['like', "%{$keyword}%"];
        }
        if ($state != "-1" && !empty($state)) {
            $map['state'] = $state;
        } else {
            $state = "-1";
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'state' => $state]];
        $customer_cash_list = $this->customer_cash_model->getPageList($map, 'id DESC', $page_config, 15);
        $state_list = [
            10 => "待付款",
            20 => "已缴纳",
            30 => "已退款",
            40 => "后台扣除"
        ];
        return $this->fetch('index', ['customer_cash_list' => $customer_cash_list, 'keyword' => $keyword, 'state' => $state, 'state_list' => $state_list]);
    }

    /**
     * 用户押金日志
     * @param string $keyword
     * @param int $page
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function log($keyword = '', $page = 1)
    {
        $map = [];
        if (!empty($keyword)) {
            $map['customer_id|pay_sn|refund_sn'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $customer_cash_log_model = new CustomerCashLog();
        $customer_cash_log_list = $customer_cash_log_model->getPageList($map, 'id DESC', $page_config, 15);
        return $this->fetch('log', ['customer_cash_log_list' => $customer_cash_log_list, 'keyword' => $keyword]);
    }

    public function lock_cash($pay_sn)
    {
        $cash_data = $this->customer_cash_model->lock_cash($pay_sn);
        if (empty($cash_data['code'])) {
            $this->success("锁定成功");
        }
        $this->error($cash_data['info']);
    }

    /**
     * 扣除押金
     * @param $pay_sn
     */
    public function deduct_cash($pay_sn)
    {
        $cash_data = $this->customer_cash_model->deduct_cash($pay_sn);
        if (empty($cash_data['code'])) {
            $this->success("扣除成功");
        }
        $this->error($cash_data['info']);
    }

    public function del_lock_cash($pay_sn)
    {
        $cash_data = $this->customer_cash_model->del_lock_cash($pay_sn);
        if (empty($cash_data['code'])) {
            $this->success("解除成功");
        }
        $this->error($cash_data['info']);

    }

}