<?php

namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

class HomeBase extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
        $this->getSystem();
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        //获取渠道信息
        $channel_uid = input('channel_uid');
        if (!empty($channel_uid)) {
            //将渠道信息保存到Session
            Session::set('channel_uid', $channel_uid);
        }
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            Cache::set('site_config', $site_config);
        }
        $this->assign($site_config);
    }
}