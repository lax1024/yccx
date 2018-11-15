//推荐建站按钮
$("#js_build_pile").on("click", function () {
    location.href = "build_pile.html";
});

var DRIVING;//行车路线规划类
var POLYLINEDRIVING;//路线覆盖物
var IS_SHOW = true;//是否显示弹窗以及调用导航

var MAP = new BMap.Map("js_pile_map");          // 创建地图实例
var POINT = new BMap.Point(106.67624, 26.468231);  // 创建点坐标
MAP.centerAndZoom(POINT, 15);                 // 初始化地图，设置中心点坐标和地图级别

MAP.addControl(new BMap.ScaleControl());//比例尺
var OPTS = {offset: new BMap.Size(10, 270)};//偏移量
MAP.addControl(new BMap.NavigationControl(OPTS));//缩放控件

// get_user_location();
// get_charge_list();

//106.675546,26.471873
//根据用户的定位坐标来获取相应的位置信息
function get_user_location() {
    //请求数据参数
    var user_lng = localStorage.getItem("old_marker_self_lng");
    var user_lat = localStorage.getItem("old_marker_self_lat");
    // var user_lng = "106.675546";
    // var user_lat = "26.471873";
    var type = "POST";
    var url = WEBSITE + "/api/Area/get_city_lng_lat_new";
    var json_param = {"lng": user_lng, "lat": user_lat};

    // loadMessageBox("定位中，请稍等...", 10);
    //调用数据请求方法
    ajax_request_byjson(type, url, json_param, data_handle);

    //数据处理函数
    function data_handle(json_data) {
        // closeLoadMessageBox();
        var pois = json_data.data.pois;
        $("#js_user_location").text(pois[0].name);
    }
}

//根据用户的定位坐标来获取充电桩的相关数据
function get_charge_list() {
    //请求数据参数
    var user_lng = localStorage.getItem("old_marker_self_lng");
    var user_lat = localStorage.getItem("old_marker_self_lat");
    // var user_lng = "106.675546";
    // var user_lat = "26.471873";
    var type = "POST";
    var url = WEBSITE + "/api/Pile_store/get_store_list.html";
    var json_param = {"lng": user_lng, "lat": user_lat};

    loadMessageBox("充电桩数据加载中，请稍等...", 10);
    //调用数据请求方法
    ajax_request_byjson(type, url, json_param, data_handle);

    //数据处理函数
    function data_handle(json_data) {
        var data = json_data.data;
        closeLoadMessageBox();//关闭加载框
        var point_pile = new Array();//数组：用来存放各个Marker的坐标点
        var marker_pile = new Array();//数组：用来存放每一个Marker
        // var lable_pile = new Array();//数组：用来存放每一个Marker的标签
        var poimt_temp;//实现GPS坐标转为百度坐标的中间变量
        //将各个Marker添加到地图上
        for (var i = 0; i < data.length; i++) {
            //GPS坐标转为百度坐标
            poimt_temp = wgs2bd(parseFloat(data[i].store_lat), parseFloat(data[i].store_lng));
            //添加每一个坐标点
            point_pile[i] = new BMap.Point(poimt_temp[1], poimt_temp[0]);
            // point_pile[i] = new BMap.Point(parseFloat(data[i].store_lng), parseFloat(data[i].store_lat));
            // point_pile[i].lat = poimt_temp[0];
            // point_pile[i].lng = poimt_temp[1];
            //Marker图标使用的自定义图片
            var pic_marker = OSSIMG + "/pile_logo.png?rand=20666&x-oss-process=image/resize,h_300";
            //Marker自定义图标
            var icon_pile = new BMap.Icon(pic_marker, new BMap.Size(50, 20), {imageSize: new BMap.Size(50, 20)});
            //实例化每一个Marker
            marker_pile[i] = new BMap.Marker(point_pile[i], {icon: icon_pile});
            //将Marker添加到地图上
            MAP.addOverlay(marker_pile[i]);
            //调用Marker的点击事件函数，显示相关信息
            add_marker_click(data[i],marker_pile[i],point_pile[i]);

        }
        //将所有的充电桩Marker调整到可视范围内
        // MAP.setViewport(point_pile);
        //实现点聚合
        var markerClusterer = new BMapLib.MarkerClusterer(MAP, {markers: marker_pile});
    }
}
var lable_pile;
/**
 * Marker的点击事件函数
 * @param data  Marker点击出现的弹窗的数据
 * @param targetMarker  点击的目标Marker
 */
