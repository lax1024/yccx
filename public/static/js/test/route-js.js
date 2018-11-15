$(function () {
    // 百度地图API功能
    var map = new BMap.Map("map");
    map.enableScrollWheelZoom();

// map.centerAndZoom(new BMap.Point(106.70875,26.554143), 13);
    arrPois = Array();
    //循环，将数据添加到数组
    $.each(arrPoisjson, function (index, item) {
        arrPois.push(new BMap.Point(item.longitude, item.latitude));
    });
    //将数组倒序
    arrPois = arrPois.reverse();

// var runLine =[];
    function initMap() {
        //设置地图中心和级别
        map.centerAndZoom(arrPois[arrPois.length / 2], 12);
        //将变量转为数组

        /**添加终点和起点的标记**/
        addMarker(arrPois[0], '终点');
        addMarker(arrPois[arrPois.length - 1], '起点');
        //创建线路
        var polyline = new BMap.Polyline(
            arrPois,//所有的GPS坐标点
            {
                strokeColor: "#009933", //线路颜色
                strokeWeight: 4,//线路大小
                //         线路类型(虚线)
                strokeStyle: "dashed"  //轨迹线为虚线
            });
        //绘制线路
        map.addOverlay(polyline);
    }

    /**
     * 标记
     * @param {Object} point
     */
    function addMarker(point, name) {
        var marker = new BMap.Marker(point);
        var label = new BMap.Label(name, {
            offset: new BMap.Size(20, -10)
        });
        marker.setLabel(label);
        map.addOverlay(marker);
    }

//初始化地图
    initMap();

    var carMk;//先将终点坐标展示的mark对象定义
    var myIcon2 = new BMap.Icon(
        'http://www.youchedongli.cn/public/static/images/route_car.png',
        new BMap.Size(52,26),
        {anchor: new BMap.Size(27, 13)});//初始化终点坐标图标
    //运动轨迹
    getRoute(arrPois,1000);

    function getRoute(route,time) {
        var i = 0;
        var interval = setInterval(function () {
            if (i >= route.length-1) {
                clearInterval(interval);
                return;
            }
            i = i + 1;
            addMarkerCarMarker(route[route.length - i], '终点', map, route[route.length - i-1]);//添加图标
        }, time);
    }

    /**
     *
     * @param point  上一个位置(起始位置)
     * @param name
     * @param map   百度地图
     * @param mapInit   下一个位置(目标位置)
     */
    function addMarkerCarMarker(point, name, map, mapInit) {
        if (name == "终点") {
            if (carMk) {//先判断第一次进来的时候这个值有没有定义，有的话就清除掉上一次的。然后在进行画图标。第一次进来时候没有定义也就不走这块，直接进行画图标
                map.removeOverlay(carMk);
            }
            map.centerAndZoom(mapInit, 16);
            carMk = new BMap.Marker(point, {icon: myIcon2});  // 创建标注
            var rota = getRotation(point, mapInit);
            carMk.setRotation(rota);
            map.addOverlay(carMk);               // 将标注添加到地图中
        }
    }

    /**
     *在每个点的真实步骤中设置小车转动的角度
     *@param{BMap.Point} curPos 起点
     *@param{BMap.Point} targetPos 终点
     */
    function getRotation(curPos, targetPos) {
        var x1 = curPos.lng;
        var y1 = curPos.lat;
        var x2 = targetPos.lng;
        var y2 = targetPos.lat;
        var deg = 0;
        if (x1 != x2) {
            var tan = (y2 - y1) / (x2 - x1),
                atan = Math.atan(tan);
            deg = atan * 360 / (2 * Math.PI);
            if (x2 < x1) {
                deg = -deg + 180;
            } else {
                deg = -deg;
            }
            if (-deg < 0) {
                return 360 + deg;
            }
            return deg;
        } else {
            var disy = y2 - y1;
            var bias = 0;
            if (disy > 0)
                bias = -1;
            else
                bias = 1;

            var deg = -bias * 90;
            if (deg < 0) {
                return 360 + deg;
            }
            return deg;
        }
    }

});
