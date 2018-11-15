<?php

namespace app\common\model;

use definition\BalanceType;
use definition\ChannelType;
use think\Config;
use think\Model;

class CustomerCashLog extends Model
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
        $channel_type = ChannelType::$CHANNEl_TYPE_CODE;
        $data['channel_str'] = $channel_type[$data['channel']];
        $data['add_time_str'] = date('Y-m-d H:i:s', $data['add_time']);
    }

    /**
     * 获取列表 框架分页
     * @param array $map
     * @param string $order
     * @param $config_page
     * @param int $limit
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPagelist($map = array(), $order = '', $config_page, $limit = 8)
    {
        $cash_log_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($cash_log_list)) {
            foreach ($cash_log_list as &$value) {
                $this->formatx($value);
            }
        }
        return $cash_log_list;
    }
}