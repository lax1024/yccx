<?php

use think\Db;
use think\Config;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Session;
use think\Cache;
use baidu\aip\AipOcr;
use baidu\baiduSmsClient;
use think\cache\driver\Redis;
use app\common\model\Customer as CustomerModel;
use app\common\model\Store as StoreModel;
use app\common\model\Reserve as ReserveModel;

error_reporting(E_ERROR | E_WARNING | E_PARSE);
/**
 * 获取分类所有子分类
 * @param int $cid 分类ID
 * @return array|bool
 */
function get_category_children($cid)
{
    if (empty($cid)) {
        return false;
    }

    $children = Db::name('category')->where(['path' => ['like', "%,{$cid},%"]])->select();

    return array2tree($children);
}

/**
 * 根据分类ID获取文章列表（包括子分类）
 * @param int $cid 分类ID
 * @param int $limit 显示条数
 * @param array $where 查询条件
 * @param array $order 排序
 * @param array $filed 查询字段
 * @return bool|false|PDOStatement|string|\think\Collection
 */
function get_articles_by_cid($cid, $limit = 10, $where = [], $order = [], $filed = [])
{
    if (empty($cid)) {
        return false;
    }

    $ids = Db::name('category')->where(['path' => ['like', "%,{$cid},%"]])->column('id');
    $ids = (!empty($ids) && is_array($ids)) ? implode(',', $ids) . ',' . $cid : $cid;

    $fileds = array_merge(['id', 'cid', 'title', 'introduction', 'thumb', 'reading', 'publish_time'], (array)$filed);
    $map = array_merge(['cid' => ['IN', $ids], 'status' => 1, 'publish_time' => ['<= time', date('Y-m-d H:i:s')]], (array)$where);
    $sort = array_merge(['is_top' => 'DESC', 'sort' => 'DESC', 'publish_time' => 'DESC'], (array)$order);

    $article_list = Db::name('article')->where($map)->field($fileds)->order($sort)->limit($limit)->select();

    return $article_list;
}

/**
 * 根据分类ID获取文章列表，带分页（包括子分类）
 * @param int $cid 分类ID
 * @param int $page_size 每页显示条数
 * @param array $where 查询条件
 * @param array $order 排序
 * @param array $filed 查询字段
 * @return bool|\think\Collection
 */
function get_articles_by_cid_paged($cid, $page_size = 15, $where = [], $order = [], $filed = [])
{
    if (empty($cid)) {
        return false;
    }

    $ids = Db::name('category')->where(['path' => ['like', "%,{$cid},%"]])->column('id');
    $ids = (!empty($ids) && is_array($ids)) ? implode(',', $ids) . ',' . $cid : $cid;

    $fileds = array_merge(['id', 'cid', 'title', 'introduction', 'thumb', 'reading', 'publish_time'], (array)$filed);
    $map = array_merge(['cid' => ['IN', $ids], 'status' => 1, 'publish_time' => ['<= time', date('Y-m-d H:i:s')]], (array)$where);
    $sort = array_merge(['is_top' => 'DESC', 'sort' => 'DESC', 'publish_time' => 'DESC'], (array)$order);

    $article_list = Db::name('article')->where($map)->field($fileds)->order($sort)->paginate($page_size);

    return $article_list;
}

/**
 * 数组层级缩进转换
 * @param array $array 源数组
 * @param int $pid
 * @param int $level
 * @return array
 */
function array2level($array, $pid = 0, $level = 1)
{
    static $list = [];
    foreach ($array as $v) {
        if ($v['pid'] == $pid) {
            $v['level'] = $level;
            $list[] = $v;
            array2level($array, $v['id'], $level + 1);
        }
    }

    return $list;
}

/**
 * 数组层级缩进转换
 * @param array $array 源数组
 * @param int $pid
 * @param int $level
 * @return array
 */
function array2levelc($array, $pid = 0, $level = 1)
{
    static $list = [];
    foreach ($array as $v) {
        if ($v['store_pid'] == $pid) {
            $v['level'] = $level;
            $list[] = $v;
            array2levelc($array, $v['id'], $level + 1);
        }
    }
    return $list;
}

function array2leveltree($array, $pid = 0, $level = 1)
{
    static $list = [];
    foreach ($array as $v) {
        if ($v['pid'] == $pid) {
            $v['level'] = $level;
            $list[] = $v;
            array2leveltree($array, $v['id'], $level + 1);
        }
    }
    return $list;
}

/**
 * 构建层级（树状）数组
 * @param array $array 要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $pid_name 父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree(&$array, $pid_name = 'pid', $child_key_name = 'children')
{
    $counter = array_children_count($array, $pid_name);
    if (!isset($counter[0]) || $counter[0] == 0) {
        return $array;
    }
    $tree = [];
    while (isset($counter[0]) && $counter[0] > 0) {
        $temp = array_shift($array);
        if (isset($counter[$temp['id']]) && $counter[$temp['id']] > 0) {
            array_push($array, $temp);
        } else {
            if ($temp[$pid_name] == 0) {
                $tree[] = $temp;
            } else {
                $array = array_child_append($array, $temp[$pid_name], $temp, $child_key_name);
            }
        }
        $counter = array_children_count($array, $pid_name);
    }

    return $tree;
}

/**
 * 子元素计数器
 * @param array $array
 * @param int $pid
 * @return array
 */
function array_children_count($array, $pid)
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }

    return $counter;
}

/**
 * 把元素插入到对应的父元素$child_key_name字段
 * @param        $parent
 * @param        $pid
 * @param        $child
 * @param string $child_key_name 子元素键名
 * @return mixed
 */
function array_child_append($parent, $pid, $child, $child_key_name)
{
    foreach ($parent as &$item) {
        if ($item['id'] == $pid) {
            if (!isset($item[$child_key_name]))
                $item[$child_key_name] = [];
            $item[$child_key_name][] = $child;
        }
    }

    return $parent;
}

/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}

/**
 * 判断是否为手机访问
 * @return  boolean
 */
function is_mobile()
{
    static $is_mobile;

    if (isset($is_mobile)) {
        return $is_mobile;
    }

    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        $is_mobile = false;
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false
    ) {
        $is_mobile = true;
    } else {
        $is_mobile = false;
    }

    return $is_mobile;
}

/**
 * 邮箱格式检查
 * @param string $email
 * @return bool
 */
function check_email($email)
{
    $mode = '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/';
    if (preg_match($mode, $email)) {
        return true;
    } else {
        return false;
    }
}

/**
 * qq格式检查
 * @param string $qq
 * @return bool
 */
