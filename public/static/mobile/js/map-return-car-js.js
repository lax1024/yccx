$("#goback").on("click",function () {
    window.history.back();
});

var order_sn = getValueByParameter(location.href, "order_sn");
var order_id = getValueByParameter(location.href, "order_id");
var goods_id = getValueByParameter(location.href, "goods_id");

//用户自身的经纬度
var marker_self_lat = localStorage.getItem("marker_self_lat");
var marker_self_lng = localStorage.getItem("marker_self_lng");

//创建地图实例
var map = new BMap.Map("l-map");
//创建点坐标,初始化地图，设置中心点坐标和地图级别
map.centerAndZoom(new BMap.Point("106.632648","26.40847866"), 15);

// var pointx = new BMap.Point(marker_self_lng, marker_self_lat);
// map.centerAndZoom(pointx, 16);

map.addControl(new BMap.NavigationControl());//级别缩放(右下角)
map.addControl(new BMap.ScaleControl());//比例尺(左下角)
map.addControl(new BMap.OverviewMapControl());


// getStoreByCar("231","106.632648","26.40847866");

//获取可还车门店
function getStoreByCar(goods_id, lng_me, lat_me) {
    $.ajax({
        url: website + "/api/car/get_elesite_list.html",
        type: "GET",
        async: true,
        data: {
            car_id: goods_id,
            lng: lng_me,//用户的经度
            lat: lat_me//用户的纬度
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {
        },
        success: function (data) {
            if (parseInt(data.code) == 0) {
                addStoreMarker(data.data);
            }
        },
        error: function (xhr, textStatus) {
        },
        complete: function () {
        }
    });
}
//多边形覆盖物边框样式
var styleOptions = {
    strokeColor:"green",    //边线颜色。
    fillColor:"green",      //填充颜色。当参数为空时，圆形将没有填充效果。
    strokeWeight: 3,       //边线的宽度，以像素为单位。
    strokeOpacity: 0.5,       //边线透明度，取值范围0 - 1。
    fillOpacity: 0.2,      //填充的透明度，取值范围0 - 1。
    strokeStyle: 'solid' //边线的样式，solid或dashed。
};
//多边形覆盖物
var polygon;
//显示多边形覆盖物
function  showPolygon(path){
    if(polygon != null){
        map.removeOverlay(polygon);
    }
    if(path != ""){
        path = path.replace(/'/g,"\"");
        var path_arr = JSON.parse(path);
        polygon = new BMap.Polygon(path_arr, styleOptions);  //创建多边形
        map.addOverlay(polygon);   //增加多边形
    }
}


var bdp;//坐标转换中间变量
var pointList = new Array();//门店的经纬度坐标列表
var markerList = new Array();//门店的Marker列表
var markerLable;//显示距离
var is_show = true;//首次进来显示最近的一个还车点

//添加门店的Marker
function addStoreMarker(data) {
    for (var i = 0; i < data.length; i++) {

        bdp = wgs2bd(parseFloat(data[i].location_latitude), parseFloat(data[i].location_longitude));
        pointList[i] = new BMap.Point(parseFloat(bdp[1]), parseFloat(bdp[0]));
        pointList[i].lat = bdp[0];
        pointList[i].lng = bdp[1];


        var myIcon;
        myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/point_return_car_max.png?rand=20666&x-oss-process=image/resize,h_300", new BMap.Size(30,40),{imageSize: new BMap.Size(30,40)});
        markerList[i] = new BMap.Marker(pointList[i], {icon: myIcon});
        map.addOverlay(markerList[i]);

        addInfoWindow(markerList[i], data[i],pointList[i]);
    }
}

// 添加信息窗口
function addInfoWindow(targetMarker, poi,point) {
    var open = function () {
        if(markerLable != null){
            map.removeOverlay(markerLable);
        }
        markerLable = new BMap.Label("<div class='marker_store_label'>距您" + poi.distance + "公里</div>", {
            offset: new BMap.Size(-28, -30),
            position: point
        });

        markerLable.setStyle({
            border:"0",
            backgroundColor:""
        });

        targetMarker.setLabel(markerLable);

        $("#js_pop_return_car").show();
        $("#js_pop_return_car").attr("data-store-id", poi.id);
        $("#js_pop_return_car").attr("data-store-lng", poi.location_longitude);
        $("#js_pop_return_car").attr("data-store-lat", poi.location_latitude);
        $("#js_pop_return_car").find(".js_return_car_name").text("门店名：" + poi.store_name);
        $("#js_pop_return_car").find(".js_return_car_address").text("门店地址：" + poi.address);
        showPolygon(poi.store_rail_point);
        var imgs = poi.store_imgs;
        if (imgs.length != 0) {
            $("#js_pop_return_car").find(".js_store_img").remove();
            $("#js_pop_return_car").find("img").attr("src", "http://img.youchedongli.cn" + imgs[0]);
            $("#js_pop_return_car").attr("data-preview-src", "http://img.youchedongli.cn" + imgs[0]);
            $("#js_pop_return_car").attr("data-preview-group", "store_img");
            for (var i = 1; i < imgs.length; i++) {
                $("#js_pop_return_car").find(".return_car_place_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://img.youchedongli.cn" + imgs[i] + "\" style='display: none'>");
            }

        } else {
            $("#js_pop_return_car").find("img").attr("src", "http://img.youchedongli.cn/public/static/mobile/images/img_return_car_no.png");
        }

        // bdp = wgs2bd(parseFloat(poi.location_latitude), parseFloat(poi.location_longitude));

        // drivingNavigation(bdp[1], bdp[0]);
    };

    if (is_show) {
        open();
        is_show = false;

        // var location_latitude = $("#js_pop_return_car").attr("data-store-lat");
        // var location_longitude = $("#js_pop_return_car").attr("data-store-lng");
        // bdp = wgs2bd(parseFloat(location_latitude), parseFloat(location_longitude));
        // drivingNavigation(bdp[1], bdp[0]);
    }

    targetMarker.addEventListener("click", function () {
        open();
    });
}

//立即还车按钮的点击事件
$("#js_user_map_fun").on("click", function () {
    $.ajax({
        url: website + "/api/Order/quick_elesite.html",
        type: "GET",
        async: true,
        data: {
            order_id: order_id,
            car_id: goods_id
        },
        timeOut: 5000,
        dataType: "json",
        beforeSend: function () {

        },
        success: function (data) {
            var code = data.code;
            if (code == 0) {
                Toast("可以还车，即将进行还车拍照", 2000);
                setTimeout(function () {
                    location.href = "return_car_pic.html?order_id=" + order_id + "&sn=" + order_sn;
                }, 2000);
            } else {
                Toast("" + data.info, 2000);
            }
        },
        error: function () {
            // Toast("请求失败",2000);
        },
        complete: function () {

        }
    });
});

var polylineDriving;//驾车路线实例
var driving;//驾车路线规划
// var star_point = new BMap.Point(parseFloat(marker_self_lng), parseFloat(marker_self_lat));//起点
var star_point = "";//起点
// var star_point = new BMap.Point(106.632648,26.40847866);//起点

//驾驶路线导航
function drivingNavigation(store_lng, store_lat) {

    if (polylineDriving != null) {
        map.removeOverlay(polylineDriving);
    }

    driving = new BMap.DrivingRoute(map,
        {renderOptions: {autoViewport: true}, policy: BMAP_DRIVING_POLICY_LEAST_DISTANCE});    //创建驾车实例
    var end_point = new BMap.Point(store_lng, store_lat);
    driving.search(star_point, end_point);//驾车路线搜索
    driving.setSearchCompleteCallback(function () {
        var pts = driving.getResults().getPlan(0).getRoute(0).getPath();    //通过驾车实例，获得一系列点的数组

        polylineDriving = new BMap.Polyline(pts, {strokeColor: "#004986", strokeWeight: 3, strokeOpacity: 1});
        map.addOverlay(polylineDriving);

        map.setViewport(pts);          //调整到最佳视野
    });
}
