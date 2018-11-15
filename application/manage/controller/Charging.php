<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\Charging as ChargingModel;
use app\common\model\Store as StoreModel;
use definition\ChargingStatus;
use definition\ChargingType;
use tool\WecharTool;

/**
 * 充电桩管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Charging extends AdminBase
{
    protected $charging_model;
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->charging_model = new ChargingModel();
        $this->store_model = new StoreModel();
    }

    /**
     * 充电桩管理
     * @param string $keyword
     * @param int $page
     * @param int $charging_status
     * @param int $charging_type
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $charging_status = 0, $charging_type = 0, $pid = 0)
    {
        $map = [];
        if ($keyword) {
            $map['store_id|store_key_id|store_key_name|store_name|device_number'] = ['like', "%{$keyword}%"];
        }
        if (!empty($charging_status)) {
            $map['charging_status'] = $charging_status;
        }
        $charging_status_list = ChargingStatus::$CHARGINGSTATUS_CODE;
        if (!empty($charging_type)) {
            $map['charging_type'] = $charging_type;
        }
        $page_config = ['page' => $page, 'query' => ['charging_status' => $charging_status, 'charging_type' => $charging_type, 'keyword' => $keyword]];
        $charging_list = $this->charging_model->getChargingList($map, 'id DESC', 15, true, '', $page_config);
        return $this->fetch('index', ['charging_list' => $charging_list, 'keyword' => $keyword, 'charging_status' => $charging_status, 'charging_type' => $charging_type, 'charging_status_list' => $charging_status_list]);
    }

    /**
     * 添加充电桩
     * @return mixed
     */
    public function add()
    {
        $map['store_pid'] = 0;
        $store_list = $this->store_model->getChildList($map);
        $charging_status = ChargingStatus::$CHARGINGSTATUS_CODE;
        $charging_type = ChargingType::$CHARGINGTYPE_CODE;
        return $this->fetch('add', ['store_list' => $store_list, 'charging_status' => $charging_status, 'charging_type' => $charging_type]);
    }

    /**
     * 保存充电桩信息
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * store_id 店铺id
//            * store_name 店铺名称
//            * name 充电桩名称
//            * device_number 充电桩设备编号
//            * device_gun 充电桩设备抢号
//            * power  充电桩功率
//            * quantity_price 充电桩电费价格
//            * hour_price 每小时充电价格
//            * location_longitude 充电桩位置经度
//            * location_latitude 充电桩位置纬度
//            * charging_status 充电桩状态
//            * charging_type 充电桩类型
//            * store_key_id 电桩归属总店id
//            * store_key_name 电桩归属总店名称
            $store_key_id = $data['store_key_id'];
            $out_store = $this->store_model->getStoreField($store_key_id, 'store_name,location_longitude,location_latitude');
            $store_info = array();
            if (!empty($out_store)) {
                $store_info = $out_store;
            } else {
                $store_info = array(
                    'store_id' => 0,
                    'location_longitude' => '106.62215718',
                    'location_latitude' => '26.40585615',
                    'store_name' => "优车出行"
                );
            }
            $store_key_name = $store_info['store_name'];
            $in_charging_data = array(
                'store_id' => $store_key_id,
                'store_name' => $store_key_name,
                'name' => $data['name'],
                'device_number' => $data['device_number'],
                'device_gun' => $data['device_gun'],
                'power' => $data['power'],
                'quantity_price' => $data['quantity_price'],
                'hour_price' => $data['hour_price'],
                'location_longitude' => $store_info['location_longitude'],
                'location_latitude' => $store_info['location_latitude'],
                'charging_status' => $data['charging_status'],
                'charging_type' => $data['charging_type'],
                'store_key_id' => $store_key_id,
                'store_key_name' => $store_key_name,
            );
            $out_data = $this->charging_model->addCharging($in_charging_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑充电桩信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $charging_data = $this->charging_model->getCharging($id, true);
        if ($charging_data['code'] == 0) {
            $map['store_pid'] = 0;
            $store_list = $this->store_model->getChildList($map);
            $charging_status = ChargingStatus::$CHARGINGSTATUS_CODE;
            $charging_type = ChargingType::$CHARGINGTYPE_CODE;
            return $this->fetch('edit', ['charging' => $charging_data['data'], 'store_list' => $store_list, 'charging_status' => $charging_status, 'charging_type' => $charging_type]);
        } else {
            $this->error($charging_data['info']);
        }
    }

    /**
     * 更新充电桩信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * store_id 店铺id
//            * store_name 店铺名称
//            * name 充电桩名称
//            * device_number 充电桩设备编号
//            * device_gun 充电桩设备抢编号
//            * power  充电桩功率
//            * quantity_price 充电桩电费价格
//            * hour_price 每小时充电价格
//            * location_longitude 充电桩位置经度
//            * location_latitude 充电桩位置纬度
//            * charging_status 充电桩状态
//            * charging_type 充电桩类型
//            * store_key_id 电桩归属总店id
//            * store_key_name 电桩归属总店名称
            $store_key_id = $data['store_key_id'];
            $out_store = $this->store_model->getStoreField($store_key_id, 'store_name,location_longitude,location_latitude');
            $store_info = array();
            if (!empty($out_store)) {
                $store_info = $out_store;
            } else {
                $store_info = array(
                    'store_id' => 0,
                    'location_longitude' => '106.62215718',
                    'location_latitude' => '26.40585615',
                    'store_name' => "优车出行"
                );
            }
            $store_key_name = $store_info['store_name'];
            $update_charging_data = array(
                'id' => $id,
                'store_id' => $data['store_key_id'],
                'store_name' => $store_info['store_name'],
                'name' => $data['name'],
                'device_number' => $data['device_number'],
                'device_gun' => $data['device_gun'],
                'power' => $data['power'],
                'quantity_price' => $data['quantity_price'],
                'hour_price' => $data['hour_price'],
                'location_longitude' => $store_info['location_longitude'],
                'location_latitude' => $store_info['location_latitude'],
                'charging_status' => $data['charging_status'],
                'charging_type' => $data['charging_type'],
                'store_key_id' => $store_key_id,
                'store_key_name' => $store_key_name
            );
            $out_data = $this->charging_model->updateCharging($update_charging_data);
            if ($out_data['code'] == 0) {
                $this->success('修改成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除充电桩
     * @param $id
     */
    public function delete($id)
    {
        $ret = $this->charging_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }

    /**
     * 下线
     * @param $id
     */
    public function lock($id)
    {
        if ($this->charging_model->addLockCar($id)) {
            $this->success('下线成功');
        } else {
            $this->error('下线失败');
        }
    }

    /**
     * 上线
     * @param $id
     */
    public function del_lock($id)
    {
        if ($this->charging_model->delLockCar($id)) {
            $this->success('上线成功');
        } else {
            $this->error('上线失败');
        }
    }

    /**
     * 创建二维码
     * @param $device_number
     * @param $device_gun
     */
    public function creat_qrcode($device_number)
    {
        $out_data = $this->charging_model->updateChargingUrl($device_number);
        if (empty($out_data['code'])) {
            $this->success("生成成功");
        }
        $this->error("生成失败");
    }
}