function check_qq($qq)
{
    $mode = '/^[1-9][0-9]{4,12}$/';
    if (preg_match($mode, $qq)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 微信格式检查
 * @param string $wechar
 * @return bool
 */
function check_wechar($wechar)
{
    if (strlen($wechar) > 1 && strlen($wechar) < 50) {
        return true;
    } else {
        return false;
    }
}

/**
 * 驾驶证编号格式检查
 * @param string $driver_license
 * @return bool
 */
function check_driver_license($driver_license)
{
    $mode = '/[0-9]{12}$/';
    if (preg_match($mode, $driver_license)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 手机号格式检查
 * @param string $mobile
 * @return bool
 */
function check_mobile_number($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    $reg = "/^1[012345789]\d{9}$/";

    return preg_match($reg, $mobile) ? true : false;
}

function array_group_by($arr, $key)
{
    $grouped = [];
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }

    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

//时转换模式 今天 18:00 昨天 18:00
function time_tostr($time)
{
    $time_ymdstr = date('Y-m-d', $time);
    $time_ymstr = date('Y-m', $time);
    $time_dstr = date('d', $time);
    $time_hsstr = date('H:s', $time);
    $time_nymdstr = date('Y-m-d');
    $time_nymstr = date('Y-m');
    $time_ndstr = date('d');

    if ($time_nymdstr == $time_ymdstr) {
        return '今天 ' . $time_hsstr; //今天
    } else {
        if ($time_ymstr == $time_nymstr) {
            $num = intval($time_ndstr) - intval($time_dstr);
            switch ($num) {
                case 1:
                    return '昨天 ' . $time_hsstr; //昨天
                    break;
                case 2:
                    return '前天 ' . $time_hsstr; //前天
                    break;
            }
        }
    }
    return $time_ymdstr;
}

/**
 * 时间转换
 * @param $next_ht
 * @return array
 */
function order_time_tostr($next_ht)
{
    $time_s = array(
        'day' => 0,
        'hour' => 0,
        'minute' => 0,
        'second' => 0,
    );
    if ($next_ht <= 0) {
        $time_s['timestr'] = "0小时0分0秒";
        return $time_s;
    }
    $time_s['hour'] = intval($next_ht / 3600);
    $time_s['minute'] = intval(($next_ht % 3600) / 60);
    $time_s['second'] = intval($next_ht % 60);
    $time_s['timestr'] = $time_s['hour'] . "小时" . $time_s['minute'] . "分钟" . $time_s['second'] . "秒";
    return $time_s;
}

/**
 * 时间转换
 * @param $next_ht
 * @return array
 */
function diy_time_tostr($next_ht)
{
    $time_s = array(
        'day' => 0,
        'hour' => 0,
        'minute' => 0,
        'second' => 0,
    );
    if ($next_ht <= 0) {
        $time_s['timestr'] = "0分0秒";
        $time_s['timestrs'] = "1秒前";
        $time_s['is_end'] = 1;
        return $time_s;
    }
    if ($next_ht >= 3600 * 24) {
        $time_s['day'] = intval($next_ht / (3600 * 24));
        $time_s['hour'] = intval(($next_ht % (3600 * 24)) / 3600);
        $time_s['minute'] = intval(($next_ht % 3600) / 60);
        $time_s['second'] = intval($next_ht % 60);
        $time_s['timestr'] = $time_s['day'] . "天 " . $time_s['hour'] . "小时";
        $time_s['timestrs'] = $time_s['day'] . "天前";
    } else if ($next_ht > 3600) {
        $time_s['hour'] = intval($next_ht / 3600);
        $time_s['minute'] = intval(($next_ht % 3600) / 60);
        $time_s['second'] = intval($next_ht % 60);
        $time_s['timestr'] = $time_s['hour'] . "小时 " . $time_s['minute'] . "分钟";
        $time_s['timestrs'] = $time_s['hour'] . "小时前";
    } else if ($next_ht > 60) {
        $time_s['minute'] = intval(($next_ht) / 60);
        $time_s['second'] = intval($next_ht % 60);
        $time_s['timestr'] = $time_s['minute'] . "分" . $time_s['second'] . "秒";
        $time_s['timestrs'] = $time_s['minute'] . "分钟前";
    } else {
        $time_s['minute'] = 0;
        $time_s['second'] = $next_ht;
        $time_s['timestr'] = 0 . "分" . $next_ht . "秒";
        $time_s['timestrs'] = $time_s['second'] . "秒前";
    }
    $time_s['is_end'] = 0;
    return $time_s;
}

/**
 * 时间转换
 * @param $time
 * @return array
 */
function time_toarray($time)
{
    $time_s = array(
        'year' => date('Y', $time),
        'month' => date('m', $time),
        'day' => date('d', $time),
        'hour' => date('H', $time),
        'minute' => date('s', $time),
        'second' => date('i', $time),
    );
    return $time_s;
}


/**
 * 获取微信 用户信息 并存储到 Session 并返回openid
 * @param $url
 * @param bool $is_s 是否获取关注
 * @return mixed|string
 */
function get_wxuser($url, $is_s = false)
{
    $customer_id = Session::get('customer_id');
    $openid = Session::get('wechar_openid');
    if (!empty($customer_id) && !empty($openid)) {
        if ($is_s) {
            $subscribe = Session::get('subscribe');
            if (!empty($subscribe)) {
                return $openid;
            }
        } else {
            return $openid;
        }
    }
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == true) {
        $wxconfig = Config::get('wxconfig');
        //配置参数的数组
        $CONF = array(
            '__APPID__' => $wxconfig['APPID'],
            '__SERECT__' => $wxconfig['APPSECRET'],
            '__CALL_URL__' => urlencode($url) //当前页地址
        );
        //没有传递code的情况下，先登录一下
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            $getCodeUrl = "https://open.weixin.qq.com/connect/oauth2/authorize" .
                "?appid=" . $CONF['__APPID__'] .
                "&redirect_uri=" . $CONF['__CALL_URL__'] .
                "&response_type=code" .
                "&scope=snsapi_userinfo" . #!!!scope设置为snsapi_base !!! snsapi_userinfo
                "&state=123#wechat_redirect";
            //跳转微信获取code值,去登陆
            header('Location:' . $getCodeUrl);
            exit;
        }
        $code = trim($_GET['code']);
        //使用code，拼凑链接获取用户openid
        $getTokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token" .
            "?appid={$CONF['__APPID__']}" .
            "&secret={$CONF['__SERECT__']}" .
            "&code={$code}" .
            "&grant_type=authorization_code";
        $res = file_get_contents($getTokenUrl);
        $json_obj = json_decode($res, true);
        //拿到openid,下面就可以继续调起支付啦
        $openid = $json_obj['openid'];
        $access_token = $json_obj['access_token'];
        Session::set('wechar_openid', $openid);
        Session::set('access_token', $access_token);
//        if ($is_s) {
        $access_tokens = get_access_token();
        $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_tokens . '&openid=' . $openid . '&lang=zh_CN';
        file_put_contents('access_token.temp', $get_user_info_url);
        $res = file_get_contents($get_user_info_url);
        //解析json
        $user_objs = json_decode($res, true);
        if (!empty($user_objs['errcode'])) {
            $access_tokens = get_access_token(true);
            $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_tokens . '&openid=' . $openid . '&lang=zh_CN';
//            file_put_contents('access_token.temp', $get_user_info_url);
            $res = file_get_contents($get_user_info_url);
            //解析json
            $user_objs = json_decode($res, true);
        }
        $subscribe = $user_objs['subscribe'];
        if (!empty($user_objs['nickname'])) {
            $nickname = $user_objs['nickname'];
        }
        $unionid = '';
        if (!empty($user_objs['unionid'])) {
            $unionid = $user_objs['unionid'];
        }
        if (!empty($user_objs['headimgurl'])) {
            $headimgurl = str_replace('/0', '/132', $user_objs['headimgurl']);
        }
        Session::set('unionid', $unionid);
        Session::set('subscribe', $subscribe);//是否已关注  0未关注 1已关注
//        }
        $get_user_info_url_userinfo = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res = file_get_contents($get_user_info_url_userinfo);
        //解析json
        $user_obj = json_decode($res, true);
//        if ($openid == "obyGk1EnEzuRNtVCRmf_Dsbi37ZE") {
////            print_r($user_obj);
////            exit();
////        }
        if (isset($user_obj['errcode']) && $user_obj['errcode'] == 48001) {
            $getCodeUrl = "https://open.weixin.qq.com/connect/oauth2/authorize" .
                "?appid=" . $CONF['__APPID__'] .
                "&redirect_uri=" . $CONF['__CALL_URL__'] .
                "&response_type=code" .
                "&scope=snsapi_base" . #!!!scope设置为snsapi_base !!! snsapi_userinfo
                "&state=123#wechat_redirect";
            //跳转微信获取code值,去登陆
            header('Location:' . $getCodeUrl);
            exit;
        }
        if (empty($nickname)) {
            $nickname = $user_obj['nickname'];
        }
        if (empty($headimgurl)) {
            $headimgurl = str_replace('/0', '/132', $user_obj['headimgurl']);
        }
        $channel_uid = Session::get('channel_uid');
        if (empty($channel_uid)) {
            $channel_uid = 0;
        }
        if (empty($openid) || empty($nickname)) {
            exit("未授权微信信息，无法使用系统");
        }
        $in_customer = array(
            'customer_nickname' => $nickname,
            'wechar_nickname' => $nickname,
            'wechar_openid' => $openid,
            'wechar_unionid' => $unionid,
            'wechar_headimgurl' => $headimgurl,
            'channel_uid' => $channel_uid
        );
        $customer_model = new CustomerModel();
        $customer_model->addWecharCustomer($in_customer);
        return $openid;
    }
    return '';
}

//oss上传
/*
 *$fFiles:文件域
 *$n：上传的路径目录
 *$ossClient
 *$bucketName
 *$web:oss访问地址
 *$isThumb:是否缩略图
 */
function oss_up_pic($fFiles, $n, $type, $bucketName, $web, $isThumb = false, $is_allpath = false)
{
    $config = config('aliyun_oss');
    $upconfig = $config['upconfig'];
    $fType = $fFiles['type'];
    $back = array(
        'error' => 0,
        'url' => '',
        'message' => '数据有误',
    );
    if (!in_array($fType, $upconfig['oss_exts'])) {
        $back['error'] = 1;
        $back['message'] = '文件格式不正确';
        exit(json_encode($back));
    }
    $fSize = $fFiles['size'];
    if ($fSize > $upconfig['size']) {
        $back['error'] = 1;
        $back['message'] = '文件超过了20M';
        exit(json_encode($back));
    }
    $fname = $fFiles['name'];
    if ($is_allpath === false) {
        $ext = substr($fname, stripos($fname, '.'));
        $fup_n = $fFiles['tmp_name'];
        $file_n = md5(time() . '_' . rand(100, 999));
        $datatime = date('Ymd');
        $object = $n . "/uploads" . $type . $datatime . "/" . $file_n . $ext;//目标文件名
    } else {
        $fup_n = $fFiles['tmp_name'];
        $object = $n;
    }
    //try 要执行的代码,如果代码执行过程中某一条语句发生异常,则程序直接跳转到CATCH块中,由$e收集错误信息和显示
    try {
        //获取配置项，并赋值给对象$config
        //实例化OSS
        $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
        //uploadFile的上传方法
        $ossClient->uploadFile($bucketName, $object, $fup_n);
    } catch (OssException $e) {
        //如果出错这里返回报错信息
        $back['error'] = 1;
        $back['message'] = $e->getMessage();
        return $back;
    }
    if ($isThumb === true) {
        // 图片缩放，参考https://help.aliyun.com/document_detail/44688.html?spm=5176.doc32174.6.481.RScf0S
        $back['thumb'] = $web . $object . "?x-oss-process=image/resize,h_300,w_300";
    }
    $back['error'] = 0;
    $back['url'] = "/" . $object;
    $back['message'] = '上传成功';
    return $back;
}

/**
 * 本地图片转移到OSS
 * @param string $file 文件全路径  路径/ 用_代替 开始路径没有/
 * @return mixed
 */
function oss_move($file = '')
{
    if (empty($file)) {
        $back['error'] = 1;
        $back['message'] = '文件不能为空';
        return $back;
    }
    $config = config('aliyun_oss');
    $bucketName = $config['Bucket'];
    $web = $config['weburl'];
    $file = str_replace('_', '/', $file);
    $filetemp = './' . $file;
    $back['url'] = '';
    if (!file_exists($filetemp)) {
        $back['error'] = 1;
        $back['message'] = '本地文件不存在';
        return $back;
    }
    $object = $file;//目标文件名
    //实例化OSS
    try {
        $ossClient = new OssClient($config['KeyId'], $config['KeySecret'], $config['Endpoint']);
        //uploadFile的上传方法
        $ossClient->uploadFile($bucketName, $object, $object);
    } catch (OssException $e) {
        //如果出错这里返回报错信息
        $back['error'] = 1;
        $back['message'] = $e->getMessage();
        return $back;
    }
    $back['error'] = 0;
    $back['url'] = $web . "/" . $object;
    $back['message'] = '上传成功';
    return $back;
}

/**
 * 获取图片保存到服务器
 * @param $serverId
 * @param $type
 * @param $path
 * @param bool $is_stream 是否返回数据流
 * @return string|array
 */
function get_img_url($serverId, $type, $path, $is_stream = false)
{
    $access_token = get_access_token();
    $openid = Session::get('wechar_openid');
    $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
    $res = file_get_contents($get_user_info_url);
    //解析json
    $user_objs = json_decode($res, true);
    if (!empty($user_objs['errcode'])) {
        $access_token = get_access_token(true);
    }
//    $serverId = "bfp4KWed89AKId3nGDa6gZoBD1j-FK1awWUOoeUmyQFyKnAPKiE7NEOz5iKV3FcJ";
//    $access_token = "aG7P6P8_8G9jNVNaq25ocNY8Vn1dfgDE-b2NVO2Qr5hRS1KNsY_ISHT70ZfXMwKRiaYYAoMeKW5aOO8Lv-T6tffOaf_DuZy-Xts5igEGpAwSh98BdFhfIkwiAN1cyVxbBTUiAGAFXY";
    $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $serverId;
    file_put_contents('serverId.temp', $serverId);
    $temp = file_get_contents($url);
    $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads/' . $path);
    $time_path = date('Ymd');
    $save_path = 'public/uploads/' . $path . '/' . $time_path;
    $file_name = md5(date('YmdHis') . rand(1000, 9999));
    if (!file_exists($upload_path . "/" . $time_path)) {
        mkdir($upload_path . "/" . $time_path, 0777, true);
    }
    $targetName = $upload_path . "/" . $time_path . '/' . $file_name . "." . $type;
    file_put_contents($targetName, $temp);
    $url = $save_path . '/' . $file_name . "." . $type;
    $url_file = str_replace('/', '_', $url);
    oss_move($url_file);
    //如果需要 返回数据流
    if ($is_stream) {
        $out_data = array(
            'data' => $temp,
            'url' => $url,
        );
        return $out_data;
    }
    return "/" . $url;
}

/**
 * 获取语音保存到服务器
 * @param $serverId
 * @param $type
 * @param $path
 * @param bool $is_stream 是否返回数据流
 * @return string
 */
function get_voice_url($serverId, $type, $path, $is_stream = false)
{
    $access_token = get_access_token();
    $openid = Session::get('wechar_openid');
    $get_user_info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
    $res = file_get_contents($get_user_info_url);
    //解析json
    $user_objs = json_decode($res, true);
    if (!empty($user_objs['errcode'])) {
        $access_token = get_access_token(true);
    }
//    $serverId = "bfp4KWed89AKId3nGDa6gZoBD1j-FK1awWUOoeUmyQFyKnAPKiE7NEOz5iKV3FcJ";
//    $access_token = "aG7P6P8_8G9jNVNaq25ocNY8Vn1dfgDE-b2NVO2Qr5hRS1KNsY_ISHT70ZfXMwKRiaYYAoMeKW5aOO8Lv-T6tffOaf_DuZy-Xts5igEGpAwSh98BdFhfIkwiAN1cyVxbBTUiAGAFXY";
    $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $serverId;
    file_put_contents('serverId.temp', $serverId);
    $temp = file_get_contents($url);
    //如果需要 返回数据流
    if ($is_stream) {
        return $temp;
    }
    $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads/' . $path);
    $time_path = date('Ymd');
    $save_path = 'public/uploads/' . $path . '/' . $time_path;
    $file_name = md5(date('YmdHis') . rand(1000, 9999));
    if (!file_exists($upload_path . "/" . $time_path)) {
        mkdir($upload_path . "/" . $time_path, 0777, true);
    }
    $targetName = $upload_path . "/" . $time_path . '/' . $file_name . "." . $type;
    file_put_contents($targetName, $temp);
    $url = $save_path . '/' . $file_name . "." . $type;
    $url_file = str_replace('/', '_', $url);
    oss_move($url_file);
    return "/" . $url;
}


/**
 * 获取$access_token
 * @param bool $is_update true 强制制刷新$access_token
 * @return bool|string
 */
function get_access_token($is_update = false)
{
    $wxconfig = Config::get('wxconfig');
    $appid = $wxconfig['APPID'];
    $secret = $wxconfig['APPSECRET'];
    $get_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;
    if ($is_update) {
        $res = file_get_contents($get_token_url);
        $json_objt = json_decode($res, true);
        $access_token = $json_objt['access_token'];
        Cache::set('temp_token_temp', $access_token, 7000);
        return $access_token;
    }
    $access_token = Cache::get('temp_token_temp');
    if (empty($access_token)) {
        $res = file_get_contents($get_token_url);
        $json_objt = json_decode($res, true);
        $access_token = $json_objt['access_token'];
        Cache::set('temp_token_temp', $access_token, 7000);
    }
    return $access_token;
}

/**
 * 获取$access_token $ticket
 * @param bool $is_update 强制制刷新$access_token
 * @return string
 */
function get_ticket($is_update = false)
{
    $access_token = get_access_token($is_update);
    $get_jsapi_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $access_token . "&type=jsapi";
    $res = file_get_contents($get_jsapi_url);
    $json_obj = json_decode($res, true);
    if (intval($json_obj['errcode']) == 0) {
        $ticket = $json_obj['ticket'];
    } else {
        $access_token = get_access_token(true);
        $get_jsapi_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $access_token . "&type=jsapi";
        $res = file_get_contents($get_jsapi_url);
        $json_obj = json_decode($res, true);
        if (intval($json_obj['errcode']) == 0) {
            $ticket = $json_obj['ticket'];
        } else {
            $ticket = '';
        }
    }
    return $ticket;
}

/**
 * 获取jssdk 签名
 * @param $url
 * @return array
 */
function get_jssdk($url)
{
    $wxconfig = Config::get('wxconfig');
    $appid = $wxconfig['APPID'];
    $ticket = Cache::get('wx_jssdk_ticket');
    if (empty($ticket)) {
        $ticket = get_ticket();
        if (empty($ticket)) {
            $ticket = get_ticket(true);
            Cache::set('wx_jssdk_ticket', $ticket, 7000);
        }
    }
    $noncestr = random_char(16);
    $timestamp = time();
    $string1 = "jsapi_ticket=" . $ticket . "&noncestr=" . $noncestr . "&timestamp=" . $timestamp . "&url=" . $url;
//    file_put_contents('wx.txt',$string1);
    $signature = sha1($string1);
    $redata = array(
        'timestamp' => $timestamp,
        'nonceStr' => $noncestr,
        'signature' => $signature,
        'appid' => $appid,
    );
//    file_put_contents('signwx.txt',json_encode($redata));
    return $redata;
}

/**
 * 删除指定数组中的指定值
 * @param $arr
 * @param $val
 * @return bool
 */
function del_arr_val(&$arr, $val)
{
    foreach ($arr as $key => $value) {
        if ($val == $value) {
            unset($arr[$key]);
            return true;
        }
    }
    return false;
}

/**
 * 获取随机字符串
 * @param int $length
 * @return string
 */
function random_char($length = 16)
{
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $char = '';
    for ($i = 0; $i < $length; $i++) {
        // 这里提供两种字符获取方式
        // 第一种是使用 substr 截取$chars中的任意一位字符；
        // 第二种是取字符数组 $chars 的任意元素
        // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
        $char .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $char;
}

//获取用户真实IP
function getIp()
{
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
    else
        if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else
            if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
                $ip = getenv("REMOTE_ADDR");
            else
                if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
                    $ip = $_SERVER['REMOTE_ADDR'];
                else
                    $ip = "unknown";
    return ($ip);
}

/**
 * 获取完整的url
 * @return string
 */
function curPageURL()
{
    $pageURL = 'http';

    if ($_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function get_wx_template()
{
    $access_token = get_access_token();
    $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=' . $access_token;
    $result = file_get_contents($url);
    $final = json_decode($result, true);
    return $final;
}

/**
 * 给指定用户发送消订单消息
 * @param $openid
 * @param string $name 操作内容
 * @param $id  订单id
 * @param int $type 1扫描成功 2开始充电  3完成充电 4支付完成
 * @return mixed|stdClass|string
 */
function reply_customer($openid, $name = '', $id, $type = 1)
{
    if (empty($openid)) {
        $data = new stdClass();
        $data->errcode = 1;
        $data->errmsg = '参数有误';
        return $data;
    }
    $access_token = get_access_token();
    $content = "";
    switch (intval($type)) {
        case 1:
            $content = "您好，欢迎使用优车出行充电服务，请尽快将充电枪插入汽车开始充电。";
            break;
        case 2:
            $text = $name . "号桩,于" . date("Y-m-d H:i:s") . "开始为您的爱车充电。";
            $content = "【开始充电】
$text

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
            break;
        case 3:
            $text = $name . "号桩,于" . date("Y-m-d H:i:s") . "已结束为您的爱车充电。本次订单费用总计" . $fee . "元";
            $content = "【充电完成】
$text
<a href='http://www.youchedongli.cn/pile/index/order_pay.html?id=$id'>立即支付</a>

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
            break;
        case 4:
            $text = "本次订单已于" . date("Y-m-d H:i:s") . "支付完成，感谢您的使用！";
            $content = "【支付完成】
$text

<a href='http://www.youchedongli.cn/pile/index/order_details.html?id=$id'>查看订单</a>";
            break;
        case 5:
            $content = "您好，欢迎使用优车出行充电服务，充电桩启动失败。";
            break;
    }
    $data['touser'] = $openid;
    $data['msgtype'] = "text";
    $data['text'] = array(
        'content' => "mytext"
    );
    $data = json_encode($data);
    $data = str_replace('mytext', $content, $data);
    $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
    $result = https_post($url, $data);
    $final = json_decode($result);
    return $final;
}


/**
 * $str Unicode编码后的字符串
 * $decoding 原始字符串的编码，默认GBK
 * $prefix 编码字符串的前缀，默认"&#"
 * $postfix 编码字符串的后缀，默认";"
 */
function unicode_decode($unistr, $encoding = 'GBK', $prefix = '&#', $postfix = ';')
{
    $arruni = explode($prefix, $unistr);
    $unistr = '';
    for ($i = 1, $len = count($arruni); $i < $len; $i++) {
        if (strlen($postfix) > 0) {
            $arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
        }
        $temp = intval($arruni[$i]);
        $unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
    }
    return iconv('UCS-2', $encoding, $unistr);
}

//发起POST请求
function https_post($url, $data)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        $result = array(
            'code' => 10000,
            'info' => "发送失败",
        );
        return $result;
    }
    curl_close($curl);
    return $result;
}

//发起GET请求
function https_get($url)
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
    //关闭URL请求
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        return 'Errno' . curl_error($curl);
    }
    curl_close($curl);
    return $result;
}

/**
 * 发送post请求
 * @param string $url 请求地址
 * @param array $post_data post键值对数据
 * @return string
 */
function send_post($url, $post_data)
{
    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 5 * 60 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result, true);
    return $result;
}

