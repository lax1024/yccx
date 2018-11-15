<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\model\CarCommon;
use app\common\model\OrderCarPath;
use app\common\model\Store;
use Endroid\QrCode\QrCode;
use think\cache\driver\Redis;
use think\Request;
use tool\CarDeviceTool;
use tool\TableTool;

class Common extends HomeBase
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * 选取坐标
     * @param string $keyword
     * @param string $lng
     * @param string $lat
     * @return mixed
     */
    public function location($keyword = '', $lng = '', $lat = '')
    {
        return $this->fetch('location', ['keyword' => $keyword, 'lng' => $lng, 'lat' => $lat]);
    }

    /**
     * 车辆记录跟踪
     * @param string $id
     * @param string $did
     * @param int $speed
     * @param string $stime
     * @param string $etime
     * @param int $real
     * @return mixed
     */
    public function car_record($id, $did = '', $speed = 1, $stime = '', $etime = '', $real = 1)
    {
        $speed = intval($speed);
        if (empty($stime)) {
            $stime = date("Y-m-d H:i:s", time() - (3600 * 2));
        }
        if (empty($etime)) {
            $etime = date("Y-m-d H:i:s");
        }
        if (empty($did)) {
            $car_model = new CarCommon();
            $out_car = $car_model->getCarCommon($id, true);
        } else {
            $out_car['data']['device_number'] = $did;
        }
        if (!empty($real)) {
            $redis = new Redis();
            $out_data_device = $redis->get("status:" . $out_car['data']['device_number']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $route_data = json_decode($out_data_device, true);
        } else {
            $car_device_tool = new CarDeviceTool();
            $out_data_device = $car_device_tool->getDeviceLog($out_car['data']['device_number'], $stime, $etime);
            if (empty($out_data_device['code'])) {
                $route_data = json_encode($out_data_device['data'], true);
            } else {
                $route_data = json_encode([], true);
            }
            $real = 0;
        }
        return $this->fetch('car_record', ['car' => $out_car, 'route_data' => $route_data, 'id' => $id, 'speed' => $speed, 'stime' => $stime, 'etime' => $etime, 'real' => $real, 'device_number' => $out_car['data']['device_number']]);
    }

    /**
     * 选取区域
     * @param string $lng
     * @param string $lat
     * @param string $map_json
     * @return mixed
     */
    public function map_area($lng = '', $lat = '', $map_json)
    {
        return $this->fetch('map_area', ['lng' => $lng, 'lat' => $lat, 'map_json' => $map_json]);
    }

    /**
     * 按照周取车统计
     * @param string $data_int
     * @return mixed
     */
    public function week_bar($data_int = '201808')
    {
        $table_model = new TableTool();
        $data_week_a = $table_model->weekAcquireData($data_int, 0);
        $data_out_a = [];
        foreach ($data_week_a as $value) {
            $data_out_a[] = $value['count'];
        }
        $acquire_data = json_encode($data_out_a);

        $data_week_r = $table_model->weekReturnData($data_int, 0);
        $data_out_r = [];
        foreach ($data_week_r as $value) {
            $data_out_r[] = $value['count'];
        }
        $return_data = json_encode($data_out_r);
        return $this->fetch('week_bar', ['acquire_data' => $acquire_data, 'return_data' => $return_data]);
    }

    /**
     * 取车热力图
     * @param string $data_int
     * @return mixed
     */
    public function hot_map_acquire($data_int = '201808')
    {
        $table_model = new TableTool();
        $data_store = $table_model->acquireStore($data_int, 0);
        $data_out = json_encode($data_store);
        return $this->fetch('hot_map', ['data' => $data_out]);
    }

    /**
     * 还车热力图
     * @param string $data_int
     * @return mixed
     */
    public function hot_map_return($data_int = '201808')
    {
        $table_model = new TableTool();
        $data_store = $table_model->returnStore($data_int, 0);
        $data_out = json_encode($data_store);
        return $this->fetch('hot_map', ['data' => $data_out]);
    }

    /**
     * 店铺点取车情况分析
     * @param string $data_int
     * @param int $type 1取车情况 2还车情况  3站点收入情况
     * @return mixed
     */
    public function point_map($data_int = '201808', $type = 1)
    {
        $table_tool_model = new TableTool();
        $type = intval($type);
        $out_data = [];
        if ($type == 1) {
            $out_data = $table_tool_model->acquireStore($data_int, 0);
        } else if ($type == 2) {
            $out_data = $table_tool_model->returnStore($data_int, 0);
        } else if ($type == 3) {
            $out_data = $table_tool_model->acquireAmountStore($data_int, 0);
        }
        $name_value = [];
        $name_coord = [];
        foreach ($out_data as $value) {
            $name_value[] = [
                'name' => $value['name'],
                'value' => floatval($value['count']),
            ];
            $name_coord[$value['name']] = [
                floatval($value['lng']), floatval($value['lat'])
            ];
        }
        $name_value = json_encode($name_value);
        $name_coord = json_encode($name_coord);
        return $this->fetch('point_map', ['name_value' => $name_value, 'name_coord' => $name_coord, 'type' => $type]);
    }

    public function path_map($data_int = '201808')
    {
        $order_path_model = new OrderCarPath();
        $map = [
            'month' => $data_int
        ];
        $data_out = $order_path_model->where($map)->select();
        $out_data = [];
        foreach ($data_out as $v) {
            $out_data[] = unserialize($v['path']);
        }
        $out_data = json_encode($out_data);
        return $this->fetch('path_map', ['data' => $out_data]);
    }

    /**
     * 安小时
     * @param string $data_int
     * @return mixed
     */
    public function hour_bar($data_int = '201808')
    {
        $acquire_time = [];
        for ($i = 0; $i < 24; $i++) {
            $acquire_time[] = $i . "时";
        }
        $table_tool_model = new TableTool();
        $out_data = $table_tool_model->hourAcquireData($data_int, 0);
        $acquire_data = [];
        foreach ($out_data as $value) {
            $acquire_data[] = intval($value['count']);
        }
        $acquire_time = json_encode($acquire_time);
        $acquire_data = json_encode($acquire_data);
        return $this->fetch('hour_bar', ['acquire_time' => $acquire_time, 'acquire_data' => $acquire_data]);
    }

    /**
     * 店铺点显示
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function store_position($id = 0)
    {
        $store_model = new Store();
        $store_list = $store_model->getStoreFieldList($id, 'store_name,location_longitude,location_latitude');
        $data = [];
        foreach ($store_list as $value) {
            $data[] = [
                'name' => $value['store_name'],
                'position' => [$value['location_longitude'], $value['location_latitude']]
            ];
        }
        $data = json_encode($data);
        return $this->fetch('store_position', ['data' => $data]);
    }

    /**
     * 创建公众号菜单
     */
    public function creat_menu()
    {
        $menu = [
            "button" => [
                [
                    "name" => "我的",
                    "sub_button" => [
                        [
                            'type' => "view",
                            'name' => "个人中心",
                            'url' => "http://www.youchedongli.cn/mobile/index/user_center.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "我的订单",
                            'url' => "http://www.youchedongli.cn/mobile/index/order_mine.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "查找充电站",
                            'url' => "http://wechat.xcharger.net/arranging/gps?oasourceid=gh_c3debd706b25&dealer=871976061741834240"
                        ],
                    ]
                ],
                [
                    'type' => "view",
                    "name" => "立即用车",
                    "url" => "http://www.youchedongli.cn/mobile/index/map_choose_car.html",
                ],
                [
                    "name" => "其他",
                    "sub_button" => [
                        [
                            'type' => "view",
                            'name' => "常规租车",
                            'url' => "http://www.youchedongli.cn/mobile/index/index_tradition.html"
                        ],
                        [
                            'type' => "view",
                            'name' => "我要充电",
                            'url' => "http://www.youchedongli.cn/pile/index/index.html"
                        ],
                        [
                            'type' => "view",
                            "name" => "关于我们",
                            "url" => "http://www.youchedongli.cn/mobile/index/ewm.html"
                        ]
                    ]
                ]
            ]
        ];
        out_json_data($menu);
    }

    public function view_qrcode($text)
    {
        if (!empty($text)) {
            $text = urldecode($text);
            //生成当前的二维码
            $qrCode = new QrCode();
            //想显示在二维码中的文字内容，这里设置了一个查看文章的地址
            $qrCode->setText($text);
            $qrCode->setSize(500);
            header("Content-Type: image/jpeg;text/html; charset=utf-8");
            echo $qrCode->writeString();
            exit();
        }
    }

}