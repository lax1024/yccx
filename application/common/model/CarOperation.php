<?php

namespace app\common\model;

/**
 *车辆状态采集数据 数据模型
 */

use definition\CarStatus;
use think\Model;

class CarOperation extends Model
{

    /**
     * 采集异常数据
     * @param $store_key_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gatherOperation($store_key_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($store_key_id)) {
            return $out_data;
        }
        $car_common_model = new CarCommon();
        $map['store_key_id'] = $store_key_id;
        $map['car_status'] = ['between', CarStatus::$CarStatusNormal['code'] . "," . CarStatus::$CarStatusInuse['code']];
        $car_common_list = $car_common_model->where($map)->select();
        $car_common_mileage = [];
        $car_common_device = [];
        foreach ($car_common_list as $value) {
            $car_common_model->formatx($value);
            if (intval($value['car_status']) == CarStatus::$CarStatusInuse['code']) {
                $value['store_site_name'] = "车辆使用中";
            }
            if (intval($value['car_device']) == 0) {
                $car_common_device[] = [
                    'goods_id' => $value['id'],
                    'licence_plate' => $value['licence_plate'],
                    'site_name' => $value['store_site_name']
                ];
            }
            if (intval($value['driving_mileage_num']) < 80) {
                $car_common_mileage[] = [
                    'goods_id' => $value['id'],
                    'licence_plate' => $value['licence_plate'],
                    'site_name' => $value['store_site_name']
                ];
            }
        }
        $operation = [
            'goods_ids' => serialize($car_common_device),
            'type' => 1,
            'store_key_id' => $store_key_id,
        ];
        $car_operation_model = new CarOperation();
        $car_operation_model->addOperation($operation);
        $operation = [
            'goods_ids' => serialize($car_common_mileage),
            'type' => 0,
            'store_key_id' => $store_key_id,
        ];
        $car_operation_model = new CarOperation();
        $out_data = $car_operation_model->addOperation($operation);
        return $out_data;
    }

    public function formatx(&$data)
    {
        $data['status_str'] = "未处理";
        if (intval($data['status']) == 1) {
            $data['status_str'] = "已处理";
        }
        if (!empty($data['goods_ids'])) {
            $data['goods_ids'] = unserialize($data['goods_ids']);
        }
    }

    /**
     * 添加采集状态
     * @param $operation
     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
     * type 故障类型 0续航低于90km  1车机掉线 1小时
     * store_key_id
     * @return array
     */
    /**
     * 添加采集状态
     * @param $operation
     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
     * type 故障类型 0续航低于90km  1车机掉线 1小时
     * store_key_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addOperation($operation)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($operation['goods_ids']) || empty($operation['store_key_id'])) {
            return $out_data;
        }

        $store_model = new Store();
        $store_data = $store_model->where(['id' => $operation['store_key_id']])->field('store_name')->find();
        $in_operation = [
            'goods_ids' => serialize($operation['goods_ids']),
            'type' => $operation['type'],
            'gain_time' => time(),
            'store_key_id' => $operation['store_key_id'],
            'store_key_name' => $store_data['store_name']
        ];
        $ret = $this->save($in_operation);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 获取采集信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperation($id)
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        if (empty($id)) {
            return $out_data;
        } else {
            $operation = $this->find($id);
            if (empty($operation)) {
                return $out_data;
            }
            $this->formatx($operation);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 根据类型获取符合的数据
     * @param $store_key_id
     * @param int $type 0续航低于90km  1车机掉线 1小时
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationCom($store_key_id, $type = 0)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        $map['store_key_id'] = $store_key_id;
        $map['type'] = $type;
        $oper_list = $this->where($map)->order('gain_time DESC')->limit(2)->select();
        if ($oper_list->count() == 2) {
            $goods_ids_old = unserialize($oper_list[1]['goods_ids']);
            $goods_ids_new = unserialize($oper_list[0]['goods_ids']);
            $goods_ids = [];
            foreach ($goods_ids_new as $value) {
                foreach ($goods_ids_old as $valuex) {
                    if ($value['goods_id'] == $valuex['goods_id']) {
                        $goods_ids[] = $value;
                    }
                }
            }
            $out_data['code'] = 0;
            $out_data['data'] = $goods_ids;
            $out_data['info'] = "获取成功";
            return $out_data;
        } else {
            $out_data['code'] = 101;
            $out_data['info'] = "数据不存在";
            return $out_data;
        }
    }

    /**
     * 获取管理员
     * @param int $store_key_id 店铺归属
     * @param $type 等级
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationList($store_key_id = 153, $type = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        if (empty($store_key_id)) {
            return $out_data;
        } else {
            $map = ['store_key_id' => $store_key_id];
            if (is_numeric($type)) {
                $map['grade'] = $type;
            }
            $operation_list = $this->where($map)->select();
            if (empty($operation_list)) {
                return $out_data;
            }
            foreach ($operation_list as &$value) {
                $this->formatx($value);
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation_list;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

}