/**
 * 发送post请求
 * @param string $url 请求地址
 * @param array $post_data post键值对数据
 * @return string
 */
function send_post_json($url, $post_data)
{
    $postdata = json_encode($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 5 * 60 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result, true);
    return $result;
}

/**
 * 对二维数组进行排序
 * 模拟 数据表记录按字段排序
 *
 * <code>
 * @list_order($list, $get['orderKey'], $get['orderType']);
 * </code>
 * @param array $array 要排序的数组
 * @param string $orderKey 排序关键字/字段
 * @param string $orderType 排序方式，'asc':升序，'desc':降序
 * @param string $orderValueType 排序字段值类型，'number':数字，'string':字符串
 * @link http://www.cnblogs.com/52php/p/5668809.html
 */
function list_order(&$array, $orderKey, $orderType = 'asc', $orderValueType = 'string')
{
    if (is_array($array)) {
        $orderArr = array();
        foreach ($array as $val) {
            $orderArr[] = $val[$orderKey];
        }
        $orderType = ($orderType == 'asc') ? SORT_ASC : SORT_DESC;
        $orderValueType = ($orderValueType == 'string') ? SORT_STRING : SORT_NUMERIC;
        array_multisort($orderArr, $orderType, $orderValueType, $array);
    }
}

/**
 * 输出json数据
 * @param $data
 */
