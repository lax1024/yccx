$(document).ready(function () {
    //从地址中获取到上一个页面传过来的城市名(还车城市)
    var rcity = localStorage.getItem("rcity");
    //将城市名设置到相应的位置(这里用来控制区域的范围)
    $("#cityKeyword2").val(rcity);
    //获取左上角的返回按钮并设置返回上一个界面的功能
    //返回上一个页面(左上角的“取消”按钮功能)
    $("#goback").on("click", function (ev) {
        window.history.back();
    });

    $.ajax({
        url: website + "/api/area/get_city_preferred_address.html",
        type: "POST",
        dataType: "json",
        data: {
            city: rcity
        },
        success: function (data) {
            var areaList = new Object();
            var header = $("#mainList").find(".areas-box").find(".header").children("span");
            $.each(data.data, function (key, value) {
                for (var i = 0; i < header.length; i++) {
                    var name = $(header).get(i).innerHTML;
                    var hearderName = name.substr(0, value.name.length);
                    if (hearderName == value.name) {
                        var list = $("#mainList").find(".areas-box").find(".header").get(i);
                        areaList = $(list).next();
                    }
                }

                $.each(value.list, function (city, cityVlaue) {
                    var item =
                        "<span class=\"area normal\"\n" +
                        "      data-name=\"" + cityVlaue.name + "\"\n" +
                        "      data-type=\"undefined\"\n" +
                        "      data-lat=\"" + cityVlaue.location.lat + "\"\n" +
                        "      data-lng=\"" + cityVlaue.location.lng + "\"\n" +
                        "      style=\"width: 33.33%\">\n" +
                        "    <span style=\"width: 100%;margin:0 14px 0 15px;\">" + cityVlaue.name + "</span>  \n" +
                        "</span>";

                    areaList.append(item);
                })
            })
            //区域(还车区域)的选择，选择后将数据传到相应的页面
            $("#mainList").find(".areas-box").find(".content").children("span").on("click", function () {
                var rarea = this.getAttribute("data-name");
                if (rarea != null) {
                    localStorage.setItem("elng", $(this).attr("data-lng"));
                    localStorage.setItem("elat", $(this).attr("data-lat"));
                    localStorage.setItem("rcity", rcity);
                    localStorage.setItem("rarea", rarea);
                    var back_url_area_returncar = localStorage.getItem('back_url_area_returncar');
                    if (back_url_area_returncar == null) {
                        back_url_area_returncar = 'index';
                    }
                    location.href = back_url_area_returncar + ".html";
                }
            });
        }
    });
//搜索框
    $("#areaKeyword").bind("input propertychange", function () {
        var cityKeyword = $('#areaKeyword').val();

        if (cityKeyword == "") {
            $("#scrollList").css({"display": "block"});
        }
        $.ajax({
            url: website + "/api/area/get_city_search_area.html",
            type: "POST",
            async: true,
            data: {
                city: rcity,
                keyword: cityKeyword
            },
            timeout: 5000,
            dataType: "json",
            beforeSend: function (xhr) {
                console.log(xhr);
                console.log("发送前")
            },
            success: function (data) {
                console.log("成功");
                console.log(data.data);
                $("#searchList").empty();
                $.each(data.data, function (key, value) {
                    $("#scrollList").css({"display": "none"});
                    console.log(value.name);
                    var searchItem = $("<div " +
                        "class=\" \" " +
                        "data-id=\"" + value.id + "\"" +
                        "data-lat=\"" + value.location.lat + "\"" +
                        "data-lng=\"" + value.location.lng + "\">" + value.name + "</div>");

                    searchItem.on("click", function () {
                        localStorage.setItem("rarea", $(this).text());
                        localStorage.setItem("elng", $(this).attr("data-lng"));
                        localStorage.setItem("elat", $(this).attr("data-lat"));
                        var back_url_area_returncar = localStorage.getItem('back_url_area_returncar');
                        if (back_url_area_returncar == null) {
                            back_url_area_returncar = 'index';
                        }
                        location.href = back_url_area_returncar + ".html";
                    });

                    $("#searchList").append(searchItem);
                });
            },
            error: function (xhr, textStatus) {
                console.log('错误');
                console.log(xhr);
                console.log(textStatus)
            },
            complete: function () {
                console.log('结束');
            }
        });
    });
});

