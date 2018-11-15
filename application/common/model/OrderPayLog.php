<?php

namespace app\common\model;

/**
 * 支付记录 数据模型
 */

use definition\PayCode;
use think\Model;

class OrderPayLog extends Model
{
    protected $insert = ['add_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    public function formatx(&$data)
    {
        $pay_code = PayCode::$PAY_CODE;
        $data['type_str'] = $pay_code[$data['type']];
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
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }
}