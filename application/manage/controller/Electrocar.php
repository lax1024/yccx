<?php

namespace app\manage\controller;

use app\common\model\CarAbnormalLog;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\Store as StoreModel;
use app\common\model\CarBrand as CarBrandModel;
use app\common\model\CarSeries as CarSeriesModel;
use app\common\model\CarType as CarTypeModel;
use app\common\controller\AdminBase;
use definition\CarColor;
use definition\CarGrade;
use definition\CarStatus;
use definition\GoodsType;

/**
 * 新能源车辆管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Electrocar extends AdminBase
{
    protected $carcommon_model;
    protected $store_model;
    protected $carbrand_model;
    protected $carseries_model;
    protected $cartype_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->carcommon_model = new CarCommonModel();
        $this->store_model = new StoreModel();
        $this->carbrand_model = new CarBrandModel();
        $this->carseries_model = new CarSeriesModel();
        $this->cartype_model = new CarTypeModel();
        $car_cc = get_car_cc_list();
        $this->assign("car_cc", $car_cc);
    }

    /**
     * 车辆管理
     * @param string $keyword
     * @param int $page
     * @param int $car_status
     * @param int $store_id
     * @param int $use
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $car_status = 0, $store_id = 0, $use = 'off')
    {
        $map = [];
        if ($keyword) {
            $map['store_key_id|brand_name|series_name|cartype_name|licence_plate'] = ['like', "%{$keyword}%"];
        }
        $order = "id DESC";
        if (!empty($store_id)) {
            $map['store_site_id'] = $store_id;
        }
        if ($use == 'on') {
            $map['return_time'] = ['lt', time() - 3600 * 72];
            $order = 'return_time ASC';
        }
        if (!empty($car_status)) {
            $map['car_status'] = $car_status;
        }

        $car_status_list = CarStatus::$CARSTATUS_CODE;
        $map['goods_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $page_config = ['page' => $page, 'query' => ['car_status' => $car_status, 'keyword' => $keyword, 'store_id' => $store_id, 'use' => $use]];
        $carcommon_list = $this->carcommon_model->getCarList($map, $order, 15, $this->web_info['is_super_admin'], $this->web_info['store_key_id'], $page_config);
        $maps['store_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $store_site_list = $this->store_model->getChildList($maps, 'id,store_name,store_pid');
        return $this->fetch('index', ['carcommon_list' => $carcommon_list, 'keyword' => $keyword, 'car_status' => $car_status, 'car_status_list' => $car_status_list, 'store_id' => $store_id, 'store_site_list' => $store_site_list, 'use' => $use]);
    }

    /**
     * 异常车辆管理
     * @param string $keyword
     * @param int $page
     * @param int $car_status
     * @return mixed
     */
    public function unusual($keyword = '', $car_status = 0)
    {
        if (empty($car_status)) {
            $car_status = 0;
        }
        $car_status_list = [
            0 => "未选择",
            1 => "补电车辆",
            2 => "掉线车辆",
            3 => "收费站点车辆",
            4 => "不在站点内",
            5 => "小电瓶电压过低"
        ];
        $map['goods_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $car_abnormal_log_model = new CarAbnormalLog();
        $car_abnormal_log_model->getAbnormalList(153, 0, 0);
        $carcommon_list = $this->carcommon_model->getUnusualCar(153, 90, $car_status, true);
        $sum = 0;
        foreach ($carcommon_list as $v) {
            $sum++;
        }
        if (empty($car_status)) {
            $carcommon_list = json_encode($carcommon_list);
            $carcommon_list = json_decode($carcommon_list, true);
            $carcommon_list = rank_elecar_grade($carcommon_list, 'type');
        }
        return $this->fetch('unusual', ['carcommon_list' => $carcommon_list, 'sum' => $sum, 'keyword' => $keyword, 'car_status' => $car_status, 'car_status_list' => $car_status_list]);
    }

    /**
     * 添加车辆
     * @return mixed
     */
    public function add()
    {
        $this->error('暂时不开通此权限');
        $year_list = array();
        for ($i = 2000; $i <= intval(date('Y')); $i++) {
            $year_list[] = array(
                'id' => $i,
                'name' => $i
            );
        }
        $map['store_key_id'] = $this->web_info['store_key_id'];
        $store_site_list = $this->store_model->getChildList($map);
        $car_status = CarStatus::$CARSTATUS_CODE;
        $car_color = CarColor::$CARCOLOR_CODE;
        $car_grade = CarGrade::$CARGRADE_CODE;
        return $this->fetch('add', ['store_site_list' => $store_site_list, 'year_list' => $year_list, 'car_status' => $car_status, 'car_color' => $car_color, 'car_grade' => $car_grade]);
    }

    /**
     * 保存车辆信息
     */
    public function save()
    {
        $this->error('暂时不开通此权限');
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * store_id 车辆id
//            * brand_id 品牌id
//            * series_id 车系id
//            * cartype_id 车型id
//            * licence_plate 车牌号
//            * engine_vin 发动机编号
//            * day_price 每天租金价格
//            * day_basic 基本保障服务费用
//            * day_procedure 车行手续费用
//            * location_longitude 地理位置经度
//            * location_latitude 地理位置纬度
//            * store_site_id 当前车辆所在门店id
//            * store_site_name 当前车辆所在门店名称
//            * age_year 车辆使用年限
//            * car_grade 车辆等级类型
//            * car_color 车辆颜色
//            * car_status 当前车辆状态
//            * car_photos 车辆图集
//            * 非必须数据
//            * brand_name 品牌名称
//            * series_name 车系名称
//            * cartype_name 车型名称
            $brand = $this->carbrand_model->get_brand_name_initial($data['brand_id']);
            $series_data = $this->carseries_model->get_series($data['series_id']);
            $series_name = $series_data['series_name'];
            $series_img = $series_data['series_img'];
            $cartype_name = $this->cartype_model->get_type_name($data['cartype_id']);
            $store_key_id = $this->web_info['store_key_id'];
            $out_store = $this->store_model->getStore($data['store_site_id'], '', true);
            $store_info = array();
            if ($out_store['code'] == 0) {
                $store_info = $out_store['data'];
            } else {
                $this->error($out_store['info']);
            }
            $in_carcommon_data = array(
                'store_key_id' => $store_key_id,
                'initial' => $brand['initial'],
                'brand_id' => $data['brand_id'],
                'brand_name' => $brand['brand_name'],
                'series_id' => $data['series_id'],
                'series_name' => $series_name,
                'series_img' => $series_img,
                'cartype_id' => $data['cartype_id'],
                'cartype_name' => $cartype_name,
                'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
                'device_number' => $data['device_number'],
                'licence_plate' => $data['licence_plate'],
                'engine_vin' => $data['engine_vin'],
                'day_price' => $data['day_price'],
                'day_basic' => $data['day_basic'],
                'day_procedure' => $data['day_procedure'],
                'location_longitude' => $store_info['location_longitude'],
                'location_latitude' => $store_info['location_latitude'],
                'store_site_name' => $store_info['store_name'],
                'store_site_id' => $data['store_site_id'],
                'age_year' => $data['age_year'],
                'car_cc' => $data['car_cc'],
                'car_grade' => $data['car_grade'],
                'car_color' => $data['car_color'],
                'car_status' => $data['car_status'],
                'car_photos' => serialize($data['photo']),
            );
            $out_data = $this->carcommon_model->addCarCommon($in_carcommon_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑车辆信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $carcommon_data = $this->carcommon_model->getCarCommon($id, true);
        if ($carcommon_data['code'] == 0) {
            $year_list = array();
            for ($i = 2000; $i <= intval(date('Y')); $i++) {
                $year_list[] = array(
                    'id' => $i,
                    'name' => $i
                );
            }
            $car_status = CarStatus::$CARSTATUS_CODE;
            $car_color = CarColor::$CARCOLOR_CODE;
            $car_grade = CarGrade::$CARGRADE_CODE;
            return $this->fetch('edit', ['carcommon' => $carcommon_data['data'], 'brand_config' => json_encode($carcommon_data['brand_config']), 'year_list' => $year_list, 'car_status' => $car_status, 'car_color' => $car_color, 'car_grade' => $car_grade]);
        } else {
            $this->error($carcommon_data['info']);
        }
    }

    /**
     * 车辆调度
     */
    public function dispatch($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['type', 'store_site_id']);
            $car_common = array(
                'id' => $id,
                'store_site_id' => $data['store_site_id']
            );
            $out_car = $this->carcommon_model->dispatchCarCommon($car_common, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
            out_json_data($out_car);
        }
    }

    /**
     * 最近站点自动车辆调度
     */
    public function dispatch_car($car_id)
    {
        $car_data = $this->carcommon_model->getCarCommonField($car_id, "goods_type,series_id,store_key_id,location_longitude,location_latitude,device_number");
        $car_data = $car_data['data'];
        $store_key_id = $car_data['store_key_id'];
        $lng = $car_data['location_longitude'];
        $lat = $car_data['location_latitude'];
        //获取经纬度范围(还车)
        $elng_lat = get_max_min_lng_lat($lng, $lat, 5);
        $emaxlng = $elng_lat['maxLng'];//最大经度
        $eminLng = $elng_lat['minLng'];//最小经度
        $emaxLat = $elng_lat['maxLat'];//最大纬度
        $eminLat = $elng_lat['minLat'];//最小纬度
        $emap['store_key_id'] = $store_key_id;
        $emap['store_status'] = 0;
        $emap['is_area'] = 1;
        $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
        $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
        $store_model = new StoreModel();
        $store_key_list = $store_model->getSiteList($emap);
        if (empty($store_key_list)) {
            $out_data['code'] = 3;
            $out_data['info'] = "当前位置无还车点";
            out_json_data($out_data);
        }
        foreach ($store_key_list as &$value) {
            $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
            $value->distance = $distance;
        }
        $store_key_list = json_encode($store_key_list);
        $store_key_list = json_decode($store_key_list, true);
        $store_key_list = rank_elecar_grade($store_key_list, 'distance');
        $return_store_id = $store_key_list[0]['id'];
        $return_store_name = $store_key_list[0]['store_name'];
        $car_common = array(
            'id' => $car_id,
            'store_site_id' => $return_store_id
        );
        $out_car = $this->carcommon_model->dispatchCarCommon($car_common, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $out_car['data'] = $return_store_name;
        out_json_data($out_car);
    }

    /**
     * 更新车辆信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * id 数据id
//            * 必输数据
//            * 非必须数据
//            * brand_id 品牌id
//            * brand_name 品牌名称
//            * series_id 车系id
//            * series_name 车系名称
//            * cartype_id 车型id
//            * cartype_name 车型名称
//            * licence_plate 车牌号
//            * engine_vin 发动机编号
//            * day_price 每天租金价格
//            * day_basic 基本保障服务费用
//            * km_price 每公里价格
//            * location_longitude 地理位置经度
//            * location_latitude 地理位置纬度
//            * store_affiliate_id 当前车辆所在门店id
//            * age_year 车辆使用年限
//            * car_grade 车辆等级类型
//            * car_color 车辆颜色
//            * car_status 当前车辆状态
//            * car_photos 车辆图集
//            * remark 变动备注信息
            $brand = $this->carbrand_model->get_brand_name_initial($data['brand_id']);
            $series_data = $this->carseries_model->get_series($data['series_id']);
            $series_name = $series_data['series_name'];
            $series_img = $series_data['series_img'];
            $cartype_name = $this->cartype_model->get_type_name($data['cartype_id']);
            $out_store = $this->store_model->getStore($data['store_site_id'], '', true);
            $store_info = array();
            if ($out_store['code'] == 0) {
                $store_info = $out_store['data'];
            } else {
                $this->error($out_store['info']);
            }
            $up_carcommon_data = array(
                'id' => $id,
                'initial' => $brand['initial'],
                'brand_id' => $data['brand_id'],
                'brand_name' => $brand['brand_name'],
                'series_id' => $data['series_id'],
                'series_name' => $series_name,
                'series_img' => $series_img,
                'cartype_id' => $data['cartype_id'],
                'cartype_name' => $cartype_name,
                'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
                'device_number' => $data['device_number'],
                'licence_plate' => $data['licence_plate'],
                'engine_vin' => $data['engine_vin'],
                'day_price' => $data['day_price'],
                'day_basic' => $data['day_basic'],
                'day_procedure' => 0,
                'km_price' => $data['km_price'],
                'location_longitude' => $store_info['location_longitude'],
                'location_latitude' => $store_info['location_latitude'],
                'store_site_name' => $store_info['store_name'],
                'store_site_id' => $data['store_site_id'],
                'age_year' => $data['age_year'],
                'car_cc' => $data['car_cc'],
                'car_grade' => $data['car_grade'],
                'car_color' => $data['car_color'],
                'car_status' => $data['car_status'],
                'car_photos' => serialize($data['photo']),
                'remark' => $data['remark']
            );
            $out_data = $this->carcommon_model->updateCarCommon($up_carcommon_data);
            if ($out_data['code'] === 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除车辆
     * @param $id
     */
    public function delete($id)
    {
        $ret = $this->carcommon_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }

    /**
     * 下线
     * @param $id
     */
    public function lock($id)
    {
        if ($this->carcommon_model->addLockCar($id)) {
            $this->success('下线成功');
        } else {
            $this->error('下线失败');
        }
    }

    /**
     * 上线
     * @param $id
     */
    public function del_lock($id)
    {
        if ($this->carcommon_model->delLockCar($id)) {
            $this->success('上线成功');
        } else {
            $this->error('上线失败');
        }
    }

    /**
     * 添加平台任务
     * @param $id
     * @param $remark
     */
    public function add_operation($id, $remark = '')
    {
        $field = "id,cartype_id,goods_type,cartype_name,licence_plate,store_site_id,store_site_name,car_status,device_number,location_longitude,location_latitude,store_key_id,store_key_name";
        $car_data = $this->carcommon_model->where(['id' => $id, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']])->field($field)->find();
        $this->carcommon_model->formatx($car_data);
        $out_data = $this->carcommon_model->addOperationList($car_data, 7, $remark);
        if (empty($out_data['code'])) {
            $this->success($out_data['info']);
        }
        $this->error($out_data['info']);
    }
}