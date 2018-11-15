<?php

namespace app\mobile\controller;

use app\common\controller\OperationBase;
use app\common\model\CarOperationPer;
use app\common\model\OrderOperation;
use app\common\model\Slide as SlideModel;
use definition\CarStatus;
use definition\OrderOperationType;
use think\Request;
use think\Session;

class Task extends OperationBase
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * 首页
     */
    public function index()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            if (!empty($mobile_phone)) {
                $operation_per_model = new CarOperationPer();
                $operation_per_data = $operation_per_model->getOperationPer('', $mobile_phone);
                if (empty($operation_per_data['code'])) {
                    $operation_per_data = $operation_per_data['data'];
                    if (intval($operation_per_data['status']) == 1) {
                        $this->error('账号已被锁定');
                    }
                    $operation_info = array(
                        'operation_id' => $operation_per_data['id'],
                        'operation_phone' => $operation_per_data['phone'],
                        'operation_name' => $operation_per_data['name'],
                        'operation_status' => $operation_per_data['status']
                    );
                    Session::set('operation_id', $operation_per_data['id']);
                    Session::set('operation_phone', $operation_per_data['phone']);
                    Session::set('operation_name', $operation_per_data['name']);
                    Session::set('store_key_id', $operation_per_data['store_key_id']);
                    Session::set('store_key_name', $operation_per_data['store_key_name']);
                    $operation_info['wechar_headimgurl'] = Session::get('wechar_headimgurl');
                    $this->assign($operation_info);
                } else {
                    exit("非运营人员不可操作此页面");
                }
            }
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
        $order_type = [
            1 => "补电+小保洁",
            2 => "大保洁",
            3 => "调度",
            6 => "小电瓶补电"
        ];
        $car_status = CarStatus::$CARSTATUS_CODE;
        //分享渠道链接
        return $this->fetch('task_index', ['jssdk' => $jssdk, 'order_type' => $order_type, 'car_status' => $car_status, 'fxurl' => $fxurl]);
    }

    /**
     * 车辆开始维护
     * @param string $goods_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_servicing($goods_id = '')
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';

        if (empty($goods_id)) {
            $this->redirect(url("mobile/task/index"));
        }
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status
            );
            if (!empty($mobile_phone)) {
                $operation_per_model = new CarOperationPer();
                $operation_per_data = $operation_per_model->getOperationPer('', $mobile_phone);
                if (empty($operation_per_data['code'])) {
                    $operation_per_data = $operation_per_data['data'];
                    if (intval($operation_per_data['status']) == 1) {
                        $this->error('账号已被锁定');
                    }
                    $operation_info = array(
                        'operation_id' => $operation_per_data['id'],
                        'operation_phone' => $operation_per_data['phone'],
                        'operation_name' => $operation_per_data['name'],
                        'operation_status' => $operation_per_data['status']
                    );
                    Session::set('operation_id', $operation_per_data['id']);
                    Session::set('operation_phone', $operation_per_data['phone']);
                    Session::set('operation_name', $operation_per_data['name']);
                    Session::set('store_key_id', $operation_per_data['store_key_id']);
                    Session::set('store_key_name', $operation_per_data['store_key_name']);
                    $operation_info['order_id'] = 0;
                    $operation_info['car_id'] = 0;
                    $operation_info['wechar_headimgurl'] = Session::get('wechar_headimgurl');
                    $operation_order_model = new OrderOperation();
                    $order_data = $operation_order_model->where(['goods_id' => $goods_id, 'order_status' => 10])->field("id,goods_id")->find();
                    if (!empty($order_data)) {
                        $operation_info['order_id'] = $order_data['id'];
                        $operation_info['car_id'] = $order_data['goods_id'];
                    }
                    $this->assign($operation_info);
                } else {
                    exit("非运营人员不可操作此页面");
                }
            }
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
        $order_type = OrderOperationType::$ORDEROPERATIONTYPE_CODE;
        $car_status = CarStatus::$CARSTATUS_CODE;
        //分享渠道链接
        return $this->fetch('task_servicing', ['jssdk' => $jssdk, 'order_type' => $order_type, 'car_status' => $car_status, 'fxurl' => $fxurl]);
    }

    /**
     * 车辆开始维护
     * @param string $goods_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_end($goods_id = '')
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';

        if (empty($goods_id)) {
            $this->redirect(url("mobile/task/index"));
        }
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            if (!empty($mobile_phone)) {
                $operation_per_model = new CarOperationPer();
                $operation_per_data = $operation_per_model->getOperationPer('', $mobile_phone);
                if (empty($operation_per_data['code'])) {
                    $operation_per_data = $operation_per_data['data'];
                    if (intval($operation_per_data['status']) == 1) {
                        $this->error('账号已被锁定');
                    }
                    $operation_info = array(
                        'operation_id' => $operation_per_data['id'],
                        'operation_phone' => $operation_per_data['phone'],
                        'operation_name' => $operation_per_data['name'],
                        'operation_status' => $operation_per_data['status']
                    );
                    Session::set('operation_id', $operation_per_data['id']);
                    Session::set('operation_phone', $operation_per_data['phone']);
                    Session::set('operation_name', $operation_per_data['name']);
                    Session::set('store_key_id', $operation_per_data['store_key_id']);
                    Session::set('store_key_name', $operation_per_data['store_key_name']);
                    $operation_info['order_id'] = 0;
                    $operation_info['car_id'] = 0;
                    $operation_info['wechar_headimgurl'] = Session::get('wechar_headimgurl');
                    $operation_order_model = new OrderOperation();
                    $order_data = $operation_order_model->where(['goods_id' => $goods_id, 'order_status' => 30])->field("id,goods_id")->find();
                    if (!empty($order_data)) {
                        $operation_info['order_id'] = $order_data['id'];
                        $operation_info['car_id'] = $order_data['goods_id'];
                    }
                    $this->assign($operation_info);
                } else {
                    exit("非运营人员不可操作此页面");
                }
            }
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
        $order_type = OrderOperationType::$ORDEROPERATIONTYPE_CODE;
        $car_status = CarStatus::$CARSTATUS_CODE;
        //分享渠道链接
        return $this->fetch('task_end', ['jssdk' => $jssdk, 'order_type' => $order_type, 'car_status' => $car_status, 'fxurl' => $fxurl]);
    }

    public function order()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            if (!empty($mobile_phone)) {
                $operation_per_model = new CarOperationPer();
                $operation_per_data = $operation_per_model->getOperationPer('', $mobile_phone);
                if (empty($operation_per_data['code'])) {
                    $operation_per_data = $operation_per_data['data'];
                    if (intval($operation_per_data['status']) == 1) {
                        $this->error('账号已被锁定');
                    }
                    $operation_info = array(
                        'operation_id' => $operation_per_data['id'],
                        'operation_phone' => $operation_per_data['phone'],
                        'operation_name' => $operation_per_data['name'],
                        'operation_status' => $operation_per_data['status'],
                        'store_key_id' => $operation_per_data['store_key_id'],
                        'store_key_name' => $operation_per_data['store_key_name'],
                    );
                    Session::set('operation_id', $operation_per_data['id']);
                    Session::set('operation_phone', $operation_per_data['phone']);
                    Session::set('operation_name', $operation_per_data['name']);
                    Session::set('store_key_id', $operation_per_data['store_key_id']);
                    Session::set('store_key_name', $operation_per_data['store_key_name']);
                    $this->assign($operation_info);
                }
            }
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
        //分享渠道链接
        return $this->fetch('task_order', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    //测试界面
    public function task_getcar()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            if (!empty($mobile_phone)) {
                $operation_per_model = new CarOperationPer();
                $operation_per_data = $operation_per_model->getOperationPer('', $mobile_phone);
                if (empty($operation_per_data['code'])) {
                    $operation_per_data = $operation_per_data['data'];
                    if (intval($operation_per_data['status']) == 1) {
                        $this->error('账号已被锁定');
                    }
                    $operation_info = array(
                        'operation_id' => $operation_per_data['id'],
                        'operation_phone' => $operation_per_data['phone'],
                        'operation_name' => $operation_per_data['name'],
                        'operation_status' => $operation_per_data['status'],
                        'store_key_id' => $operation_per_data['store_key_id'],
                        'store_key_name' => $operation_per_data['store_key_name'],
                    );
                    Session::set('operation_id', $operation_per_data['id']);
                    Session::set('operation_phone', $operation_per_data['phone']);
                    Session::set('operation_name', $operation_per_data['name']);
                    Session::set('store_key_id', $operation_per_data['store_key_id']);
                    Session::set('store_key_name', $operation_per_data['store_key_name']);
                    $this->assign($operation_info);
                }
            }
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
        return $this->fetch('task_getcar', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }
}