<?php

namespace app\common\model;

use definition\SignType;
use think\Config;
use think\Model;

class CustomerSignLog extends Model
{
    protected $insert = ['add_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return time();
    }

    public function formatx(&$data)
    {
        $sign_type = SignType::$GOODSTYPE_CODE;
        $data['type_str'] = $sign_type[intval($data['type'])];
        $data['add_time'] = date("Y-m-d H;i:s",$data['add_time']);
    }

    /**
     * 添加数据标记
     * @param $sign_data
     * @return array
     */
    public function addSign($sign_data)
    {
        $out_data = array(
            'code' => 1200,
            'info' => '参数有误'
        );
        if (empty($sign_data['customer_id']) || empty($sign_data['type'])) {
            return $out_data;
        }
        //最大添加桩的数量判断
        $data_cout = $this->where(array('customer_id' => $sign_data['customer_id'],'type'=>$sign_data['type']))->count();
        $sign_max_count = Config::get('sign_max_count');
        if (intval($data_cout) >= intval($sign_max_count)) {
            $out_data['code'] = 1201;
            $out_data['info'] = '您已经，添加标记超过最大数量';
            return $out_data;
        }
        $in_sign_data = array(
            'customer_id' => $sign_data['customer_id'],
            'longitude' => $sign_data['longitude'],
            'latitude' => $sign_data['latitude'],
            'type' => $sign_data['type'],
            'remark' => $sign_data['remark'],
            'address' => $sign_data['address'],
            'add_time' => time()
        );
        if ($this->save($in_sign_data)) {
            $id = $this->getLastInsID();
            $out_data['code'] = 0;
            $data = array(
                'id' => $id,
                'remark' => $sign_data['remark']
            );
            $out_data['data'] = $data;
            $out_data['info'] = "标注成功";
            return $out_data;
        }
        $out_data['code'] = 1202;
        $out_data['info'] = "标注失败";
        return $out_data;
    }

    /**
     * 删除数据标记
     * @param $sign_data
     * @return array
     */
    public function delSign($sign_data)
    {
        $out_data = array(
            'code' => 1200,
            'info' => '参数有误'
        );
        if (empty($sign_data['id']) || empty($sign_data['customer_id'])) {
            return $out_data;
        }
        $ret = $this->where(array('id' => $sign_data['id'], 'customer_id' => $sign_data['customer_id']))->delete();
        if ($ret) {
            $out_data['code'] = 0;
            $out_data['info'] = "删除成功";
        } else {
            $out_data['code'] = 1201;
            $out_data['info'] = "删除失败";
        }
        return $out_data;
    }

    /**
     * 获取列表
     * @param $map
     * @param $order
     * @param $page
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList($map, $order, $page, $limit)
    {
        $device_list = $this->where($map)->order($order)->limit($page * $limit, $limit)->select();
        return $device_list;
    }


    /**
     * 获取列表（有页码）
     * @param array $map
     * @param string $order
     * @param int $page_config
     * @param int $limit
     * @return \think\Paginator
     */
    public function getPageList($map = array(), $order = '', $page_config, $limit = 8)
    {
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }

}