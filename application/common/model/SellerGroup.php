<?php

namespace app\common\model;

use think\Model;

class SellerGroup extends Model
{
    public function getList($map)
    {
        $group_list = $this->where($map)->select();
        return $group_list;
    }
}