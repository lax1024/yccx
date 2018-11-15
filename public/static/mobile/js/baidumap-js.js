
//创建变量
var pointList = new Array();
var markerList = new Array();
var infoWindow = new Array();
var map;
var walking;
var polylineWalking;
var driving;
var polylineDriving;
var star_point = new BMap.Point(106.676701,26.47075);
var end_point;

// myGetLocation();
function myGetLocation() {
    //定位并获取坐标
    var geolocation = new BMap.Geolocation();
    geolocation.getCurrentPosition(function(r){
        if(this.getStatus() == BMAP_STATUS_SUCCESS){
            alert("允许获取您的位置");
            // var mk = new BMap.Marker(r.point);
            // map.addOverlay(mk);
            // map.panTo(r.point);
            //p1 获取的定位坐标
            star_point = new BMap.Point(r.point.lng,r.point.lat);
            alert(r.point.lat);
        }
        else {
            alert('failed'+this.getStatus());
        }
    });
}

var listPoint = [
    {"lng":106.676629,"lat":26.416663,"name":"花溪区政府","day_price":"156.00元/天","car_type":"燃油车","renewal":"80%(120Km)","license_num":"贵A133CX"},
    {"lng":106.676701,"lat":26.47075,"name":"贵州名族大学","day_price":"156.00元/天","car_type":"电动车","renewal":"80%(120Km)","license_num":"贵A133CX"},
    {"lng":106.633798,"lat":26.406956,"name":"贵安新区数字经济产业园","day_price":"236.00元/天","car_type":"电动车","renewal":"80%(120Km)","license_num":"贵A133CX"},
    {"lng":106.692789,"lat":26.571237,"name":"花果园湿地公园","day_price":"180.00元/天","car_type":"燃油车","renewal":"80%(120Km)","license_num":"贵A133CX"},
    {"lng":106.588011,"lat":26.609585,"name":"贵阳西南国际商贸城","day_price":"196.00元/天","car_type":"电动车","renewal":"80%(120Km)","license_num":"贵A133CX"}
];

