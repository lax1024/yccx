<?php

namespace app\common\model;

use think\Db;
use think\Model;

class AuthRule extends Model
{
    /**
     * 更新管理员菜单
     * @param $pid
     * @param $is_admin
     * @return bool
     */
    public function update_is_admin($pid, $is_admin)
    {
        $this->where(array('pid' => $pid))->setField('is_admin', $is_admin);
        $auth_list = $this->where(array('pid' => $pid))->field('id')->select();
        if (empty($auth_list)) {
            return true;
        }
        foreach ($auth_list as $value) {
            $this->update_is_admin($value['id'], $is_admin);
        }
        return true;
    }
}