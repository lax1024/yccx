<?php

namespace app\manage\controller;

use app\common\model\OrderPayLog as OrderPayLogModel;
use app\common\controller\AdminBase;

/**
 * 订单支付日志管理
 * Class AuthGroup
 * @package app\manage\controller
 */
class OrderPayLog extends AdminBase
{
    protected $order_pay_log_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_pay_log_model = new OrderPayLogModel();
    }

    /**订单管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        if (empty($page)) {
            $page = 1;
        }
        $map = array();
        if (!empty($keyword)) {
            $map['id|pay_sn|money|remark'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $order_pay_list = $this->order_pay_log_model->getPagelist($map, ' id DESC ', $page_config, 15);
        return $this->fetch('index', ['order_pay_list' => $order_pay_list, 'keyword' => $keyword]);
    }
}