<?php
namespace app\common\model;

use think\Model;

class ApiCode extends Model
{
    protected $insert = ['create_time'];

    /**
     * 自动生成时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

}