function out_json_data($data)
{
    header('Content-type:application/json;charset=UTF-8');
    header('Connection: keep-alive');
    //转换json 字符串
    $data_json = json_encode($data);
    //去除null 保证IOS 能正常解析
    $data_json = str_replace('null', '""', $data_json);
    exit($data_json);
}

//验证身份证号码
function validation_filter_id_card($id_card)
{
    if (strlen($id_card) == 18) {
        return idcard_checksum18($id_card);
    } elseif ((strlen($id_card) == 15)) {
        $id_card = idcard_15to18($id_card);
        return idcard_checksum18($id_card);
    } else {
        return false;
    }
}

// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base)
{
    if (strlen($idcard_base) != 17) {
        return false;
    }
    //加权因子
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    //校验码对应值
    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    $checksum = 0;
    for ($i = 0; $i < strlen($idcard_base); $i++) {
        $checksum += substr($idcard_base, $i, 1) * $factor[$i];
    }
    $mod = $checksum % 11;
    $verify_number = $verify_number_list[$mod];
    return $verify_number;
}

// 将15位身份证升级到18位
function idcard_15to18($idcard)
{
    if (strlen($idcard) != 15) {
        return false;
    } else {
        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
            $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
        } else {
            $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
        }
    }
    $idcard = $idcard . idcard_verify_number($idcard);
    return $idcard;
}

