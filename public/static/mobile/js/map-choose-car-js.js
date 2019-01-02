//获取单个数据节点.
var js_marker_items = $("#js_marker_group").find('.js-car-item').clone(true);
$("#js_marker_group").find('.js-car-item').remove();
var js_marker_indexs = $("#js_marker_index_group").find('.js_car_index_item').clone(true);
$("#js_marker_index_group").find('.js_car_index_item').remove();

var colorList = ["#fd4514", "#ff820c", "#ffbd06", "#fff300", "#f3ff00", "#caff02", "#aaff04", "#74fa09", "#4ff10c", "#2be910"];
var bars = new Array();//电量
var bd_point = new Array();//用来实现GPS坐标转为百度坐标的中间变量
var store_point = new Array();//门店的坐标数组
var store_marker = new Array();//门店的marker(标识)数组
var polylineDriving;//路线覆盖物
var driving;//路线规划实例
var star_point;//路线规划起点
var carMarker;//车辆图标
var is_have = false;//判断附近是否有可用车
var is_show = true;//判断是否显示第一个有车门店的信息弹窗
var store_lable;//门店marker的距离标签
var store_data;//门店数据(请求得到的)
var main_lable = new Array();//小级别(1、2级)的门店名称数组

//创建地图实例
var map = new BMap.Map("l-map");
//创建点坐标,初始化地图，设置中心点坐标和地图级别
map.centerAndZoom(new BMap.Point(106.634237, 26.4081193), 16);
// var pointx = new BMap.Point(marker_self_lng, marker_self_lat);
// map.centerAndZoom(pointx, 16);

map.addControl(new BMap.NavigationControl({anchor: BMAP_ANCHOR_BOTTOM_LEFT}));
map.addControl(new BMap.ScaleControl());//比例尺(左下角)
map.addControl(new BMap.OverviewMapControl());//地图缩放


// get_car_list();
//获取车辆列表
function get_car_list() {
    //用户自身的经纬度，用作获取附近车辆的条件(参数)
    marker_self_lat = localStorage.getItem("old_marker_self_lat");
    marker_self_lng = localStorage.getItem("old_marker_self_lng");
    $.ajax({
        url: "http://www.youchedongli.cn/api/car/get_carcommon_list.html",
        // url: "http://www.ycdl.com/api/car/get_carcommon_list.html",
        type: "POST",
        async: true,
        data: {
            goods_type: "2",
            slng: marker_self_lng,
            slat: marker_self_lat,
            // slng:"106.632199",
            // slat:"26.405257",
            is_map: "1"
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            if (data.code == 0) {
                // console.log(data);
                store_data = data;
                add_store_marker(data, 3);
            }
        },
        fail: function () {
            Toast("请求失败", 2000);
        }
    });
}
/**
 * 添加门店的marker标注，默认为3级
 * @param data  请求到的数据
 */
function add_store_marker(data, level) {
    store_point = new Array();
    for (var i = 0; i < store_marker.length; i++) {
        map.removeOverlay(store_marker[i]);
    }
    store_marker = new Array();
    $.each(data.level, function (index, value) {
        if (index == level) {
            for (var i = 0; i < value.length; i++) {
                bd_point = wgs2bd(parseFloat(value[i].store.location_latitude), parseFloat(value[i].store.location_longitude));
                var myIcon;
                if (value[i].list.length >= 0 && value[i].list.length < 10) {
                    myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/marker_car_num_" + value[i].list.length + ".png?rand=20666&x-oss-process=image/resize,h_300", new BMap.Size(30, 53), {imageSize: new BMap.Size(30, 53)});
                } else if (value[i].list.length >= 10) {
                    myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/marker_car_num_9up.png?rand=20666&x-oss-process=image/resize,h_300", new BMap.Size(30, 53), {imageSize: new BMap.Size(30, 53)});
                }
                store_point[i] = new BMap.Point(bd_point[1], bd_point[0]);
                store_marker[i] = new BMap.Marker(store_point[i], {icon: myIcon});
                map.addOverlay(store_marker[i]);

                add_marker_info_click(value[i], store_marker[i], store_point[i]);

            }
        }
    });
    if (!is_have) {
        Toast("附近暂无可用车辆", 2000);
    }
}

/**
 * 添加门店的marker标注，这里处理的是1、2级的
 * @param data  请求到的数据
 */
