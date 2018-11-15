<?php

namespace app\common\model;

use app\common\model\CustomerChannel as CustomerChannelModel;
use definition\CouponCode;
use think\Config;
use think\Model;

class CustomerCoupon extends Model
{
    private $customer_channel_model;//用户模型
    protected $insert = ['start_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_channel_model = new CustomerChannelModel();
    }

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
        $this->is_past_coupon($data);
        $coupon_code_arr = [
            'platformp' => "平台发放",
            'activity' => "活动获取",
            'merchant' => "商户发放",
        ];
        //获取方式名称代码 platformp平台发放 activity活动获取 merchant商户发放
        $data['coupon_code_str'] = $coupon_code_arr[$data['coupon_code']];
//        代金券状态0已过期 10 待使用 20已使用
        $state_arr = [
            0 => "已过期",
            10 => "待使用",
            20 => "已使用"
        ];
        if (empty($data['admin_name'])) {
            $data['admin_name'] = "系统发放";
        }
        $data['state_str'] = $state_arr[intval($data['state'])];
        $data['end_time_str'] = date("Y-m-d H:i:s", $data['end_time']);
        $data['end_time_str_day'] = date("Y-m-d", $data['end_time']);
    }

    /**
     * 获取列表
     * @param array $map
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getlist($map = array(), $order = '', $page = 0, $limit = 8)
    {
        $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }

    /**
     * 获取列表 框架分页
     * @param array $map
     * @param string $order
     * @param $config_page
     * @param int $limit
     * @return \think\Paginator
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

    /**
     * 注册实名制 根据渠道反代金券
     * @param string $channel_uid
     * @param $customer_id
     * @return bool
     */
    public function register_coupon($channel_uid = '', $customer_id)
    {
//        if (empty($channel_uid)) {
//            return false;
//        }
//        $channel_data = $this->customer_channel_model->where(['customer_id' => $channel_uid, 'status' => 1])->field('condition')->find();
//        if (empty($channel_data)) {
//            return false;
//        }
//        $channel_condition = Config::get('ChannelCondition');
//        $channel_type = $channel_condition[intval($channel_data['condition'])];
        $channel_condition = Config::get('ChannelCondition');
        $channel_type = $channel_condition[1];
        if (!empty($channel_uid)) {
            $channel_in_coupon = [
                'customer_id' => $channel_uid,
                'coupon_code' => CouponCode::$CouponCodeActivity['code'],
                'coupon_type' => $channel_type['channel_register']['rebate'],
                'remark' => $channel_type['channel_register']['info'],
                'explain' => $channel_type['channel_register']['explain'],
                'end_time' => 3600 * 24 * 365
            ];
            $coupon_model = new CustomerCoupon();
            return $coupon_model->add_coupon($channel_in_coupon);
        }
//        if (!empty($channel_uid)) {
//            $channel_in_coupon = [
//                'customer_id' => $channel_uid,
//                'coupon_code' => CouponCode::$CouponCodeActivity['code'],
//                'coupon_type' => $channel_type['channel_register']['rebate'],
//                'remark' => $channel_type['channel_register']['info'],
//                'explain' => $channel_type['channel_register']['explain'],
//                'end_time' => 3600 * 24 * 365
//            ];
//            $coupon_model = new CustomerCoupon();
//            return $coupon_model->add_coupon($channel_in_coupon);
//        }
//
//        $in_coupon = [
//            'customer_id' => $customer_id,
//            'coupon_code' => CouponCode::$CouponCodeActivity['code'],
//            'coupon_type' => $channel_type['register']['rebate'],
//            'remark' => $channel_type['register']['info'],
//            'explain' => $channel_type['register']['explain'],
//            'end_time' => 3600 * 24 * 30
//        ];
//        if ($this->add_coupon($in_coupon)) {
//            if (!empty($channel_uid)) {
//                $channel_in_coupon = [
//                    'customer_id' => $channel_uid,
//                    'coupon_code' => CouponCode::$CouponCodeActivity['code'],
//                    'coupon_type' => $channel_type['channel_register']['rebate'],
//                    'remark' => $channel_type['channel_register']['info'],
//                    'explain' => $channel_type['channel_register']['explain'],
//                    'end_time' => 3600 * 24 * 365
//                ];
//                $coupon_model = new CustomerCoupon();
//                return $coupon_model->add_coupon($channel_in_coupon);
//            }
//            return true;
//        }
        return false;
    }

    /**
     * 使用代金券
     * @param $coupon
     * @return bool
     */
    public function use_coupon($coupon)
    {
        $coupon_data = $this->where(array('coupon_sn' => $coupon['coupon_sn'], 'coupon_type' => $coupon['coupon_type'], 'state' => 10))->find();
        if (empty($coupon_data)) {
            return false;
        }
        //判断代金券是否可用
        if (!$this->is_past_coupon($coupon_data)) {
            return false;
        }
        $coupon_data->state = 20;
        $coupon_data->remark = $coupon['remark'];
        $coupon_data->user_coupon_sn = $coupon['user_coupon_sn'];
        $coupon_data->user_time = date("Y-m-d H:i:s");
        if ($coupon_data->save()) {
            return true;
        }
        return false;
    }

    /**
     * 获取代金券
     * @param $coupon
     * @return bool
     */
    public function add_coupon($coupon)
    {
        if (empty($coupon['admin_id'])) {
            $coupon['admin_id'] = 0;
            $coupon['admin_name'] = "";
        }
        $coupon_sn = $this->_create_coupon_sn();
        $in_coupon = array(
            'customer_id' => $coupon['customer_id'],
            'coupon_sn' => $coupon_sn,
            'coupon_code' => $coupon['coupon_code'],
            'coupon_type' => $coupon['coupon_type'],
            'remark' => $coupon['remark'],
            'explain' => $coupon['explain'],
            'start_time' => date("Y-m-d H:i:s"),
            'end_time' => time() + $coupon['end_time'],
            'admin_id' => $coupon['admin_id'],
            'admin_name' => $coupon['admin_name'],
            'state' => 10
        );
        $ret = $this->save($in_coupon);
        if ($ret !== false) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否过期
     * @param $coupon
     * @return bool
     */
    public function is_past_coupon($coupon)
    {
        $coupon_data = $this->where(array('coupon_sn' => $coupon['coupon_sn'], 'coupon_type' => $coupon['coupon_type'], 'state' => 10))->find();
        if (empty($coupon_data)) {
            return false;
        } else {
            if (intval($coupon_data['end_time']) <= time()) {
                $coupon_data->state = 0;
                $coupon_data->remark = $coupon['remark'];
                $coupon_data->user_time = date("Y-m-d H:i:s");
                $coupon_data->save();
                return false;
            }
        }
        return true;
    }

    /**
     * 获取充值订单号
     * @return string
     */
    private function _create_coupon_sn()
    {
        $sn = "coupon_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

}