// 18位身份证校验码有效性检查
function idcard_checksum18($idcard)
{
    if (strlen($idcard) != 18) {
        return false;
    }
    $idcard_base = substr($idcard, 0, 17);
    if (idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
        return false;
    } else {
        return true;
    }
}

//校验车牌号
function check_licence_plate($license)
{
    if (empty($license)) {
        return false;
    }
//#匹配民用车牌和使馆车牌
//# 判断标准
//# 1，第一位为汉字省份缩写
//# 2，第二位为大写字母城市编码
//# 3，后面是5位仅含字母和数字的组合
    $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5}$/u";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

//#匹配特种车牌(挂,警,学,领,港,澳)
//#参考 https://wenku.baidu.com/view/4573909a964bcf84b9d57bc5.html
    $regular = '/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{4}[挂警学领港澳]{1}$/u';
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

////#匹配武警车牌
////#参考 https://wenku.baidu.com/view/7fe0b333aaea998fcc220e48.html
//    $regular = '/^WJ[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]?[0-9a-zA-Z]{5}$/ui';
//    preg_match($regular, $license, $match);
//    if (isset($match[0])) {
//        return true;
//    }

////#匹配军牌
////#参考 http://auto.sina.com.cn/service/2013-05-03/18111149551.shtml
//    $regular = "/[A-Z]{2}[0-9]{5}$/";
//    preg_match($regular, $license, $match);
//    if (isset($match[0])) {
//        return true;
//    }
//#匹配新能源车辆6位车牌
//#参考 https://baike.baidu.com/item/%E6%96%B0%E8%83%BD%E6%BA%90%E6%B1%BD%E8%BD%A6%E4%B8%93%E7%94%A8%E5%8F%B7%E7%89%8C
    #小型新能源车
    $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[DF]{1}[0-9a-zA-Z]{5}$/u";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }
    #大型新能源车
    $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{5}[DF]{1}$/u";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }
    return false;
}

/**
 * 校验车辆VIN编号是否有效
 * <li>使用vin的校验算法，直接计算出vin是否有效</li>
 * @param string $sVin 车辆的VIN码
 * @return boolean true:校验通过 | false:校验失败
 */
function check_engine_vin($sVin)
{
    $aCharMap = array(
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'J' => 1, 'K' => 2,
        'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9, 'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6,
        'X' => 7, 'Y' => 8, 'Z' => 9
    );
    $aWeightMap = array(8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2);
    $aCharKeys = array();
    foreach (array_keys($aCharMap) as $sNode) {//取出key
        $aCharKeys[] = strval($sNode);
    }
    $sVin = strtoupper($sVin); //强制输入大写

    if (strlen($sVin) !== 17) {
        return false; //长度不对
    } elseif (!in_array($sVin{8}, array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'X'))) {
        return false; //校验位的值不对
    }
    //检查vincode字符是否超表
    for ($i = 0; $i < 17; $i++) {
        if (!in_array($sVin{$i}, $aCharKeys)) {
            return false; //超出范围
        }
    }
    //计算权值总和
    $iTotal = 0;
    for ($i = 0; $i < 17; $i++) {
        $iTotal += $aCharMap[$sVin{$i}] * $aWeightMap[$i];
    }
    //计算校验码
    $sMode = $iTotal % 11;
    if ($sMode < 10 && $sMode === intval($sVin{8})) {
        return true;
    } elseif (10 === $sMode && 'X' === $sVin{8}) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取24小时 时间数组 $selected 0-23数字
 * @param $selected
 * @return array
 */
function get_time_hours($selected)
{
    $data_time = array();
    for ($i = 0; $i < 24; $i++) {
        $data_time_item = array(
            'name' => str_pad($i, 2, '0', STR_PAD_LEFT) . ":00",
            'selected' => ""
        );
        if ($i == $selected) {
            $data_time_item['selected'] = "selected";
        }
        $data_time[] = $data_time_item;
    }
    return $data_time;
}

/**
 * 获取时间差 换算成 天数 （小数）
 * @param int $acquire_time 开始时间戳
 * @param int $return_time 结束的时间戳
 * @return float 返回的天数 （小数）
 */
function get_acquire_return($acquire_time, $return_time)
{
    $count = $return_time - $acquire_time;
    $h = $count / 3600;
    $d = $h / 24;
    return round($d, 2);
}

/**
 * 获取时间差 换算成 天数 （向上取整）
 * @param int $acquire_time 开始时间戳
 * @param int $return_time 结束的时间戳
 * @return float 返回的天数 （小数）
 */
function get_acquire_return_car($acquire_time, $return_time)
{
    $count = $return_time - $acquire_time;
    $h = $count / 3600;
    $d = $h / 24;
    return ceil($d);
}

/**
 * 获取额外费用
 * @param $in_data
 * @return mixed
 */
function rests_cost_calc($in_data)
{
    $out_data['rests_cost'] = 0.00;
    $out_data['extra_cost_notes'] = "";
    $rests_cost = Config::get('rests_cost');
    if ($in_data['acquire_store_id'] != $in_data['return_store_id']) {
        $out_data['rests_cost'] = $rests_cost;
        $out_data['extra_cost_notes'] = "异地停车费用";
    }
    return $out_data;
}

/**
 * 计算查询的经纬度范围
 * @param string $lng 当前经度
 * @param string $lat 当前纬度
 * @param int $km 千米数 默认1千米
 * @return array
 */
function get_max_min_lng_lat($lng = '106.717801', $lat = '26.58804', $km = 1)
{
    $lng = doubleval($lng);
    $lat = doubleval($lat);
    //以下为核心代码
    $range = 180 / pi() * $km / 6372.797;     //里面的 $km 就代表搜索 1km 之内，单位km
    $lngR = $range / cos($lat * pi() / 180);
    $maxLng = $lng + $lngR;//最大经度
    $minLng = $lng - $lngR;//最小经度
    $maxLat = $lat + $range;//最大纬度
    $minLat = $lat - $range;//最小纬度
    //得出这四个值以后，就可以根据你数据库里存的经纬度信息查找记录了~
    $out_data = array(
        'maxLng' => $maxLng,//最大经度
        'minLng' => $minLng,//最小经度
        'maxLat' => $maxLat,//最大纬度
        'minLat' => $minLat//最小纬度
    );
    return $out_data;
}

/**
 *
 * /**
 *处理车型数据
 * @param $car_list 车列表
 * @param string $lng 经度
 * @param string $lat 纬度
 * @param int $start_time 开始时间戳
 * @param int $end_time 结束的时间戳
 * @param bool $is_other 是否是异地还车
 * @param array $store_key_id 异地可还车的店铺id
 * @return array
 */
