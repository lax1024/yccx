//创建变量
var pointList = new Array();
var markerList = new Array();
var map;
var old_marker_self_lat = localStorage.getItem("old_marker_self_lat");
var old_marker_self_lng = localStorage.getItem("old_marker_self_lng");
var marker_self_lat = localStorage.getItem("marker_self_lat");
var marker_self_lng = localStorage.getItem("marker_self_lng");

var goods_id = getValueByParameter(location.href,"goods_id");
var order_id = getValueByParameter(location.href,"order_id");

var polylineDriving;
var driving;
//是否显示弹窗，首次加载时需要显示
var is_show = true;
//百度坐标
var bdp = new Array();

$(".topBack").on("click", function () {
    location.href = "order_details_newenergy.html?order_id=" + order_id;
});

//创建地图实例
map = new BMap.Map("l-map");
//创建点坐标,初始化地图，设置中心点坐标和地图级别
map.centerAndZoom(new BMap.Point(marker_self_lng, marker_self_lat), 15);
map.addControl(new BMap.NavigationControl());//级别缩放(右下角)
map.addControl(new BMap.ScaleControl());//比例尺(左下角)
map.addControl(new BMap.OverviewMapControl());

getStoreByCar(goods_id,old_marker_self_lng,old_marker_self_lat);
// getStoreByCar("231","106.632648","26.40847866");
//过去可还车门店
function getStoreByCar(goods_id,lng_me, lat_me) {
    $.ajax({
        url: website + "/api/car/get_elesite_list.html",
        type: "GET",
        async: true,
        data: {
            car_id:goods_id,
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
//添加门店的Marker
function addStoreMarker(data) {
    for (var i = 0; i < data.length; i ++){

        // pointList[i] = new BMap.Point(parseFloat(data[i].location_longitude),parseFloat(data[i].location_latitude));
        //
        // pointList[i].lat = data[i].location_latitude;
        // pointList[i].lng = data[i].location_longitude;

        bdp = wgs2bd(parseFloat(data[i].location_latitude), parseFloat(data[i].location_longitude));
        pointList[i] = new BMap.Point(parseFloat(bdp[1]),parseFloat(bdp[0]));
        pointList[i].lat = bdp[0];
        pointList[i].lng = bdp[1];
        var myIcon;
        myIcon = new BMap.Icon("../../.././public/static/mobile/images/marker_car_parking.png?rand=2019", new BMap.Size(36, 36));

        markerList[i] = new BMap.Marker(pointList[i], {icon: myIcon});
        map.addOverlay(markerList[i]);

        addInfoWindow(markerList[i], data[i]);
    }
}

// 添加信息窗口
function addInfoWindow(targetMarker, poi) {
    var open = function () {
        $("#l-map").css("height","64%");
        $("#js_marker_store_pop").fadeIn(200);

        $("#js_marker_store_name").text(poi.store_name);
        $("#js_marker_store_range").text(poi.store_scope + "米");
        $("#js_marker_store_explain").text(poi.store_intro);
        if(parseInt(poi.store_charging_is) == 1){
            $("#js_marker_store_pile_site").text("有（用户将充电枪插上后，方可进行还车）");
        }else {
            $("#js_marker_store_pile_site").text("无（用户将车停到所选门店的可还车范围内，方可进行还车）");
        }

        $("#js_marker_store_distance").text(poi.distance + "公里");
        $("#js_marker_store_address").text(poi.address);
        $("#js_marker_store_id").text(poi.id);
        $("#js_marker_store_lng").text(poi.location_longitude);
        $("#js_marker_store_lat").text(poi.location_latitude);

        bdp = wgs2bd(parseFloat(poi.location_latitude), parseFloat(poi.location_longitude));
        drivingNavigation(parseFloat(bdp[1]),parseFloat(bdp[0]));
        // drivingNavigation(parseFloat(poi.location_longitude), parseFloat(poi.location_latitude));
    };

    // if(is_show){
    //     open();
    //     is_show = false;
    // }

    targetMarker.addEventListener("click", function () {
        open();
    });
}
//关闭弹窗
$("#js_marker_store_close").on("click",function () {
    $("#l-map").css("height","100%");
    $("#js_marker_store_pop").fadeOut(200);
});
//选择门店
$("#js_choose_this_store").on("click",function () {

    var store_id = $("#js_marker_store_id").text();

    $.ajax({
        url: website + "/api/Order/return_store_site.html",
        type: "POST",
        async: true,
        data: {
            order_id: order_id,
            return_store_id: store_id
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {

        },
        success: function (data) {

            if (data.code == 0) {
                Toast(data.info, 2000);
                location.href = "order_details_newenergy.html?order_id=" + order_id;
            } else {
                Toast(data.info, 2000);
            }
        },
        error: function (xhr, textStatus) {

        },
        complete: function () {

        }
    });
});

//导航去还车
$("#js_go_navi").on("click", function () {
    go_openLocation(parseFloat($("#js_marker_store_lat").text()), parseFloat($("#js_marker_store_lng").text()));
});

//步行导航
function drivingNavigation(store_lng,store_lat) {

    if(polylineDriving != null){
        map.removeOverlay(polylineDriving);
    }

    driving = new BMap.DrivingRoute(map,
        {renderOptions: {autoViewport: true}, policy: BMAP_DRIVING_POLICY_LEAST_DISTANCE});    //创建驾车实例
    var star_point = new BMap.Point(parseFloat(marker_self_lng), parseFloat(marker_self_lat));
    var end_point = new BMap.Point(store_lng,store_lat);

    driving.search(star_point, end_point);
    driving.setSearchCompleteCallback(function () {
        var pts = driving.getResults().getPlan(0).getRoute(0).getPath();    //通过步行实例，获得一系列点的数组
        polylineDriving = new BMap.Polyline(pts);
        map.addOverlay(polylineDriving);

        // pts.push(star_point);
        // pts.push(end_point);
        map.setViewport(pts);          //调整到最佳视野
    });
}
