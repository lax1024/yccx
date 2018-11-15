<?php
namespace app\api\validate;

use think\Validate;

/**
 * 后台登录验证
 * Class Login
 * @package app\seller\validate
 */
class Login extends Validate
{
    protected $rule = [
        'seller_name' => 'require',
        'password' => 'require',
        'verify'   => 'require|captcha'
    ];

    protected $message = [
        'seller_name.require' => '请输入用户名',
        'password.require' => '请输入密码',
        'verify.require'   => '请输入验证码',
        'verify.captcha'   => '验证码不正确'
    ];
}