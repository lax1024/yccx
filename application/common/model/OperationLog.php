<?php

namespace app\common\model;

use think\Model;

/**
 * 管理员操作日志
 * Class OperationLog
 * @package app\common\model
 */
class OperationLog extends Model
{
    /**
     * 添加操作日志
     * @param $operation_log
     * @return bool
     */
    public function addOperationLog($operation_log)
    {
        if (empty($operation_log['operation_id']) || empty($operation_log['operation_name']) || empty($operation_log['type']) || empty($operation_log['path'])) {
            return false;
        }
        if (!$this->_filtration($operation_log['path'])) {
            return false;
        }
        $in_operation_log = [
            'operation_id' => $operation_log['operation_id'],
            'operation_name' => $operation_log['operation_name'],
            'type' => $operation_log['type'],
            'remark' => $operation_log['remark'],
            'path' => $operation_log['path'],
            'parameter' => serialize($operation_log['parameter']),
            'add_time' => date("Y-m-d H:i:s")
        ];
        $ret = $this->save($in_operation_log);
        if ($ret !== false) {
            return true;
        }
        return false;
    }

    private function _filtration($path)
    {
        if (empty($path)) {
            return false;
        }
        $filtration = ['index', 'add', 'edit'];
        $fun = substr($path, strrpos($path, '/') + 1);
        if (in_array($fun, $filtration)) {
            return false;
        }
        return true;
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
        $operation_log_list = $this->where($map)->order($order)->paginate($limit, false, $config_page);
        return $operation_log_list;
    }
}