function add_store_marker_up(data, level) {
    store_point = new Array();
    for (var i = 0; i < store_marker.length; i++) {
        map.removeOverlay(store_marker[i]);
    }
    store_marker = new Array();
    for (var i = 0; i < main_lable.length; i++) {
        map.removeOverlay(main_lable[i]);
    }
    main_lable = new Array();
    $.each(data.level, function (index, value) {
        if (index == level) {
            for (var i = 0; i < value.length; i++) {

                bd_point = wgs2bd(parseFloat(value[i].store.location_latitude), parseFloat(value[i].store.location_longitude));
                var myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/store_main.png?rand=20666&x-oss-process=image/resize,h_300", new BMap.Size(50, 50), {imageSize: new BMap.Size(50, 50)});

                store_point[i] = new BMap.Point(bd_point[1], bd_point[0]);
                store_marker[i] = new BMap.Marker(store_point[i], {icon: myIcon});

                main_lable[i] = new BMap.Label("<div style='width: 45px;height: 45px;text-align: center;display: table;white-space:normal;'><div style='width: 100%;height: 100%;display: table-cell;vertical-align: middle'>" + value[i].store.store_name +"</div></div>", {
                    offset: new BMap.Size(-25, -23),
                    position: store_point[i]
                });
                main_lable[i].setStyle({
                    border: "0",
                    backgroundColor: "",
                    transform:"scale(0.8)"
                });
                map.addOverlay(main_lable[i]);

                map.addOverlay(store_marker[i]);
                add_marker_info_click_up(store_marker[i], store_point[i]);
            }
        }
    });
}

/**
 * 当地图缩放当小级别时，呈现的“聚合”marker的点击事件
 * @param store_marker   点击的目标marker
 */
function add_marker_info_click_up(store_marker, store_point) {
    var open = function () {
        add_store_marker(store_data, 3);
        map.centerAndZoom(store_point, 14);
    };
    //点击Marker时展示详细信息窗口
    store_marker.addEventListener("click", function () {
        open();
    });
}

/**
 * 添加门店marker的点击事件
 * @param store_data   门店数据
 * @param store_marker   门店marker，目标marker
 * @param store_point   门店的位置:用来设置距离标签
 */
function add_marker_info_click(store_data, store_marker, store_point) {
    // is_have = false;//初始化是否有车
    /**
     * 分类设置门店marker的点击事件
     * 如果门店的车辆列表大于0，则点击后显示相关的门店及车辆信息
     * 如果门店的车辆列表不大于0，则点击后做相应的提示
     */
    if (store_data.list.length > 0) {
        /**
         * 在上一级的判断下，判断如果附近(2公里范围内)是否有车可用
         */
        if (parseInt(store_data.distance) < 2) {
            is_have = true;
        }
        //设置有车门店的marker点击事件
        var open = function () {
            //如果该marker的距离标签存在，则先清掉
            if (store_lable != null) {
                map.removeOverlay(store_lable);
            }
            //创建marker的标签对象
            store_lable = new BMap.Label("<div class='marker_store_label'>距您" + store_data.distance + "公里</div>", {
                offset: new BMap.Size(-28, -30),
                position: store_point
            });
            store_lable.setStyle({
                border: "0",
                backgroundColor: ""
            });
            //如果门店marker的标签为空，则添加
            if (store_marker.getLabel() == null) {
                store_marker.setLabel(store_lable);
            }
            //提示所点击门店共有多少车辆可以
            Toast(store_data.store.store_name + "共" + store_data.list.length + "辆车可用", 5000);
            //显示信息弹窗
            $("#js_car_pop").fadeOut(100);
            $("#js_marker_group").find('.js-car-item').remove();
            //在信息弹窗中添加相应的门店下的车辆信息
            add_car_pop_item(store_data.list, "js_marker_group", js_marker_items);
            $("#js_marker_car_pop").fadeIn(500);
            $("#js_user_map_fun").hide();
        };
        /**
         * 显示最近一家门店的信息弹窗
         */
        if (is_show && is_have) {
            open();
            bd_point = wgs2bd(parseFloat(store_data.list[0].lat), parseFloat(store_data.list[0].lng));
            drivingNavigation(bd_point[1], bd_point[0]);
            is_show = false;
        }
    } else {
        //设置无车门店的marker点击事件
        var open = function () {
            //如果该marker的距离标签存在，则先清掉
            if (store_lable != null) {
                map.removeOverlay(store_lable);
            }
            //创建marker的标签对象
            store_lable = new BMap.Label("<div class='marker_store_label'>距您" + store_data.distance + "公里</div>", {
                offset: new BMap.Size(-28, -30),
                position: store_point
            });
            store_lable.setStyle({
                border: "0",
                backgroundColor: ""
            });
            //如果门店marker的标签为空，则添加
            if (store_marker.getLabel() == null) {
                store_marker.setLabel(store_lable);
            }
            Toast(store_data.store.store_name + "暂无可用车辆", 5000);
            $("#js_marker_group").find('.js-car-item').remove();
            if (carMarker != null) {
                map.removeOverlay(carMarker);
            }
            if (polylineDriving !== null) {
                map.removeOverlay(polylineDriving);
            }
            $("#js_marker_car_pop").fadeOut(200);
        };
    }
    //点击Marker时展示详细信息窗口
    store_marker.addEventListener("click", function () {
        open();
    });
}