$(document).ready(function () {

    $(".topBack").on("click",function () {
        location.href = "index.html";
    });

    //创建地图实例
    map = new BMap.Map("l-map");
    //创建点坐标,初始化地图，设置中心点坐标和地图级别
    map.centerAndZoom(new BMap.Point(106.676701,26.47075), 12);
    map.addControl(new BMap.NavigationControl());//级别缩放(右下角)
    map.addControl(new BMap.ScaleControl());//比例尺(左下角)
    map.addControl(new BMap.OverviewMapControl());
    map.addControl(new BMap.MapTypeControl());//地图样式切换(右上角)
    addMarkerList();
    function addMarkerList(){
        //循环向地图中添加标注(Marker)
        for (var i = 0; i < listPoint.length;i ++){
            pointList[i] = new BMap.Point(listPoint[i].lng,listPoint[i].lat);
            pointList[i].lat = listPoint[i].lat;
            pointList[i].lng = listPoint[i].lng;
            pointList[i].name = listPoint[i].name;
            pointList[i].car_type = listPoint[i].car_type;
            pointList[i].renewal = listPoint[i].renewal;
            pointList[i].license_num = listPoint[i].license_num;
            pointList[i].day_price = listPoint[i].day_price;
            var myIcon;
            if(pointList[i].car_type == "燃油车"){
                myIcon = new BMap.Icon("../../.././public/static/mobile/images/marker_tradition_car.png", new BMap.Size(25,36));
            }else {
                myIcon = new BMap.Icon("../../.././public/static/mobile/images/marker_new_energy.png", new BMap.Size(25,36));
            }

            markerList[i] = new BMap.Marker(pointList[i],{icon:myIcon});
            map.addOverlay(markerList[i]);
            addInfoWindow(markerList[i],pointList[i],infoWindow[i]);
        }
    }

    function protectLicense(license) {
        var license_star = license.substr(0,3);
        var license_end = license.substr(5,2);

        return license_star+"**"+license_end;
    }


    // 添加信息窗口
    function addInfoWindow(targetMarker, poi,infoWindow) {
        //pop弹窗标题
        var title = '<p style="margin-top: 5px;font-weight:bold;color:#CE5521;font-size:13px">车辆信息</p>';
        //pop弹窗信息
        var html = [];
        html.push('<table cellspacing="1" style="margin-left: 20px;table-layout:fixed;width:80%;font:12px arial,simsun,sans-serif"><tbody>');

        html.push('<tr>');
        html.push('<td style="vertical-align:top;line-height:16px;width:38px;white-space:nowrap;word-break:keep-all">车型:</td>');
        html.push('<td style="vertical-align:top;line-height:16px">' + poi.car_type + '</td>');
        html.push('</tr>');

        html.push('<tr>');
        html.push('<td style="vertical-align:top;line-height:16px;width:38px;white-space:nowrap;word-break:keep-all">车牌号:</td>');
        html.push('<td style="vertical-align:top;line-height:16px">' + protectLicense(poi.license_num) + '</td>');
        html.push('</tr>');

        html.push('<tr>');
        html.push('<td style="vertical-align:top;line-height:16px;width:38px;white-space:nowrap;word-break:keep-all">单价:</td>');
        html.push('<td style="vertical-align:top;line-height:16px;color: #ff0000">' + poi.day_price + '</td>');
        html.push('</tr>');

        html.push('<tr>');
        html.push('<td style="vertical-align:top;line-height:16px;width:38px;white-space:nowrap;word-break:keep-all">续航:</td>');
        html.push('<td style="vertical-align:top;line-height:16px;color: #00B83F">' + poi.renewal + '</td>');
        html.push('</tr>');

        html.push('<tr>');
        html.push('<td style="vertical-align:top;line-height:16px;width:38px;white-space:nowrap;word-break:keep-all">地址:</td>');
        html.push('<td style="vertical-align:top;line-height:16px">' + poi.name + '</td>');
        html.push('</tr>');

        html.push('</tbody></table>');
        html.push('<div align="right" style="margin-top: 10px;margin-bottom: -20px">');
        if(poi.car_type == "燃油车"){
            html.push('<a href="http://www.youchedongli.cn/mobile/index/carsearch_tradition.html"><input type="button" value="去预订"/></a>');
        }else {
            html.push('<input type="button" style="margin-left: 10px" value="导航去取车" onclick="gotoNavi('+poi.lat+','+poi.lng+')"/>');
        }
        html.push('<input type="button" style="margin-left: 10px" value="查看路线" onclick="showList('+poi.lat+','+poi.lng+');"/>');
        html.push('</div>');
        if(poi.car_type == "燃油车"){
        }else {
            html.push('<div align="right" style="margin-top: 30px;margin-bottom: -20px">');
            html.push('<a href="http://www.youchedongli.cn/mobile/index/qrcode_or_license.html"><input type="button" style="margin-left: 10px" value="扫码租车""/></a>');
            html.push('</div>');
        }

        infoWindow = new BMap.InfoWindow(html.join(""), { title: title, width: 50 });

        var open = function (){targetMarker.openInfoWindow(infoWindow);}

        targetMarker.addEventListener("click",function () {
            open();
            $("#l-map").css("height","100%");
            $("#main").css("display","none");
            $("#r-result").css("display","none");

            map.removeOverlay(polylineDriving);
            map.removeOverlay(polylineWalking);
        });
    }

    map.addEventListener("click",function () {
        $("#l-map").css("height","100%");
        $("#main").css("display","none");
        $("#r-result").css("display","none");
    });

    $("#car").on("click",function () {
        drivingNavigation();
        map.removeOverlay(polylineWalking);
        map.removeOverlay(polylineDriving);
        $("#car").css("background","#6495ED");
        $("#walking").css("background","#ffffff");
    });

    $("#walking").on("click",function () {
        map.removeOverlay(polylineDriving);
        map.removeOverlay(polylineWalking);
        walkingNavigation();
        $("#car").css("background","#ffffff");
        $("#walking").css("background","#6495ED");
    });

});

function drivingNavigation() {
    driving = new BMap.DrivingRoute(map,
        {renderOptions: {panel: "r-result", autoViewport: true}, policy: BMAP_DRIVING_POLICY_LEAST_DISTANCE});    //创建驾车实例
    // var end_point = new BMap.Point(lng,lat);
    driving.search(star_point, end_point);//驾车路线搜索
    driving.setSearchCompleteCallback(function(){
        var pts = driving.getResults().getPlan(0).getRoute(0).getPath();    //通过驾车实例，获得一系列点的数组

        polylineDriving = new BMap.Polyline(pts);
        map.addOverlay(polylineDriving);

        map.setViewport([star_point,end_point]);          //调整到最佳视野
    });
}

function walkingNavigation() {
    walking = new BMap.WalkingRoute(map,
        {renderOptions: {panel: "r-result", autoViewport: true}});    //创建步行实例

    walking.search(star_point, end_point);//驾车路线搜索
    walking.setSearchCompleteCallback(function(){
        var pts = walking.getResults().getPlan(0).getRoute(0).getPath();    //通过步行实例，获得一系列点的数组

        polylineWalking = new BMap.Polyline(pts);
        map.addOverlay(polylineWalking);

        map.setViewport([star_point,end_point]);          //调整到最佳视野
    });
}

function gotoNavi(lat,lng) {
    location.href = "http://api.map.baidu.com/direction?origin=26.47075,106.676701&destination="+lat+","+lng+"&mode=driving&region=贵阳&output=html";
}

function showList(lat,lng) {
    end_point = new BMap.Point(lng,lat);
    drivingNavigation();
    $("#l-map").css("height","80%");
    $("#main").css("display","block");
    $("#r-result").css("display","block");
}