<?php

namespace app\seller\controller;

use app\common\controller\AdminBase;
use app\common\controller\SellerAdminBase;
use app\common\model\StoreCharging as StoreChargingModel;

/**
 * 充电桩店铺管理
 * Class AdminUser
 * @package app\manage\controller
 */
class StoreCharging extends SellerAdminBase
{
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->store_model = new StoreChargingModel();
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
            $map['store_name|address|store_principal|store_tel'] = ['like', "%{$keyword}%"];
        }
        $map['store_key_id'] = $this->web_info['store_key_id'];
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
        $map['store_key_id'] = $this->web_info['store_key_id'];
        $store_site_list = $this->store_model->getChildList($map);
        return $this->fetch('add', ['store_site_list' => $store_site_list]);
    }

    /**
     * 保存店铺信息
     */
    public function save()
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * 必须数据
//            * id 数据id
//            * 非必须数据
//            * store_pid 店铺父级id
//            * store_name 店铺名称
//            * store_tel 店铺负责人
//            * store_principal 店铺负责人
//            * store_intro 店铺简介
//            * commission_str 平台收取服务费
//            * commission 平台提点
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * store_banner 店铺图片
//            * store_imgs店铺图片
//            * store_longitude 地理位置 经度
//            * store_latitude 地理位置  纬度
//            * store_tag 店铺标签数据
//            * store_status 店铺状态 0正常 1关闭
//            * num_quick 快充桩个数
//            * num_slow 慢充桩个数
//            * power 慢充桩个数
//            * charging_price 充电桩单价
//            * parting_price 停车场单价
//            * car_accept 停车场适合车型
//            * car_unaccept 停车场不适合车型
//            * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
            if (!empty($data['store_tag']) && strpos($data['store_tag'], "、") > 0) {
                $store_tag = explode("、", $data['store_tag']);
                $data['store_tag'] = serialize($store_tag);
            }

            $in_store_data = array(
                'store_pid' => $data['store_pid'],
                'store_name' => $data['store_name'],
                'store_intro' => $data['store_intro'],
                'commission' => $data['commission'],
                'commission_str' => "百分之" . $data['commission'],
                'num_quick' => $data['num_quick'],
                'num_slow' => $data['num_slow'],
                'power' => $data['power'],
                'charging_price' => $data['charging_price'],
                'parting_price' => $data['parting_price'],
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
                'store_tag' => $data['store_tag'],
                'car_accept' => $data['car_accept'],
                'car_unaccept' => $data['car_unaccept'],
                'store_longitude' => $data['store_longitude'],
                'store_latitude' => $data['store_latitude'],
                'store_status' => $data['store_status'],
                'store_key_id' => $this->web_info['store_key_id']
            );
            $out_data = $this->store_model->addStore($in_store_data);
            if ($out_data['code'] === 0) {
                $this->success('更新成功');
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
            $map['store_key_id'] = $this->web_info['store_key_id'];
            $store_site_list = $this->store_model->getChildList($map);
            return $this->fetch('edit', ['store' => $store_data['data'], 'store_site_list' => $store_site_list, 'address_config' => json_encode($store_data['address_config'])]);
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
//            * store_pid 店铺父级id
//            * store_name 店铺名称
//            * store_tel 店铺负责人
//            * store_principal 店铺负责人
//            * store_intro 店铺简介
//            * commission_str 平台收取服务费
//            * commission 平台提点
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * store_banner 店铺图片
//            * store_imgs店铺图片
//            * store_longitude 地理位置 经度
//            * store_latitude 地理位置  纬度
//            * store_tag 店铺标签数据
//            * store_status 店铺状态 0正常 1关闭
//            * num_quick 快充桩个数
//            * num_slow 慢充桩个数
//            * power 慢充桩个数
//            * charging_price 充电桩单价
//            * parting_price 停车场单价
//            * car_accept 停车场适合车型
//            * car_unaccept 停车场不适合车型
//            * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
            if (!empty($data['store_tag']) && strpos($data['store_tag'], "、") > 0) {
                $store_tag = explode("、", $data['store_tag']);
                $data['store_tag'] = serialize($store_tag);
            }

            $up_store_data = array(
                'id' => $id,
                'store_pid' => $data['store_pid'],
                'store_name' => $data['store_name'],
                'store_intro' => $data['store_intro'],
                'commission' => $data['commission'],
                'commission_str' => "百分之" . $data['commission'],
                'num_quick' => $data['num_quick'],
                'num_slow' => $data['num_slow'],
                'power' => $data['power'],
                'charging_price' => $data['charging_price'],
                'parting_price' => $data['parting_price'],
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
                'store_tag' => $data['store_tag'],
                'car_accept' => $data['car_accept'],
                'car_unaccept' => $data['car_unaccept'],
                'store_longitude' => $data['store_longitude'],
                'store_latitude' => $data['store_latitude'],
                'store_status' => $data['store_status']
            );
            $out_data = $this->store_model->updateStore($up_store_data);
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
        $map['id|store_name|address|store_principal|store_tel'] = ['like', "%{$keyword}%"];
        $store_list = $this->store_model->where($map)->select();
        return $store_list;
    }
}