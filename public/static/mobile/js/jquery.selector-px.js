;(function ($) {
    // 取消选择
    $('.sel-box .cancel,.sel-boxs .bg').click(function () {
        $('.sel-boxs .bg')[0].removeEventListener('touchmove', preDef, false);
        $('.sel-boxs .btn')[0].removeEventListener('touchmove', preDef, false);
        $('.sel-boxs').find('.sel-box').removeClass('fadeInUp').addClass('fadeInDown');
        setTimeout(function () {
            $('.sel-boxs').hide();
        }, 300);
    });

    //取消ios在zepto下的穿透事件
    $(".sel-con").on("touchend", function (event) {
        event.preventDefault();
    });

    //取消默认行为   灰层底部不能滑动
    var preDef = function (e) {
        e.preventDefault();
        return false;
    };

    function dataFrame(ele) {
        // ele数组转换成相应结构
        var eleText = '';
        for (var i = 0; i < ele.length; i++) {
            eleText += '<div class="ele">' + ele[i] + '</div>';
        }
        ;
        return '<div class="cell elem"><div class="scroll">' + eleText + '</div></div>';
    };

    // 每个月的天数
    function getMonthDays(year, month) {
        return new Date(parseInt(year), parseInt(month), 0).getDate();
    };
    // 天数小于10天在前面加"0"
    function twoZero(n) {
        return n < 10 ? n = '0' + n + "日" : n = n + '日';
    };
    // 天数转换成数组
    function couDay(n) {
        arrDay = [];
        for (var i = 1; i <= n; i++) {
            arrDay.push(twoZero(i));
        }
        ;
        return arrDay;
    };

    function getdhm(times) {
        times = transformYMDHis(times);
        // console.log(times);
        var time_time = Date.parse(times) / 1000;
        time_time += 3600;
        var year = new Date(time_time * 1000).getFullYear();
        var month = pad(new Date(time_time * 1000).getMonth() + 1,2);
        var day = pad(new Date(time_time * 1000).getDate(),2);
        var hour = pad(new Date(time_time * 1000).getHours(),2);
        return [year, month, day, hour];
    }

    $.dateSelector = function (params) {
        var hunYear = [];
        var evEle = params.evEle || evEle;
        var year = params.year || new Date().getFullYear() + "年";
        var month = params.month || (new Date().getMonth() + 1) + "月";
        var day = params.day || new Date().getDate() + "日";
        var type = params.type || 'click'; //事件类型
        var startYear = params.startYear || '';
        var endYear = params.endYear || '';
        var timeBoo = params.timeBoo || false;
        var hour = params.hour || new Date().getHours() < 10 ? "0" + new Date().getHours() + "时" : new Date().getHours() + "时";
        var minute = params.minute || (new Date().getMinutes() > 0 && new Date().getMinutes() <= 30) ? "30分" : "00分";
        var out_time = getdhm(year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00");
        year = out_time[0]+"年";
        month = out_time[1]+"月";
        day = out_time[2]+"日";
        hour = out_time[3]+"时";
        var beforeAction = params.beforeAction || function () {
            }; //执行前的动作  无参数
        var afterAction = params.afterAction || function () {
            };//执行后的动作   参数：选择的文字

        // 年 默认范围：当前年份-10 ~ 当前年份 ~ 当前年份+10
        if (startYear !== '' && endYear !== '') {
            for (var i = startYear; i <= endYear; i++) {
                hunYear.push(i + "年")
            }
            ;
        } else {
            for (var i = -10; i < 10; i++) {
                hunYear.push((new Date().getFullYear() - i) + "年");
            }
            ;
        }

        $(evEle).attr('readonly', 'readonly');

        // 月 范围：十二个月份
        // var tweMonth = ["01月","02月","03月","04月","05月","06月","07月","08月","09月","10月","11月","12月"];
        var tweMonth = new Array();
        for (var i = 0; i < 12; i++) {
            if ((new Date().getMonth() + 1 + i) > 12) {
                tweMonth[i] = (new Date().getMonth() + 1 + i - 12) < 10 ?
                    "0" + (new Date().getMonth() + 1 + i - 12) + "月" :
                    (new Date().getMonth() + 1 + i - 12) + "月";
            } else {
                tweMonth[i] = (new Date().getMonth() + 1 + i) < 10 ?
                    "0" + (new Date().getMonth() + 1 + i) + "月" :
                    (new Date().getMonth() + 1 + i) + "月";
            }
        }

        // 日 获取日期
        var arrDay = [];
        // 小时
        var timeHour = ["00时", "01时", "02时", "03时", "04时", "05时", "06时", "07时",
            "08时", "09时", "10时", "11时", "12时", "13时", "14时", "15时",
            "16时", "17时", "18时", "19时", "20时", "21时", "22时", "23时"];
        // 分钟
        var timeMinute = ["00分", "30分"];

        // 年月日选择器
        $(evEle).on(type, function () {

            $('.sel-boxs .bg')[0].addEventListener('touchmove', preDef, false);
            $('.sel-boxs .btn')[0].addEventListener('touchmove', preDef, false);
            beforeAction();
            var timeGroup = '';
            if (timeBoo) {
                timeGroup = dataFrame(timeHour) + dataFrame(timeMinute);
            }
            ;
            $('.sel-con .table').html(
                dataFrame(hunYear) +
                dataFrame(tweMonth) +
                dataFrame(couDay(getMonthDays(hunYear[0], tweMonth[0])))
                + timeGroup);

            var ptime = $("#time_TakeCar").html();
            var rtime = $("#time_ReturnCar").html();

            $('.sel-box .name').text(getDaysHoursMinutes(localStorage.getItem("acquire_time"), localStorage.getItem("return_time")));

            $('.sel-boxs').show().find('.sel-box').removeClass('fadeInDown').addClass('fadeInUp');
            // 选择器
            if ($(evEle).val() != '') {
                year = $(evEle).attr('data-sel01');
                month = $(evEle).attr('data-sel02');
                day = $(evEle).attr('data-sel03');
                if (timeBoo) {
                    hour = $(evEle).attr('data-sel04');
                    minute = $(evEle).attr('data-sel05');
                }
                ;
            }
            ;
            var scText = year; // 年
            var scText2 = month; // 月
            var scText3 = day; // 日
            var scText4 = hour; // 小时
            var scText5 = minute; // 分钟
            $('.sel-con').find('.elem').eq(0).find('.ele').each(function () {
                if ($(this).text() == year) {
                    $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                }
            });
            $('.sel-con').find('.elem').eq(1).find('.ele').each(function () {
                if ($(this).text() == month) {
                    $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                }
            });
            $('.sel-con').find('.elem').eq(2).find('.ele').each(function () {
                if ($(this).text() == day) {
                    $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                }
            });
            if (timeBoo) {
                $('.sel-con').find('.elem').eq(3).find('.ele').each(function () {
                    if ($(this).text() == hour) {
                        $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                    }
                });
                $('.sel-con').find('.elem').eq(4).find('.ele').each(function () {
                    if ($(this).text() == minute) {
                        $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                    }
                });
            }
            ;
            $('.sel-con .scroll').eq(0).scroll(function () {
                var that = $(this);
                // 数值显示
                var scTop = $(this)[0].scrollTop + 18;
                var scNum = Math.floor(scTop / 36);
                // 类型名称
                scText = $(this).find('.ele').eq(scNum).text();
                // 停止锁定
                clearTimeout($(this).attr('timer'));
                $(this).attr('timer', setTimeout(function () {
                    that[0].scrollTop = scNum * 36;
                }, 100));
                $('.sel-con .table').find('.elem').eq(2).remove();
                $('.sel-con .table').find('.elem').eq(1).after(dataFrame(couDay(getMonthDays(scText, scText2))));
                // 固定在原来的值
                $('.sel-con').find('.elem').eq(2).find('.ele').each(function () {
                    if (Number($(this).text()) <= Number(scText3)) {
                        $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                    }
                });
                $('.sel-con .scroll').eq(2).scroll(function () {
                    var that = $(this);
                    // 数值显示
                    var scTop = $(this)[0].scrollTop + 18;
                    var scNum = Math.floor(scTop / 36);
                    // 类型名称
                    scText3 = $(this).find('.ele').eq(scNum).text();
                    // 停止锁定
                    clearTimeout($(this).attr('timer'));
                    $(this).attr('timer', setTimeout(function () {
                        that[0].scrollTop = scNum * 36;
                    }, 100));
                });
            });
            $('.sel-con .scroll').eq(1).scroll(function () {
                var that = $(this);
                // 数值显示
                var scTop = $(this)[0].scrollTop + 18;
                var scNum = Math.floor(scTop / 36);
                // 类型名称
                scText2 = $(this).find('.ele').eq(scNum).text();
                // 停止锁定
                clearTimeout($(this).attr('timer'));
                $(this).attr('timer', setTimeout(function () {
                    that[0].scrollTop = scNum * 36;
                }, 100));
                // $('.sel-con .table').find('.elem').eq(2).remove();
                // $('.sel-con .table').find('.elem').eq(1).after(dataFrame(couDay(getMonthDays(scText,scText2))));
                // 固定在原来的值
                $('.sel-con').find('.elem').eq(2).find('.ele').each(function () {
                    if (Number($(this).text()) <= Number(scText3)) {
                        $(this).parents('.scroll')[0].scrollTop = $(this).index() * 36;
                    }
                    ;
                });
                $('.sel-con .scroll').eq(2).scroll(function () {
                    var that = $(this);
                    // 数值显示
                    var scTop = $(this)[0].scrollTop + 18;
                    var scNum = Math.floor(scTop / 36);
                    // 类型名称
                    scText3 = $(this).find('.ele').eq(scNum).text();
                    // 停止锁定
                    clearTimeout($(this).attr('timer'));
                    $(this).attr('timer', setTimeout(function () {
                        that[0].scrollTop = scNum * 36;
                    }, 100));
                });
            });
            $('.sel-con .scroll').eq(2).scroll(function () {
                var that = $(this);
                // 数值显示
                var scTop = $(this)[0].scrollTop + 18;
                var scNum = Math.floor(scTop / 36);
                // 类型名称
                scText3 = $(this).find('.ele').eq(scNum).text();
                // 停止锁定
                clearTimeout($(this).attr('timer'));
                $(this).attr('timer', setTimeout(function () {
                    that[0].scrollTop = scNum * 36;
                }, 100));
            });
            var time = '';
            if (timeBoo) {
                $('.sel-con .scroll').scroll(function () {
                    var that = $(this);
                    // 数值显示
                    var scTop = $(this)[0].scrollTop + 18;
                    var scNum = Math.floor(scTop / 36);
                    // 类型名称
                    if ($(this).parents('.elem').index() == 3) {
                        scText4 = $(this).find('.ele').eq(scNum).text();
                    } else if ($(this).parents('.elem').index() == 4) {
                        scText5 = $(this).find('.ele').eq(scNum).text();
                    }
                    ;
                    time = ' ' + scText4 + ':' + scText5
                    // 停止锁定
                    clearTimeout($(this).attr('timer'));
                    $(this).attr('timer', setTimeout(function () {
                        that[0].scrollTop = scNum * 36;
                    }, 100));
                });
            }
            var pTime, rTime;
            //移除之前的绑定事件
            $(".sel-box .ok").off();
            // 进行传值
            $('.sel-box .ok').click(function () {
                $(evEle).attr('data-sel01', scText);
                $(evEle).attr('data-sel02', scText2);
                $(evEle).attr('data-sel03', scText3);
                $(evEle).attr('data-sel04', scText4);
                $(evEle).attr('data-sel05', scText5);

                if ($("#List_Time_ReturnCar").hasClass("on")) {
                    rTime = scText + scText2 + scText3 + " " + scText4 + scText5;
                    afterAction(pTime, rTime);

                    var rtime = new Date(transformTime(rTime)).getTime();
                    var ptime = new Date(transformTime(pTime)).getTime();
                    if (rtime - ptime < 0) {
                        Toast("还车时间必须晚于取车时间！", 2000);
                    } else {
                        if (rtime - ptime < 1800000) {
                            Toast("用车时间必须大于等于30分钟！", 2000);
                        } else {
                            localStorage.setItem("return_time", transformTime(rTime));
                            $('.sel-box .name').text(getDaysHoursMinutes(transformTime(pTime), transformTime(rTime)));
                            $("#time_ReturnCar").html(scText + scText2 + scText3 + " " + scText4 + scText5);
                            $('.sel-boxs').find('.sel-box').removeClass('fadeInUp').addClass('fadeInDown');
                            setTimeout(function () {
                                $('.sel-boxs').hide();
                                $("#List_Time_ReturnCar").removeClass("on");
                                $("#List_Time_TakeCar").addClass("on");
                            }, 300);
                        }
                    }
                } else {
                    pTime = scText + scText2 + scText3 + " " + scText4 + scText5;
                    if (new Date().getTime() > new Date(transformTime(pTime))) {
                        Toast("不能选取过去的时间！", 2000);
                    } else {
                        localStorage.setItem("acquire_time", transformTime(pTime));
                        $("#List_Time_ReturnCar").addClass("on");
                        $("#List_Time_TakeCar").removeClass("on");
                        $("#time_TakeCar").html(scText + scText2 + scText3 + " " + scText4 + scText5);
                    }
                }
                $('.sel-boxs .bg')[0].removeEventListener('touchmove', preDef, false);
                $('.sel-boxs .btn')[0].removeEventListener('touchmove', preDef, false);
            });

            //移除之前的绑定事件
            $(".sel-box .cancel").off();
            // 取消按钮
            $('.sel-box .cancel').click(function () {
                $('.sel-boxs').find('.sel-box').removeClass('fadeInUp').addClass('fadeInDown');
                setTimeout(function () {
                    $('.sel-boxs').hide();
                    $("#List_Time_TakeCar").addClass("on");
                    $("#List_Time_ReturnCar").removeClass("on");
                }, 300);
                $('.sel-boxs .bg')[0].removeEventListener('touchmove', preDef, false);
                $('.sel-boxs .btn')[0].removeEventListener('touchmove', preDef, false);
            });

            // 背景(空白区域/非时间选择器内)
            $('.bg').click(function () {
                $('.sel-boxs').find('.sel-box').removeClass('fadeInUp').addClass('fadeInDown');
                setTimeout(function () {
                    $('.sel-boxs').hide();
                    $("#List_Time_TakeCar").addClass("on");
                    $("#List_Time_ReturnCar").removeClass("on");
                }, 300);
                $('.sel-boxs .bg')[0].removeEventListener('touchmove', preDef, false);
                $('.sel-boxs .btn')[0].removeEventListener('touchmove', preDef, false);
            });
        });
    }
})($);
