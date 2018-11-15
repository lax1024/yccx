<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    'app_namespace' => 'app',
    // 应用调试模式
    'app_debug' => true,
    // 应用Trace
    'app_trace' => false,
    // 应用模式状态
    'app_status' => '',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => [],
    // 扩展函数文件
    'extra_file_list' => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 默认时区
    'default_timezone' => 'PRC',
    // 是否开启多语言
    'lang_switch_on' => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => 'htmlspecialchars',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 应用类库后缀
    'class_suffix' => false,
    // 控制器类后缀
    'controller_suffix' => false,

    // auth配置
    'auth' => [
        // 权限开关
        'auth_on' => 1,
        // 认证方式，1为实时认证；2为登录认证。
        'auth_type' => 1,
        // 用户组数据不带前缀表名
        'auth_group' => 'auth_group',
        // 用户-用户组关系不带前缀表
        'auth_group_access' => 'auth_group_access',
        // 权限规则不带前缀表
        'auth_rule' => 'auth_rule',
        // 用户信息不带前缀表
        'auth_user' => 'admin_user',
    ],
    // auth配置
    'auth_seller' => [
        // 权限开关
        'auth_on' => 1,
        // 认证方式，1为实时认证；2为登录认证。
        'auth_type' => 1,
        // 用户组数据不带前缀表名
        'auth_group' => 'seller_group',
        // 用户-用户组关系不带前缀表
        'auth_group_access' => 'seller_group_access',
        // 权限规则不带前缀表
        'auth_rule' => 'auth_rule',
        // 用户信息不带前缀表
        'auth_user' => 'seller',
    ],
    // 全站加密密钥（开发新站点前请修改此项）
    'salt' => '1dFlxLhiuLqnUZe9kA',

    // 验证码配置
    'captcha' => [
        // 验证码字符集合
        'codeSet' => '0123456789',
        // 验证码字体大小(px)
        'fontSize' => 22,
        // 是否画混淆曲线
        'useCurve' => false,
        // 验证码位数
        'length' => 4,
        // 验证成功后是否重置
        'reset' => true
    ],

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module' => 'index',
    // 禁止访问模块
    'deny_module_list' => ['common'],
    // 默认控制器名
    'default_controller' => 'Index',
    // 默认操作名
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 操作方法后缀
    'action_suffix' => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo' => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr' => '/',
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由
    'url_route_on' => true,
    // 路由使用完整匹配
    'route_complete_match' => false,
    // 路由配置文件（支持配置多个）
    'route_config_file' => ['route'],
    // 是否强制使用路由
    'url_route_must' => false,
    // 域名部署
    'url_domain_deploy' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',
    // 表单ajax伪装变量
    'var_ajax' => '_ajax',
    // 表单pjax伪装变量
    'var_pjax' => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache' => false,
    // 请求缓存有效期
    'request_cache_expire' => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template' => [
        // 模板引擎类型 支持 php think 支持扩展
        'type' => 'Think',
        // 模板路径
        'view_path' => '',
        // 模板后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr' => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin' => '{',
        // 模板引擎普通标签结束标记
        'tpl_end' => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end' => '}',
    ],

    // 手机模板开启
    'mobile_theme' => false,

    // 视图输出字符串内容替换
    'view_replace_str' => [
        '__OSSIMG__' => 'http://img.youchedongli.cn',
        '__PUBLIC__' => '/public',
        '__UPLOAD__' => '/public/uploads',
        '__STATIC__' => '/public/static',
        '__IMAGES__' => '/public/static/images',
        '__JS__' => '/public/static/js',
        '__CSS__' => '/public/static/css',
        '__API__' => '/index.php/api',
        '__WAP__' => '/index.php/wap',
    ],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl' => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl' => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace' => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache' => [
        // 驱动方式
        'type' => 'File',
        // 缓存保存目录
        'path' => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session' => [
        'id' => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix' => 'think',
        // 驱动方式 支持redis memcache memcached
        'type' => '',
        // 是否自动开启 SESSION
        'auto_start' => true,
        //过期时间 s
        'expire' => 3600 * 12
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie' => [
        // cookie 名称前缀
        'prefix' => '',
        // cookie 保存时间
        'expire' => 86400 * 30,
        // cookie 保存路径
        'path' => '/',
        // cookie 有效域名
        'domain' => '',
        //  cookie 启用安全传输
        'secure' => false,
        // httponly设置
        'httponly' => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],


    //阿里云OSS配置
    'aliyun_oss' => [
        'KeyId' => 'LTAI6gNBV1SiMGRL',  //您的Access Key ID
        'KeySecret' => 'l1eBpb37dL3wZUzFQ4oT9KEouS1w68 ',  //您的Access Key Secret
        'Endpoint' => 'oss-cn-shenzhen.aliyuncs.com',  //阿里云oss 外网地址endpoint
        'Bucket' => 'ycdl',  //Bucket名称
        'Path' => 'public',
        'weburl' => 'http://img.youchedongli.cn',
        'upconfig' => [
            'size' => 2097152,
            'ext' => 'jpg,gif,png,bmp,txt',
            'oss_exts' => array(
                'image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'text/plain'
            )
        ]
    ],

    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 7200,
        'persistent' => false,
        'prefix' => '',
    ],
    //分页配置
    'paginate' => [
        'type' => 'bootstrap',
        'var_page' => 'page',
        'list_rows' => 15,
    ],
    //AES加密
    'aes' => [
        'IV' => 'Jji89lsLpIoLL91Y',
        'KEY' => 'ycDlyIAj817721WL',
    ],
    'goods_img_url' => 'http://www.youchedongli.cn/public',
    'web_url' => 'http://www.youchedongli.cn',
    'cmd_url' => 'http://localhost:8090/api/command',
    'charging_cmd_url' => 'http://localhost:8250/api/command',//POST
    'charging_terminal_get_url' => 'http://localhost:8250/api/terminal',//GET
    'device_get_url' => 'http://localhost:8090/api/device',
    'device_get_log_url' => 'http://localhost:8090/api/holLoo/position',
    'device_update_url' => 'http://localhost:8090/api/device!operation',
    'device_del_url' => 'http://localhost:8090/api/device!delete',
    'pay_code' => [
        'balance' => '余额支付',
        'weixin' => '微信支付',
        'alipay' => '支付宝支付',
        'unionpay' => '银联支付'
    ],
    'cash' => 599,//用户押金
    //订单超过此价格时  付款后用户可以随机得到一棵树种
    'give_pay_max' => 100,//单位元
    'channel_max_min' => [
        'max' => 100,//单笔最大分成 100元
        'min' => 0.01//单笔最小分成 0.01元
    ],
    'sign_max_count' => 3,//我要一个充电桩标记最大数
    'face_effective' => 0.45,//身份证人脸识别相似度 系数
    'electric_quantity' => 40,//低于多少km电量下架
    'extra_cost' => 0,//异地还车费用
    'rests_cost' => 0,//异地还车费用
    'reserve_time' => 60 * 15,//预定车辆时间（单位秒）
    'reserve_mileage' => 40,//预定车辆最小续航
    'charging_min' => 30,//充电桩下单最小余额
    'token_out_time' => 86400 * 5, //5天，
    'store_tel' => "02161542455", //客服电话，
    //拍照像素大小 分辨率 01 160*120,02 320*240 ,03 640*480
    'TakePhotosDpi' => [
        'front_back' => '02',//前后
        'content' => '03',//内部
    ],
    //渠道方案
    'ChannelCondition' => [
        1 => [
            50 => [
                'rebate' => 0.2,
                'info' => "充值大于0小于50送20%"
            ],
            100 => [
                'rebate' => 0.3,
                'info' => "充值大于50小于100送30%"
            ],
            200 => [
                'rebate' => 0.4,
                'info' => "充值大于100小于200送40%"
            ],
            500 => [
                'rebate' => 0.5,
                'info' => "充值大于200小于500送50%"
            ],
            'register' => [
                'rebate' => 30,
                'info' => "注册实名制完成,返30代金券",
                'explain' => "只可抵扣大于30元的费用",
            ],
            'channel_register' => [
                'rebate' => 15,
                'info' => "注册实名制完成,渠道返15代金券",
                'explain' => "只可抵扣大于15元的费用",
            ],
            'name' => '常规推广模式',
            'info' => "充值大于0小于50送20%,充值大于50小于100送30%,充值大于100小于200送40%,充值大于200小于500送50%"
        ]
    ],
    //设备升级  'updateTerminalData' => "HLT401_V202265,120.92.20.209,21,ftp_holloo,ftp_holloo_*TP_SZ,HLT401_V202265.BIN,./,",
