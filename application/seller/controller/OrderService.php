<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use app\common\model\Order as OrderModel;
use app\common\model\Store as StoreModel;
use app\common\model\OrderComment as OrderCommentModel;
use definition\GoodsType;
use definition\OrderStatus;
use tool\CarDeviceTool;

/**
 * 订单管理
 * Class AuthGroup
 * @package app\seller\controller
 */
class OrderService extends SellerAdminBase
{
    protected $order_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_model = new OrderModel();
    }

    /**
     * 订单管理
     * @param string $keyword
     * @param int $page
     * @param string $order_status
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $order_status = '')
    {
        if (empty($page)) {
            $page = 1;
        }
        $map = array();
        if (!empty($keyword)) {
            $map['id|store_id|order_sn|pay_sn|store_name|customer_name|customer_phone|goods_licence_plate'] = ['like', "%{$keyword}%"];
        }
        if ($order_status == '' || $order_status == "-1") {
            $order_status = "-1";
        } else if (intval($order_status) < 70) {
            $map['order_status'] = $order_status;
        } else if (intval($order_status) == 80) {
            $map['return_expect'] = 1;
        }
        $page_config = ['page' => $page, 'query' => ['order_status' => $order_status, 'keyword' => $keyword]];
        $order_list = $this->order_model->getPageList($map, ' id DESC ', $page_config, 15, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_status_list = OrderStatus::$ORDER_STATUS_CODE;
        return $this->fetch('index', ['order_list' => $order_list, 'order_status_list' => $order_status_list, 'order_status' => $order_status, 'keyword' => $keyword]);
    }

    /**
     * 编辑订单管理
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $out_data = $this->order_model->getOrder($id, '', $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_comment_model = new OrderCommentModel();
        $map['order_id'] = $id;
        $order_comment = $order_comment_model->getComment($map);
        $store_model = new StoreModel();
        $maps['store_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $store_site_list = $store_model->getChildList($maps, 'id,store_name,store_pid');
        return $this->fetch('edit', ['order' => $out_data['data'], 'order_comment' => $order_comment['data'],'store_site_list'=>$store_site_list]);
    }

    /**
     * 取消订单
     * @param $id
     */
    public function cancel_order($id)
    {
        if ($this->order_model->cancelOrder($id, '', $this->seller_info['seller_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('取消成功');
        } else {
            $this->error('取消失败');
        }
    }

    /**
     * 提取商品
     * @param $id
     */
    public function acquire_order($id)
    {
        if ($this->order_model->acquireOrder($id, '', $this->seller_info['seller_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('提取商品成功');
        } else {
            $this->error('提取商品失败');
        }
    }

    /**
     * 归还商品
     * @param $id
     */
    public function return_order($id)
    {
        if ($this->order_model->returnOrder($id, '', $this->seller_info['seller_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $order_data = $this->order_model->getOrderIdField($id, '', "goods_type,goods_device");
            if (intval($order_data['data']['goods_type']) === GoodsType::$GoodsTypeElectrocar['code']) {
                $device_model = new CarDeviceTool();
                $device_ID = $order_data['data']['goods_device'];
                $out_data = $device_model->clearOrder($device_ID);
                $device_model->clearOrder($device_ID);
                $device_model->powerFailure($device_ID);
                $this->success($out_data['info']);
            }
            $this->success("归还商品成功");
        } else {
            $this->error('归还商品失败');
        }
    }

    /**
     * 其他费用设定
     * @param $id
     */
    public function rests_cost_order($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['rests_cost', 'rests_cost_notes']);
            $out_data = $this->order_model->restsCostOrder($data['rests_cost'], $data['rests_cost_notes'], $id, $this->seller_info['seller_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
            if ($out_data['code'] == 0) {
                $this->success('其他费用设定成功');
            } else {
                $this->error($out_data['info']);
            }
        }
        $this->error('参数有误');
    }

    /**
     * 确认订单
     * @param $id
     */
    public function end_order($id)
    {
        if ($this->order_model->endOrder($id, '', $this->seller_info['seller_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('确认订单成功');
        } else {
            $this->error('确认订单失败');
        }
    }

    /**
     * 更新还车门店
     * @param $id
     * @param $site_id
     */
    public function return_site($id, $site_id)
    {
        $store_model = new StoreModel();
        $store_data = $store_model->getStoreField($site_id, 'store_name');
        $store_name = $store_data['store_name'];
        $set_data = [
            'return_store_id' => $site_id,
            'return_store_name' => $store_name,
            'reality_return_time' => time(),
        ];
        $ret = $this->order_model->where(['id' => $id])->setField($set_data);
        if ($ret !== false) {
            $this->success("更改成功");
        }
        $this->error("更改失败");
    }
}