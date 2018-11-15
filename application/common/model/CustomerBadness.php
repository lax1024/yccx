<?php

namespace app\common\model;

use app\common\model\Customer as CustomerModel;
use definition\CustomerBadnessType;
use think\Model;

/**
 * 用户不良记录模型
 * Class CarRegulations
 * @package app\common\model
 */
class CustomerBadness extends Model
{
    private $customer_model;//用户模型
    protected $insert = ['add_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new CustomerModel();
    }

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
        if (!empty($data['badness_img'])) {
            $data['badness_img'] = unserialize($data['badness_img']);
        }
        $customer_badness = CustomerBadnessType::$CUSTOMERSTATUS_CODE;
        $data['type_str'] = $customer_badness[intval($data['type'])];
    }

    /**
     * 获取违章信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCustomerBadness($id)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($id)) {
            return $out_data;
        }
        $customer_badness_data = $this->where(['id' => $id])->find();
        $this->formatx($customer_badness_data);
        $out_data['data'] = $customer_badness_data;
        $out_data['code'] = 0;
        $out_data['info'] = '获取成功';
        return $out_data;
    }

    /**
     * 获取列表
     * @param array $map
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getlist($map = array(), $order = '', $page = 0, $limit = 8)
    {
        $customer_badness_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($customer_badness_list)) {
            foreach ($customer_badness_list as &$value) {
                $this->formatx($value);
            }
        }
        return $customer_badness_list;
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
        $customer_badness_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($customer_badness_list)) {
            foreach ($customer_badness_list as &$value) {
                $this->formatx($value);
            }
        }
        return $customer_badness_list;
    }

    /**
     * 添加不良记录
     * @param $badness_info
     * customer_phone 用户电话
     * badness_notes 不良记录
     * badness_img 不良记录图片
     * type 不良记录类型
     * admin_id 操作管理员id
     * admin_name 操作管理员账号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addCustomerBadness($badness_info)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($badness_info['customer_phone']) || empty($badness_info['badness_notes'])) {
            return $out_data;
        }
        $customer_model = new Customer();
        $customer_data = $customer_model->where(['mobile_phone' => $badness_info['customer_phone']])->field('id,mobile_phone,customer_name')->find();
        if (empty($customer_data)) {
            $out_data['code'] = 101;
            $out_data['info'] = "用户数据不存在，请检查电话号码是否正确";
            return $out_data;
        }
        $in_badness = [
            'customer_id' => $customer_data['id'],
            'customer_name' => $customer_data['customer_name'],
            'customer_phone' => $customer_data['mobile_phone'],
            'badness_notes' => $badness_info['badness_notes'],
            'badness_img' => serialize($badness_info['badness_img']),
            'type' => $badness_info['type'],
            'add_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
            'admin_id' => $badness_info['admin_id'],
            'admin_name' => $badness_info['admin_name'],
        ];
        $ret = $this->save($in_badness);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "不良信息录入成功";
            return $out_data;
        }
        $out_data['code'] = 102;
        $out_data['info'] = "不良信息录入失败";
        return $out_data;
    }

    /**
     * 修改不良记录
     * @param $badness_info
     * customer_phone 用户电话
     * badness_notes 不良记录
     * badness_img 不良记录图片
     * type 不良记录类型
     * admin_id 操作管理员id
     * admin_name 操作管理员账号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateCustomerBadness($badness_info)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($badness_info['customer_phone']) || empty($badness_info['badness_notes'])) {
            return $out_data;
        }
        $customer_model = new Customer();
        $customer_data = $customer_model->where(['mobile_phone' => $badness_info['customer_phone']])->field('id,mobile_phone,customer_name')->find();
        if (empty($customer_data)) {
            $out_data['code'] = 101;
            $out_data['info'] = "用户数据不存在，请检查电话号码是否正确";
            return $out_data;
        }
        $update_badness = [
            'customer_id' => $customer_data['id'],
            'customer_name' => $customer_data['customer_name'],
            'customer_phone' => $customer_data['mobile_phone'],
            'badness_notes' => $badness_info['badness_notes'],
            'type' => $badness_info['type'],
            'badness_img' => serialize($badness_info['badness_img']),
            'update_time' => date("Y-m-d H:i:s"),
            'admin_id' => $badness_info['admin_id'],
            'admin_name' => $badness_info['admin_name']
        ];
        $ret = $this->save($update_badness, ['id' => $badness_info['id']]);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "不良信息修改成功";
            return $out_data;
        }
        $out_data['code'] = 102;
        $out_data['info'] = "不良信息修改失败";
        return $out_data;
    }

}