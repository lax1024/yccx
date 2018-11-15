<?php

namespace app\api\controller;

use app\common\controller\UserBase;
use app\common\model\CustomerCash;
use think\Config;
use think\Session;
use tool\CarDeviceTool;

/**
 * 管理员接口
 * Class Ueditor
 * @package app\api\controller
 */
class AdminDevice extends UserBase
{
    private $car_device_tool;

    protected function _initialize()
    {
        parent::_initialize();
        $this->car_device_tool = new CarDeviceTool();
        $out_data = array(
            'code' => 1,
            'info' => "无权访问数据"
        );
        $admin_dispatch_id = Config::get('admin_dispatch_id');
        $customer_id = Session::get('customer_id');
        if (!in_array($customer_id, $admin_dispatch_id)) {
            out_json_data($out_data);
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
        $out_data = $this->car_device_tool->clearOrder($device_number);
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

}