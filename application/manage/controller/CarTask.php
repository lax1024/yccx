<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\CarAbnormalLog;
use app\common\model\CarCommon;
use app\common\model\CarOperationList as CarOperationListModel;

/**
 * 任务车辆管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CarTask extends AdminBase
{
    protected $car_operation_list_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->car_operation_list_model = new CarOperationListModel();
    }

    /**
     * 任务管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $status = 0)
    {
        $car_abnormal_log_model = new CarAbnormalLog();
        $car_abnormal_log_model->getAbnormalList(153, 0, 0);
        $carcommon_model = new CarCommon();
        $carcommon_model->getUnusualCar($this->web_info['store_key_id'], 90, 0,true);
        $map = [];
        if (!empty($keyword)) {
            $map['device_number|store_site_name|licence_plate'] = ['like', "%{$keyword}%"];
        }
        if (empty($status)) {
            $status = 0;
        }
        $map['status'] = $status;
        $page_config = ['page' => $page, 'query' => ['status' => $status, 'keyword' => $keyword]];
//        $map = array(), $order = '', $config_page, $limit = 8
        $car_operation_list = $this->car_operation_list_model->getPagelist($map, 'type DESC', $page_config, 15);
        return $this->fetch('index', ['car_operation_list' => $car_operation_list, 'keyword' => $keyword, 'status' => $status]);
    }

    /**
     * 删除车辆违章
     * @param $id
     */
    public function cancel($id)
    {
        $ret = $this->car_operation_list_model->endOperationList($id);
        if ($ret !== false) {
            $this->success('取消成功');
        }
        $this->success('取消失败');
    }

}