$(document).ready(function () {

    ;(function($){
        $(function () {
            // ---------【数据部分开始】---------
            var nowDate = new Date();
            // 年月日 时分
            $.dateSelector({
                evEle: '#j_pdate,#j_rdate',
                year: nowDate.getFullYear() + "年",
                month: (nowDate.getMonth() + 1) < 10 ?"0"+(nowDate.getMonth() + 1)+"月":(nowDate.getMonth() + 1)+"月",
                day: nowDate.getDate() < 10?"0"+nowDate.getDate() + "日":nowDate.getDate() + "日",
                startYear: nowDate.getFullYear(),
                endYear: nowDate.getFullYear() + 1,
                timeBoo: true,
                //添加到控件
                afterAction: function (pTime,rTime) {
                    setTimeout(function(){
                        var acquire_time = transformDateTime(pTime)+":00";
                        var return_time = transformDateTime(rTime)+":00";
                        console.log(acquire_time);
                        console.log(return_time);
                        localStorage.setItem("acquire_time",acquire_time);
                        localStorage.setItem("return_time",return_time);
                        //取用时间
                        $("#j_pdata_ymd").html(getMD(acquire_time));
                        $("#j_pdata_ymd").attr("data-time",acquire_time);
                        $("#j_pdata_whm").html(getWHM(acquire_time));

                        //归还时间
                        $("#j_rdata_ymd").html(getMD(return_time));
                        $("#j_rdata_ymd").attr("data-time",return_time);
                        $("#j_rdata_whm").html(getWHM(return_time));
                        $("#j_day").html(getDaysHoursMinutes(acquire_time,return_time));
                    },400);
                },
            });
        });
    })($);

});