function add_marker_click(data,targetMarker,point_pile) {
    //闭包：Marker的点击事件函数
    var open = function () {
        if(lable_pile != null){
            MAP.removeOverlay(lable_pile);
        }

        //设置每一个Marker的Label图标
        lable_pile = new BMap.Label("<div class='marker_store_label'>距您 " + data.distance + " 公里</div>", {
            offset: new BMap.Size(-30, -30),
            position: point_pile
        });
        //Label的样式
        lable_pile.setStyle({
            border: "0",
            backgroundColor: ""
        });
        //将Label加到对应的Marker上面去
        targetMarker.setLabel(lable_pile);

        $("#js_pope_marker").show();//显示弹窗
        //加载弹窗的相关信息
        $("#js_pope_marker").attr("data-store-id",data.store_id);
        $("#js_pope_marker").attr("data-store-lat",data.store_lat);
        $("#js_pope_marker").attr("data-store-lng",data.store_lng);
        $("#js_pope_marker_name").text(data.store_name);
        $("#js_pope_marker_address").text(data.store_address);
        $("#js_num_direct").text(data.num_direct);
        $("#js_pile_power").text(data.power);
        $("#js_num_alternation").text(data.num_alternating);
        $("#js_store_price").text(data.pile_price);
        $("#js_store_parking_price").text(data.parting_price);
        $("#js_store_accept").text("适合车型："+data.car_accept);
        $("#js_store_unaccept").text("不适合车型："+data.car_unaccept);
        var bdp = wgs2bd(parseFloat($("#js_pope_marker").attr("data-store-lat")), parseFloat($("#js_pope_marker").attr("data-store-lng")));
        navi_go_pile(bdp[1], bdp[0]);
        var tags = data.store_tag;
        $(".tag-span-div").find("span").remove();
        for (var i = 0; i < tags.length; i++){
            $(".tag-span-div").append("<span style='padding: 1px 3px;border-radius: 2px;margin-right: 5px;'>" + tags[i] + "</span>");
        }
        var imgs = data.store_imgs;//获取门店图片(数组)
        if (imgs != "") {//判断是否有图片
            //先将之前的图片清掉，以免重复添加
            $("#js_pope_marker_img").find(".js_store_img").remove();
            //添加第一张图片(显示的那张)
            $("#js_pope_marker_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://www.youchedongli.cn" + imgs[0] + "\"'>");
            //循环添加其他图片，其他图片隐藏
            for (var i = 1; i < imgs.length; i++) {
                $("#js_pope_marker_img").append("<img class=\"js_store_img\" data-preview-src='' data-preview-group='store_img' src=\"http://www.youchedongli.cn" + imgs[i] + "\" style='display: none'>");
            }
        } else {//图片(数组)为空
            $("#js_pope_marker_img").find("img").attr("src", "http://img.youchedongli.cn/public/static/mobile/images/img_return_car_no.png");
        }
        //关闭按钮
        $("#js_pope_marker_close").on("click",function () {
            if(lable_pile != null){
                MAP.removeOverlay(lable_pile);
            }
            $("#js_pope_marker").hide();
            if (POLYLINEDRIVING != null) {
                MAP.removeOverlay(POLYLINEDRIVING);
            }
        });
    };
    //显示最近一个点的弹窗信息以及路线规划
    if (IS_SHOW) {//IS_SHOW，表示第一个点
        open();
        //将GPS坐标转化为百度地图坐标
        var bdp = wgs2bd(parseFloat($("#js_pope_marker").attr("data-store-lat")), parseFloat($("#js_pope_marker").attr("data-store-lng")));
        //调用路线规划方法
        navi_go_pile(bdp[1], bdp[0]);
        //修改显示状态
        IS_SHOW = false;
    }

    //调用Marker的点击函数(闭包)
    targetMarker.addEventListener("click", function () {
        open();
    });
}
//导航
$("#js_pope_marker_navi").on("click",function () {
    //导航参数
    var lat = parseFloat($("#js_pope_marker").attr("data-store-lat"));
    var lng = parseFloat($("#js_pope_marker").attr("data-store-lng"));
    var name = $("#js_pope_marker_name").text();
    var address = $("#js_pope_marker_address").text();
    //调起微信导航
    go_openLocation(lat, lng,name,address);
});
//物理返回键的点击事件监听
//主要用来监听图片预览显示大图
window.addEventListener("popstate", function (e) {
    if (document.querySelector(".mui-preview-in")) {
        mui.previewImage().close();
    } else {
        window.history.back();
    }
}, false);

//用户建议
$("#js_user_advise").on("click",function () {
    location.href = "pile_advise.html"
});
//定位图标(刷新定位)
$("#js_update_user_location").on("click",function () {
    weixinLocation(false);
});

/**
 * 行车导航路线规划
 * @param pile_lng   充电桩(目的地)经度
 * @param pile_lat   充电桩(目的地)纬度
 */
function navi_go_pile(pile_lng, pile_lat) {
    //如果路线存在，先将其清除
    if (POLYLINEDRIVING != null) {
        MAP.removeOverlay(POLYLINEDRIVING);
    }
    //行车路线规划类
    DRIVING = new BMap.DrivingRoute(MAP, {
        renderOptions: {autoViewport: true},
        policy: BMAP_DRIVING_POLICY_LEAST_DISTANCE
    });
    //终点类
    var end_point = new BMap.Point(pile_lng, pile_lat);
    //起点经纬度：用户通过微信进行定位得到的
    var star_lat = localStorage.getItem("marker_self_lat");
    var star_lng = localStorage.getItem("marker_self_lng");
	
    // marker_self_lat = "26.405257";
    // marker_self_lng = "106.632199";
    //起点类
    var star_point = new BMap.Point(parseFloat(star_lng), parseFloat(star_lat));
    //调用百度API进行路线的规划
    DRIVING.search(star_point, end_point);
    //路线规划的回调
	DRIVING.setSearchCompleteCallback(function () {
        //得到第一条路线一系列的点
        var pts = DRIVING.getResults().getPlan(0).getRoute(0).getPath();    //通过步行实例，获得一系列点的数组
        //设置路线样式
        POLYLINEDRIVING = new BMap.Polyline(pts, {strokeColor: "#004986", strokeWeight: 3, strokeOpacity: 1});
        //将路线添加到地图上
        MAP.addOverlay(POLYLINEDRIVING);
        //调整到最佳视野
        MAP.setViewport(pts);
    });
}