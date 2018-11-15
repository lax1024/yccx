$(document).ready(function () {
    //获取左上角的返回按钮并设置返回上一个界面的功能
    $("#goback").on("click", function (ev) {
        window.history.back();
    });

    //从地址中获取到上一个页面传过来的城市名(取车城市)
    //从Cookie中回去城市名(取车城市)
    var pcity = localStorage.getItem("pcity");

    //将城市名设置到相应的位置(这里用来控制区域的范围)
    $("#cityKeyword2").val(pcity);

    $.ajax({
        url: website + "/api/area/get_city_preferred_address.html",
        type: "POST",
        dataType: "json",
        data: {
            city: pcity
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
            //区域(取车区域)的选择，选择后将数据传到相应的页面
            $("#mainList").find(".areas-box").find(".content").children("span").on("click", function () {
                var parea = $(this).attr("data-name");
                if (parea != null) {
                    localStorage.setItem("slng", $(this).attr("data-lng"));
                    localStorage.setItem("slat", $(this).attr("data-lat"));
                    localStorage.setItem("pcity", pcity);
                    localStorage.setItem("parea", parea);
                    var back_url_area_takecar = localStorage.getItem('back_url_area_takecar');
                    if (back_url_area_takecar == null) {
                        back_url_area_takecar = 'index';
                    }
                    location.href = back_url_area_takecar + ".html";
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
                city: pcity,
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
                    var loction = value.location;
                    var searchItem = $("<div " +
                        "class=\" \" " +
                        "data-id=\"" + value.id + "\"" +
                        "data-lat=\"" + loction.lat + "\"" +
                        "data-lng=\"" + loction.lng + "\">" + value.name + "</div>");

                    searchItem.on("click", function () {
                        localStorage.setItem("slng", $(this).attr("data-lng"));
                        localStorage.setItem("slat", $(this).attr("data-lat"));
                        localStorage.setItem("pcity", pcity);
                        localStorage.setItem("parea", $(this).text());
                        var back_url_area_takecar = localStorage.getItem('back_url_area_takecar');
                        if (back_url_area_takecar == null) {
                            back_url_area_takecar = 'index';
                        }
                        location.href = back_url_area_takecar + ".html";
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