/**
 * 添加单个节点数据
 * @param json_item_data
 * @param div_id
 * @param item
 */
function add_car_pop_item(json_item_data, div_id, item) {
    $("#js_marker_group").find('.js-car-item').remove();
    $("#js_marker_index_group").find('.js_car_index_item').remove();
    for (var i = 0; i < json_item_data.length; i++) {
        add_marker_car_index(i, "js_marker_index_group", js_marker_indexs);
        //复制样式对象
        var item_temp = item.clone(true);
        if (i == 0) {//设置第一条车辆数据为默认显示(选择)的
            $(item_temp).addClass("mui-active");
            bdp = wgs2bd(parseFloat(json_item_data[i].lat), parseFloat(json_item_data[i].lng));
            drivingNavigation(bdp[1], bdp[0]);
            // walkingNavigation(parseFloat(json_item_data[i].lng), parseFloat(json_item_data[i].lat));
        }
        //填充数据
        item_temp.attr("data-index", i);
        item_temp.find(".js_marker_car_id").text(json_item_data[i].id);
        item_temp.find(".js_marker_car_lat").text(json_item_data[i].lat);
        item_temp.find(".js_marker_car_lng").text(json_item_data[i].lng);
        item_temp.find(".js_marker_car_series_img").attr("src", "http://img.youchedongli.cn/public/" + json_item_data[i].series_img + "?x-oss-process=image/resize,w_275&rand=20180723");
        item_temp.find(".js_marker_car_pop_renewal").text(json_item_data[i].renewal);
        item_temp.find(".js_marker_car_plate").text(json_item_data[i].license_num);
        item_temp.find(".js_marker_car_name").text(json_item_data[i].cartype_name);
        if (parseInt(json_item_data[i].series_id) === 4664) {
            item_temp.find(".js_marker_car_tag").html("<span style=\"font-size: 90%;color:#ff8730\">仅限大学城和金石产业园区域还车</span>");
        }
        item_temp.find(".js_marker_car_driving_mileage").text(json_item_data[i].driving_mileage + "公里");
        item_temp.find(".js_marker_car_price").text(parseFloat(json_item_data[i].day_price).toFixed(1));
        item_temp.find(".js_marker_km_price").text(parseFloat(json_item_data[i].km_price).toFixed(1));
        item_temp.find(".js_marker_car_store_id").text(json_item_data[i].store_site_id);
        item_temp.find(".js_marker_car_close").on("click", function () {
            $("#js_marker_group").find('.js-car-item').remove();
            $("#js_marker_car_pop").fadeOut(200);
            $("#js_user_map_fun").show();

            if (polylineDriving != null) {
                map.removeOverlay(polylineDriving);
                map.removeOverlay(carMarker);
            }

            if (store_lable != null) {
                map.removeOverlay(store_lable);
            }
        });

        bars = item_temp.find(".bar");

        for (var j = 0; j < (parseInt(json_item_data[i].energy) / 10); j++) {
            $(bars[j]).css("background", colorList[j]);
        }
        // parseInt(json_item_data[i].energy)/10
        //将填充好数据的样式对象元素加到指定的控件中
        item_temp.appendTo($("#" + div_id));
    }

    /**
     * 获得slider插件对象
     * 实现显示车辆信息时默认显示第一辆
     */
    var gallery = mui('.mui-slider');
    gallery.slider().gotoItem(0);
}

/**
 * 添加单个节点数据
 * @param index
 * @param div_id
 * @param item
 */
function add_marker_car_index(index, div_id, item) {
    //复制样式对象
    var item_temp = item.clone(true);

    if (index == 0) {
        $(item_temp).addClass("car_index_item_checked");
    } else {
        $(item_temp).removeClass("car_index_item_checked");
    }
    //填充数据
    item_temp.attr("data-index", index);

    //将填充好数据的样式对象元素加到指定的控件中
    item_temp.appendTo($("#" + div_id));

}

