<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\Store as StoreModel;
use definition\ChargingStatus;
use definition\ChargingType;
use tool\ChargingDeviceTool;

/**
 * 充电桩设备管理
 * Class AdminUser
 * @package app\manage\controller
 */
class ChargingDevice extends AdminBase
{
    protected $charging_device_tool;
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->charging_device_tool = new ChargingDeviceTool();
        $this->store_model = new StoreModel();
    }

    /**
     * 充电桩管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['terminal_number|store_key_id|store_key_name'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $charging_device_list = $this->charging_device_tool->getChargingDeviceList($map, 'id DESC', 15, $this->web_info['is_super_admin'], $this->web_info['store_key_id'], $page_config);
        return $this->fetch('index', ['charging_device_list' => $charging_device_list, 'keyword' => $keyword]);
    }


    /**
     * 编辑充电桩信息
     * @param $id
     * @return mixed
     */
    public function device($id)
    {
        $charging_gun_data = $this->charging_device_tool->getChargingGun($id, true);
        if ($charging_gun_data['code'] == 0) {
            $map['store_key_id'] = $this->web_info['store_key_id'];
            $store_site_list = $this->store_model->getChildList($map);
            return $this->fetch('edit', ['charging' => $charging_gun_data['data'], 'store_site_list' => $store_site_list]);
        } else {
            $this->error($charging_gun_data['info']);
        }
    }
}