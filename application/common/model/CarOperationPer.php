<?php

namespace app\common\model;

/**
 *添加管理人员 数据模型
 */

use think\Model;

class CarOperationPer extends Model
{
    public function formatx(&$data)
    {
        $data['status_str'] = "正常";
        if (intval($data['status']) == 1) {
            $data['status_str'] = "关闭";
        }
        $data['grade_str'] = "普通运维";
        if (intval($data['grade']) == 1) {
            $data['grade_str'] = "运维管理";
        }
    }

    /**
     * 添加管理人员
     * @param $operation_per
     * name 管理人员姓名
     * phone 管理人员电话
     * grade 管理人员等级
     * status 管理人员状态
     * store_key_id 归属店铺
     * store_key_name
     * @return array
     */
    public function addOperationPer($operation_per)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($operation_per['name']) || empty($operation_per['phone']) || empty($operation_per['store_key_id'])) {
            return $out_data;
        }
        $in_operation_per = [
            'name' => $operation_per['name'],
            'phone' => $operation_per['phone'],
            'grade' => $operation_per['grade'],
            'status' => $operation_per['status'],
            'add_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
            'store_key_id' => $operation_per['store_key_id'],
            'store_key_name' => $operation_per['store_key_name']
        ];
        $ret = $this->save($in_operation_per);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 更新管理人员
     * @param $operation_per
     *
     * goods_ids 发送的问题车辆 ['goods_id'=>'商品id','licence_plate'=>'车牌号','site_name'=>'站点名称']
     * @return array
     */
    public function updateOperationPer($operation_per)
    {
        $out_data = [
            'code' => 100,
            'info' => '参数有误'
        ];
        if (empty($operation_per['name']) || empty($operation_per['phone']) || empty($operation_per['store_key_id'])) {
            return $out_data;
        }
        $update_operation_per = [
            'name' => $operation_per['name'],
            'phone' => $operation_per['phone'],
            'grade' => $operation_per['grade'],
            'status' => $operation_per['status'],
            'update_time' => date("Y-m-d H:i:s")
        ];
        $ret = $this->save($update_operation_per, ['id' => $operation_per['id']]);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "更新成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "更新失败";
        return $out_data;
    }

    /**
     * 获取管理员
     * @param $id
     * @param $phone
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationPer($id = '', $phone = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        if (!empty($phone)) {
            $operation_per = $this->where(['phone' => $phone])->find();
            if (empty($operation_per)) {
                return $out_data;
            }
            $this->formatx($operation_per);
            $out_data['code'] = 0;
            $out_data['data'] = $operation_per;
            $out_data['info'] = "获取成功";
            return $out_data;
        } else if (!empty($id)) {
            $operation_per = $this->find($id);
            if (empty($operation_per)) {
                return $out_data;
            }
            $this->formatx($operation_per);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation_per;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 获取管理员
     * @param int $store_key_id 店铺归属
     * @param $grade 等级
     * @param $status 等级
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationPerList($store_key_id = 153, $grade = '', $status = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "数据不存在"
        ];
        $map = [];
        if (!empty($store_key_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        if (is_numeric($grade)) {
            $map['grade'] = $grade;
        }
        if (is_numeric($status)) {
            $map['status'] = $status;
        }
        $operation_per_list = $this->where($map)->select();
        if (empty($operation_per_list)) {
            return $out_data;
        }
        foreach ($operation_per_list as &$value) {
            $this->formatx($value);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $operation_per_list;
        $out_data['info'] = "获取成功";
        return $out_data;
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
        $operation_per_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        if (!empty($operation_per_list)) {
            foreach ($operation_per_list as &$value) {
                $this->formatx($value);
            }
        }
        return $operation_per_list;
    }


}