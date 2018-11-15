<?php

namespace app\seller\controller;

use app\common\controller\AdminBase;
use app\common\controller\SellerAdminBase;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\Store as StoreModel;
use definition\CarDeviceType;
use definition\DeviceStatus;
use definition\TerminalCarType;
use think\Config;
use tool\CarDeviceTool;

/**
 * 车机管理管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CarDevice extends SellerAdminBase
{
    protected $carcommon_model;
    protected $car_device_tool;
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->carcommon_model = new CarCommonModel();
        $this->car_device_tool = new CarDeviceTool();
        $this->store_model = new StoreModel();
    }

    /**
     * 车机设备管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $cardevice_list_all = $this->car_device_tool->getPageList($keyword, 'DESC', $page_config, 500);
        $device_status = DeviceStatus::$DEVICESTATUS_CODE;
        $device_type = CarDeviceType::$CARDEVICETYPE_CODE;
        $terminal_car_type = TerminalCarType::$CARDEVICETYPE_CODE;
        $cardevice_list = [];
        if (!empty($cardevice_list_all)) {
            foreach ($cardevice_list_all as $value) {
                if ($value['storeKeyId'] == $this->web_info['store_key_id']) {
                    $cardevice_list[] = $value;
                }
            }
        }
        return $this->fetch('index', ['cardevice_list' => $cardevice_list, 'device_status' => $device_status, 'device_type' => $device_type, 'terminal_car_type' => $terminal_car_type, 'keyword' => $keyword]);
    }

    /**
     * 添加车机设备
     * @return mixed
     */
    public function add()
    {
        $this->error('暂时未添加');
        $car_device_type = CarDeviceType::$CARDEVICETYPE_CODE;
        $car_device_status = DeviceStatus::$DEVICESTATUS_CODE;
        $store_list = $this->store_model->where(array('store_pid' => 0))->field('id,store_name')->select();
        return $this->fetch('add', ['device_type' => $car_device_type, 'store_list' => $store_list, 'device_status' => $car_device_status]);
    }

    /**
     * 保存车机设备信息
     */
    public function save()
    {
        $this->error('暂时未添加');
    }

    /**
     * 编辑车机设备信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $cardevice_data = $this->car_device_tool->getDevice($id);
        if (empty($cardevice_data)) {
            $this->error('数据不存在');
        }
        $car_device_type = CarDeviceType::$CARDEVICETYPE_CODE;
        $car_device_status = DeviceStatus::$DEVICESTATUS_CODE;
        $store_list = $this->store_model->where(array('store_pid' => 0))->field('id,store_name')->select();
        return $this->fetch('edit', ['cardevice' => $cardevice_data['data'], 'store_list' => $store_list, 'device_type' => $car_device_type, 'device_status' => $car_device_status]);
    }

    /**
     * 更新车机设备信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * name 设备名称
//            * device_number 设备编号
//            * device_type 设备类型
//            * device_status 设备状态
//            * sim_imsi 设备SIM卡号
//            * obd_versions 设备OBD版本
//            * store_key_id 总店铺id
//            * store_key_name 总店名称
            $name = $data['name'];
            $device_number = $data['device_number'];
            $device_type = $data['device_type'];
            $device_status = $data['device_status'];
            $sim_imsi = $data['sim_imsi'];
            $store_key_id = $data['store_key_id'];
            $obd_versions = $data['obd_versions'];
            $store_key_name = "";
            $out_store = $this->store_model->getStore($data['store_key_id'], '', true);
            if ($out_store['code'] == 0) {
                $store_key_name = $out_store['data']['store_name'];
            } else {
                $this->error($out_store['info']);
            }
            $update_cardevice_data = array(
                'id' => $id,
                'name' => $name,
                'device_type' => $device_type,
                'device_status' => $device_status,
                'store_key_name' => $store_key_name,
                'store_key_id' => $store_key_id
            );
            $out_data = $this->car_device_tool->updateDevice($update_cardevice_data);
            if ($out_data['code'] == 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 开门
     * @param $device_number
     */
    public function openDoor($device_number)
    {
        $out_data = $this->car_device_tool->openDoor($device_number);
        out_json_data($out_data);
    }

    /**
     * 关门
     * @param $device_number
     */
    public function closeDoor($device_number)
    {
        $out_data = $this->car_device_tool->closeDoor($device_number);
        out_json_data($out_data);
    }

    /**
     * 重启设备
     * @param $device_number
     */
    public function restartTerminal($device_number)
    {
        $out_data = $this->car_device_tool->restartTerminal($device_number);
        out_json_data($out_data);
    }

    /**
     * 点火
     * @param $device_number
     */
    public function powerSupply($device_number)
    {
        $out_data = $this->car_device_tool->powerSupply($device_number);
        out_json_data($out_data);
    }

    /**
     * 熄火
     * @param $device_number
     */
    public function powerFailure($device_number)
    {
        $out_data = $this->car_device_tool->powerFailure($device_number);
        out_json_data($out_data);
    }

    /**
     * 还车
     * @param $device_number
     */
    public function clearOrder($device_number)
    {
//        $out_data = $this->car_device_tool->clearOrder($device_number);
        $out_data = $this->car_device_tool->closeDoor($device_number);
        $out_data = $this->car_device_tool->powerFailure($device_number);
        out_json_data($out_data);
    }

    /**
     * 下单取车
     * @param $device_number
     */
    public function startOrder($device_number)
    {
        $out_data = $this->car_device_tool->startOrder($device_number);
        out_json_data($out_data);
    }


    /**
     * 寻车
     * @param $device_number
     */
    public function findCar($device_number)
    {
        $out_data = $this->car_device_tool->findCar($device_number);
        out_json_data($out_data);
    }

    /**
     * 设备点名
     * @param $device_number
     */
    public function callTerminal($device_number)
    {
        $out_data = $this->car_device_tool->callTerminal($device_number);
        out_json_data($out_data);
    }

    /**
     * 设备升级
     * @param $device_number
     */
    public function updateTerminal($device_number)
    {
        $updata = Config::get('updateTerminalData');
        $out_data = $this->car_device_tool->updateTerminal($device_number, $updata);
        out_json_data($out_data);
    }

    /**
     * 拍照
     * @param $device_number 设备id
     * @param int $direction 拍照摄像头 1前面 2内部 3后面
     */
    public function takePhotos($device_number, $direction = 1)
    {
        $out_data = $this->car_device_tool->takePhotos($device_number, $direction);
        out_json_data($out_data);
    }

    /**
     * 设置车型
     * @param $device_number
     * @param $car_type
     */
    public function setTerminalCarType($device_number, $car_type)
    {
        $device_data = $this->car_device_tool->getDevice($device_number);
        if (!empty($device_data['code'])) {
            $out_data['code'] = 100;
            $out_data['info'] = "设备信息有误";
            out_json_data($out_data);
        }
        $carType = '';
        switch (intval($car_type)) {
            case TerminalCarType::$CarTypeBQEC200['code']:
                $carType = TerminalCarType::$CarTypeBQEC200['value'];
                break;
            case TerminalCarType::$CarTypeQREQ1['code']:
                $carType = TerminalCarType::$CarTypeQREQ1['value'];
                break;
            case TerminalCarType::$CarTypeQRQE['code']:
                $carType = TerminalCarType::$CarTypeQRQE['value'];
                break;
            case TerminalCarType::$CarTypeDFQC['code']:
                $carType = TerminalCarType::$CarTypeDFQC['value'];
                break;
            case TerminalCarType::$CarTypeZT100['code']:
                $carType = TerminalCarType::$CarTypeZT100['value'];
                break;
            case TerminalCarType::$CarTypeYQQ1['code']:
                $carType = TerminalCarType::$CarTypeYQQ1['value'];
                break;
        }
        if (empty($carType)) {
            $out_data['code'] = 100;
            $out_data['info'] = "车型有误";
            out_json_data($out_data);
        }
        $this->car_device_tool->updateDevice(['id' => $device_data['data']['id'], 'terminal_car_type' => $car_type]);
        $out_data = $this->car_device_tool->setTerminalCarType($device_number, $carType);
        out_json_data($out_data);
    }

    /**
     * 删除设备
     * @param $device_id
     */
    public function delDevice($device_id)
    {
        $out_data = $this->car_device_tool->delTerminal($device_id);
        out_json_data($out_data);
    }
}