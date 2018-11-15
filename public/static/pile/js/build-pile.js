//返回键的点击监听
$("#js_goback").on("click",function () {
    window.history.back();
});

//定位图标的点击监听
$("#js_location_self").on("click",function () {
    weixinLocation();
});

//获取屏幕高度，用来设置提交按钮的位置(底部)
var TOTAL_HEIGHT = $(window).height();
$("#js_comment_submit").css("top",TOTAL_HEIGHT - 45);
//创建并设置地图
var MAP = new BMap.Map("js_map_build_pile");          // 创建地图实例
var POINT = new BMap.Point(106.67624, 26.468231);  // 创建点坐标
MAP.centerAndZoom(POINT, 15);                 // 初始化地图，设置中心点坐标和地图级别

MAP.addControl(new BMap.ScaleControl());//比例尺
MAP.addControl(new BMap.NavigationControl());//缩放控件

/**地图的移动(结束)事件监听函数
 * 1、获取地图中心坐标
 * 2、根据与坐标获取位置信息
 * 3、将第一个位置加到搜索框
 * 4、将位置加到搜索框下边列表中以供选择
 */
MAP.addEventListener("moveend",function () {
   var point_center = MAP.getCenter();//获取地图中心坐标
   var json_param = {"lat":point_center.lat,"lng":point_center.lng};//设置请求参数
    //通过请求获取位置信息
   /** type, url, json_param, fun_handle */
   ajax_request_byjson("POST",WEBSITE + "/api/Area/get_city_lng_lat_new.html",json_param,data_handle);

    /**
     * 数据请求函数
     * @param data  位置信息数据
     */
   function data_handle(data) {
       var data = data.data;//拿到目标数据
       $("#js_place_search_result").show();//显示结果框
       $("#js_place_search_result").empty();//清空结果框
        //将第一个位置信息加到搜索框中
       $("#js_point_name_input").val(data.pois[0].name);
       $("#js_point_name_input").attr("data-lat",data.pois[0].point.y);
       $("#js_point_name_input").attr("data-lng",data.pois[0].point.x);
       //遍历位置信息数据，将各条数据加到结果框
       $.each(data.pois, function (key, value) {
           var loction = value.point;//各条数据
           //结果条
           var searchItem = $("<div style='border-bottom: #999999 solid 1px;padding: 5px 10px' " +
               "class=\"sub-list\" " +
               "data-lat=\"" + loction.y + "\"" +
               "data-lng=\"" + loction.x + "\">" + value.name + "</div>");
           //结果条点击(选择)事件
           searchItem.on("click", function () {
               //通过数据条获取所需信息(名称及经纬度)
               var name = $(this).text();
               var lat = $(this).attr("data-lat");
               var lng = $(this).attr("data-lng");
               //将所选的位置作为地图中心
               MAP.setCenter(new BMap.Point(parseFloat(lng),parseFloat(lat)));
               //将所选的位置信息设置到搜索框中
               $("#js_point_name_input").val(name);
               $("#js_point_name_input").attr("data-lat",lat);
               $("#js_point_name_input").attr("data-lng",lng);
               //延时，隐藏结果框
               setTimeout(function (args) {
                   $("#js_place_search_result").empty();
                   $("#js_place_search_result").hide();
               },500);
           });
           //将数据条添加到结果框
           $("#js_place_search_result").append(searchItem);
       });
   }
});
/**
 * 搜索框的输入监听函数
 * 1、监听输入数据
 * 2、根据输入的数据来查询位置信息
 * 3、将第一条位置信息加载到搜索框
 * 4、将位置信息添加到结果框中以供选择
 */
$("#js_point_name_input").bind("input propertychange", function () {
    //获取输入的数据
    var cityKeyword = $('#js_point_name_input').val();
    //设置请求参数
    var json_param = {"city":CITY,"keyword":cityKeyword};
    //调用请求函数
    /** type, url, json_param, fun_handle */
    ajax_request_byjson("POST",WEBSITE + "/api/area/get_city_search_area.html",json_param,data_handle);

    /**
     * 数据请求方法
     * @param data   通过请求得到的位置信息数据
     */
    function data_handle(data) {
        //显示结果框
        $("#js_place_search_result").show();
        //清空结果框
        $("#js_place_search_result").empty();
        //遍历位置信息数据
        $.each(data.data, function (key, value) {
            //获取位置信息单挑数据
            var loction = value.location;
            //设置结果框的选择
            var searchItem = $("<div style='border-bottom: #999999 solid 1px;padding: 5px 10px'" +
                "class=\"sub-list\" " +
                "data-lat=\"" + loction.lat + "\"" +
                "data-lng=\"" + loction.lng + "\">" + value.name + "</div>");
            //结果框的各个选项的点击事件
            searchItem.on("click", function () {
                //通过选项获取所选的相关数据(位置名称及经纬度)
                var name = $(this).text();
                var lat = $(this).attr("data-lat");
                var lng = $(this).attr("data-lng");
                //将所选的位置加载到(设为)地图中心
                MAP.setCenter(new BMap.Point(parseFloat(lng),parseFloat(lat)));
                //将所选的位置信息设置到搜索框
                $("#js_point_name_input").val(name);
                $("#js_point_name_input").attr("data-lat",lat);
                $("#js_point_name_input").attr("data-lng",lng);
                //延时、隐藏结果框
                setTimeout(function (args) {
                    $("#js_place_search_result").empty();
                    $("#js_place_search_result").hide();
                },500);
            });
            //将结果数据选项加载到结果框中
            $("#js_place_search_result").append(searchItem);
        });
    }
});
//“推荐建点”的点击事件监听
$("#js_comment_submit").on("click",function () {
    //获取推荐的点的位置信息(位置名称及经纬度)
    var point_name = $("#js_point_name_input").val();
    var point_lat = MAP.getCenter().lat;
    var point_lng = MAP.getCenter().lng;

    //获取推荐点的介绍和建议
    var point_advise = $("#js_point_advise").val();

    // loadMessageBox("正在提交推荐位置，请稍等...",10);
    /** type, url, json_param, fun_handle */
    // json_data_car(type, url, json_param, fun_handle);

    var json_param = {"latitude":point_lat,"longitude":point_lng,"type":1,"remark":point_advise,"address":point_name};//设置请求参数

    ajax_request_byjson("POST",WEBSITE + "/api/Customer/add_customer_sign.html",json_param,data_handle);

    function data_handle(data) {
        if (parseInt(data.code) == 0) {
            Toast("感谢您的推荐",2000);
            setTimeout(function () {
                window.history.back();
            },2000);
        }else {
            Toast(data.info,2000);
        }
    }

});
