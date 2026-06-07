// API文档页面交互逻辑
(function($) {
    'use strict';

    // 调试函数
    function debugLog(message, data) {
        console.log('[API Debug]', message, data);
    }

    function showError(message) {
        $('.response-area').show();
        $('#response-content').html('错误: ' + message);
        $('.response-status').html('<span class="error">Status: Error</span>');
        console.error('[API Error]', message);
    }

    // 初始化时检查配置
    $(document).ready(function() {
        debugLog('初始化配置:', window.yxsApiSettings);
        
        if (typeof window.yxsApiSettings === 'undefined') {
            showError('API设置未正确加载 (yxsApiSettings未定义)');
            return;
        }

        if (!window.yxsApiSettings.apiUrl) {
            showError('API URL未设置');
            return;
        }

        debugLog('API配置验证成功', {
            apiUrl: window.yxsApiSettings.apiUrl,
            endpoint: window.yxsApiSettings.endpoint,
            method: window.yxsApiSettings.method
        });
    });

    // 初始化复制功能
    var clipboard = new ClipboardJS('.copy-btn');
    clipboard.on('success', function(e) {
        var $btn = $(e.trigger);
        var originalText = $btn.text();
        $btn.text('已复制!');
        setTimeout(function() {
            $btn.text(originalText);
        }, 1000);
    });

    // 代码示例标签切换
    $('.tab-btn').on('click', function() {
        $('.tab-btn').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        $('.tab-content[data-lang="' + $(this).data('lang') + '"]').addClass('active');
    });

    // API测试表单提交
    $('#api-test-form').on('submit', function(e) {
        e.preventDefault();
        
        debugLog('表单提交开始');
        
        // 验证yxsApiSettings是否存在
        if (typeof window.yxsApiSettings === 'undefined') {
            showError('API设置未正确加载');
            return;
        }

        // 验证必要的设置
        if (!window.yxsApiSettings.apiUrl) {
            showError('API URL未设置');
            return;
        }
        
        // 收集表单参数
        var params = {};
        $('.param-input').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            if (value) {
                params[name] = value;
            }
        });

        // 构建URL
        var apiUrl = $('#endpoint').val();
        var method = window.yxsApiSettings.method || $('#method').val();
        if (method === 'GET' && Object.keys(params).length > 0) {
            apiUrl = apiUrl + '?' + $.param(params);
        }

        console.log('[API Debug] 发送请求到:', apiUrl);
        
        // 显示加载状态
        $('.submit-btn').prop('disabled', true).text('请求中...');
        $('.response-area').show();
        $('#response-content').html('Loading...');

        // 记录开始时间
        var startTime = new Date();

        // 获取API端点和方法
        var endpoint = window.yxsApiSettings.endpoint || $('#endpoint').val();
        var apiUrlFinal = window.yxsApiSettings.apiUrl + endpoint;

        // 验证端点和方法
        if (!endpoint) {
            showError('API端点未设置');
            $('.submit-btn').prop('disabled', false).text('发送请求');
            return;
        }

        if (!method) {
            showError('请求方法未设置');
            $('.submit-btn').prop('disabled', false).text('发送请求');
            return;
        }

        debugLog('准备发送请求', {
            url: apiUrlFinal,
            method: method,
            params: params,
            settings: window.yxsApiSettings
        });

        // 发送请求
        $.ajax({
            url: method === 'GET' ? apiUrlFinal + '?' + $.param(params) : apiUrlFinal,
            method: method,
            headers: {
                'X-API-Key': $('#api-key').val()
            },
            data: method === 'GET' ? null : JSON.stringify(params),
            processData: method === 'GET',
            contentType: method === 'GET' ? 'application/x-www-form-urlencoded' : 'application/json',
            success: function(response, status, xhr) {
                var endTime = new Date();
                var duration = endTime - startTime;
                
                $('.response-time').text('耗时: ' + duration + 'ms');
                $('.response-status').html('<span class="success">Status: ' + xhr.status + '</span>');
                $('#response-content').html(JSON.stringify(response, null, 2));
                
                console.log('请求成功:', {
                    url: apiUrlFinal,
                    method: method,
                    params: params,
                    response: response
                });
            },
            error: function(xhr) {
                var endTime = new Date();
                var duration = endTime - startTime;
                
                $('.response-time').text('耗时: ' + duration + 'ms');
                $('.response-status').html('<span class="error">Status: ' + xhr.status + '</span>');
                
                var errorMessage = '';
                try {
                    errorMessage = JSON.stringify(xhr.responseJSON || xhr.responseText, null, 2);
                } catch(e) {
                    errorMessage = xhr.responseText || '请求失败';
                }
                $('#response-content').html(errorMessage);
                
                console.log('请求失败:', {
                    url: apiUrlFinal,
                    method: method,
                    params: params,
                    status: xhr.status,
                    response: xhr.responseText,
                    settings: window.yxsApiSettings
                });
            },
            complete: function() {
                $('.submit-btn').prop('disabled', false).text('发送请求');
            }
        });
    });

})(jQuery); 