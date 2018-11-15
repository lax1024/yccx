<?php

namespace app\seller\controller;

use app\common\controller\AdminBase;
use app\common\controller\SellerAdminBase;
use app\common\model\CarRegulations as CarRegulationsModel;
use app\common\model\Order as OrderModel;

/**
 * 车辆违章违章管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CarRegulations extends SellerAdminBase
{
    protected $car_regulations_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->car_regulations_model = new CarRegulationsModel();
    }

    /**
     * 车辆违章管理
     * @param string $keyword
     * @param int $page
     * @param int $state
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $state = 10)
    {
        $map = ['store_key_id' => $this->web_info['store_key_id']];
        if ($keyword) {
            $map['customer_id|customer_name|customer_phone|licence_plate'] = ['like', "%{$keyword}%"];
        }
        if (!empty($state)) {
            $map['state'] = $state;
        }
        $page_config = ['page' => $page, 'query' => ['state' => $state, 'keyword' => $keyword]];
//        $map = array(), $order = '', $config_page, $limit = 8
        $car_regulations_list = $this->car_regulations_model->getPagelist($map, 'id DESC', $page_config, 15);
        return $this->fetch('index', ['car_regulations_list' => $car_regulations_list, 'keyword' => $keyword, 'state' => $state]);
    }

    /**
     * 添加车辆违章
     * @param string $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($order_id = '')
    {
        $order_model = new OrderModel();
        $order_data = $order_model->getOrder($order_id, '', true);
        if (empty($order_data['code'])) {
            $u_data = [
                'customer_name' => $order_data['data']['customer_information']['vehicle_drivers'],
                'order_id' => $order_id,
                'customer_phone' => $order_data['data']['customer_information']['mobile_phone'],
                'licence_plate' => $order_data['data']['order_goods']['licence_plate'],
            ];
        } else {
            $u_data = [
                'customer_name' => '',
                'order_id' => '',
                'customer_phone' => '',
                'licence_plate' => '',
            ];
        }

        return $this->fetch('add', $u_data);
    }

    /**
     * 保存车辆违章信息
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * start_time 违章时间
//            * licence_plate 车牌号
//            * info 违章信息
//            * admin_id 操作管理员id
//            * admin_name 操作管理员账号
            $in_regulations_data = array(
                'order_id' => $data['order_id'],
                'start_time' => strtotime($data['start_time']),
                'end_time' => $data['end_time'],
                'licence_plate' => $data['licence_plate'],
                'info' => $data['info'],
                'remark' => $data['remark'],
                'admin_id' => 0,
                'admin_name' => '',
                'seller_id' => $this->seller_info['seller_id'],
                'state' => $data['state'],
                'seller_name' => $this->seller_info['seller_name'],
                'store_key_id' => $this->seller_info['store_key_id'],
                'store_key_name' => $this->seller_info['store_key_name'],
            );
            $out_data = $this->car_regulations_model->add_regulations($in_regulations_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑车辆违章信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $car_regulations_data = $this->car_regulations_model->getCarRegulations($id);
        if ($car_regulations_data['code'] == 0) {
            if ($car_regulations_data['data']['store_key_id'] != $this->web_info['store_key_id']) {
                $this->error("无权操作");
            }
            return $this->fetch('edit', ['car_regulations' => $car_regulations_data['data']]);
        } else {
            $this->error($car_regulations_data['info']);
        }
    }

    /**
     * 更新车辆违章信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * start_time 违章时间
//            * licence_plate 车牌号
//            * state 处理状态
//            * info 违章信息
//            * remark 违章处理信息
//            * admin_id 操作管理员id
//            * admin_name 操作管理员账号
            $in_regulations_data = array(
                'id' => $id,
                'start_time' => strtotime($data['start_time']),
                'end_time' => $data['end_time'],
                'licence_plate' => $data['licence_plate'],
                'info' => $data['info'],
                'remark' => $data['remark'],
                'state' => $data['state'],
                'admin_id' => 0,
                'admin_name' => '',
                'seller_id' => $this->seller_info['manage_name'],
                'seller_name' => $this->seller_info['manage_name'],
            );
            $out_data = $this->car_regulations_model->update_regulations($in_regulations_data);
            if ($out_data['code'] == 0) {
                $this->success('修改成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除车辆违章
     * @param $id
     */
    public function delete($id)
    {
        $ret = $this->car_regulations_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }

}