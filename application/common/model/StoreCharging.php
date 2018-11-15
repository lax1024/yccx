<?php

namespace app\common\model;

/**
 * 充电桩站点信息管理
 * 实现站点信息的基础数据操作
 * 添加站点信息
 * 修改站点信息
 */

use definition\StoreType;
use think\Cache;
use think\Model;

class StoreCharging extends Model
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
        $data['store_tag_str'] = "";
        if (!empty($data['store_tag'])) {
            $data['store_tag'] = unserialize($data['store_tag']);
            foreach ($data['store_tag'] as $v) {
                if (!empty($v)) {
                    $data['store_tag_str'] .= $v . "、";
                }
            }
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
     * 获取店铺信息
     * @param string $store_id
     * @param string $seller_id
     * @param bool $is_admin
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStore($store_id = '', $is_admin = false)
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
                $out_data['address_config'] = $address_config;
                $out_data['code'] = 0;
                $out_data['data'] = $store_data;
                $out_data['info'] = "获取成功";
                return $out_data;//获取成功
            }
        }
    }

    /**
     * 获取充电桩站点名称
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
     * 获取充电桩站点指定字段
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
     * 添加店铺
     * @param $store_data
     * 必须数据
     * store_pid 店铺父级id
     * store_name 店铺名称
     * store_intro 店铺简介
     * commission_str 平台收取服务费
     * commission  平台提点
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 客户地址
     * store_longitude 店铺地址经度
     * store_latitude 店铺地址纬度
     * store_tag 店铺标签数据
     * store_imgs 店铺图片
     * store_status 店铺状态
     * num_quick 快充桩个数
     * num_slow 慢充桩个数
     * power 充电桩功率60KW～120KW
     * charging_price 充电桩单价
     * parting_price 停车场单价
     * car_accept 停车场适合车型
     * car_unaccept 停车场不适合车型
     * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
     * store_key_id 总店id
     * store_key_name 总店id
     *非必须数据
     * store_banner 店铺图片
     * store_tel 店铺图片
     * store_principal 店铺负责人
     * @return array
     */
    public function addStore($store_data)
    {
        $out_data = array();//状态返回
        $in_store_data = array();//添加数据数组
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
        //店铺管理电话
        $store_tel = $store_data['store_tel'];
        $in_store_data['store_tel'] = $store_tel;
        //获取店铺负责人
        $store_principal = $store_data['store_principal'];
        $in_store_data['store_principal'] = $store_principal;
        //获取店铺横幅
        $store_banner = $store_data['store_banner'];
        $in_store_data['store_banner'] = $store_banner;

        //获取店铺图集
        $store_imgs = $store_data['store_imgs'];
        if (empty($store_imgs)) {
            $in_store_data['store_imgs'] = serialize($store_imgs);
        }
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
        //获取单位地址区/县级id
        $area_id = $store_data['area_id'];
        if (!is_numeric($area_id)) {
            $out_data['code'] = 905;
            $out_data['info'] = "单位地址地区id不符合规范";
            return $out_data;//单位地址市级id不符合规范
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
        if (is_numeric($is_area)) {
            $in_store_data['is_area'] = $is_area;
        } else {
            $in_store_data['is_area'] = 0;
        }
        //获取单位地址经度
        $store_longitude = $store_data['store_longitude'];
        if (!is_numeric($store_longitude)) {
            $out_data['code'] = 909;
            $out_data['info'] = "单位地址经度不符合规范";
            return $out_data;//单位地址经度不符合规范
        }
        $in_store_data['store_longitude'] = $store_longitude;
        //获取单位地址纬度
        $store_latitude = $store_data['store_latitude'];
        if (!is_numeric($store_latitude)) {
            $out_data['code'] = 910;
            $out_data['info'] = "单位地址纬度不符合规范";
            return $out_data;//单位地址纬度不符合规范
        }
        $in_store_data['store_latitude'] = $store_latitude;

        $power = $store_data['power'];
        $in_store_data['power'] = $power;

        $charging_price = $store_data['charging_price'];
        $in_store_data['charging_price'] = $charging_price;

        $parting_price = $store_data['parting_price'];
        $in_store_data['parting_price'] = $parting_price;

        $num_quick = $store_data['num_quick'];
        $in_store_data['num_quick'] = $num_quick;

        $num_slow = $store_data['num_slow'];
        $in_store_data['num_slow'] = $num_slow;

        $store_tag = $store_data['store_tag'];
        $in_store_data['store_tag'] = $store_tag;

        $car_accept = $store_data['car_accept'];
        $in_store_data['car_accept'] = $car_accept;

        $car_unaccept = $store_data['car_unaccept'];
        $in_store_data['car_unaccept'] = $car_unaccept;

        $store_key_id = $store_data['store_key_id'];
        if (is_numeric($store_key_id)) {
            $in_store_data['store_key_id'] = $store_key_id;
            $store_model = new Store();
            $store_key_name = $store_model->getStoreName($store_key_id);
            $in_store_data['store_key_name'] = $store_key_name;
        }
        $datetime = date("Y-m-d H:i:s");
        $in_store_data['create_time'] = $datetime;
        $in_store_data['update_time'] = $datetime;
        if ($this->save($in_store_data)) {
            $store_id = $this->getLastInsID();
            if (intval($store_key_id) == 0) {
                $this->where(array('id' => $store_id))->setField(['store_key_id' => $store_id, 'store_key_name' => $store_name]);
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
     * 添加店铺
     * @param $store_data
     * 必须数据
     * id 店铺id
     * store_pid 店铺父级id
     * store_name 店铺名称
     * store_intro 店铺简介
     * store_imgs 店铺图片
     * commission_str 平台收取服务费
     * commission  平台提点
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 客户地址
     * store_longitude 店铺地址经度
     * store_latitude 店铺地址纬度
     * store_tag 店铺标签数据
     * store_status 店铺状态
     * num_quick 快充桩个数
     * num_slow 慢充桩个数
     * power 充电桩功率60KW～120KW
     * charging_price 充电桩单价
     * parting_price 停车场单价
     * car_accept 停车场适合车型
     * car_unaccept 停车场不适合车型
     * is_area 是否是区域店铺 0表示店铺区域 1表示门店店铺
     * store_key_id 总店id
     * store_key_name 总店id
     *非必须数据
     * store_banner 店铺图片
     * store_tel 店铺图片
     * store_principal 店铺负责人
     * @return array
     */
    public function updateStore($store_data)
    {
        $out_data = array();//状态返回
        $up_store_data = array();//添加数据数组
        $id = $store_data['id'];
        if (!is_numeric($id)) {
            $out_data['code'] = 921;
            $out_data['info'] = "店铺id不合法";
            return $out_data;//店铺id不合法
        }
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
            $out_data['code'] = 902;
            $out_data['info'] = "店铺名称不符合规范";
            return $out_data;//店铺名称不符合规范
        }
        $up_store_data['store_name'] = $store_name;
        //获取店铺说明
        $store_intro = $store_data['store_intro'];
        $up_store_data['store_intro'] = $store_intro;
        //店铺管理电话
        $store_tel = $store_data['store_tel'];
        $up_store_data['store_tel'] = $store_tel;
        //获取店铺负责人
        $store_principal = $store_data['store_principal'];
        $up_store_data['store_principal'] = $store_principal;
        //获取店铺横幅
        $store_banner = $store_data['store_banner'];
        $up_store_data['store_banner'] = $store_banner;

        //获取店铺图集
        $store_imgs = $store_data['store_imgs'];
        if (empty($store_imgs)) {
            $up_store_data['store_imgs'] = serialize($store_imgs);
        }
        //获取单位地址省级id
        $province_id = $store_data['province_id'];
        //验证单位地址省级id是否符合规范
        if (!is_numeric($province_id)) {
            $out_data['code'] = 903;
            $out_data['info'] = "单位地址省级id不符合规范";
            return $out_data;//单位地址省级id不符合规范
        }
        $up_store_data['province_id'] = $province_id;
        //获取单位地址市级id
        $city_id = $store_data['city_id'];
        //验证单位地址市级id是否符合规范
        if (!is_numeric($city_id)) {
            $out_data['code'] = 904;
            $out_data['info'] = "单位地址市级id不符合规范";
            return $out_data;//单位地址市级id不符合规范
        }
        $up_store_data['city_id'] = $city_id;
        //获取单位地址区/县级id
        $area_id = $store_data['area_id'];
        if (!is_numeric($area_id)) {
            $out_data['code'] = 905;
            $out_data['info'] = "单位地址地区id不符合规范";
            return $out_data;//单位地址市级id不符合规范
        }
        $up_store_data['area_id'] = $area_id;
        //获取单位地街道id
        $street_id = $store_data['street_id'];
//        //街道id是否符合规范
//        if (!is_numeric($street_id)) {
//            $out_data['code'] = 906;
//            $out_data['info'] = "单位地址街道id不符合规范";
//            return $out_data;//单位地址街道id不符合规范
//        }
        $up_store_data['street_id'] = $street_id;
        //获取单位地址
        $address = $store_data['address'];
        if (strlen($address) > 255 || strlen($address) < 2) {
            $out_data['code'] = 907;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $up_store_data['address'] = $address;
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
        $up_store_data['is_area'] = $is_area;
        //获取单位地址经度
        $store_longitude = $store_data['store_longitude'];
        if (!is_numeric($store_longitude)) {
            $out_data['code'] = 909;
            $out_data['info'] = "单位地址经度不符合规范";
            return $out_data;//单位地址经度不符合规范
        }
        $up_store_data['store_longitude'] = $store_longitude;
        //获取单位地址纬度
        $store_latitude = $store_data['store_latitude'];
        if (!is_numeric($store_latitude)) {
            $out_data['code'] = 910;
            $out_data['info'] = "单位地址纬度不符合规范";
            return $out_data;//单位地址纬度不符合规范
        }
        $up_store_data['store_latitude'] = $store_latitude;

        $power = $store_data['power'];
        $up_store_data['power'] = $power;

        $charging_price = $store_data['charging_price'];
        $up_store_data['charging_price'] = $charging_price;

        $parting_price = $store_data['parting_price'];
        $up_store_data['parting_price'] = $parting_price;

        $num_quick = $store_data['num_quick'];
        $up_store_data['num_quick'] = $num_quick;

        $num_slow = $store_data['num_slow'];
        $up_store_data['num_slow'] = $num_slow;

        $store_tag = $store_data['store_tag'];
        $up_store_data['store_tag'] = $store_tag;

        $car_accept = $store_data['car_accept'];
        $up_store_data['car_accept'] = $car_accept;

        $car_unaccept = $store_data['car_unaccept'];
        $up_store_data['car_unaccept'] = $car_unaccept;


        $store_key_id = $store_data['store_key_id'];
        if (is_numeric($store_key_id)) {
            $up_store_data['store_key_id'] = $store_key_id;
            $store_model = new Store();
            $store_key_name = $store_model->getStoreName($store_key_id);
            $up_store_data['store_key_name'] = $store_key_name;
        }
        $datetime = date("Y-m-d H:i:s");
        $up_store_data['update_time'] = $datetime;
        if ($this->save($up_store_data, ['id' => $id])) {
            $out_data['code'] = 0;
            $out_data['store_id'] = $id;
            $out_data['info'] = "店铺修改成功";
            return $out_data;//店铺添加成功
        }
        $out_data['code'] = 909;
        $out_data['info'] = "店铺修改失败";
        return $out_data;//店铺添加失败
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
        $store_list = $this->where($map)->order($order)->field('id,store_name,store_intro,store_tel,store_pid,store_longitude,store_latitude')->limit($limit, $page);
        return $store_list;
    }

    /**
     * 根据条件获取还车点列表
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSiteList($map)
    {
        $store_list = $this->where($map)->field('id,store_name,store_intro,store_tel,store_pid,store_longitude,store_latitude')->select();
//        $store_list = array2levelc($store_list);
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
            $store_list = $this->where($map)->field('id,store_name,store_intro,store_tel,store_pid,store_longitude,store_latitude')->order($order)->select();
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
}