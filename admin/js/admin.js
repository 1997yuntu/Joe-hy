jQuery(document).ready(function($) {
    // 图表配置
    var chartConfig = {
        color: ['#2196f3', '#4caf50', '#ff9800', '#f44336'],
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            data: []
        },
        yAxis: {
            type: 'value'
        },
        series: []
    };
    
    // 小图表配置
    var miniChartConfig = {
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
    
    // 初始化图表
    var charts = {};
    
    function initCharts() {
        // 实时请求数图表
        if ($('#realtime-requests-chart').length) {
            charts.requests = echarts.init(document.getElementById('realtime-requests-chart'));
            charts.requests.setOption(miniChartConfig);
        }
        
        // 响应时间图表
        if ($('#response-time-chart').length) {
            charts.responseTime = echarts.init(document.getElementById('response-time-chart'));
            charts.responseTime.setOption(miniChartConfig);
        }
        
        // 错误率图表
        if ($('#error-rate-chart').length) {
            charts.errorRate = echarts.init(document.getElementById('error-rate-chart'));
            charts.errorRate.setOption(miniChartConfig);
        }
        
        // API趋势图表
        if ($('#api-trend-chart').length) {
            charts.apiTrend = echarts.init(document.getElementById('api-trend-chart'));
            charts.apiTrend.setOption(chartConfig);
        }
    }
    
    // 更新实时数据
    function updateRealtimeStats() {
        $.ajax({
            url: yxsApiAdmin.ajaxurl,
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
                
                // 更新小图表
                if (charts.requests) {
                    charts.requests.setOption({
                        series: [{
                            data: data.requests_trend
                        }]
                    });
                }
                
                if (charts.responseTime) {
                    charts.responseTime.setOption({
                        series: [{
                            data: data.response_time_trend
                        }]
                    });
                }
                
                if (charts.errorRate) {
                    charts.errorRate.setOption({
                        series: [{
                            data: data.error_rate_trend
                        }]
                    });
                }
            }
        });
    }
    
    // 更新API趋势
    function updateApiTrend() {
        var period = $('#trend-period').val();
        
        $.ajax({
            url: yxsApiAdmin.ajaxurl,
            data: {
                action: 'get_yxs_api_trend',
                period: period,
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (!response.success || !charts.apiTrend) {
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
                            type: 'line',
                            smooth: true,
                            data: data.requests
                        },
                        {
                            name: '错误数',
                            type: 'line',
                            smooth: true,
                            data: data.errors
                        }
                    ]
                });
            }
        });
    }
    
    // API搜索功能
    var searchTimeout;
    $('#api-search').on('input', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val().toLowerCase();
        
        searchTimeout = setTimeout(function() {
            $('.api-card').each(function() {
                var $card = $(this);
                var title = $card.find('.api-title').text().toLowerCase();
                var endpoint = $card.find('.api-endpoint').text().toLowerCase();
                var description = $card.find('.api-description').text().toLowerCase();
                
                if (title.includes(query) || endpoint.includes(query) || description.includes(query)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        }, 300);
    });
    
    // API分类筛选
    $('.api-categories a').on('click', function(e) {
        e.preventDefault();
        
        $('.api-categories li').removeClass('active');
        $(this).parent().addClass('active');
        
        var category = $(this).data('category');
        if (category === 'all') {
            $('.api-card').show();
        } else {
            $('.api-card').each(function() {
                var categories = $(this).data('categories').split(',');
                if (categories.includes(category.toString())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
    
    // API删除确认
    $('.delete-api').on('click', function() {
        var $button = $(this);
        var apiId = $button.data('id');
        
        if (confirm(yxsApiAdmin.i18n.confirmDelete)) {
            $button.prop('disabled', true);
            
            $.ajax({
                url: yxsApiAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_yxs_api',
                    api_id: apiId,
                    _ajax_nonce: yxsApiAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('.api-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('删除失败，请重试');
                    $button.prop('disabled', false);
                }
            });
        }
    });
    
    // 设置表单提交
    $('#yxs-api-settings').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $spinner = $form.find('.spinner');
        var $submit = $form.find(':submit');
        
        $spinner.addClass('is-active');
        $submit.prop('disabled', true);
        
        $.ajax({
            url: yxsApiAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_yxs_api_settings',
                settings: $form.serializeArray(),
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('设置已保存');
                } else {
                    alert('保存失败：' + response.data);
                }
            },
            error: function() {
                alert('保存失败，请重试');
            },
            complete: function() {
                $spinner.removeClass('is-active');
                $submit.prop('disabled', false);
            }
        });
    });
    
    // 生成API密钥
    $('#generate-api-key').on('click', function() {
        var length = 32;
        var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        var result = '';
        
        for (var i = length; i > 0; --i) {
            result += chars[Math.floor(Math.random() * chars.length)];
        }
        
        $('#api-key').val(result);
    });
    
    // 自动刷新控制
    var refreshInterval;
    
    $('#refresh-interval').on('change', function() {
        var interval = parseInt($(this).val());
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        
        if (interval > 0) {
            refreshInterval = setInterval(updateRealtimeStats, interval * 1000);
        }
    });
    
    // 趋势周期切换
    $('#trend-period').on('change', function() {
        updateApiTrend();
    });
    
    // 初始化
    initCharts();
    
    // 如果在统计页面，开始自动更新
    if ($('#realtime-requests-chart').length) {
        updateRealtimeStats();
        updateApiTrend();
        refreshInterval = setInterval(updateRealtimeStats, 10000); // 默认10秒更新一次
    }
    
    // 窗口大小改变时重绘图表
    $(window).on('resize', function() {
        Object.values(charts).forEach(function(chart) {
            if (chart) {
                chart.resize();
            }
        });
    });
    
    // 暗色模式切换
    function updateChartsTheme() {
        var isDarkMode = $('body').hasClass('darkmode');
        var theme = {
            backgroundColor: isDarkMode ? '#1e1e1e' : '#ffffff',
            textStyle: {
                color: isDarkMode ? '#ffffff' : '#333333'
            },
            title: {
                textStyle: {
                    color: isDarkMode ? '#ffffff' : '#333333'
                }
            },
            tooltip: {
                backgroundColor: isDarkMode ? '#2d2d2d' : '#ffffff',
                textStyle: {
                    color: isDarkMode ? '#ffffff' : '#333333'
                }
            },
            legend: {
                textStyle: {
                    color: isDarkMode ? '#ffffff' : '#333333'
                }
            },
            xAxis: {
                axisLine: {
                    lineStyle: {
                        color: isDarkMode ? '#666666' : '#cccccc'
                    }
                },
                axisLabel: {
                    color: isDarkMode ? '#ffffff' : '#333333'
                }
            },
            yAxis: {
                axisLine: {
                    lineStyle: {
                        color: isDarkMode ? '#666666' : '#cccccc'
                    }
                },
                axisLabel: {
                    color: isDarkMode ? '#ffffff' : '#333333'
                }
            }
        };
        
        Object.values(charts).forEach(function(chart) {
            if (chart) {
                chart.setOption(theme);
            }
        });
    }
    
    // 监听暗色模式切换
    $('body').on('darkmode-changed', function() {
        updateChartsTheme();
    });
    
    // API密钥管理
    $('.generate-api-key').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var userId = $button.data('user-id');
        
        $button.prop('disabled', true).text('生成中...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'yxs_generate_api_key',
                user_id: userId,
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('生成API密钥失败');
            },
            complete: function() {
                $button.prop('disabled', false).text('生成API密钥');
            }
        });
    });
    
    // API密钥状态切换
    $('.toggle-api-key').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var keyId = $button.data('key-id');
        var newStatus = $button.data('new-status');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'yxs_toggle_api_key',
                key_id: keyId,
                status: newStatus,
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // 删除API密钥
    $('.delete-api-key').on('click', function(e) {
        e.preventDefault();
        if (!confirm('确定要删除这个API密钥吗？此操作不可恢复。')) {
            return;
        }
        
        var $button = $(this);
        var keyId = $button.data('key-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'yxs_delete_api_key',
                key_id: keyId,
                _ajax_nonce: yxsApiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data);
                }
            }
        });
    });
    
    // 复制API密钥
    $('.copy-api-key').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $input = $button.prev('.api-key');
        
        $input.select();
        document.execCommand('copy');
        
        var originalText = $button.text();
        $button.text('已复制！');
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });
    
    // API列表搜索
    $('#api-search').on('input', function() {
        var query = $(this).val().toLowerCase();
        
        $('.api-item').each(function() {
            var $item = $(this);
            var title = $item.find('.api-title').text().toLowerCase();
            var endpoint = $item.find('.api-endpoint').text().toLowerCase();
            var description = $item.find('.api-description').text().toLowerCase();
            
            if (title.includes(query) || endpoint.includes(query) || description.includes(query)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    });
    
    // API分类筛选
    $('.api-category-filter').on('change', function() {
        var category = $(this).val();
        
        if (category === 'all') {
            $('.api-item').show();
        } else {
            $('.api-item').each(function() {
                var $item = $(this);
                var categories = $item.data('categories').split(',');
                
                if (categories.includes(category)) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }
    });
    
    // 保存API设置
    $('#api-settings-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find(':submit');
        
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert('设置已保存');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('保存设置失败');
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });
});
