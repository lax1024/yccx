<?php
namespace app\seller\validate;

use think\Validate;

/**
 * 友情链接验证器
 * Class Link
 * @package app\seller\validate
 */
class Link extends Validate
{
    protected $rule = [
        'name' => 'require'
    ];

    protected $message = [
        'name.require' => '请输入名称'
    ];
}