//使用车辆（点击门店出现的弹窗）
$(".js_marker_car_use").on("click", function () {
    var active_car_item = $("#js_marker_group").find(".mui-active");
    var car_store_id = active_car_item.find(".js_marker_car_store_id");
    var car_id = active_car_item.find(".js_marker_car_id");
    $.ajax({
        url: website + "/api/customer/add_reserve.html",
        type: "GET",
        async: true,
        data: {
            car_id: car_id.text()
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            // alert(JSON.stringify(data));
            if (parseInt(data.code) === 0) {
                useThisCar(car_store_id.text(), car_id.text());
            } else if (parseInt(data.code) === 5) {
                Toast(data.info, 3000);
                setTimeout(function () {
                    location.href = "user_cash.html";
                }, 3200);
            } else if (parseInt(data.code) === 3) {
                Toast(data.info, 3000);
                setTimeout(function () {
                    location.href = "add_user_info.html";
                }, 3200);
            } else if (parseInt(data.code) === 1112) {
                Toast(data.info, 3000);
                setTimeout(function () {
                    location.href = "order_mine.html";
                }, 3200);
            } else {
                Toast(data.info, 3000);
            }
        }
    });
});
//使用车辆（车牌搜索出现的弹窗）
$("#js_marker_car_use").on("click", function () {
    var js_car_pop = $("#js_car_pop");

    var car_store_id = js_car_pop.find(".js_marker_car_store_id");
    var car_id = js_car_pop.find(".js_marker_car_id");
    $.ajax({
        url: website + "/api/customer/add_reserve.html",
        type: "GET",
        async: true,
        data: {
            car_id: car_id.text()
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            if (data.code === 0) {
                useThisCar(car_store_id.text(), car_id.text());
            } else {
                Toast(data.info, 2000);
            }
        }
    });
});

/**
 * 驾驶路线导航
 * @param car_lng  车辆经度
 * @param car_lat  车辆纬度
 */
function drivingNavigation(car_lng, car_lat) {
    if (polylineDriving != null) {
        map.removeOverlay(polylineDriving);
    }

    driving = new BMap.DrivingRoute(map, {
        renderOptions: {autoViewport: true},
        policy: BMAP_DRIVING_POLICY_LEAST_DISTANCE
    });    //创建步行实例
    var end_point = new BMap.Point(car_lng, car_lat);
    marker_self_lat = localStorage.getItem("marker_self_lat");
    marker_self_lng = localStorage.getItem("marker_self_lng");
    // marker_self_lat = "26.405257";
    // marker_self_lng = "106.632199";
    star_point = new BMap.Point(parseFloat(marker_self_lng), parseFloat(marker_self_lat));
    var myIcon;
    myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/marker_car_flag.png?rand=20668", new BMap.Size(30, 53));

    if (carMarker != null) {
        map.removeOverlay(carMarker);
    }
    carMarker = new BMap.Marker(end_point, {icon: myIcon});
    map.addOverlay(carMarker);
    // alert("路线");
    driving.search(star_point, end_point);
    driving.setSearchCompleteCallback(function () {
        var pts = driving.getResults().getPlan(0).getRoute(0).getPath();    //通过步行实例，获得一系列点的数组
        polylineDriving = new BMap.Polyline(pts, {strokeColor: "#004986", strokeWeight: 3, strokeOpacity: 1});
        map.addOverlay(polylineDriving);

        // map.setViewport(pts);          //调整到最佳视野
    });
}

/**
 * 百度地图的缩放监听
 */
map.addEventListener("zoomend", function () {
    if (parseInt(map.getZoom()) == 13) {
        add_store_marker(store_data, 3);
    }
    if (parseInt(map.getZoom()) == 12) {
        add_store_marker_up(store_data, 2);
    }
    if (parseInt(map.getZoom()) == 8) {
        add_store_marker_up(store_data, 1);
    }
});

/**
 * 使用车辆：根据车辆id使用车辆
 * 跳转到使用车辆界面
 * @param store_site_id  所属门店id(取/还车门店：这里的取/还车门店默认为相同的)
 */
function useThisCar(store_site_id, goods_id) {
    localStorage.setItem("choose_store_id_take", store_site_id);
    localStorage.setItem("choose_store_id_return", store_site_id);
    location.href = "order_fillin_newenergy.html?goods_id=" + goods_id;
}

/**
 * 滑动监听事件
 */
