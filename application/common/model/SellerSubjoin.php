<?php

namespace app\common\model;

/**
 *商家附加信息 数据模型
 */
use think\Model;

class SellerSubjoin extends Model
{
    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
        $area_model = new Area();
        $data['province_name'] = $area_model->get_area_name($data['province_id']);
        $data['city_name'] = $area_model->get_area_name($data['city_id']);
        $data['area_name'] = $area_model->get_area_name($data['area_id']);
        $data['street_name'] = $area_model->get_area_name($data['street_id']);
        $seller_model = new Seller();
        $seller_data = $seller_model->where(array('id' => $data['seller_id']))->find();
        $data['seller_name'] = $seller_data['seller_name'];
        $data['seller_mobile'] = $seller_data['seller_mobile'];
    }

    /**
     * 获取数据
     * @param $id
     * @return array
     */
    public function getSellerSubjoin($id)
    {
        $out_data = array(
            'code' => 1,
            'info' => "数据不存在"
        );
        $seller_subjoin_data = $this->where(array('id' => $id))->find();
        if (empty($seller_subjoin_data)) {
            return $out_data;
        }
        $this->formatx($seller_subjoin_data);
        $out_data['code'] = 0;
        $out_data['data'] = $seller_subjoin_data;
        $out_data['info'] = "获取成功";
        return $out_data;

    }
}