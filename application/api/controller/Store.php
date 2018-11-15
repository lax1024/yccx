<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\Store as StoreModel;

/**
 * 店铺接口 对外公共
 * Class Ueditor
 * @package app\api\controller
 */
class Store extends HomeBase
{
    private $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->store_model = new StoreModel();
    }

    /**
     * 获取区域列表
     * @param $pid
     * @param $type
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_store_area_list($pid = '', $type = 2)
    {
        $store_model = new StoreModel();
        $store_list = $store_model->getStoreAreaList($pid, "id,store_name", $type);
        $out_data['code'] = 0;
        $out_data['data'] = $store_list;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }
}