function group_car_grade($car_list, $lng = '106.717801', $lat = '26.58804', $start_time = 0, $end_time = 0, $is_other = false, $store_key_id = array())
{
    $group_car_list = array();
    $group_store_list = array();
    $in_store_series = array();//车型店铺存储
    //将车信息 和店铺信息分离
    foreach ($car_list as $key => $value) {
        $return_store_id = 0;
        //如果区间已经被占用 则不记录
//        if (is_time_interval($value['reserve_interval'], $start_time, $end_time)) {
//            continue;
//        }
        if ($is_other) {
            //判断还车地址是否有门店
            foreach ($store_key_id as $ke => $val) {
                if (intval($value['store_key_id']) == intval($val)) {
                    $return_store_id = intval($ke);
                }
            }
            //如果找不到门店  则不添加数据
            if ($return_store_id == 0) {
                continue;
            }
        } else {
            $return_store_id = $value['store_site_id'];
        }
        $group_car_list[$value['series_id']]['car'] = array(
            'brand_id' => $value['brand_id'],
            'brand_name' => $value['brand_name'],
            'series_id' => $value['series_id'],
            'series_name' => $value['series_name'],
            'series_img' => $value['series_img'],
            'cartype_id' => $value['cartype_id'],
            'cartype_name' => $value['cartype_name'],
            'car_grade' => $value['car_grade'],
            'longitude' => $value['location_longitude'],
            'latitude' => $value['location_latitude']
        );
        if (!empty($in_store_series[intval($value['series_id'])])) {
            if (in_array($value['store_site_id'], $in_store_series[intval($value['series_id'])])) {
                //如果同车型 店铺信息没有被记录 则记录店铺信息
                continue;
            }
        }
        $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
        $group_store_list[$value['series_id']][] = array(
            'id' => $value['id'],
            'store_id' => $value['store_site_id'],
            'store_name' => $value['store_site_name'],
            'licence_plate' => $value['licence_plate'],
            'engine_vin' => $value['engine_vin'],
            'day_price' => $value['day_price'],
            'day_basic' => $value['day_basic'],
            'day_procedure' => $value['day_procedure'],
            'acquire_store_id' => $value['store_site_id'],
            'return_store_id' => $return_store_id,//还车店铺id
            'location_longitude' => $value['location_longitude'],
            'location_latitude' => $value['location_latitude'],
            'distance' => $distance,
            'synthesis' => floatval($value['day_price']) + floatval($value['day_basic']) + floatval($value['day_procedure']) + floatval($distance) * 50,
            'rent_count' => $value['rent_count'],
            'store_key_id' => $value['store_key_id'],
            'store_key_name' => $value['store_key_name']
        );
        $in_store_series[$value['series_id']][] = $value['store_site_id'];
    }
    //整合车型对应店铺列表
    foreach ($group_car_list as $key => $value) {
        $group_car_list[intval($key)]['store'] = $group_store_list[intval($key)];
    }
    //按照车辆级别分类
    $car_grade_list = array();
    foreach ($group_car_list as $value) {
        $car_grade_list[intval($value['car']['car_grade'])] = $value;
    }
    $car_list = array();
    $car_list['day_price'] = rank_car_grade($car_grade_list, 'day_price');
    $car_list['distance'] = rank_car_grade($car_grade_list, 'distance');
    $car_list['synthesis'] = rank_car_grade($car_grade_list, 'synthesis');
    return $car_list;
}

/**
 *
 * /**
 *处理新能源车型数据
 * @param $car_list 车列表
 * @param string $lng 经度
 * @param string $lat 纬度
 * @param bool $is_other 是否是异地还车
 * @param array $store_key_id 异地可还车的店铺点id
 * @return array
 */
function group_elecar_grade($car_list, $lng = '106.717801', $lat = '26.58804', $is_other = false, $store_key_id = array())
{
    $electric_quantity = Config::get('electric_quantity');
    $group_car_list = array();
    $reserve_model = new ReserveModel();
    $reserve_data_list = $reserve_model->getReserveKeyID();
    //将车信息 和店铺信息分离
    foreach ($car_list as $key => $value) {
        $redis = new Redis();
        if ($redis->has("login:" . $value['device_number']) && $redis->has("status:" . $value['device_number'])) {
            $out_data_device = $redis->get("status:" . $value['device_number']);
            if (empty($out_data_device)) {
                continue;
            }
            $car_device_data = json_decode($out_data_device, true);
            $driving_mileage = $car_device_data['drivingMileage'];
            $energy = $car_device_data['energy'];
            if (empty($energy)) {
                $energy = (intval($driving_mileage) / 180) * 100;
            } else if (empty($driving_mileage)) {
                $driving_mileage = intval(floatval($energy) / 100 * 100);
            }
            if ((floatval($driving_mileage) < $electric_quantity)) {
                continue;
            }
            $energy = $energy . "%";
        } else {
            continue;
        }
        if (in_array(intval($value['id']), $reserve_data_list)) {
            continue;
        }
        $value['location_longitude'] = $car_device_data['longitude'];
        $value['location_latitude'] = $car_device_data['latitude'];
        $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
        $group_car_list[] = array(
            'id' => $value['id'],
            'brand_id' => $value['brand_id'],
            'brand_name' => $value['brand_name'],
            'series_id' => $value['series_id'],
            'series_name' => $value['series_name'],
            'series_img' => $value['series_img'],
            'cartype_id' => $value['cartype_id'],
            'cartype_name' => $value['cartype_name'],
            'goods_type' => $value['goods_type'],
            'car_grade' => $value['car_grade'],
            'driving_mileage' => $driving_mileage,
            'energy' => $energy,
            'longitude' => $value['location_longitude'],
            'latitude' => $value['location_latitude'],
            'licence_plate' => $value['licence_plate'],
            'day_price' => $value['day_price'],
            'day_basic' => $value['day_basic'],
            'day_procedure' => $value['day_procedure'],
            'km_price' => $value['km_price'],
            'store_site_id' => $value['store_site_id'],
            'store_site_name' => $value['store_site_name'],
            'distance' => $distance,
            'synthesis' => floatval($value['day_price']) + floatval($value['day_basic']) + floatval($value['day_procedure']) + floatval($distance) * 50,
            'rent_count' => $value['rent_count'],
            'store_key_id' => $value['store_key_id'],
            'store_key_name' => $value['store_key_name']
        );
    }
    $car_list = array();
    $car_list['day_price'] = rank_elecar_grade($group_car_list, 'day_price');
    $car_list['distance'] = rank_elecar_grade($group_car_list, 'distance');
    $car_list['synthesis'] = rank_elecar_grade($group_car_list, 'synthesis');
    return $car_list;
}

/**
 *处理新能源车型数据
 * @param $car_list 车列表
 * @param string $lng 经度
 * @param string $lat 纬度
 * @return array
 */
function group_elecar_map($car_list, $lng = '106.717801', $lat = '26.58804')
{
    $electric_quantity = Config::get('electric_quantity');
    $group_car_list = array();
    $reserve_model = new ReserveModel();
    $reserve_data_list = $reserve_model->getReserveKeyID();
    //将车信息 和店铺信息分离
    foreach ($car_list as $key => $value) {
        $redis = new Redis();
        if ($redis->has("login:" . $value['device_number']) && $redis->has("status:" . $value['device_number'])) {
            $out_data_device = $redis->get("status:" . $value['device_number']);
            if (empty($out_data_device)) {
                continue;
            }
            $car_device_data = json_decode($out_data_device, true);
            $driving_mileage = $car_device_data['drivingMileage'];
            $energy = $car_device_data['energy'];
            if (empty($energy)) {
                $energy = ($driving_mileage / 180) * 100;
            } else if (empty($driving_mileage)) {
                $driving_mileage = intval(floatval($energy) / 100 * 100);
//                $driving_mileage = $energy;
            }
            if ((floatval($driving_mileage) < $electric_quantity)) {
                continue;
            }
        } else {
            continue;
        }
        if (in_array(intval($value['id']), $reserve_data_list)) {
            continue;
        }
        $value['location_longitude'] = $car_device_data['longitude'];
        $value['location_latitude'] = $car_device_data['latitude'];
        $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
        $renewal = $energy . "%(" . $driving_mileage . "km)";
        $group_car_list[] = array(
            'id' => $value['id'],
            'renewal' => $renewal,
            'brand_name' => $value['brand_name'],
            'series_name' => $value['series_name'],
            'series_img' => $value['series_img'],
            'series_id' => $value['series_id'],
            'cartype_name' => $value['cartype_name'],
            'driving_mileage' => $driving_mileage,
            'energy' => $energy,
            'day_price' => $value['day_price'],
            'km_price' => $value['km_price'],
            'car_name' => $value['cartype_name'],
            'license_num' => $value['licence_plate'],
            'license_num_str' => $value['licence_plate'],
            'lng' => $value['location_longitude'],
            'lat' => $value['location_latitude'],
            'store_site_id' => $value['store_site_id'],
            'store_site_name' => $value['store_site_name'],
            'rent_count' => $value['rent_count'],
            'store_key_id' => $value['store_key_id'],
            'store_key_name' => $value['store_key_name'],
            'distance' => $distance
        );
    }
    $store_map['store_status'] = 0;
    $store_map['store_type'] = 2;
    $order = " id ASC ";
    $store_model = new StoreModel();
    $store_key_id_list = $store_model->getStoreSiteKeyIdList($store_map, $order);
    $group_site_list_data = array();
    foreach ($store_key_id_list as $value) {
        $group_site_list_data[intval($value['id'])]['store'] = $value;
        $group_site_list_data[intval($value['id'])]['distance'] = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
        $group_site_list_data[intval($value['id'])]['list'] = array();
    }
    $group_car_list = rank_elecar_grade($group_car_list, 'distance');
    foreach ($group_car_list as $valx) {
        $group_site_list_data[intval($valx['store_site_id'])]['list'][] = $valx;
    }
//    print_r(json_encode($group_car_list));
    $group_site_list_data = rank_elecar_grade($group_site_list_data, 'distance');
    return $group_site_list_data;
}


