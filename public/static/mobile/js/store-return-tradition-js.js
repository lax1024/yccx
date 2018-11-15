$("#js_go_back").on("click",function () {
    window.history.back();
});
//自己的经纬度(GPS坐标)
var old_marker_self_lat = localStorage.getItem("old_marker_self_lat");
var old_marker_self_lng = localStorage.getItem("old_marker_self_lng");
//自己的经纬度(百度坐标)
var marker_self_lat = localStorage.getItem("marker_self_lat");
var marker_self_lng = localStorage.getItem("marker_self_lng");

var map = new BMap.Map("store_map");
//创建点坐标,初始化地图，设置中心点坐标和地图级别
// var pointc = new BMap.Point(parseFloat(marker_self_lng),parseFloat(marker_self_lat));
// map.centerAndZoom(new BMap.Point(pointc, 15));
map.centerAndZoom(new BMap.Point("106.632648", "26.40847866"), 15);

map.addControl(new BMap.NavigationControl());//级别缩放(右下角)
map.addControl(new BMap.ScaleControl());//比例尺(左下角)
map.addControl(new BMap.OverviewMapControl());

function get_store_list_ajax() {
    loadMessageBox("正在查询取车门店数据", 10);
    $.ajax({
        url: website + '/api/Car/get_carsite_list',//请求地址
        type: 'POST', //请求方式
        async: true,    //或false,是否异步
        data: {//请求参数
            lat: old_marker_self_lat,
            lng: old_marker_self_lng
            // lat: "26.40847866",
            // lng: "106.632648"
        },
        timeout: 5000,    //超时时间
        dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success: function (data) {//请求成功，做数据处理
            if (parseInt(data.code) == 0) {
                closeLoadMessageBox();
                addStoreMarker(data.data);
            } else {
                Toast(data.info, 2000);
            }
        }
    });
}
var bdp;//坐标转换中间变量
var pointList = new Array();//门店的经纬度坐标列表
var markerList = new Array();//门店的Marker列表
var markerLable = new Array();//显示距离
var is_show = true;//首次进来显示最近的一个还车点

//添加门店的Marker
function addStoreMarker(data) {
    for (var i = 0; i < data.length; i++) {

        bdp = wgs2bd(parseFloat(data[i].location_latitude), parseFloat(data[i].location_longitude));
        pointList[i] = new BMap.Point(parseFloat(bdp[1]), parseFloat(bdp[0]));
        pointList[i].lat = bdp[0];
        pointList[i].lng = bdp[1];

        markerLable[i] = new BMap.Label("<div class='marker_store_label'>距您 " + data[i].distance + " 公里</div>", {
            offset: new BMap.Size(-50, -41),
            position: pointList[i]
        });

        var myIcon;
        myIcon = new BMap.Icon("http://img.youchedongli.cn/public/static/mobile/images/point_return_car_max.png?rand=20666&x-oss-process=image/resize,h_300", new BMap.Size(30, 40), {imageSize: new BMap.Size(30, 40)});
        markerList[i] = new BMap.Marker(pointList[i], {icon: myIcon});
        map.addOverlay(markerList[i]);
        markerLable[i].setStyle({
            border: "0",
            backgroundColor: ""
        });

        markerList[i].setLabel(markerLable[i]);
        addInfoWindow(markerList[i], data[i]);

    }
}


// 添加信息窗口
function addInfoWindow(targetMarker, poi) {
    var open = function () {
        $("#js_pop_store_take").show();
        $("#js_pop_store_take").attr("data-store-id", poi.id);
        $("#js_pop_store_take").attr("data-store-lng", poi.location_longitude);
        $("#js_pop_store_take").attr("data-store-lat", poi.location_latitude);
        $("#js_pop_store_take").find(".js_store_name").text(poi.store_name);
        $("#js_pop_store_take").find(".js_store_address").text(poi.address);
        $("#js_pop_store_take").find(".js_store_tel").html("<a id=\"js_call_store_tel\" href=\"tel:" + poi.store_tel + "\">" + poi.store_tel + "</a>");
        // $("#js_pop_store_take").find("#js_call_store_tel").attr("href", "tel:" + poi.store_tel);
        $("#js_pop_store_take").find(".js_store_close").on("click", function () {
            $("#js_pop_store_take").fadeOut(300);
        });
        var imgs = poi.store_imgs;
        if (imgs.length != 0) {
            $("#js_pop_store_take").find(".js_store_img").remove();
            // $("#js_pop_store_take").find("img").attr("src", "http://img.youchedongli.cn" + imgs[0]);
            // $("#js_pop_store_take").attr("data-preview-src", "http://img.youchedongli.cn" + imgs[0]);
            // $("#js_pop_store_take").attr("data-preview-group", "store_img");
            $("#js_pop_store_take").find(".return_car_place_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://img.youchedongli.cn" + imgs[0] + "\">");
            for (var i = 1; i < imgs.length; i++) {
                $("#js_pop_store_take").find(".return_car_place_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://img.youchedongli.cn" + imgs[i] + "\" style='display: none'>");
            }
        } else {
            $("#js_pop_store_take").find(".return_car_place_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://img.youchedongli.cn/public/static/mobile/images/img_return_car_no.png\">");
            // $("#js_pop_store_take").find("img").attr("src", "http://img.youchedongli.cn/public/static/mobile/images/img_return_car_no.png");
        }
    };

    if (is_show) {
        open();
        is_show = false;
    }

    targetMarker.addEventListener("click", function () {
        open();
    });
}

//按钮“选择该门店”的点击事件
$("#js_choose_this_store").on("click", function () {
    var store_id = $("#js_pop_store_take").attr("data-store-id");
    var store_name = $("#js_pop_store_take").find(".js_store_name").text();
    // alert(store_id);
    localStorage.setItem("id_store_return_tradition",store_id+"");
    localStorage.setItem("name_store_return_tradition",store_name+"");

    location.href = "index_tradition.html";
});

//按钮“选择该门店”的点击事件
$("#user_location_self").on("click", function () {
    weixinLocation();
});

