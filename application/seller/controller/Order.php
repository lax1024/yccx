<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use app\common\model\CarCommon;
use app\common\model\CarOperationPer;
use app\common\model\Order as OrderModel;
use app\common\model\Store as StoreModel;
use app\common\model\OrderComment as OrderCommentModel;
use definition\CarStatus;
use definition\GoodsType;
use definition\OrderStatus;
use definition\PayCode;
use tool\CarDeviceTool;

/**
 * 订单管理
 * Class AuthGroup
 * @package app\seller\controller
 */
class Order extends SellerAdminBase
{
    protected $order_model;
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_model = new OrderModel();
        $this->store_model = new StoreModel();
    }

    /**
     * 订单管理
     * @param string $keyword
     * @param int $page
     * @param string $order_status
     * @param string $start_time
     * @param string $end_time
     * @param string $payment_code
     * @param int $limit
     * @param int $is_excl 是否是导出表数据
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $order_status = '', $start_time = '', $end_time = '', $payment_code = '', $limit = 15, $is_excl = 0)
    {
        $map = array(
            'store_key_id' => $this->web_info['store_key_id']
        );
        if (empty($page)) {
            $page = 1;
        }
        if (!empty($payment_code)) {
            $map['payment_code'] = $payment_code;
        }
        if (empty($start_time)) {
            $start_time_num = "1483200000";
        } else {
            $start_time_num = strtotime($start_time);
        }
        if (!empty($end_time)) {
            $end_time_num = strtotime($end_time);
            if (!empty($payment_code)) {
                $map['payment_time'] = array('between', $start_time_num . ',' . $end_time_num);
            } else {
                $map['create_time'] = array('between', $start_time . ',' . $end_time);
            }
        }
        if (!empty($keyword)) {
            $map['id|store_id|order_sn|pay_sn|store_name|customer_name|customer_phone|goods_licence_plate'] = ['like', "%{$keyword}%"];
        }
        if ($order_status == '' || $order_status == "-1") {
            $order_status = "-1";
        } else if (intval($order_status) < 70) {
            $map['order_status'] = $order_status;
        } else if (intval($order_status) == 80) {
            $map['return_expect'] = 1;
        } else if (intval($order_status) == 90) {
            $map['is_refund'] = 1;
        } else if (intval($order_status) == 100) {
            $map['is_refund'] = 2;
        }
        $page_config = ['page' => $page, 'query' => ['order_status' => $order_status, 'start_time' => $start_time, 'end_time' => $end_time, 'payment_code' => $payment_code, 'keyword' => $keyword]];
        $order_list = $this->order_model->getPageList($map, ' id DESC ', $page_config, $limit, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_status_list = OrderStatus::$ORDER_STATUS_CODE;
        $payment_code_list = PayCode::$PAY_CODE;
        if (empty($payment_code)) {
            $map['payment_code'] = array('neq', '');
        }
        $order_amount = $this->order_model->where($map)->sum('order_amount');
        $map['is_refund'] = 2;
        $refund_amount = $this->order_model->where($map)->sum('refund_amount');
        $out_field_list = ["订单编号", "支付单号", "订单类型", "订单状态", "订单来源", "所属门店", "车辆牌号", "客户姓名", "联系电话", "取车时间", "还车时间", "实际还车时间", "取车网点", "还车网点", "行驶里程", "使用时长", "合计金额", "余额支付", "优惠券抵扣", "第三方支付", "退还金额"];
        if (intval($is_excl) == 1) {
            $out_order_data = [];
            foreach ($order_list as $value) {
                if (!empty($value)) {
                    $out_order_data[] = [
                        'order_sn' => $value['order_sn'],
                        'pay_sn' => $value['pay_sn'],
                        'goods_type' => $value['goods_type_str'],
                        'order_status' => $value['order_status_str'],
                        'channel_uid' => $value['channel_uid'],
                        'store_name' => $value['store_name'],
                        'goods_licence_plate' => $value['goods_licence_plate'],
                        'customer_name' => $value['customer_name'],
                        'customer_phone' => $value['customer_phone'],
                        'acquire_time' => $value['acquire_time_d_str'],
                        'return_time' => $value['return_time_d_str'],
                        'reality_return_time' => $value['reality_return_time_str'],
                        'acquire_store_name' => $value['acquire_store_name'],
                        'return_store_name' => $value['return_store_name'],
                        'all_mileage' => $value['all_mileage'],
                        'user_time' => $value['user_time'],
                        'order_amount' => $value['order_amount'],
                        'pd_amount' => $value['pd_amount'],
                        'coupon_amount' => $value['coupon_amount'],
                        'pay_amount' => $value['pay_amount'],
                        'refund_amount' => $value['refund_amount'],
                    ];
                }
            }
            if (!empty($out_order_data)) {
                $out_data = [
                    'code' => 0,
                    'data' => $out_order_data,
                    'info' => "获取成功"
                ];
            } else {
                $out_data = [
                    'code' => 1,
                    'info' => "获取完成"
                ];
            }
            out_json_data($out_data);
        }
        return $this->fetch('index', ['order_list' => $order_list, 'out_field_list' => $out_field_list, 'order_amount' => $order_amount, 'refund_amount' => $refund_amount, 'order_status_list' => $order_status_list, 'payment_code_list' => $payment_code_list, 'start_time' => $start_time, 'end_time' => $end_time, 'payment_code' => $payment_code, 'order_status' => $order_status, 'keyword' => $keyword]);
    }


    /**
     * 添加订单管理
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存订单管理
     */
    public function save()
    {
        $this->error('暂时不提供');
        if ($this->request->isPost()) {
            $data = $this->request->post();
//     * @param $goodsinfo 商品信息
//            * store_id 店铺id 门店
//            * store_name 店铺店铺名称
//            * goods_amount 商品单价
//            * order_amount 订单总价格
//            * goods_id 商品id
//            * goods_type 商品类
//            * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_sum'=>'购买数量','goods_img'=>'商品图片']
//            * acquire_time 取用时间 时间戳
//            * acquire_address 取用地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//            * return_time 归还时间 时间戳
//            * return_address 归还地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//     * @param $customer_info 客户信息
//            * customer_id 客户id
//            * mobile_phone 客户电话
//            * customer_information 客户证件信息 ['real_name'=>'客户真实姓名','id_number'=>'身份证号码','mobile_phone'=>'电话号码']
//     * @param $channel_uid 渠道信息
            $goodsinfo = array(
                'store_id' => $data['store_id'],
                'store_name' => $data['store_name'],
                'goods_amount' => $data['goods_amount'],
                'order_amount' => $data['order_amount'],
                'goods_id' => $data['goods_id'],
                'goods_type' => $data['goods_type'],
                'order_goods' => $data['order_goods'],
                'acquire_time' => $data['acquire_time'],
                'acquire_address' => $data['acquire_address'],
                'return_time' => $data['return_time'],
                'return_address' => $data['return_address']
            );
            $customer_info = array(
                'customer_id' => $data['customer_id'],
                'mobile_phone' => $data['mobile_phone'],
                'customer_information' => $data['customer_information']
            );
            $channel_uid = 0;
            $out_data = $this->order_model->addOrder($goodsinfo, $customer_info, $channel_uid, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
            if (intval($out_data['code']) == 0) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
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
        $maps['store_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $store_site_list = $this->store_model->getChildList($maps, 'id,store_name,store_pid');
        return $this->fetch('edit', ['order' => $out_data['data'], 'order_comment' => $order_comment['data'], 'store_site_list' => $store_site_list]);
    }


    /**
     * 更新订单管理
     * @param $id
     */
    public function update($id)
    {
        $this->error('暂时不提供次方法');
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
        $store_data = $this->store_model->getStoreField($site_id, 'store_name');
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

    /**
     * @param string $ym
     * @param string $ymd
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view($ym = '', $ymd = '')
    {
        $operation_per_model = new CarOperationPer();
        $operation_per_list = $operation_per_model->getOperationPerList($this->web_info['store_key_id']);
        $operation_per_arr = [];
        if (!empty($operation_per_list['data'])) {
            foreach ($operation_per_list['data'] as $value) {
                $operation_per_arr[] = $value['phone'];
            }
        }
        if (empty($ymd) && empty($ym)) {
            $ymd = date("Y-m-d");
        }
        $map = [
            'store_key_id' => $this->web_info['store_key_id'],
            'order_status' => ['egt', OrderStatus::$OrderStatusReturn['code']]
        ];
        $days = 1;
        if (!empty($ym)) {
            $map['return_month'] = $ym;
            $days = 30;
        } else {
            $map['return_date'] = $ymd;

        }
        $order_list = $this->order_model->getList($map, 'id ASC', 0, $limit = 0, true);
        $order_amount = 0;
        $coupon_amount = 0;
        $first_sub_money = 0;
        if (!empty($order_list)) {
            foreach ($order_list as $value) {
                if (!in_array($value['customer_phone'], $operation_per_arr)) {
                    $order_amount += floatval($value['order_amount']);
                    $coupon_amount += floatval($value['coupon_amount']);
                    $first_sub_money += floatval($value['first_sub_money']);
                }
            }
        }
        $order_data = [
            'order_amount' => $order_amount,
            'coupon_amount' => $coupon_amount,
            'first_sub_money' => $first_sub_money,
            'income' => $order_amount - $coupon_amount - $first_sub_money,
        ];
        $car_model = new CarCommon();
        $map = ['goods_type' => GoodsType::$GoodsTypeElectrocar['code'], 'store_key_id' => $this->web_info['store_key_id']];
        $car_all = $car_model->where($map)->count();
        $map['car_status']  =['egt', CarStatus::$CarStatusNormal['code']];
        $car_sum = $car_model->where($map)->count();
        $car_data = [
            'day_all' => $car_all,
            'day_sum' => $car_sum,
            'money' => round(($order_amount / $car_sum), 2),
            'day_money' => round(($order_amount / $car_sum) / $days, 2),
        ];
        return $this->fetch('view', ['order_data' => $order_data, 'car_data' => $car_data, 'ym' => $ym, 'ymd' => $ymd]);
    }
}