<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>API调用记录</h1>
    <div class="yxs-api-container">
        <!-- 筛选器 -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="filter_status" id="filter-status">
                    <option value="">所有状态</option>
                    <option value="success">成功</option>
                    <option value="error">错误</option>
                </select>
                <select name="filter_method" id="filter-method">
                    <option value="">所有方法</option>
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>
                <input type="submit" class="button" value="筛选">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo count($logs); ?> 个项目</span>
            </div>
        </div>
        <!-- 日志表格 -->
        <?php if (!empty($logs)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>用户</th>
                        <th>API密钥</th>
                        <th>接口名称</th>
                        <th>请求方法</th>
                        <th>状态码</th>
                        <th>IP地址</th>
                        <th>详情</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        // ==========修复user_id未定义警告==========
                        $user_info = '-';
                        if (isset($log->user_id) && !empty($log->user_id)) {
                            $user = get_userdata($log->user_id);
                            if ($user) {
                                $user_info = $user->user_login;
                            }
                        }
                        
                        // 获取API名称
                        $api_name = '-';
                        $api_post = get_posts(array(
                            'post_type' => 'yxs_api',
                            'meta_key' => 'endpoint',
                            'meta_value' => $log->endpoint ?? '',
                            'posts_per_page' => 1
                        ));
                        if (!empty($api_post)) {
                            $api_name = $api_post[0]->post_title;
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at ?? ''); ?></td>
                            <td><?php echo esc_html($user_info); ?></td>
                            <td>
                                <code class="api-key"><?php echo esc_html($log->api_key ?? ''); ?></code>
                                <button class="copy-btn" data-clipboard-text="<?php echo esc_attr($log->api_key ?? ''); ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td>
                                <?php echo esc_html($api_name); ?>
                                <div class="row-actions">
                                    <span class="endpoint">
                                        <small><?php echo esc_html($log->endpoint ?? ''); ?></small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="api-method <?php echo strtolower($log->method ?? ''); ?>">
                                    <?php echo esc_html($log->method ?? ''); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-code <?php echo ((($log->response_code ?? 500) < 400) ? 'success' : 'error'); ?>">
                                    <?php echo esc_html($log->response_code ?? ''); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->ip_address ?? ''); ?></td>
                            <td>
                                <button class="button view-details" data-log-id="<?php echo esc_attr($log->id ?? ''); ?>">
                                    查看详情
                                </button>
                                <!-- =========新增隐藏域：存请求、响应数据（解决JS取不到值）========= -->
                                <input type="hidden" class="request-data" value="<?php echo esc_attr($log->request_data ?? ''); ?>">
                                <input type="hidden" class="response-data" value="<?php echo esc_attr($log->response_data ?? ''); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="no-items">
                <p>暂无调用记录</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- 详情模态框 -->
<div id="log-details-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>调用详情</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="details-section">
                <h3>请求数据</h3>
                <pre id="request-data"></pre>
            </div>
            <div class="details-section">
                <h3>响应数据</h3>
                <pre id="response-data"></pre>
            </div>
        </div>
    </div>
</div>
<style>
.api-method {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.api-method.get {
    background: #e3f2fd;
    color: #1976d2;
}
.api-method.post {
    background: #e8f5e9;
    color: #388e3c;
}
.api-method.put {
    background: #fff3e0;
    color: #f57c00;
}
.api-method.delete {
    background: #ffebee;
    color: #d32f2f;
}
.status-code {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}
.status-code.success {
    background: #e8f5e9;
    color: #388e3c;
}
.status-code.error {
    background: #ffebee;
    color: #d32f2f;
}
.api-key {
    font-family: monospace;
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    margin-right: 5px;
}
.copy-btn {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 0;
    font-size: 14px;
}
.copy-btn:hover {
    color: #333;
}
.endpoint small {
    color: #666;
    font-family: monospace;
}
/* 模态框样式 */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
    font-size: 1.5em;
}
.close {
    font-size: 28px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
}
.close:hover {
    color: #000;
}
.modal-body {
    padding: 20px;
}
.details-section {
    margin-bottom: 20px;
}
.details-section h3 {
    margin: 0 0 10px 0;
    color: #666;
}
pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
    margin: 0;
    font-family: monospace;
}
</style>
<!-- 关键修复1：前置引入ClipboardJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // 初始化复制，增加判断防止找不到按钮报错
    if($('.copy-btn').length > 0){
        new ClipboardJS('.copy-btn');
    }
    
    // 查看详情
    $('.view-details').on('click', function() {
        var $row = $(this).closest('tr');
        // 读取隐藏域里的请求、响应数据
        let requestData = $row.find('.request-data').val();
        let responseData = $row.find('.response-data').val();
        
        // JSON格式化，捕获异常避免报错弹窗卡死
        try {
            requestData = JSON.stringify(JSON.parse(requestData), null, 2);
        } catch (e) {}
        try {
            responseData = JSON.stringify(JSON.parse(responseData), null, 2);
        } catch (e) {}
        
        $('#request-data').text(requestData || '无数据');
        $('#response-data').text(responseData || '无数据');
        $('#log-details-modal').show();
    });
    
    // 关闭模态框
    $('.close').on('click', function() {
        $('#log-details-modal').hide();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).is('#log-details-modal')) {
            $('#log-details-modal').hide();
        }
    });
    
    // 前端筛选
    $('#filter-status, #filter-method').on('change', function() {
        var status = $('#filter-status').val();
        var method = $('#filter-method').val();
        
        $('table tbody tr').each(function() {
            var $row = $(this);
            var rowStatus = $row.find('.status-code').hasClass('success') ? 'success' : 'error';
            var rowMethod = $row.find('.api-method').text().trim();
            
            let showRow = true;
            if (status && status !== rowStatus) showRow = false;
            if (method && method !== rowMethod) showRow = false;
            
            $row.toggle(showRow);
        });
        var visibleRows = $('table tbody tr:visible').length;
        $('.displaying-num').text(visibleRows + ' 个项目');
    });
});
</script>
