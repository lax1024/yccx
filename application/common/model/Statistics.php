<?php

namespace app\common\model;

/**
 * 站点信息统计管理
 * 实现统计数据记录添加
 */

use definition\CarStatus;
use think\Model;

class Statistics extends Model
{
    protected $insert = ['create_time'];

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
        $data['data_log'] = unserialize($data['data_log']);
    }

    /**
     * 添加统计记录
     * @param $statistics_data
     * data_log 统计的数据
     * year 统计年
     * month 统计年-月
     * week 统计年-月-周
     * day 统计年-月-日
     * hour 统计小时
     * type 统计类型 1订单运营情况 2取车情况 3还车情况 4空站情况
     * d_type 统计类型 1日统计 2周统计 3月统计 4年统计
     * store_key_id 数据归属
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addStatistics($statistics_data)
    {
        $retuen_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($statistics_data['data_log']) || empty($statistics_data['type'])) {
            return $retuen_data;
        }
        $map = [
            'type' => $statistics_data['type'],
            'd_type' => $statistics_data['d_type'],
            'status' => 0,
        ];
        switch ($statistics_data['d_type']) {
            case 1:
                $map['day'] = $statistics_data['day'];
                break;
            case 2:
                $map['week'] = $statistics_data['week'];
                break;
            case 3:
                $map['month'] = $statistics_data['month'];
                break;
            case 4:
                $map['year'] = $statistics_data['year'];
                break;
        }
        $data_temp = $this->where($map)->find();
        if (!empty($data_temp)) {
            $retuen_data['code'] = 101;
            $retuen_data['info'] = "数据已存在";
            return $retuen_data;
        }
        $in_data = [
            'data_log' => serialize($statistics_data['data_log']),
            'year' => $statistics_data['year'],
            'month' => $statistics_data['month'],
            'week' => $statistics_data['week'],
            'day' => $statistics_data['day'],
            'type' => $statistics_data['type'],
            'd_type' => $statistics_data['d_type'],
            'status' => 0,
            'create_time' => time(),
            'store_key_id' => $statistics_data['store_key_id']
        ];
        $ret = $this->save($in_data);
        if ($ret !== false) {
            $retuen_data['code'] = 0;
            $retuen_data['info'] = "添加成功";
            return $retuen_data;
        }
        $retuen_data['code'] = 102;
        $retuen_data['info'] = "添加失败";
        return $retuen_data;
    }

    /**
     * 获取指定日期的数据
     * @param $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStatistics($map)
    {
        $retuen_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($map['type']) || empty($map['day'])) {
            return $retuen_data;
        }
        $map_s = [
            'type' => $map['type'],
            'day' => $map['day']
        ];
        $data_s = $this->where($map_s)->find();
        if (empty($data_s)) {
            $retuen_data['code'] = 101;
            $retuen_data['info'] = "无此数据";
            return $retuen_data;
        }
        $this->formatx($data_s);
        $retuen_data['code'] = 0;
        $retuen_data['data'] = $data_s;
        $retuen_data['info'] = "数据获取成功";
        return $retuen_data;
    }

    /**
     * 按页获取指定数据
     * @param array $map
     * @param string $order
     * @param $page_config
     * @param int $limit
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPageList($map, $order = '', $page_config, $limit = 8, $store_key_id = '')
    {
        if (!empty($store_key_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $statistics_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($statistics_list)) {
            foreach ($statistics_list as &$value) {
                $this->formatx($value);
            }
        }
        return $statistics_list;
    }


    /**
     * 获取统计列表
     * @param array $map
     * @param string $order
     * @param $page
     * @param int $limit
     * @param string $store_key_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($map, $order = '', $page, $limit = 8, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!empty($store_key_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        if (intval($limit) == 0) {
            $statistics_list = $this->where($map)->order($order)->select();
        } else {
            $statistics_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        }
        if (!empty($statistics_list)) {
            foreach ($statistics_list as &$value) {
                $this->formatx($value);
            }
        }
        return $statistics_list;
    }

}