/**
 * 常规排序
 * @param $car_grade_list
 * @param string $sort_key
 * @return mixed
 */
function rank_car_grade($car_grade_list, $sort_key = 'day_price')
{
    foreach ($car_grade_list as $key => $value) {
        $car_grade_list[$key]['store'] = two_sort($value['store'], $sort_key);
    }
    return $car_grade_list;
}

/**
 * 新能源车排序
 * @param $car_grade_list
 * @param string $sort_key
 * @return mixed
 */
function rank_elecar_grade($car_grade_list, $sort_key = 'day_price')
{
    if (empty($car_grade_list)) {
        return array();
    }
    $car_grade_list = two_sort($car_grade_list, $sort_key);
    return $car_grade_list;
}

/**
 * 计算两点地理坐标之间的距离
 * @param  string $longitude1 起点经度
 * @param  string $latitude1 起点纬度
 * @param  string $longitude2 终点经度
 * @param  string $latitude2 终点纬度
 * @param  Int $unit 单位 1:米 2:公里
 * @param  Int $decimal 精度 保留小数位数
 * @return double
 */
function get_distance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{
    $longitude1 = doubleval($longitude1);
    $latitude1 = doubleval($latitude1);
    $longitude2 = doubleval($longitude2);
    $latitude2 = doubleval($latitude2);
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;
    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }
    return round($distance, $decimal);

}

/**
 * 二维数组排序
 * @param $arrays 需要排序数组
 * @param string $sort_key 排序键值
 * @param int $sort_order SORT_ASC - 默认，按升序排列。(A-Z) SORT_DESC - 按降序排列。(Z-A)
 * @param int $sort_type
 * @return array
 */
function two_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
{
    if (is_array($arrays)) {
        foreach ($arrays as $array) {
            if (is_array($array)) {
                $key_arrays[] = $array[$sort_key];
            } else {
                return array();
            }
        }
    } else {
        return array();
    }
    array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
    return $arrays;
}

/**
 * 判断时间区间是否在 已有区间中
 * @param $reserve_interval
 * @param $start_time
 * @param $end_time
 * @return bool
 */
function is_time_interval($reserve_interval, $start_time, $end_time)
{
    if (empty($reserve_interval)) {
        return false;
    }
    $reserve_interval = unserialize($reserve_interval);
    foreach ($reserve_interval as $value) {
        if (intval($value['start_time']) <= intval($start_time) && intval($value['end_time']) >= intval($end_time)) {
            return true;//输入的时间区间在已有区间中
        }
    }
    return false;//输入的时间区间不在已有区间
}

/**
 * 删除已有时间区间
 * @param $reserve_interval
 * @param $start_time
 * @param $end_time
 * @return bool
 */
function del_time_interval($reserve_interval, $start_time, $end_time)
{
    if (empty($reserve_interval)) {
        return $reserve_interval;
    }
    $time = time();
    $reserve_interval = unserialize($reserve_interval);
    foreach ($reserve_interval as $key => $value) {
        if (intval($value['start_time']) <= intval($start_time) && intval($value['end_time']) >= intval($end_time)) {
            unset($reserve_interval[$key]);
        }
        if (intval($value['end_time'] < $time)) {
            unset($reserve_interval[$key]);
        }
    }
    return $reserve_interval;//删除预定区间
}

/**
 * 过滤无效的数据区间
 * @param $reserve_interval
 * @return mixed
 */
function filter_time_interval($reserve_interval)
{
    if (empty($reserve_interval)) {
        return $reserve_interval;
    }
    $time = time();
    $reserve_interval = unserialize($reserve_interval);
    foreach ($reserve_interval as $key => $value) {
        if (intval($value['end_time'] < $time)) {
            unset($reserve_interval[$key]);
        }
    }
    return $reserve_interval;
}

/**
 * 获取车辆排量
 * @return array
 */
function get_car_cc_list()
{
    $car_cc_list = ["1.0L", "1.0T", "1.2L", "1.2T", "1.3L", "1.3T", "1.4L", "1.4T", "1.5L", "1.5T", "1.6L",
        "1.6T", "1.7L", "1.7T", "1.8L", "2.0T", "2.0L", "2.1L", "2.1T", "2.2L", "2.2T", "2.3L", "2.3T",
        "2.4L", "2.4T", "2.5L", "2.5T", "2.6L", "2.6T", "2.7L", "2.7T", "2.8L", "2.8T", "3.0L",
        "3.0T", "3.5L", "3.5T", "4.0L", "4.0T", "4.5L", "4.5T", "5.0L", "5.0T", "5.5L", "5.5T", "6.0L", "6.0T",
        "7.0L", "7.0T", "8.0L", "8.0T",
    ];
    $car_cc = array();
    $car_cc[] = ['id' => "0", "name" => "无排量"];
    foreach ($car_cc_list as $value) {
        $car_cc[] = ['id' => $value, "name" => $value];
    }
    return $car_cc;
}

/**
 * 字符串转十六进制
 * @param $string
 * @return string
 */
function strToHex($string)
{
    $hex = "";
    for ($i = 0; $i < strlen($string); $i++)
        $hex .= dechex(ord($string[$i]));
    $hex = strtoupper($hex);
    return $hex;
}

/**
 * 姓名影藏
 * @param $vehicle_drivers
 * @return string
 */
function name_hide($vehicle_drivers)
{
    if (mb_strlen($vehicle_drivers) <= 2) {
        $drivers = "*" . mb_substr($vehicle_drivers, 1, 1);
    } else {
        $drivers = mb_substr($vehicle_drivers, 0, 1) . "*" . mb_substr($vehicle_drivers, -1);
    }
    return $drivers;
}

/**
 * 身份证号码影藏
 * @param $idcard
 * @return string strin
 */
function idcard_hide($idcard)
{
    $idcard = substr($idcard, 0, 6) . "**********" . substr($idcard, -2);
    return $idcard;
}

/**
 * 电话号码号码影藏
 * @param $phone
 * @return string strin
 */
function phone_hide($phone)
{
    $phone = substr($phone, 0, 3) . "****" . substr($phone, -4);
    return $phone;
}

/**
 * 根据图片地址获取车牌号码
 * @param $url
 * @param $images
 * @return array color 车牌颜色，number 车牌号码
 */
function license_plate($url, $images = '')
{
    $baidu_config = Config::get('baiduaip');
    $client = new AipOcr($baidu_config['APP_ID'], $baidu_config['API_KEY'], $baidu_config['SECRET_KEY']);
    if (empty($images)) {
        $image = file_get_contents($url);
    } else {
        $image = $images;
    }
    $data = $client->licensePlate($image);
    if (isset($data['words_result']['number'])) {
        $out_data = ['code' => 0, 'info' => '获取成功', 'data' => ['color' => $data['words_result']['color'], 'number' => $data['words_result']['number']]];
    } else {
        $out_data = ['code' => 10, 'info' => '获取失败'];
    }
    return $out_data;
}

/**
 * 返回星期几
 * @param $day
 * @return string
 */
function week_str($day)
{
    $week = date('w', strtotime($day));
    switch ($week) {
        case 0:
            return "日";
            break;
        case 1:
            return "一";
            break;
        case 2:
            return "二";
            break;
        case 3:
            return "三";
            break;
        case 4:
            return "四";
            break;
        case 5:
            return "五";
            break;
        case 6:
            return "六";
            break;
    }
}

/**
 * 验证时间区间否有效
 * @param $idcard_time
 * @param int $month 限制月数
 * @return bool
 */
function validity_time($idcard_time, $month = 2)
{
    //2014.02.11-2024.02.11
    $temp_time = explode('-', $idcard_time);
    if ($temp_time[1] == "长期") {
        return true;
    }
    if (strpos($temp_time[1], '年') !== false) {
        $time_arr = explode('.', $temp_time[0]);
        $y_t = intval($time_arr[0]) + intval($temp_time[1]);
        $timex = strtotime($y_t . "-" . $time_arr[1] . "-" . $time_arr[2]) - (3600 * 8);
        if ($timex - time() <= 3600 * 24 * 30 * $month) {
            return false;
        }
        return true;
    }
    $time = strtotime2038($temp_time[1]);
    if ($time - time() < 3600 * 24 * 30 * $month) {
        return false;
    }
    return true;
}

