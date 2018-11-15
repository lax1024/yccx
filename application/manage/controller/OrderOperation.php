<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\OrderOperation as OrderOperationModel;
use definition\OrderOperationType;
use definition\OrderStatus;

/**
 * 任务订单管理
 * Class AuthGroup
 * @package app\seller\controller
 */
class OrderOperation extends AdminBase
{
    protected $order_operation;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_operation = new OrderOperationModel();
    }

    /**
     * 订单管理
     * @param string $keyword
     * @param int $page
     * @param int $order_type
     * @param int $order_status
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $order_type = 0, $order_status = -1, $start_time = '', $end_time = '')
    {
        if (empty($page)) {
            $page = 1;
        }
        $map = array();
        if (!empty($keyword)) {
            $map['id|order_sn|operation_id|operation_name|operation_phone|goods_id|licence_plate'] = ['like', "%{$keyword}%"];
        }
        if (intval($order_status) >= 0) {
            $map['order_status'] = $order_status;
        }
        if (!empty($order_type)) {
            $map['order_type'] = $order_type;
        }
        $page_config = ['page' => $page, 'query' => ['order_type' => $order_type, 'order_status' => $order_status, 'keyword' => $keyword]];
        if (!empty($start_time) || !empty($end_time)) {
            $map['create_time'] = ['between', $start_time . "," . $end_time];
            $page_config['query']['start_time'] =  $start_time;
            $page_config['query']['end_time'] =  $end_time;
        }
        $order_operation_list = $this->order_operation->getPageList($map, $order = 'id DESC', $page_config, 15, true, '');
        $type_list = OrderOperationType::$ORDEROPERATIONTYPE_CODE;
        $order_list = OrderStatus::$ORDER_OP_STATUS_CODE;
        return $this->fetch('index', ['order_operation_list' => $order_operation_list, 'type_list' => $type_list, 'order_type' => $order_type, 'order_list' => $order_list, 'order_status' => $order_status, 'keyword' => $keyword,'start_time'=>$start_time,'end_time'=>$end_time]);
    }

    /**
     * 编辑订单管理
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $out_data = $this->order_operation->getOrderOperation($id, '', true, '');
        return $this->fetch('edit', ['order' => $out_data['data']]);
    }

    /**
     * 取消已接任务
     * @param $id
     */
    public function cancel($id)
    {
        $ret = $this->order_operation->cancelOperation($id);
        if ($ret !== false) {
            file_get_contents("http://www.youchedongli.cn/api/Common/car_abnormal.html");
            $this->success('取消成功');
        }
        $this->success('取消失败');
    }
}