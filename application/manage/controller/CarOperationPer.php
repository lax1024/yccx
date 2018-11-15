<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\CarOperationPer as CarOperationPerModel;
use app\common\model\Store as StoreModel;
use definition\StoreType;

/**
 * 管理人员
 * Class AdminUser
 * @package app\manage\controller
 */
class CarOperationPer extends AdminBase
{
    protected $car_operation_per_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->car_operation_per_model = new CarOperationPerModel();
    }

    /**
     * 车辆违章管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['name|phone'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
//        $map = array(), $order = '', $config_page, $limit = 8
        $car_regulations_list = $this->car_operation_per_model->getPagelist($map, 'id DESC', $page_config, 15);
        return $this->fetch('index', ['car_regulations_list' => $car_regulations_list, 'keyword' => $keyword]);
    }

    /**
     * 添加车辆违章
     * @return mixed
     */
    public function add()
    {
        $store_model = new StoreModel();
        $store_key_list = $store_model->getSiteList(['store_pid'=>0,'store_type'=>StoreType::$StoreTypeElectrocar['code']]);
        return $this->fetch('add', ['store_key_list' => $store_key_list]);
    }

    /**
     * 保存车辆违章信息
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//             * name 管理人员姓名
//             * phone 管理人员电话
//             * grade 管理人员等级
//             * status 管理人员状态
//             * store_key_id 归属店铺
//             * store_key_name
            $store_model = new StoreModel();
            $store_data = $store_model->getStoreField($data['store_key_id'],'store_name');
            $store_store_pid
            $in_operation_per_data = array(
                'name' => $data['name'],
                'phone' => $data['phone'],
                'grade' => $data['grade'],
                'status' => $data['status'],
                'store_key_id' => $data['store_key_id'],
                'store_key_name' => $store_data['store_name'],
            );
            $out_data = $this->car_operation_per_model->addOperationPer($in_operation_per_data);
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
        $car_operation_per_data = $this->car_operation_per_model->getOperationPer($id);
        if ($car_operation_per_data['code'] == 0) {
            return $this->fetch('edit', ['car_operation_per_data' => $car_operation_per_data['data']]);
        } else {
            $this->error($car_operation_per_data['info']);
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
//             * id 数据id
//             * name 管理人员姓名
//             * phone 管理人员电话
//             * grade 管理人员等级
//             * status 管理人员状态
//             * store_key_id 归属店铺
//             * store_key_name
            $update_operation_per_data = array(
                'id' => $id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'grade' => $data['grade'],
                'status' => $data['status'],
                'store_key_id' => 153,
                'store_key_name' => "优车出行"
            );
            $out_data = $this->car_operation_per_model->updateOperationPer($update_operation_per_data);
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
        $ret = $this->car_operation_per_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }

}