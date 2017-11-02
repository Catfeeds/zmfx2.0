//统计图一
Morris.Donut({
    element: 'graph-next',
    data: [
        {value: 40, label: '', formatted: ' 占 40%' },
        {value: 25, label: '', formatted: '占 30%' },
        {value: 10, label: '', formatted: '占 30%' },
        {value: 25, label: '', formatted: '占 30%' },
    ],
    backgroundColor: false,
    labelColor: '#000',
    colors: [
        '#6cbde2','#ff7272','#fe834e','#f2af59'
    ],
    formatter: function (x, data) { return data.formatted; }
});


$(function() {

    var d1 = [
        [0, 501],
        [1, 620],
        [2, 437],
        [3, 361],
        [4, 549],
        [5, 618],
        [6, 570],
        [7, 758],
        [8, 658],
        [9, 538],
        [10, 488]

    ];
    var d2 = [
        [0, 401],
        [1, 520],
        [2, 337],
        [3, 261],
        [4, 449],
        [5, 518],
        [6, 470],
        [7, 658],
        [8, 558],
        [9, 438],
        [10, 388]
    ];

    var data = ([{
        label: "最近7天订单走势图表",
        data: d1,
        lines: {
            show: true,
            fill: true,
            fillColor: {
                colors: ["rgba(220,97,102,.4)", "rgba(253,244,243,.4)"]
            }
        }
    },
       
    ]);

    var options = {
        grid: {
            backgroundColor:
            {
                colors: ["#f4f4f6", "#fff"]
            },
            hoverable: true,
            clickable: true,
            tickColor: "#eeeeee",
            borderWidth: 1,
            borderColor: "#eeeeee"
        },
        // Tooltip
        tooltip: true,
        tooltipOpts: {
            content: "%s X: %x Y: %y",
            shifts: {
                x: -60,
                y: 25
            },
            defaultTheme: false
        },
        legend: {
            labelBoxBorderColor: "#000000",
            container: $("#main-chart-legend"), 
            noColumns: 0
        },
        series: {
            stack: true,
            shadowSize: 0,
            highlightColor: 'rgba(000,000,000,.2)'
        },
//        lines: {
//            show: true,
//            fill: true
//
//        },
        points: {
            show: true,
            radius: 3,
            symbol: "circle"
        },
        colors: ["#dc6166", "#fff"]
    };
    var plot = $.plot($("#main-chart #main-chart-content"), data, options);
});