//    'updateTerminalData' => "HLT401_V202265,47.106.157.21,21,ftp_holloo,ftp_holloo_*TP_SZ,HLT401_V202265.BIN,./,",
    'updateTerminalData' => "HLT401_V202267,47.106.157.21,21,ftp_holloo,ftp_holloo_*TP_SZ,HLT401_V202267.BIN,./,",
    //百度短消息
    'baidusms' => [
        'endPoint' => 'sms.bj.baidubce.com',
        'accessKey' => '2b8d0e1f6e6c4e1bbf91dd6f55b9bdf3',
        'secretAccessKey' => 'dffc72741e304219b23ba3580a867564',
        'tel' => '18785160986',
    ],
    //百度图像识别接口
    'baiduaip' => [
        'APP_ID' => '11428775',
        'API_KEY' => 'B1G8G1rKRZGCwNhsIe54IiWm',
        'SECRET_KEY' => 'UMpvYzwOxZIdvnKmeIHTb4mhcRZmCA5E',
    ],
    'MONEY_LOG_TYPE' => [
        'ADD' => ['code' => 'ADD', 'info' => '获得'],
        'SUB' => ['code' => 'SUB', 'info' => '减少'],
        'PAY' => ['code' => 'PAY', 'info' => '支付'],
        'DED' => ['code' => 'DED', 'info' => '后台扣除'],
        'REFUND' => ['code' => 'REFUND', 'info' => '退款'],
        'CODE' => ['ADD' => '获得', 'SUB' => '减少', 'PAY' => '支付', 'DED' => '后台扣除', 'REFUND' => '退款',]
    ],
    'wxconfig' => [
        'APPID' => 'wx4e093d94c1e9f807',
        'MCHID' => '1501465161',
        'KEY' => 'ad6c24c44c3bb45f450950ff875b1ab9',
        'APPSECRET' => '14f1bec2646e7cd55ad21652fc075984',
        'NOTIFY_URL' => 'http://www.youchedongli.cn/index/Notify/weixin.html',
        'NOTIFY_URL_CASH' => 'http://www.youchedongli.cn/index/Notify/weixincash.html',
        'NOTIFY_URL_BALANCE' => 'http://www.youchedongli.cn/index/Notify/weixinbalance.html',
        'QUERY_URL' => 'http://www.youchedongli.com/index/Notify/query_weixin_h5.html',
    ],
    'course' => [
        //北汽
        4660 => [
            'info' => "启动前，请先确认档位处于N档，踩住脚刹，将钥匙向前拧，直到仪表盘出现绿色的“READY”字样，然后调整档位，确认手刹松开即可移动。注：续航里程仅为在D档且未开空调情况下的预估里程，请按实际情况调整行程",
            'url' => "http://img.youchedongli.cn/public/video/course/EC200.mp4",
            'drive_km' => 205,
			'online_drive_km'=>120
        ],
        //东风ER30
        4661 => [
            'info' => "",
            'url' => "",
            'drive_km' => 300,
			'online_drive_km'=>120
        ],
        //奇瑞EQ
        4662 => [
            'info' => "启动前，请先确认档位处于N档，踩上脚刹，将钥匙向前拧，直到仪表盘出现绿色的“READY”字样，然后调整档位，确认手刹松开即可移动。",
            'url' => "http://img.youchedongli.cn/public/video/course/QR_EQ.mp4",
            'drive_km' => 180,
			'online_drive_km'=>100
        ],
        //奇瑞EQ1
        4663 => [
            'info' => "启动前，请先确认档位处于N档，踩住脚刹，按下点火按键“POWER”，直到仪表盘出现绿色的“READY”字样，然后调整档位，确认手刹松开即可移动。注：续航里程仅为在“节能模式”且未开空调情况下的预估里程，请按实际情况调整行程",
            'url' => "http://img.youchedongli.cn/public/video/course/QR_EQ1.mp4",
            'drive_km' => 160,
			'online_drive_km'=>100
        ],
        //众泰云100
        4664 => [
            'info' => "启动前，请先确认档位处于N档，踩上脚刹，将钥匙向前拧，直到仪表盘出现绿色的“READY”字样，然后调整档位，确认手刹松开，注意前进档“D档”为向前推，后退档“R档”为向后拨。",
            'url' => "http://img.youchedongli.cn/public/video/course/ZTY100.mp4",
            'drive_km' => 100,
			'online_drive_km'=>90
        ],
        //云雀Q1
        4665 => [
            'info' => "",
            'url' => ""
        ]
    ],
    //管理员
    'admin_dispatch_id' => [10, 17, 63, 71],
    'admin_dispatch_store_id' => [10 => 153, 17 => 153, 63 => 153, 71 => 153],
];
