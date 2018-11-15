<?php

namespace app\common\model;

use app\common\model\Customer as CustomerModel;
use definition\OrderStatus;
use think\Model;

/**
 * 车辆违章模型
 * Class CarRegulations
 * @package app\common\model
 */
class CarRegulations extends Model
{
    private $customer_model;//用户模型
    protected $insert = ['add_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new CustomerModel();
    }

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    public function formatx(&$data)
    {
        if (!empty($data['start_time'])) {
            $data['start_time_str'] = date('Y-m-d H:i:s', $data['start_time']);
        }
        $data['state_str'] = "待处理";
        if ($data['state'] == 20) {
            $data['state_str'] = "已处理";
        }
    }

    /**
     * 获取违章信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCarRegulations($id)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($id)) {
            return $out_data;
        }
        $car_reg_data = $this->where(['id' => $id])->find();
        $this->formatx($car_reg_data);
        $out_data['data'] = $car_reg_data;
        $out_data['code'] = 0;
        $out_data['info'] = '获取成功';
        return $out_data;
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
        $regulations_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($regulations_list)) {
            foreach ($regulations_list as &$value) {
                $this->formatx($value);
            }
        }
        return $regulations_list;
    }

    /**
     * 获取列表 框架分页
     * @param array $map
     * @param string $order
     * @param $config_page
     * @param int $limit
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPagelist($map = array(), $order = '', $config_page, $limit = 8)
    {
        $regulations_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($regulations_list)) {
            foreach ($regulations_list as &$value) {
                $this->formatx($value);
            }
        }
        return $regulations_list;
    }

    /**
     * 判断是否可以退押金
     * @param $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function is_return_cash($customer_id)
    {
        $out_data = [
            'code' => 0,
            'info' => '违章验证通过'
        ];
        $map = [
            'customer_id' => $customer_id,
            'state' => 10
        ];
        $regulations_list = $this->where($map)->select();
        if (empty($regulations_list)) {
            return $out_data;
        }
        foreach ($regulations_list as &$value) {
            $this->formatx($value);
        }
        $out_data['data'] = $regulations_list;
        $out_data['code'] = 1001;
        $out_data['info'] = "存在未处理的违章";
        return $out_data;
    }

    /**
     * 添加违章记录
     * @param $regulations_info
     * start_time 违章时间
     * licence_plate 车牌号
     * info 违章信息
     * remark 违章处理信息
     * state 处理状态
     * admin_id 操作管理员id
     * admin_name 操作管理员账号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_regulations($regulations_info)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($regulations_info['start_time']) || empty($regulations_info['licence_plate']) || empty($regulations_info['info'])) {
            return $out_data;
        }
        $map = array(
            'start_time' => $regulations_info['start_time'],
            'licence_plate' => $regulations_info['licence_plate'],
        );
        $cash_data = $this->where($map)->find();
        if (empty($cash_data)) {
            $car_model = new CarCommon();
            $car_data = $car_model->where(['licence_plate' => $regulations_info['licence_plate']])->field('id')->find();
            if (empty($car_data)) {
                $out_data['code'] = 101;
                $out_data['info'] = "车辆数据不存在，请检查车牌还是否正确";
                return $out_data;
            }
            $in_regulations = [
                'customer_id' => 0,
                'customer_name' => '',
                'customer_phone' => '',
                'order_id' => 0,
                'good_id' => $car_data['id'],
                'licence_plate' => $regulations_info['licence_plate'],
                'info' => $regulations_info['info'],
                'remark' => $regulations_info['remark'],
                'state' => $regulations_info['state'],
                'add_time' => date("Y-m-d H:i:s"),
                'update_time' => date("Y-m-d H:i:s"),
                'end_time' => $regulations_info['end_time'],
                'start_time' => $regulations_info['start_time'],
                'admin_id' => $regulations_info['admin_id'],
                'admin_name' => $regulations_info['admin_name'],
                'seller_id' => $regulations_info['seller_id'],
                'seller_name' => $regulations_info['seller_name'],
                'store_key_id' => $regulations_info['store_key_id'],
                'store_key_name' => $regulations_info['store_key_name'],
            ];
            if (empty($regulations_info['order_id'])) {
                $map_order = [
                    'goods_id' => $car_data['id'],
                    'acquire_time' => ['elt', $regulations_info['start_time']],
                    'return_time' => ['egt', $regulations_info['start_time']],
                    'order_status' => OrderStatus::$OrderStatusFinish['code']
                ];
                $order_model = new Order();
                $order_data = $order_model->where($map_order)->field('id,customer_id,customer_name,customer_phone')->find();
                if (!empty($order_data)) {
                    $in_regulations['customer_id'] = $order_data['customer_id'];
                    $in_regulations['customer_name'] = $order_data['customer_name'];
                    $in_regulations['customer_phone'] = $order_data['customer_phone'];
                    $in_regulations['order_id'] = $order_data['id'];
                }
            } else {
                $map_order['id'] = $regulations_info['order_id'];
                $order_model = new Order();
                $order_data = $order_model->where($map_order)->field('id,customer_id,customer_name,customer_phone')->find();
                if (!empty($order_data)) {
                    $in_regulations['customer_id'] = $order_data['customer_id'];
                    $in_regulations['customer_name'] = $order_data['customer_name'];
                    $in_regulations['customer_phone'] = $order_data['customer_phone'];
                    $in_regulations['order_id'] = $order_data['id'];
                }
            }
            $ret = $this->save($in_regulations);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "违章信息录入成功";
                return $out_data;
            }
            $out_data['code'] = 102;
            $out_data['info'] = "违章信息录入失败";
            return $out_data;
        }
        $out_data = [
            'code' => 100,
            'info' => '违章信息已存在，不得重复添加'
        ];
        return $out_data;
    }

    /**
     * 修改违章记录
     * @param $regulations_info
     * id 违章数据id
     * start_time 违章时间
     * licence_plate 车牌号
     * state 处理状态
     * info 违章信息
     * remark 违章处理信息
     * admin_id 操作管理员id
     * admin_name 操作管理员账号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update_regulations($regulations_info)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($regulations_info['start_time']) || empty($regulations_info['licence_plate']) || empty($regulations_info['info'])) {
            return $out_data;
        }
        $map = array(
            'id' => $regulations_info['id'],
        );
        $cash_data = $this->where($map)->find();
        if (!empty($cash_data)) {
            $car_model = new CarCommon();
            $car_data = $car_model->where(['licence_plate' => $regulations_info['licence_plate']])->field('id')->find();
            if (empty($car_data)) {
                $out_data['code'] = 101;
                $out_data['info'] = "车辆数据不存在，请检查车牌还是否正确";
                return $out_data;
            }
            $update_regulations = [
//                'customer_id' => 0,
//                'order_id' => 0,
//                'good_id' => $car_data['id'],
                'licence_plate' => $regulations_info['licence_plate'],
                'info' => $regulations_info['info'],
                'remark' => $regulations_info['remark'],
                'update_time' => date("Y-m-d H:i:s"),
                'start_time' => $regulations_info['start_time'],
                'end_time' => $regulations_info['end_time'],
                'admin_id' => $regulations_info['admin_id'],
                'admin_name' => $regulations_info['admin_name'],
                'seller_id' => $regulations_info['seller_id'],
                'seller_name' => $regulations_info['seller_name'],
                'state' => $regulations_info['state']
            ];
//            $map_order = [
//                'goods_id' => $car_data['id'],
//                'acquire_time' => ['elt', $regulations_info['start_time']],
//                'return_time' => ['egt', $regulations_info['start_time']],
//                'order_status' => OrderStatus::$OrderStatusFinish['code']
//            ];
//            $order_model = new Order();
//            $order_data = $order_model->where($map_order)->field('id,customer_id,customer_information')->find();
//            if (!empty($order_data)) {
//                $customer_information = unserialize($order_data['customer_information']);
//                $update_regulations['customer_id'] = $order_data['customer_id'];
//                $update_regulations['customer_name'] = $customer_information['vehicle_drivers'];
//                $update_regulations['customer_phone'] = $customer_information['mobile_phone'];
//                $update_regulations['order_id'] = $order_data['id'];
//            }
            $ret = $this->save($update_regulations, ['id' => $regulations_info['id']]);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "违章信息修改成功";
                return $out_data;
            }
            $out_data['code'] = 102;
            $out_data['info'] = "违章信息修改失败";
            return $out_data;
        }
        $out_data['code'] = 103;
        $out_data['info'] = "违章信息不存在";
        return $out_data;
    }

}