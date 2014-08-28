/*
 <?php print_r($_GET);?>
 */
// JavaScript Document
chart = new Highcharts.Chart({
    chart: {
        renderTo: '<?php echo $_GET['div']?>',
        zoomType: 'x',
        type: 'area',
        spacingRight: 20
    },
    title: {
        text: '<?php echo $_GET['type']?> - <?php echo $_GET['v3']?>(可以选择区域进行放大缩小)'
    },
    xAxis: {
        type: 'datetime',
        maxZoom: 30 * 24 * 3600, // fourteen days
        title: {
            text: null
        }
    },
    yAxis: {
        title: {
            text: 'Hello world'
        },
        min: 0.6,
        startOnTick: false,
        showFirstLabel: false
    },
    tooltip: {
        shared: true,
        crosshairs: true
    },
    legend: {
        enabled: true
    },
    plotOptions: {
        pie: {
            allowPointSelect: true,
            dataLabels: {
                enabled: true
            }
        },
        area: {
            fillColor: {
                linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1},
                stops: [
                    [0, Highcharts.getOptions().colors[0]],
                    [1, 'rgba(12,0,0,0)']
                ]
            },
            lineWidth: 1,
            marker: {
                enabled: false,
                states: {
                    hover: {
                        enabled: true,
                        radius: 1
                    }
                }
            },
            shadow: true,
            states: {
                hover: {
                    lineWidth: 1
                }
            }
        },
        pie: {
            allowPointSelect: true,
            percentageDecimals: 1,
            cursor: 'pointer',
            dataLabels: {
                enabled: true,
                color: '#000000',
                connectorColor: '#000000',
                formatter: function () {
                    return '<b>' + this.point.name + '</b>: (' + Highcharts.numberFormat(this.percentage, 2) + ') %';
                }
            }
        }
    },

    series: [<?php $i=0;$ic=count($this->all_datashow);foreach($this->all_datashow as $k=>$v){ $i++;?>{
        type: 'area',
        name: '<?php echo $k?>',
        color: '#<?php  for($a=0;$a<6;$a++) echo dechex(rand(0,15)); ?>', // Jane's color
        pointInterval: <?php echo $_GET['pointInterval']?>,
                pointStart: Date.UTC(<?php echo date('Y',strtotime($s1))?>, <?php echo date('m',strtotime($s1))?>, <?php echo date('d',strtotime($s1))?>),
                data: [<?php echo join(',',$v)?>]
        }<?php  if($i<>$ic){ echo ",\n"; } ?><?php }?>

    <?php if($ic>1){?>,{
        type: 'pie',
        name: '汇总',
        data: [<?php $i=0;$ic=count($this->all_datashow_pie);foreach($this->all_datashow_pie as $k=>$v){?>{
        name: '<?php echo $k?>',
        y: <?php echo $v?>,
        color: '#<?php  for($a=0;$a<6;$a++) echo dechex(rand(0,15)); ?>' // Jane's color
                }<?php  if($i<>$ic){ echo ",\n"; } }?>],
    center: [120, 40],
    size: 70,
    showInLegend: false,
    dataLabels: {
        enabled: true
        }
    }
    <?php }?>
    ]

    });