$('.mui-slider').on('slide', function (event) {
    // var slide_index = event.detail.slideNumber;//获得当前显示项的索引
    var slide_index = mui("#js_marker_car_pop").slider().getSlideNumber();//获得当前显示项的索引
    var index = $("#js_marker_index_group").find("div");
    var car_items = $(".mui-slider-item");

    for (var i = 0; i < index.length; i++) {
        if (parseInt($(index[i]).attr("data-index")) == slide_index) {
            $(index[i]).addClass("car_index_item_checked");

            var car_lat = parseFloat($(car_items[i]).find(".js_marker_car_lat").text());
            var car_lng = parseFloat($(car_items[i]).find(".js_marker_car_lng").text());

            bdp = wgs2bd(parseFloat(car_lat), parseFloat(car_lng));
            drivingNavigation(bdp[1], bdp[0]);
        } else {
            $(index[i]).removeClass("car_index_item_checked");
        }
    }
});

/**
 * 七、八位车牌号认证
 * @param str  车牌字符串
 * @returns {*|boolean}  是否是合法车牌号
 */
function isLicenseNo(str) {
    //新能源车车牌号验证（8位）
    var xreg = /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}(([0-9]{5}[DF]$)|([DF][A-HJ-NP-Z0-9][0-9]{4}$))/;
    //常规车车车牌号验证（7位）
    var creg = /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-HJ-NP-Z0-9]{4}[A-HJ-NP-Z0-9挂学警港澳]{1}$/;
    var len = str.length;
    if (len === 7) {
        return creg.test(str);
    } else {
        return xreg.test(str);
    }
}

/**
 * 根据车牌号获取门店信息(车辆所属门店的信息)
 * @param license
 */
function getStoreByLicense(license) {
    $.ajax({
        url: "http://www.youchedongli.cn/api/car/get_qrcode_car.html",
        type: "GET",
        async: true,
        data: {
            licence_plate: license
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            // Toast("获取数据成功！",2000);
            if (parseInt(data.code) == 0) {
                $("#js_marker_car_pop").fadeOut(100);
                $("#js_car_pop").fadeIn(500);
                // $("#l-map").css("height",window_height - 195 + "px");
                var data = data.data;
                $("#js_car_pop").find(".js_marker_car_id").text(data.id);
                $("#js_car_pop").find(".js_marker_car_lat").text(data.location_latitude);
                $("#js_car_pop").find(".js_marker_car_lng").text(data.location_longitude);
                $("#js_car_pop").find(".js_go_navi").attr("data-lat",data.location_latitude);
                $("#js_car_pop").find(".js_go_navi").attr("data-lng",data.location_longitude);
                $("#js_car_pop").find(".js_marker_car_series_img").attr("src", "http://img.youchedongli.cn/public/" + data.series_img + "?x-oss-process=image/resize,w_275&rand=20180723");
                $("#js_car_pop").find(".js_marker_car_pop_renewal").text(data.renewal);
                $("#js_car_pop").find(".js_marker_car_plate").text(data.licence_plate);
                if (parseInt(data.series_id) === 4664) {
                    $("#js_car_pop").find(".js_marker_car_tag").html("<span style=\"font-size: 90%;color:#ff8730\">仅限大学城和金石产业园区域还车</span>");
                }
                $("#js_car_pop").find(".js_marker_car_name").text(data.cartype_name);
                $("#js_car_pop").find(".js_marker_car_driving_mileage").text(data.driving_mileage + "公里");
                $("#js_car_pop").find(".js_marker_car_price").text(parseFloat(data.day_price).toFixed(1));
                $("#js_car_pop").find(".js_marker_km_price").text(parseFloat(data.km_price).toFixed(1));
                $("#js_car_pop").find(".js_marker_car_store_id").text(data.store_site_id);

                $("#js_car_pop").find(".js_marker_car_close").on("click", function () {
                    // $("#l-map").css("height","100%");
                    $("#js_car_pop").fadeOut(500);
                    $("#js_user_map_fun").show();
                    // $("#js_user_map_fun").css("opacity",1);
                    if (polylineDriving != null) {
                        map.removeOverlay(polylineDriving);
                        map.removeOverlay(carMarker);
                    }
                });

                bars = $("#js_car_pop").find(".bar");

                for (var j = 0; j < (parseInt(data.energy) / 10); j++) {
                    $(bars[j]).css("background", colorList[j]);
                }

                bdp = wgs2bd(parseFloat(data.location_latitude), parseFloat(data.location_longitude));
                drivingNavigation(bdp[1], bdp[0]);

            } else {
                Toast(data.info, 2000);
                // $("#l-map").css("height","100%");
                $("#js_car_pop").fadeOut(500);
				$("#js_marker_car_pop").fadeOut(500);
            }
        }
    });
}

