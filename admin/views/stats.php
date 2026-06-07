<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap yxs-api-wrap">
    <h1>API数据统计</h1>
    
    <div class="zib-admin-box">
        <div class="zib-admin-box-header">
            <div class="zib-admin-box-title">
                <h2>实时监控</h2>
            </div>
            <div class="zib-admin-box-tools">
                <select id="refresh-interval">
                    <option value="0">手动刷新</option>
                    <option value="5">5秒</option>
                    <option value="10" selected>10秒</option>
                    <option value="30">30秒</option>
                    <option value="60">1分钟</option>
                </select>
                <button class="button" id="refresh-stats">
                    <span class="dashicons dashicons-update"></span>
                    刷新
                </button>
            </div>
        </div>
        
        <div class="zib-admin-content">
            <div class="stats-grid">
                <!-- 实时请求数 -->
                <div class="stats-card">
                    <div class="stats-card-header">
                        <h3>实时请求数</h3>
                        <span class="stats-period">最近1分钟</span>
                    </div>
                    <div class="stats-card-body">
                        <div class="stats-value" id="realtime-requests">0</div>
                        <div class="stats-chart" id="realtime-requests-chart"></div>
                    </div>
                </div>
                
                <!-- 响应时间 -->
                <div class="stats-card">
                    <div class="stats-card-header">
                        <h3>平均响应时间</h3>
                        <span class="stats-period">最近1分钟</span>
                    </div>
                    <div class="stats-card-body">
                        <div class="stats-value" id="avg-response-time">0ms</div>
                        <div class="stats-chart" id="response-time-chart"></div>
                    </div>
                </div>
                
                <!-- 错误率 -->
                <div class="stats-card">
                    <div class="stats-card-header">
                        <h3>错误率</h3>
                        <span class="stats-period">最近1分钟</span>
                    </div>
                    <div class="stats-card-body">
                        <div class="stats-value" id="error-rate">0%</div>
                        <div class="stats-chart" id="error-rate-chart"></div>
                    </div>
                </div>
                
                <!-- 在线用户 -->
                <div class="stats-card">
                    <div class="stats-card-header">
                        <h3>活跃用户</h3>
                        <span class="stats-period">最近5分钟</span>
                    </div>
                    <div class="stats-card-body">
                        <div class="stats-value" id="active-users">0</div>
                        <div class="stats-chart" id="active-users-chart"></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-row">
                <!-- API调用趋势 -->
                <div class="stats-panel">
                    <div class="stats-panel-header">
                        <h3>API调用趋势</h3>
                        <div class="stats-panel-tools">
                            <select id="trend-period">
                                <option value="hour">最近1小时</option>
                                <option value="day" selected>最近24小时</option>
                                <option value="week">最近7天</option>
                                <option value="month">最近30天</option>
                            </select>
                        </div>
                    </div>
                    <div class="stats-panel-body">
                        <div id="api-trend-chart" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-row">
                <!-- TOP API -->
                <div class="stats-panel">
                    <div class="stats-panel-header">
                        <h3>热门API排行</h3>
                    </div>
                    <div class="stats-panel-body">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>API</th>
                                    <th>调用次数</th>
                                    <th>平均响应时间</th>
                                    <th>错误率</th>
                                </tr>
                            </thead>
                            <tbody id="top-apis">
                                <tr>
                                    <td colspan="4">加载中...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stats-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.stats-card-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.stats-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.stats-period {
    font-size: 12px;
    color: #666;
}

.stats-card-body {
    padding: 15px;
    position: relative;
}

.stats-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.stats-chart {
    height: 50px;
}

.stats-row {
    margin-bottom: 20px;
}

.stats-panel {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.stats-panel-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stats-panel-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.stats-panel-body {
    padding: 15px;
}

#refresh-interval {
    margin-right: 10px;
}

