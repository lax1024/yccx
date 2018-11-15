<?php

namespace app\common\model;

/**
 * 店铺信息管理
 * 实现店铺信息的基础数据操作
 * 添加店铺信息
 * 修改店铺信息
 */

use definition\GoodsType;
use definition\StoreType;
use think\Cache;
use think\Model;

class Store extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    public function formatx(&$data)
    {
        if (!empty($data['store_imgs'])) {
            $data['store_imgs'] = unserialize($data['store_imgs']);
        }
        if (!empty($data['store_rail_point'])) {
            $data['store_rail_point'] = htmlspecialchars_decode(unserialize($data['store_rail_point']));
            $data['store_rail_point'] = str_replace('"', '\"', $data['store_rail_point']);
        }
    }

    /**
     * 获取全部分店与门店列表
     * @param $map
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildList($map, $field = '')
    {
        if (empty($field)) {
            $store_list = $this->where($map)->select();
        } else {
            $store_list = $this->where($map)->field($field)->select();
        }
        $store_list = array2levelc($store_list);
        return $store_list;
    }

    /**
     * 获取店铺名称
     * @param string $store_id
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreName($store_id = '')
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('id' => $store_id))->find();
            if (!empty($store_data)) {
                return $store_data['store_name'];
            }
        }
        return "";
    }

    /**
     * 获取店铺指定字段
     * @param string $store_id
     * @param $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreField($store_id = '', $field)
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('id' => $store_id))->field($field)->find();
            if (!empty($store_data)) {
                return $store_data;
            }
        }
        return array();
    }

    /**
     * 获取店铺信息
     * @param string $store_id
     * @param string $seller_id
     * @param bool $is_admin
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStore($store_id = '', $seller_id = '', $is_admin = false)
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('id' => $store_id))->find();
            $this->formatx($store_data);
            if (!empty($store_data)) {
                if (intval($store_data['store_status']) == 1 && $is_admin === false) {
                    $out_data['code'] = 950;
                    $out_data['info'] = "店铺已关闭";
                    return $out_data;//店铺已关闭
                }
                $address_config = array(
                    'default' => false,
                    'type' => 1,
                    'province' => "",
                    'city' => "",
                    'area' => "",
                    'street' => "",
                );
                if (!empty($store_data['province_id']) && !empty($store_data['city_id']) && !empty($store_data['area_id'])) {
                    $address_config['default'] = true;
                    $address_config['type'] = 4;
                    $address_config['province'] = $store_data['province_id'] . "";
                    $address_config['city'] = $store_data['city_id'] . "";
                    $address_config['area'] = $store_data['area_id'] . "";
                    $address_config['street'] = $store_data['street_id'] . "";
                }
                if (!empty($store_data['business_start'])) {
                    $business_start = intval(substr($store_data['business_start'], 0, 2));
                } else {
                    $business_start = 8;
                }
                if (!empty($store_data['business_end'])) {
                    $business_end = intval(substr($store_data['business_end'], 0, 2));
                } else {
                    $business_end = 17;
                }
                $business = array(
                    'business_start' => $business_start,
                    'business_end' => $business_end
                );

                $out_data['business'] = $business;
                $out_data['address_config'] = $address_config;
                $out_data['code'] = 0;
                $out_data['data'] = $store_data;
                $out_data['info'] = "获取成功";
                return $out_data;//获取成功
            }
        }
        if (!empty($seller_id)) {
            $store_data = $this->where(array('seller_id' => $store_id, 'is_area' => 0))->find();
            $this->formatx($store_data);
            if (!empty($store_data)) {
                if (intval($store_data['store_status']) == 1 && $is_admin === false) {
                    $out_data['code'] = 950;
                    $out_data['info'] = "店铺已关闭";
                    return $out_data;//店铺已关闭
                }
                $address_config = array(
                    'default' => false,
                    'type' => 1,
                    'province' => "",
                    'city' => "",
                    'area' => "",
                    'street' => "",
                );
                if (!empty($store_data['province_id']) && !empty($store_data['city_id']) && !empty($store_data['area_id'])) {
                    $address_config['default'] = true;
                    $address_config['type'] = 4;
                    $address_config['province'] = $store_data['province_id'] . "";
                    $address_config['city'] = $store_data['city_id'] . "";
                    $address_config['area'] = $store_data['area_id'] . "";
                    $address_config['street'] = $store_data['street_id'] . "";
                }
                $out_data['address_config'] = $address_config;
                $out_data['code'] = 0;
                $out_data['data'] = $store_data;
                $out_data['info'] = "获取成功";
                return $out_data;//获取成功
            }
        }
    }

    /**
     * 获取店铺父级店铺信息
     * @param string $store_pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getParentStoreList($store_pid = '')
    {
        if (!empty($store_id)) {
            $store_data = $this->where(array('store_pid' => $store_pid))->find();
            if (!empty($store_data)) {
                $store_data_list = $this->where(array('store_key_id' => $store_data['store_pid']))->select();
                if (!empty($store_data_list)) {
                    $out_data['code'] = 0;
                    $out_data['data'] = $store_data_list;
                    $out_data['info'] = "获取成功";
                    return $out_data;//获取成功
                }
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = array(array());
        $out_data['info'] = "获取失败";
        return $out_data;//获取失败
    }

    /**
     * 获取所有的门店信息
     * @param string $seller_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSiblingStoreList($seller_id = '')
    {
        if (!empty($store_id)) {
            if (!empty($seller_id)) {
                $store_data_list = $this->where(array('seller_id' => $seller_id, 'is_area' => 1))->select();
                if (!empty($store_data_list)) {
                    $out_data['code'] = 0;
                    $out_data['data'] = $store_data_list;
                    $out_data['info'] = "获取成功";
                    return $out_data;//获取成功
                }
            }
        }
        $out_data['code'] = 0;
        $out_data['data'] = array(array());
        $out_data['info'] = "获取失败";
        return $out_data;//获取失败
    }

    /**
     * 添加店铺
     * @param $store_data
     * 必须数据
     * seller_id 商家管理员用户id
     * store_pid 店铺父级id
     * store_key_id 总店id
     * store_name 店铺名称
     * store_principal 店铺负责人
     * store_charging_is 是否有充电桩
     * store_scope 允许还车范围 单位米
     * store_park_price 停车场收费价格
     * take_park_remark 取车时说明文字
     * return_park_remark 还车时的文字说明
     * store_type 店铺类型
     * store_tel 店铺电话
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 客户地址
     * car_num 车辆数量
     * location_longitude 店铺地址经度
     * location_latitude 店铺地址纬度
     * business_start 营业开始时间
     * business_end 营业关店时间
     * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
     *非必须数据
     * store_banner 店铺图片
     * store_tel 店铺图片
     * @return array
     */
    public function addStore($store_data)
    {
        $out_data = array();//状态返回
        $in_store_data = array();//添加数据数组
        //获取商户id
        $seller_id = $store_data['seller_id'];
        $is_admin = $this->_verifySellerId($seller_id);
        if ($is_admin === false) {
            $out_data['code'] = 900;
            $out_data['info'] = "商户信息不存在";
            return $out_data;//商户信息不存在
        }
        $in_store_data['seller_id'] = $seller_id;
        //获取店铺父级
        $store_pid = $store_data['store_pid'];
        if (is_numeric($store_pid)) {
            $in_store_data['store_pid'] = $store_pid;
        } else {
            $out_data['code'] = 901;
            $out_data['info'] = "店铺父级id格式不符合规范";
            return $out_data;//店铺父级id格式不符合规范
        }
        //获取店铺名称
        $store_name = $store_data['store_name'];
        //验证单位名称是否符合规范
        if (strlen($store_name) < 2) {
            $out_data['code'] = 902;
            $out_data['info'] = "店铺名称不符合规范";
            return $out_data;//店铺名称不符合规范
        }
        $in_store_data['store_name'] = $store_name;
        //获取店铺说明
        $store_intro = $store_data['store_intro'];
        $in_store_data['store_intro'] = $store_intro;
        //获取店铺有无充电桩
        $store_charging_is = $store_data['store_charging_is'];
        if (is_numeric($store_charging_is)) {
            $in_store_data['store_charging_is'] = $store_charging_is;
        }
        //获取还车半径
        $store_scope = $store_data['store_scope'];
        $in_store_data['store_scope'] = $store_scope;

        //店铺类型
        $store_type = $store_data['store_type'];
        if (is_numeric($store_type)) {
            $in_store_data['store_type'] = $store_type;
        }
        //获取店铺负责人
        $store_principal = $store_data['store_principal'];
        //验证店铺负责人否符合规范
        if (strlen($store_principal) < 2) {
            $out_data['code'] = 902;
            $out_data['info'] = "店铺负责人名称不符合规范";
            return $out_data;//店铺负责人名称不符合规范
        }
        $in_store_data['store_principal'] = $store_principal;
        //获取店铺横幅
        $store_banner = $store_data['store_banner'];
        $in_store_data['store_banner'] = $store_banner;

        $store_park_price = $store_data['store_park_price'];
        if (!is_numeric($store_park_price) || intval($store_park_price) < 0) {
            $store_park_price = 0;
        }
        $in_store_data['store_park_price'] = $store_park_price;
        //取车文字说明
        $take_park_remark = $store_data['take_park_remark'];
        $in_store_data['take_park_remark'] = $take_park_remark;
        //还车文字说明
        $return_park_remark = $store_data['return_park_remark'];
        $in_store_data['return_park_remark'] = $return_park_remark;

        //获取店铺图集
        $store_imgs = $store_data['store_imgs'];
        $in_store_data['store_imgs'] = serialize($store_imgs);
        //店铺围栏点集合
        $store_rail_point = $store_data['store_rail_point'];
        if (!empty($store_rail_point)) {
            $store_rail_point = str_replace(" ", '', $store_rail_point);
            $in_store_data['store_rail_point'] = serialize($store_rail_point);
        }
        //获取店铺负责人联系电话
        $store_tel = $store_data['store_tel'];
        $in_store_data['store_tel'] = $store_tel;
        //获取单位地址省级id
        $province_id = $store_data['province_id'];
        //验证单位地址省级id是否符合规范
        if (!is_numeric($province_id)) {
            $out_data['code'] = 903;
            $out_data['info'] = "单位地址省级id不符合规范";
            return $out_data;//单位地址省级id不符合规范
        }
        $in_store_data['province_id'] = $province_id;
        //获取单位地址市级id
        $city_id = $store_data['city_id'];
        //验证单位地址市级id是否符合规范
        if (!is_numeric($city_id)) {
            $out_data['code'] = 904;
            $out_data['info'] = "单位地址市级id不符合规范";
            return $out_data;//单位地址市级id不符合规范
        }
        $in_store_data['city_id'] = $city_id;

        //获取单位地址区/县级id
        $area_id = $store_data['area_id'];
        //验证区/县级id是否符合规范
        if (!is_numeric($area_id)) {
            $out_data['code'] = 905;
            $out_data['info'] = "单位地址区县级id不符合规范";
            return $out_data;//单位地址区县级id不符合规范
        }
        $in_store_data['area_id'] = $area_id;
        //获取单位地街道id
        $street_id = $store_data['street_id'];
//        //街道id是否符合规范
//        if (!is_numeric($street_id)) {
//            $out_data['code'] = 906;
//            $out_data['info'] = "单位地址街道id不符合规范";
//            return $out_data;//单位地址街道id不符合规范
//        }
        $in_store_data['street_id'] = $street_id;
        $car_num = $store_data['car_num'];
        $in_store_data['car_num'] = $car_num;
        //获取单位地址
        $address = $store_data['address'];
        if (strlen($address) > 255 || strlen($address) < 2) {
            $out_data['code'] = 907;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $in_store_data['address'] = $address;
        //获取是否是区域店铺
        $is_area = $store_data['is_area'];
        if (!is_numeric($is_area)) {
            $out_data['code'] = 908;
            $out_data['info'] = "是否是区域店铺不符合规范";
            return $out_data;//是否是区域店铺不符合规范
        } else if (!(intval($is_area) >= 0 && intval($is_area) <= 1)) {
            $out_data['code'] = 908;
            $out_data['info'] = "是否是区域店铺不符合规范";
            return $out_data;//是否是区域店铺不符合规范
        }
        $in_store_data['is_area'] = $is_area;
        //获取单位地址经度
        $location_longitude = $store_data['location_longitude'];
        if (!is_numeric($location_longitude)) {
            $out_data['code'] = 909;
            $out_data['info'] = "单位地址经度不符合规范";
            return $out_data;//单位地址经度不符合规范
        }
        $in_store_data['location_longitude'] = $location_longitude;
        //获取单位地址纬度
        $location_latitude = $store_data['location_latitude'];
        if (!is_numeric($location_latitude)) {
            $out_data['code'] = 910;
            $out_data['info'] = "单位地址纬度不符合规范";
            return $out_data;//单位地址纬度不符合规范
        }
        $in_store_data['location_latitude'] = $location_latitude;

        $business_start = $store_data['business_start'];
        if (!empty($business_start)) {
            $in_store_data['business_start'] = $business_start;
        }
        $business_end = $store_data['business_end'];
        if (!empty($business_end)) {
            $in_store_data['business_end'] = $business_end;
        }
        $store_key_id = $store_data['store_key_id'];
        if (is_numeric($store_key_id)) {
            $in_store_data['store_key_id'] = $store_key_id;
        }
        $datetime = date("Y-m-d H:i:s");
        $in_store_data['create_time'] = $datetime;
        $in_store_data['update_time'] = $datetime;
        if ($this->save($in_store_data)) {
            $store_id = $this->getLastInsID();
            if (intval($store_key_id) == 0) {
                $this->where(array('id' => $store_id))->setField('store_key_id', $store_id);
            }
            $out_data['code'] = 0;
            $out_data['store_id'] = $store_id;
            $out_data['info'] = "店铺添加成功";
            return $out_data;//店铺添加成功
        }
        $out_data['code'] = 909;
        $out_data['info'] = "店铺添加失败";
        return $out_data;//店铺添加失败
    }

    /**
     * 修改店铺信息
     * @param $store_data
     * @param bool $is_verify 是否验证操作权限
     * 必须数据
     * id 数据id
     * 非必须数据
     * seller_id 商家管理员用户id
     * store_pid 店铺父级id
     * store_name 店铺名称
     * store_principal 店铺负责人
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 客户地址
     * store_banner 店铺图片
     * location_longitude 地理位置 经度
     * location_latitude 地理位置  纬度
     * business_start 营业开始时间
     * business_end 营业关店时间
     * store_status 店铺状态 0正常 1关闭
     * @return array
     */
    public function updateStore($store_data, $is_verify = true)
    {
        $out_data = array();//状态返回
        $up_store_data = array();//添加数据数组
        //获取数据id
        $id = $store_data['id'];
        if (empty($id)) {
            $out_data['code'] = 920;
            $out_data['info'] = "店铺id有误";
            return $out_data;//店铺id有误
        }
        //获取商户id
//        $seller_id = $store_data['seller_id'];
////        if ($is_verify) {
////            //验证操作权限
////            if (!$this->_verifySeller($seller_id, $id)) {
////                $out_data['code'] = 921;
////                $out_data['info'] = "此用户无权操作此数据";
////                return $out_data;//此用户无权操作此数据
////            }
////            //获取店铺父级
////            $store_pid = $store_data['store_pid'];
////            if (is_numeric($store_pid)) {
////                $up_store_data['seller_id'] = $seller_id;
////            } else {
////                $out_data['code'] = 922;
////                $out_data['info'] = "店铺父级id格式不符合规范";
////                return $out_data;//店铺父级id格式不符合规范
////            }
////        }
        //获取店铺父级
        $store_pid = $store_data['store_pid'];
        if (is_numeric($store_pid)) {
            $up_store_data['store_pid'] = $store_pid;
        } else {
            $out_data['code'] = 901;
            $out_data['info'] = "店铺父级id格式不符合规范";
            return $out_data;//店铺父级id格式不符合规范
        }
        //获取店铺名称
        $store_name = $store_data['store_name'];
        //验证单位名称是否符合规范
        if (strlen($store_name) < 2) {
            $out_data['code'] = 923;
            $out_data['info'] = "店铺名称不符合规范";
            return $out_data;//店铺名称不符合规范
        }
        $up_store_data['store_name'] = $store_name;

        //获取店铺说明
        $store_intro = $store_data['store_intro'];
        $up_store_data['store_intro'] = $store_intro;
        //获取店铺有无充电桩
        $store_charging_is = $store_data['store_charging_is'];
        if (is_numeric($store_charging_is)) {
            $up_store_data['store_charging_is'] = $store_charging_is;
        }
        //获取还车半径
        $store_scope = $store_data['store_scope'];
        $up_store_data['store_scope'] = $store_scope;

        //店铺类型
        $store_type = $store_data['store_type'];
        if (is_numeric($store_type)) {
            $up_store_data['store_type'] = $store_type;
        }

        //获取单位负责人
        $store_principal = $store_data['store_principal'];
        //验证单位负责人是否符合规范
        if (!empty($store_principal)) {
            $up_store_data['store_principal'] = $store_principal;
        }
        //获取店铺横幅
        $store_banner = $store_data['store_banner'];
        if (!empty($store_banner)) {
            $up_store_data['store_banner'] = $store_banner;
        }

        $store_park_price = $store_data['store_park_price'];
        if (!is_numeric($store_park_price) || intval($store_park_price) < 0) {
            $store_park_price = 0;
        }
        $up_store_data['store_park_price'] = $store_park_price;
        //取车文字说明
        $take_park_remark = $store_data['take_park_remark'];
        $up_store_data['take_park_remark'] = $take_park_remark;
        //还车文字说明
        $return_park_remark = $store_data['return_park_remark'];
        $up_store_data['return_park_remark'] = $return_park_remark;
        //获取店铺图集
        $store_imgs = $store_data['store_imgs'];
        $up_store_data['store_imgs'] = serialize($store_imgs);
        //店铺围栏点集合
        $store_rail_point = $store_data['store_rail_point'];
        if (!empty($store_rail_point)) {
            $store_rail_point = str_replace(" ", '', $store_rail_point);
            $up_store_data['store_rail_point'] = serialize($store_rail_point);
        }
        //获取店铺负责人电话
        $store_tel = $store_data['store_tel'];
        if (!empty($store_tel)) {
            $up_store_data['store_tel'] = $store_tel;
        }
        //获取单位地址省级id
        $province_id = $store_data['province_id'];
        //验证单位地址省级id是否符合规范
        if (!is_numeric($province_id)) {
            $out_data['code'] = 924;
            $out_data['info'] = "单位地址省级id不符合规范";
            return $out_data;//单位地址省级id不符合规范
        }
        $up_store_data['province_id'] = $province_id;
        //获取单位地址市级id
        $city_id = $store_data['city_id'];
        //验证单位地址市级id是否符合规范
        if (!is_numeric($city_id)) {
            $out_data['code'] = 925;
            $out_data['info'] = "单位地址市级id不符合规范";
            return $out_data;//单位地址市级id不符合规范
        }
        $up_store_data['city_id'] = $city_id;
        //获取单位地址区/县级id
        $area_id = $store_data['area_id'];
        //验证区/县级id是否符合规范
        if (!is_numeric($area_id)) {
            $out_data['code'] = 926;
            $out_data['info'] = "单位地址区县级id不符合规范";
            return $out_data;//单位地址区县级id不符合规范
        }
        $up_store_data['area_id'] = $area_id;

        //获取单位地街道id
        $street_id = $store_data['street_id'];
        //街道id是否符合规范
//        if (!is_numeric($street_id)) {
//            $out_data['code'] = 927;
//            $out_data['info'] = "单位地址街道id不符合规范";
//            return $out_data;//单位地址街道id不符合规范
//        }
        $up_store_data['street_id'] = $street_id;
        $car_num = $store_data['car_num'];
        $up_store_data['car_num'] = $car_num;
        //获取单位地址
        $address = $store_data['address'];
        if (strlen($address) > 255 || strlen($address) < 2) {
            $out_data['code'] = 928;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $up_store_data['address'] = $address;
        //获取 地理位置经度
        $location_longitude = $store_data['location_longitude'];
        if (empty($location_longitude)) {
            $out_data['code'] = 929;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $up_store_data['location_longitude'] = $location_longitude;
        //获取 地理位置纬度
        $location_latitude = $store_data['location_latitude'];
        if (empty($location_latitude)) {
            $out_data['code'] = 930;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $up_store_data['location_latitude'] = $location_latitude;
        //获取 店铺状态
        $store_status = $store_data['store_status'];
        if (!is_numeric($store_status)) {
            $out_data['code'] = 931;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        } else if (!(intval($store_status) >= 0 && intval($store_status) <= 1)) {
            $out_data['code'] = 931;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $business_start = $store_data['business_start'];
        if (!empty($business_start)) {
            $up_store_data['business_start'] = $business_start;
        }
        $business_end = $store_data['business_end'];
        if (!empty($business_end)) {
            $up_store_data['business_end'] = $business_end;
        }
        $up_store_data['store_status'] = $store_status;
        $datetime = date("Y-m-d H:i:s");
        $up_store_data['update_time'] = $datetime;
        if ($this->save($up_store_data, array('id' => $store_data['id']))) {
            if ($up_store_data['store_type'] == StoreType::$StoreTypeCar['code']) {
                $carcommon_model = new CarCommon();
                $carcommon_model->updateCarLocation($store_data['id'], $up_store_data['location_longitude'], $up_store_data['location_latitude']);
            }
            $out_data['code'] = 0;
            $out_data['info'] = "店铺修改成功";
            return $out_data;//店铺修改成功
        }
        $out_data['code'] = 932;
        $out_data['info'] = "店铺修改失败";
        return $out_data;//店铺添修改败
    }

    /**
     * 获取子级 店铺列表
     * @param $store_pid
     * @param bool $is_status
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSubsetStoreList($store_pid, $is_status = true)
    {
        if ($is_status) {
            $store_list = $this->where(array('store_pid' => $store_pid, 'store_status' => 0))->select();
        } else {
            $store_list = $this->where(array('store_pid' => $store_pid))->select();
        }
        return $store_list;
    }

    /**
     * 根据条件获取店铺列表
     * @param $map
     * @param $order
     * @param $page_config
     * @param int $limit
     * @return \think\Paginator
     */
    public function getStorePageList($map, $order, $page_config, $limit = 15)
    {
        $store_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        return $store_list;
    }

    /**
     * 根据条件获取店铺列表
     * @param $map
     * @param $order
     * @param $page
     * @param int $limit
     * @return $this
     */
    public function getStoreList($map, $order, $page, $limit = 15)
    {
        $store_list = $this->where($map)->order($order)->field('store_name,store_intro,store_tel,store_type,location_longitude,location_latitude')->limit($limit, $page);
        return $store_list;
    }

    /**
     * 根据条件获取还车点列表
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSiteList($map)
    {
        $store_list = $this->where($map)->field('id,store_name,address,store_intro,store_tel,store_pid,store_imgs,store_charging_is,store_scope,address,location_longitude,location_latitude,store_rail_point')->select();
//        $store_list = array2levelc($store_list);
        foreach ($store_list as $value) {
            $this->formatx($value);
        }
        return $store_list;
    }

    /**
     * 根据条件获取还车点列表
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getCSiteList($map)
    {
        $store_list = $this->where($map)->field('id,store_name,address,store_intro,store_tel,store_pid,store_imgs,store_charging_is,store_scope,address,location_longitude,location_latitude,store_rail_point')->select();
        $store_list = array2levelc($store_list);
        foreach ($store_list as $value) {
            $this->formatx($value);
        }
        return $store_list;
    }

    /**
     * 根据条件获站点列表
     * @param $map
     * @param $order
     * @return \think\Paginator
     */
    public function getStoreSiteKeyIdList($map, $order)
    {
        if (Cache::has('getStoreSiteList')) {
            $store_site_list = Cache::get('getStoreSiteList');
        } else {
            $store_list = $this->where($map)->field('id,location_longitude,store_pid,location_latitude,store_name')->order($order)->select();
            $store_list = array2levelc($store_list);
            $store_site_list = array();
            foreach ($store_list as $value) {
                $store_site_list[intval($value['id'])] = $value;
            }
            Cache::set('getStoreSiteList', $store_site_list, 3600 * 24);
        }
        return $store_site_list;
    }

    /**
     * 获取区域内的店铺id
     * @param $map 条件
     * @param $order 排序
     * @param $page 分页
     * @param int $limit 数据条数
     * @return array 店铺id=>总店id 键值对
     */
    public function getStoreKeyIdList($map, $order, $page, $limit = 15)
    {
        $map['store_status'] = 0;
        $field = "id,store_key_id";
        $car_common_list = $this->where($map)->order($order)->field($field)->paginate($limit, false, ['page' => $page]);
        //区域内的店铺 店铺id=>总店id
        $store_key_id_list = array();
        foreach ($car_common_list as $value) {
            $store_key_id_list[intval($value['id'])] = intval($value['store_key_id']);
        }
        return $store_key_id_list;
    }

    /**
     * 获取全部站点的数据
     * @param string $store_key_id
     * @param $field
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreFieldList($store_key_id = '', $field)
    {
        if (!empty($store_key_id)) {
            $store_data = $this->where(array('store_key_id' => $store_key_id, 'store_pid' => ['gt', 0], 'store_type' => GoodsType::$GoodsTypeElectrocar['code']))->field($field)->select();
            if (!empty($store_data)) {
                return $store_data;
            }
        } else {
            $store_data = $this->where(array('store_pid' => 0, 'store_type' => GoodsType::$GoodsTypeElectrocar['code']))->field($field)->select();
            if (!empty($store_data)) {
                return $store_data;
            }
        }
        return array();
    }

    /**
     * 锁定店铺
     * @param $store_id
     * @return bool
     *  0锁定成功
     *  150 锁定失败
     */
    public function addLockStore($store_id)
    {
        $lock_store_data = array(
            'store_status' => 1
        );
        if ($this->save($lock_store_data, array('id' => $store_id))) {
            return true;//店铺锁定成功
        }
        return false;//店铺锁定失败
    }

    /**
     * 解除锁定
     * @param $store_id
     * @return bool
     *  0锁定成功
     *  150 锁定失败
     */
    public function delLockStore($store_id)
    {
        $lock_store_data = array(
            'store_status' => 0
        );
        if ($this->save($lock_store_data, array('id' => $store_id))) {
            return true;//解除锁定成功
        }
        return false;//解除锁定失败
    }

    /**
     * 验证商家是否存在 返回商家是否是管理员
     * @param $seller_id
     * @return bool|mixed
     */
    private function _verifySellerId($seller_id)
    {
        //检查电话号码是否符合格式
        if (!empty($seller_id)) {
            $seller_model = new Seller();
            //验证账号是否已经注册
            $seller = $seller_model->where(array('id' => $seller_id))->find();
            if (!empty($seller)) {
                return $seller['is_admin'];//账号已被注册
            }
        }
        return false;//账号不存在 用户名格式有误
    }

    /**
     * 验证是否有修改权限
     * @param $seller_id
     * @param $store_id
     * @return bool|mixed
     */
    private function _verifySeller($seller_id, $store_id)
    {
        //检查电话号码是否符合格式
        if (!empty($seller_id) && !empty($store_id)) {
            //验证账号是否已经注册
            $store = $this->where(array('id' => $store_id, 'seller_id' => $seller_id))->find();
            if (!empty($store)) {
                return true;
            }
        }
        return false;//账号不存在 用户名格式有误
    }

}