/**
 * 超过2038年
 * @param $time_str
 * @return int
 */
function strtotime2038($time_str)
{
    if (strlen($time_str) < 10) {
        return 0;
    }
    $time_str = str_replace('.', '-', $time_str);
    $obj = new DateTime($time_str);
    return intval($obj->format("U")) - (3600 * 8);
}


/**
 * 查找字符串中的数据
 * @param string $str
 * @return string
 */
function findNum($str = '')
{
    $str = trim($str);
    if (empty($str)) {
        return '';
    }
    $reg = '/(\d{3}(\.\d+)?)/is';//匹配数字的正则表达式
    preg_match_all($reg, $str, $result);
    if (is_array($result) && !empty($result) && !empty($result[1]) && !empty($result[1][0])) {
        return $result[1][0];
    }
    return '';
}

/**
 * 字符串转Bytes
 * @param $string
 * @param bool $is_string 是否是字符串
 * @return array|string
 */
function getBytes($string, $is_string = true)
{
    if ($is_string) {
        $bytes = "";
    } else {
        $bytes = array();
    }
    for ($i = 0; $i < strlen($string); $i++) {
        if ($is_string) {
            $bytes .= ord($string[$i]);
        } else {
            $bytes[] = ord($string[$i]);
        }
    }
    return $bytes;
}

/**
 *字节倒置
 * @param $data
 * @param int $byte
 * @return string
 */
function byteConvert($data, $byte = 2)
{
    $data = str_split(strtoupper(str_pad(dechex($data), $byte * 2, "0", STR_PAD_LEFT)));
    $out_data = "";
    $i = 0;
    while ($i < $byte * 2) {
        $out_data = $data[$i] . $data[$i + 1] . $out_data;
        $i += 2;
    }
    return $out_data;
}

/**
 * 申请提前还车
 * @param $mobile
 * @param $name
 * @param $phone
 * @param $order_sn
 * @return mixed
 */
function send_return_reserve_sms($mobile, $name, $phone, $order_sn)
{
    //发送还车申请 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:8ed8edad-5e98-4911-bc9c-2b794602dc33",
        "contentVar" => array(
            "phone" => $phone . "",
            "name" => $name . "",
            "order_sn" => $order_sn . "",
            "time" => date("Y-m-d H:i:s")
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

/**
 * 发送续航过低 运营信息
 * @param $mobile
 * @param $message
 * @return mixed
 */
function send_mileage_sms($mobile, $message)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:4f71692f-5840-4ece-b132-a9a37df6f657",
        "contentVar" => array(
            "plate" => $message . "",
            "time" => date("Y-m-d H:i:s")
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

/**
 * 发送车机掉线信息
 * @param $mobile
 * @param $message
 * @return mixed
 */
function send_device_sms($mobile, $message)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:cb3aeef3-0319-46f2-8fa5-a37518c7fbf4",
        "contentVar" => array(
            "plate" => $message . "",
            "time" => date("Y-m-d H:i:s")
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

/**
 * 提醒用户实名制 短信消息
 * @param $mobile
 * @return mixed
 */
function send_customer_sms($mobile)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:469816d9-baf2-4d62-b15d-b3ce2e9e32c6",
        "contentVar" => array(
            'content' => "您关注的共享汽车——优车出行 诚挚邀请您完成实名注册",
            'time' => "在注册完成后您可享受3小时内只需1元的用车体验，感谢您的参与。"
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

/**
 * 提醒用户实名制 短信消息
 * @param $mobile
 * @return mixed
 */
function send_customer_sms_message($mobile)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:e0b550b3-5d37-4f99-a18f-9463e0bbb797",
        "contentVar" => array(
            'time' => "10月14日起",
            'tip' => "更多详情，请进入微信公众号“优车出行”了解，期待您的用车。"
        ),
        //尊敬的用户，10月6日起，优车出行用车价格调整，所有车型均免收里程费，只收取小时费，费用如下：。优惠券多开多送，期待您的用车。
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}


/**
 * 提醒工作人员以及用户的超额订单
 * @param $mobile
 * @return mixed
 */
function send_use_order_sms($mobile, $data)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:145dbb12-feda-4087-83a7-d78998ed8bd1",
        "contentVar" => array(
            'licence_plate' => $data['licence_plate'],
            'time' => date("Y-m-d H:i:s"),
            'km' => $data['km'],
            'phone' => $data['phone'],
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

/**
 * 发送用户评论到管理员
 * @param $mobile
 * @param $data
 * @return mixed
 */
function send_manage_order_sms($mobile, $data)
{
    //发送验证码 代码
    $baidusms = new baiduSmsClient();
    $config = Config::get('baidusms');
    $baidusms->set_config($config);
//    管理员通知：客户${user}于${time}对用车做出来评论，评论内容。评分：${level}，标签：${tag}，内容：${content}。
    $message = array(
        "invokeId" => "GQou3Krg-WP40-GVNh",
        "phoneNumber" => $mobile . "",
        "templateCode" => "smsTpl:7ed61938-4701-41b5-b047-99dcaa07398e",
        "contentVar" => array(
            'user' => $data['user'],
            'time' => date("Y-m-d H:i:s"),
            'level' => $data['level'],
            'tag' => $data['tag'],
            'content' => $data['content']
        ),
    );
    $ret = $baidusms->sendMessage($message);
    if (intval($ret->code) == 1000) {
        $dataout['code'] = 0;
        $dataout['info'] = '已发送到手机';
        return $dataout;
    } else {
        $dataout['code'] = 1005;
        $dataout['info'] = $ret->message;
        return $dataout;
    }
}

//php异步请求
function doRequest($host, $path, $param = array())
{
    $query = isset($param) ? http_build_query($param) : '';
    $port = 80;
    $errno = 0;
    $errstr = '';
    $timeout = 10;
    $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
    $out = "POST " . $path . " HTTP/1.1\r\n";
    $out .= "host:" . $host . "\r\n";
    $out .= "content-length:" . strlen($query) . "\r\n";
    $out .= "content-type:application/x-www-form-urlencoded\r\n";
    $out .= "connection:close\r\n\r\n";
    $out .= $query;
    fputs($fp, $out);
    fclose($fp);
}

/**
 * 获取车辆指定的数据
 * @param $goods_device
 * @return array
 */
function getCarDevice($goods_device)
{
    $device = [
        'code' => 100,
        'info' => "获取失败"
    ];
    if (empty($goods_device)) {
        return $device;
    }
    $redis = new Redis();
    if (!$redis->has("login:" . $goods_device)) {
        $data_device['car_device_str'] = "离线";
        $data_device['car_device'] = 0;
    } else {
        $data_device['car_device_str'] = "在线";
        $data_device['car_device'] = 1;
    }
    $out_data_device = $redis->get("status:" . $goods_device);
    $data_device['driving_mileage'] = "无数据";
    $data_device['energy'] = "无数据";
    $data_device['voltage'] = "无数据";
    $data_device['location_latitude'] = 0;
    $data_device['location_longitude'] = 0;
    $data_device['odometer'] = 0;
    if (!empty($out_data_device)) {
        $car_device_data = json_decode($out_data_device, true);
        $data_device['driving_mileage'] = $car_device_data['drivingMileage'];
        $data_device['energy'] = $car_device_data['energy'];
        $data_device['voltage'] = $car_device_data['batteryVoltage'];
        $data_device['odometer'] = $car_device_data['odometer'];
        if (empty($data_device['energy'])) {
            $data_device['energy'] = (intval($data_device['driving_mileage']) / 160) * 100;
        } else if (empty($data_device['driving_mileage'])) {
            $data_device['driving_mileage'] = intval(floatval($data_device['energy']) / 100 * 100);
        }
        $data_device['location_latitude'] = $car_device_data['latitude'];
        $data_device['location_longitude'] = $car_device_data['longitude'];
        $data_device['driving_mileage_num'] = $data_device['driving_mileage'];
        $device['code'] = 0;
        $device['data'] = $data_device;
        $device['info'] = "获取成功";
    }
    return $device;
}
