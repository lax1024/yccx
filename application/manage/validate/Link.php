<?php
namespace app\manage\validate;

use think\Validate;

/**
 * 友情链接验证器
 * Class Link
 * @package app\manage\validate
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