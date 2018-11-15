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

var opts = {offset: new BMap.Size(10, 150)}
map.addControl(new BMap.NavigationControl(opts));

// map.addControl(new BMap.NavigationControl({anchor: BMAP_ANCHOR_TOP_RIGHT}));//级别缩放(右下角)
map.addControl(new BMap.ScaleControl());//比例尺(左下角)
map.addControl(new BMap.OverviewMapControl());


//刷新按钮的点击事件
$("#user_location_self").on("click", function () {
    weixinLocation();
});

var pt;

map.addEventListener("moveend",function(){
    //获取地图中心的坐标
    pt = map.getBounds().getCenter();
    // var point = new BMap.Point(pt.lng,pt.lat);
    // var m = new BMap.Marker(point);
    // map.addOverlay(m);

    $.ajax({
        url: website + "/api/Area/get_city_lng_lat_new",
        type: "POST",
        async: true,
        data: {
            lat:pt.lat,
            lng:pt.lng
        },
        outTime: 5000,
        dataType: "json",
        success: function (data) {
            if (parseInt(data.code) == 0) {
                var pois = data.data.pois;
                var addr = pois[0].addr;
                $("#js_place_take_car").text(addr.substring(addr.indexOf("市") + 1,addr.length) + pois[0].name);
            }
        }
    });
});
$("#js_place_take_car").on("click",function () {

    mui.prompt("输入详细地址作为您的取车点","输入取车的详细地址","提示",["确认","取消"],function (e) {
        if(e.index == 0){
            $("#js_place_take_car").text(e.value);
        }
    },"div");

});
//“选择该点”的点击事件
$("#js_choose_this_point").on("click",function () {
        Toast("选择成功",2000);
        setTimeout(function (){
            pt = map.getBounds().getCenter();
            var point_take = {"lat":pt.lat,"lng":pt.lng,"name":$("#js_place_take_car").text()};
            localStorage.setItem("point_take",JSON.stringify(point_take));
            // location.href = "index_tradition.html";
            // location.href = getReferrUrl();
            location.href = document.referrer;
        },2000);
});

//搜索框
$("#js_place_search").bind("input propertychange", function () {
    $("#js_place_search_result").show();
    var cityKeyword = $('#js_place_search').val();

    if (cityKeyword == "") {
        $("#scrollList").css({"display": "block"});
    }
    $.ajax({
        url: website + "/api/area/get_city_search_area.html",
        type: "POST",
        async: true,
        data: {
            // city: "贵阳",
            city: city,
            keyword: cityKeyword
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {
        },
        success: function (data) {
            $("#js_place_search_result").empty();

            $.each(data.data, function (key, value) {
                var loction = value.location;
                var searchItem = $("<div " +
                    "class=\"sub-list\" " +
                    "data-lat=\"" + loction.lat + "\"" +
                    "data-lng=\"" + loction.lng + "\">" + value.name + "</div>");

                searchItem.on("click", function () {
                    var name = $(this).text();
                    var lat = $(this).attr("data-lat");
                    var lng = $(this).attr("data-lng");
                    map.setCenter(new BMap.Point(parseFloat(lng),parseFloat(lat)));
                    $("#js_place_search_result").empty();
                    $("#js_place_search_result").hide();
                    $("#js_place_take_car").text(name);
                    $("#js_place_search").val(name);
                });

                $("#js_place_search_result").append(searchItem);
            });
        },
        error: function (xhr, textStatus) {
        },
        complete: function () {
        }
    });
});