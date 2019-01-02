<?php

namespace app\common\model;

/**
 *常规租车 拓展信息 数据模型
 */
use definition\CarStatus;
use think\Model;

class CarCommonExpand extends Model
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

    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
        $car_status = CarStatus::$CARSTATUS_CODE;
        $data['car_status_str'] = $car_status[intval($data['car_status'])];
        $store_model = new Store();
        $store = $store_model->where(array('id' => $data['store_key_id']))->find();
        $data['store_name'] = $store['store_name'];
    }

    /**
     * 获取车辆拓展信息
     * @param $id
     * @return mixed
     */
    public function getCarCommonExpand($id)
    {
        if (!is_numeric($id)) {
            $out_data['code'] = 840;
            $out_data['info'] = "车辆数据id有误";
            return $out_data;
        }
        $carcommon_expand = $this->find($id);
        $out_data['code'] = 0;
        $out_data['data'] = $carcommon_expand;
        $out_data['info'] = "车辆拓展数据获取成功";
        return $out_data;
    }

    /**
     * 更新车辆信息
     * @param $carcommon_data
     * 必输数据
     * id 数据id
     * 必输数据
     * 非必须数据
     * initial 品牌首字母
     * brand_id 品牌id
     * brand_name 品牌名称
     * series_id 车系id
     * series_name 车系名称
     * cartype_id 车型id
     * cartype_name 车型名称
     * licence_plate 车牌号
     * engine_vin 发动机编号
     * day_price 每天租金价格
     * location_longitude 地理位置经度
     * location_latitude 地理位置纬度
     * store_site_id 当前车辆所在门店id
     * store_site_name 当前车辆所在门店名称
     * age_year 车辆使用年限
     * car_grade 车辆等级类型
     * car_status 当前车辆状态
     * car_photos 车辆照片图集
     * remark 变动备注信息
     * @return array
     */
    public function updateCarCommonExpand($carcommon_data)
    {
        $out_data = array();
        $up_carcommon_data = array();

        if (!is_numeric($carcommon_data['id'])) {
            $out_data['code'] = 830;
            $out_data['info'] = "车辆数据id有误";
            return $out_data;
        }
        //获取店铺id
        $store_id = $carcommon_data['store_id'];
        if (is_numeric($store_id)) {
            $up_carcommon_data['store_id'] = $store_id;
        }
        //获取车品牌id
        $brand_id = $carcommon_data['brand_id'];
        if (is_numeric($brand_id)) {
            $up_carcommon_data['brand_id'] = $brand_id;
        }
        //获取车系id
        $series_id = $carcommon_data['series_id'];
        if (is_numeric($series_id)) {
            $up_carcommon_data['series_id'] = $series_id;
        }
        //获取车型id
        $cartype_id = $carcommon_data['cartype_id'];
        if (is_numeric($cartype_id)) {
            $up_carcommon_data['cartype_id'] = $cartype_id;
        }
        //获取车辆品首字母
        $initial = $carcommon_data['initial'];
        if (!empty($initial)) {
            $up_carcommon_data['initial'] = $initial;
        }
        $initial = $carcommon_data['initial'];
        if (!preg_match("/^[A-Za-z]/", $initial)) {
            $up_carcommon_data['initial'] = strtoupper($initial);
        }
        //获取车辆品牌名称
        $brand_name = $carcommon_data['brand_name'];
        if (!empty($brand_name)) {
            $up_carcommon_data['brand_name'] = $brand_name;
        }
        //获取车系名称
        $series_name = $carcommon_data['series_name'];
        if (!empty($series_name)) {
            $up_carcommon_data['series_name'] = $series_name;
        }
        //获取车型名称
        $cartype_name = $carcommon_data['cartype_name'];
        if (!empty($cartype_name)) {
            $in_carcommon_data['cartype_name'] = $cartype_name;
        }

        //获取车牌号码
        $licence_plate = $carcommon_data['licence_plate'];
        if (check_licence_plate($licence_plate) === true) {
            $up_carcommon_data['licence_plate'] = $licence_plate;
        }

        $engine_vin = $carcommon_data['engine_vin'];
        if (check_engine_vin($engine_vin) === false) {
            $up_carcommon_data['engine_vin'] = $engine_vin;
        }
        //获取租车单价/天
        $day_price = $carcommon_data['day_price'];
        if (is_numeric($day_price)) {
            $up_carcommon_data['day_price'] = $day_price;
        }
        //获取地理位置经度
        $location_longitude = $carcommon_data['location_longitude'];
        if (is_numeric($location_longitude)) {
            $up_carcommon_data['location_longitude'] = $location_longitude;
        }
        //获取地理位置纬度
        $location_latitude = $carcommon_data['location_latitude'];
        if (is_numeric($location_latitude)) {
            $up_carcommon_data['location_latitude'] = $location_latitude;
        }
        //获取车辆所在店铺点id
        $store_site_id = $carcommon_data['store_site_id'];
        if (is_numeric($store_site_id)) {
            $up_carcommon_data['store_site_id'] = $store_site_id;
            //获取车辆所在店铺点名称
            $store_site_name = $carcommon_data['store_site_name'];
            if (!empty($store_site_name)) {
                $in_carcommon_data['store_site_name'] = $store_site_name;
            }
        }

        //获取车辆使用年限
        $age_year = $carcommon_data['age_year'];
        if (is_numeric($age_year)) {
            $up_carcommon_data['age_year'] = $age_year;
        }
        //获取车辆当前状态
        $car_status = $carcommon_data['car_status'];
        if (is_numeric($car_status)) {
            $up_carcommon_data['car_status'] = $car_status;
        }
        //获取车辆图集
        $car_photos = $carcommon_data['car_photos'];
        if (!empty($car_photos)) {
            $in_carcommon_data['car_photos'] = $car_photos;
        }
        //获取车辆备注
        $remark = $carcommon_data['remark'];
        if (!empty($remark)) {
            $up_carcommon_data['remark'] = $remark;
        }

        //获取车辆等级类型
        $car_grade = $carcommon_data['car_grade'];
        if (is_numeric($car_grade)) {
            $up_carcommon_data['car_grade'] = $car_grade;
        }
        $datetime = date("Y-m-d H:i:s");
        $up_carcommon_data['update_time'] = $datetime;
        if ($this->save($up_carcommon_data, array('id' => $carcommon_data['id']))) {
            $out_data['code'] = 0;
            $out_data['info'] = "车辆信息更新成功";
            return $out_data;
        }
        $out_data['code'] = 831;
        $out_data['info'] = "车辆信息更新失败";
        return $out_data;
    }


}