.button .dashicons {
    margin-right: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 初始化图表
    var charts = {
        requests: echarts.init(document.getElementById('realtime-requests-chart')),
        responseTime: echarts.init(document.getElementById('response-time-chart')),
        errorRate: echarts.init(document.getElementById('error-rate-chart')),
        activeUsers: echarts.init(document.getElementById('active-users-chart')),
        apiTrend: echarts.init(document.getElementById('api-trend-chart'))
    };
    
    // 实时数据
    var realtimeData = {
        time: [],
        requests: [],
        responseTime: [],
        errorRate: [],
        activeUsers: []
    };
    
    // 基础图表配置
    var baseChartOption = {
        grid: {
            top: 5,
            right: 5,
            bottom: 5,
            left: 5,
            containLabel: false
        },
        xAxis: {
            type: 'category',
            show: false
        },
        yAxis: {
            type: 'value',
            show: false
        },
        series: [{
            type: 'line',
            smooth: true,
            symbol: 'none',
            areaStyle: {}
        }]
    };
    
    // 初始化小图表
    Object.values(charts).forEach(function(chart) {
        if (chart !== charts.apiTrend) {
            chart.setOption(baseChartOption);
        }
    });
    
    // API趋势图配置
    charts.apiTrend.setOption({
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: ['请求数', '错误数']
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: []
        },
        yAxis: {
            type: 'value'
        },
        series: [
            {
                name: '请求数',
                type: 'line',
                smooth: true,
                data: []
            },
            {
                name: '错误数',
                type: 'line',
                smooth: true,
                data: []
            }
        ]
    });
    
    // 更新实时数据
    function updateRealtimeStats() {
        $.ajax({
            url: yxsApiAdmin.ajaxUrl,
            data: {
                action: 'get_yxs_api_realtime_stats',
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                
                var data = response.data;
                
                // 更新数值
                $('#realtime-requests').text(data.requests);
                $('#avg-response-time').text(data.avg_response_time.toFixed(2) + 'ms');
                $('#error-rate').text(data.error_rate.toFixed(2) + '%');
                $('#active-users').text(data.active_users);
                
                // 更新图表数据
                realtimeData.time.push(new Date().toLocaleTimeString());
                realtimeData.requests.push(data.requests);
                realtimeData.responseTime.push(data.avg_response_time);
                realtimeData.errorRate.push(data.error_rate);
                realtimeData.activeUsers.push(data.active_users);
                
                // 保持最近12个数据点
                if (realtimeData.time.length > 12) {
                    realtimeData.time.shift();
                    realtimeData.requests.shift();
                    realtimeData.responseTime.shift();
                    realtimeData.errorRate.shift();
                    realtimeData.activeUsers.shift();
                }
                
                // 更新图表
                charts.requests.setOption({
                    xAxis: { data: realtimeData.time },
                    series: [{ data: realtimeData.requests }]
                });
                
                charts.responseTime.setOption({
                    xAxis: { data: realtimeData.time },
                    series: [{ data: realtimeData.responseTime }]
                });
                
                charts.errorRate.setOption({
                    xAxis: { data: realtimeData.time },
                    series: [{ data: realtimeData.errorRate }]
                });
                
                charts.activeUsers.setOption({
                    xAxis: { data: realtimeData.time },
                    series: [{ data: realtimeData.activeUsers }]
                });
            }
        });
    }
    
    // 更新API趋势
    function updateApiTrend() {
        var period = $('#trend-period').val();
        
        $.ajax({
            url: yxsApiAdmin.ajaxUrl,
            data: {
                action: 'get_yxs_api_trend',
                period: period,
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                
                var data = response.data;
                
                charts.apiTrend.setOption({
                    xAxis: {
                        data: data.time
                    },
                    series: [
                        {
                            name: '请求数',
                            data: data.requests
                        },
                        {
                            name: '错误数',
                            data: data.errors
                        }
                    ]
                });
            }
        });
    }
    
    // 更新热门API
    function updateTopApis() {
        $.ajax({
            url: yxsApiAdmin.ajaxUrl,
            data: {
                action: 'get_yxs_api_top',
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                
                var html = '';
                response.data.forEach(function(api) {
                    html += `
                        <tr>
                            <td>${api.name}</td>
                            <td>${api.calls}</td>
                            <td>${api.avg_response_time}ms</td>
                            <td>${api.error_rate}%</td>
                        </tr>
                    `;
                });
                
                $('#top-apis').html(html);
            }
        });
    }
    
    // 自动刷新控制
    var refreshInterval = null;
    
    $('#refresh-interval').on('change', function() {
        var interval = parseInt($(this).val());
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        
        if (interval > 0) {
            refreshInterval = setInterval(updateRealtimeStats, interval * 1000);
        }
    });
    
    $('#refresh-stats').on('click', function() {
        updateRealtimeStats();
    });
    
    $('#trend-period').on('change', function() {
        updateApiTrend();
    });
    
    // 初始加载
    updateRealtimeStats();
    updateApiTrend();
    updateTopApis();
    
    // 默认10秒自动刷新
    refreshInterval = setInterval(updateRealtimeStats, 10000);
    
    // 窗口大小改变时重绘图表
    $(window).on('resize', function() {
        Object.values(charts).forEach(function(chart) {
            chart.resize();
        });
    });
});
</script>