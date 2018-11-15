<?php

namespace app\common\model;

/**
 *短信推送 数据模型
 */

use think\Model;

class CarOperationSms extends Model
{


    /**
     * 普通数据采集
     * @param $store_key_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function analyzeOperation($store_key_id)
    {
        $map['store_key_id'] = $store_key_id;
        $car_operation_model = new CarOperation();
        $oper_mileage_sms = new CarOperationSms();
        $car_mileage_list = $car_operation_model->getOperationCom($store_key_id, 0);
//        * pers 发送的管理人员 ['name'=>'姓名','phone'=>'电话号码']
//     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
//    * type 故障类型 0续航低于90km  1车机掉线 1小时
//    * store_key_id 归属总店
//    * store_key_name 车辆归属总店名称
        $store_model = new Store();
        $store_data = $store_model->where(['id' => $store_key_id])->field('store_name')->find();
        $pers_model = new CarOperation();
        $map = [
            'status' => 0, 'store_key_id' => $store_key_id, 'grade' => 0
        ];
        $pers = $pers_model->where($map)->field('name,phone')->select();
        $in_p_oper_sms = [
            'pers' => $pers,
            'grade' => 0,
            'goods_ids' => $car_mileage_list['data'],
            'type' => 1,
            'store_key_id' => $store_key_id,
            'store_key_name' => $store_data['store_name'],
        ];
        $oper_mileage_sms->addOperationSms($in_p_oper_sms, 0);

        $oper_device_sms = new CarOperationSms();
        $car_device_list = $car_operation_model->getOperationCom($store_key_id, 1);
        $in_m_oper_sms = [
            'pers' => $pers,
            'grade' => 0,
            'goods_ids' => $car_device_list['data'],
            'type' => 0,
            'store_key_id' => $store_key_id,
            'store_key_name' => $store_data['store_name'],
        ];
        $oper_device_sms->addOperationSms($in_m_oper_sms, 0);
    }

    /**
     * 普通数据采集
     * @param $store_key_id
     * @param string $grade
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function analyzeOperationManage($store_key_id, $grade = 1)
    {
        $map['store_key_id'] = $store_key_id;
        $car_operation_model = new CarOperation();
        $oper_mileage_sms = new CarOperationSms();
        $car_mileage_list = $car_operation_model->getOperationCom($store_key_id, 0);
//        * pers 发送的管理人员 ['name'=>'姓名','phone'=>'电话号码']
//     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
//    * type 故障类型 0续航低于90km  1车机掉线 1小时
//    * store_key_id 归属总店
//    * store_key_name 车辆归属总店名称
        $store_model = new Store();
        $store_data = $store_model->where(['id' => $store_key_id])->field('store_name')->find();
        $pers_model = new CarOperation();
        $map = [
            'status' => 0, 'store_key_id' => $store_key_id
        ];
        if (is_numeric($grade)) {
            $map['grade'] = $grade;
        }
        $pers = $pers_model->where($map)->field('name,phone')->select();
        $in_p_oper_sms = [
            'pers' => $pers,
            'grade' => $grade,
            'goods_ids' => $car_mileage_list['data'],
            'type' => 1,
            'store_key_id' => $store_key_id,
            'store_key_name' => $store_data['store_name'],
        ];
        $oper_mileage_sms->addOperationSms($in_p_oper_sms, 1);

        $oper_device_sms = new CarOperationSms();
        $car_device_list = $car_operation_model->getOperationCom($store_key_id, 1);

        $in_m_oper_sms = [
            'pers' => $pers,
            'grade' => $grade,
            'goods_ids' => $car_device_list['data'],
            'type' => 0,
            'store_key_id' => $store_key_id,
            'store_key_name' => $store_data['store_name'],
        ];
        $oper_device_sms->addOperationSms($in_m_oper_sms, 1);
    }

    public function formatx(&$data)
    {
        $data['type_str'] = "续航低于90km";
        if (intval($data['type']) == 1) {
            $data['type_str'] = "车机掉线两小时以上";
        }
        if (!empty($data['goods_ids'])) {
            $data['goods_ids'] = unserialize($data['goods_ids']);
        }
        if (!empty($data['pers'])) {
            $data['pers'] = unserialize($data['pers']);
        }
        $data['send_time_str'] = date("Y-m-d H:i:s", $data['send_time']);
    }

    /**
     * 添加推送短信数据
     * @param $operation_sms
     * pers 发送的管理人员 ['name'=>'姓名','phone'=>'电话号码']
     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
     * sms_content 短信内容 车牌号（店铺名称），车牌号（店铺名称）
     * type 故障类型 0续航低于90km  1车机掉线 1小时
     * store_key_id 归属总店
     * store_key_name 车辆归属总店名称
     * @param $manage 0普通推送  1管理员推送
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addOperationSms($operation_sms, $manage = 0)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($operation_sms['pers']) || empty($operation_sms['goods_ids']) || empty($operation_sms['store_key_id'])) {
            return $out_data;
        }
        $sms_content = "";
        foreach ($operation_sms['goods_ids'] as $v) {
            $sms_content .= $v['licence_plate'] . "(" . $v['site_name'] . ")、";
        }
        $in_operation_sms = [
            'pers' => serialize($operation_sms['pers']),
            'send_time' => time(),
            'goods_ids' => serialize($operation_sms['goods_ids']),
            'sms_content' => $sms_content,
            'manage' => $manage,
            'type' => $operation_sms['type'],
            'store_key_id' => $operation_sms['store_key_id'],
            'store_key_name' => $operation_sms['store_key_name']
        ];
        $ret = $this->save($in_operation_sms);
        if ($ret !== false) {
            foreach ($operation_sms['pers'] as $vx) {
                send_mileage_sms($vx['phone'], $sms_content);
            }
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 获取管理员
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationSms($id)
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        if (empty($id)) {
            return $out_data;
        } else {
            $operation_sms = $this->find($id);
            if (empty($operation_sms)) {
                return $out_data;
            }
            $this->formatx($operation_sms);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation_sms;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 获取管理员
     * @param int $store_key_id 店铺归属
     * @param $type 类型
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationSmsList($store_key_id = 153, $type = '')
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
                $map['type'] = $type;
            }
            $operation_sms_list = $this->where($map)->select();
            if (empty($operation_sms_list)) {
                return $out_data;
            }
            foreach ($operation_sms_list as &$value) {
                $this->formatx($value);
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation_sms_list;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

}