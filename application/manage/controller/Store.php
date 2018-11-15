<?php

namespace app\manage\controller;

use app\common\model\Store as StoreModel;
use app\common\controller\AdminBase;
use definition\StoreType;

/**
 * 店铺管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Store extends AdminBase
{
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->store_model = new StoreModel();
    }

    /**
     * 店铺管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['seller_id|store_name|address|store_principal'] = ['like', "%{$keyword}%"];
        }
        if ($this->web_info['is_super_admin']) {
            $map['store_pid'] = 0;
        } else {
            $map['store_pid'] = $this->web_info['store_key_id'];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $store_list = $this->store_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        return $this->fetch('index', ['store_list' => $store_list, 'keyword' => $keyword]);
    }

    /**
     * 添加店铺
     * @return mixed
     */
    public function add()
    {
        $this->error('禁止访问');
        $business_start = get_time_hours(8);
        $business_end = get_time_hours(17);
        $store_type = StoreType::$STORETYPE_CODE;
        return $this->fetch('add', ['business_start' => $business_start, 'store_type' => $store_type, 'business_end' => $business_end]);
    }

    /**
     * 保存店铺信息
     */
    public function save()
    {
        $this->error('禁止访问');
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * seller_id 商家管理员用户id
//            * store_pid 店铺父级id
//            * store_name 店铺名称
//            * store_principal 店铺负责人
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
//            *非必须数据
//            * store_banner 店铺图片
//            * store_tel 店铺图片
            $in_store_data = array(
                'seller_id' => $data['seller_id'],
                'store_pid' => $data['store_pid'],
                'store_name' => $data['store_name'],
                'store_principal' => $data['store_principal'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'street_id' => $data['street_id'],
                'address' => $data['address'],
                'is_area' => $data['is_area'],
                'store_banner' => $data['store_banner'],
                'store_imgs' => $data['store_imgs'],
                'store_tel' => $data['store_tel'],
            );
            $out_data = $this->store_model->addStore($in_store_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑店铺信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $store_data = $this->store_model->getStore($id, '', true);
        if ($store_data['code'] == 0) {
            $business_start = get_time_hours($store_data['business']['business_start']);
            $business_end = get_time_hours($store_data['business']['business_end']);
            $parent_store = $this->store_model->getParentStoreList($store_data['store_pid']);
            $store_type = StoreType::$STORETYPE_CODE;
            return $this->fetch('edit', ['store' => $store_data['data'], 'parent_store_list' => $parent_store['data'], 'store_type' => $store_type, 'address_config' => json_encode($store_data['address_config']), 'business_start' => $business_start, 'business_end' => $business_end]);
        } else {
            $this->error($store_data['info']);
        }
    }

    /**
     * 更新店铺信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * 必须数据
//            * id 数据id
//            * 非必须数据
//            * seller_id 商家管理员用户id
//            * store_pid 店铺父级id
//            * store_name 店铺名称
//            * store_principal 店铺负责人
//            * store_charging_is 是否有充电桩
//            * store_scope 允许还车范围 单位米
//            * store_park_price 停车场收费价格
//            * take_park_remark 取车时说明文字
//            * return_park_remark 还车时的文字说明
//            * store_type 店铺类型
//            * store_tel 店铺负责人电话
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * store_banner 店铺图片
//            * store_imgs店铺图片
//            * is_area 店铺是否是区域
//            * location_longitude 地理位置 经度
//            * location_latitude 地理位置  纬度
//            * business_start 开店时间
//            * business_end 关店时间
//            * store_status 店铺状态 0正常 1关闭
//
            $up_store_data = array(
                'id' => $id,
                'store_pid' => $data['store_pid'],
                'store_name' => $data['store_name'],
                'store_intro' => $data['store_intro'],
                'store_scope' => $data['store_scope'],
                'store_charging_is' => $data['store_charging_is'],
                'store_park_price' => $data['store_park_price'],
                'take_park_remark' => $data['take_park_remark'],
                'return_park_remark' => $data['return_park_remark'],
                'store_type' => $data['store_type'],
                'store_principal' => $data['store_principal'],
                'store_tel' => $data['store_tel'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'street_id' => $data['street_id'],
                'address' => $data['address'],
                'is_area' => $data['is_area'],
                'store_banner' => $data['store_banner'],
                'store_imgs' => $data['store_imgs'],
                'location_longitude' => $data['location_longitude'],
                'location_latitude' => $data['location_latitude'],
                'business_start' => $data['business_start'],
                'business_end' => $data['business_end'],
                'store_status' => $data['store_status']
            );
            $out_data = $this->store_model->updateStore($up_store_data, false);
            if ($out_data['code'] === 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除店铺
     * @param $id
     */
    public function delete($id)
    {
        $ret = $this->store_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }

    /**
     * 关闭店铺
     * @param $id
     */
    public function lock($id)
    {
        if ($this->store_model->addLockStore($id)) {
            $this->success('关闭成功');
        } else {
            $this->error('锁定失败');
        }
    }

    /**
     * 开启店铺
     * @param $id
     */
    public function del_lock($id)
    {
        if ($this->store_model->delLockStore($id)) {
            $this->success('开启成功');
        } else {
            $this->error('开启失败');
        }
    }

    /**
     * 获取店铺列表信息
     * @param $keyword
     * @return mixed
     */
    public function getlistjson($keyword)
    {
        $map['id|seller_id|store_name|address|store_principal'] = ['like', "%{$keyword}%"];
        $store_list = $this->store_model->where($map)->select();
        return $store_list;
    }
}