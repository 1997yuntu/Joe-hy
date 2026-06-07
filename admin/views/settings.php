<?php
/**
 * 基础设置视图文件 (仅在非 CSF 模式下使用)
 * 如果使用子比主题 CSF 框架，设置将在 yxs-api 菜单的"API 基础设置"标签页中管理
 */
if (!defined('ABSPATH')) {
    exit;
}

if ( class_exists( 'CSF' ) ) {
    echo '<div class="wrap">';
    echo '<h1>提示</h1>';
    echo '<div class="notice notice-info"><p>您已启用子比主题 CSF 框架，所有设置请在 <strong>网亿 API</strong> 菜单中进行管理。</p></div>';
    echo '</div>';
    return;
}

$settings = get_option( 'yxs_api_settings', array() );
$redis_settings = isset($settings['redis']) ? $settings['redis'] : array();
$rate_limits = isset($settings['rate_limits']) ? $settings['rate_limits'] : array();
?>
<div class="wrap yxs-api-wrap">
    <h1>API设置</h1>
    
    <div class="zib-admin-box">
        <form id="yxs-api-settings" method="post">
            <!-- Redis设置 -->
            <div class="settings-section">
                <h2>Redis设置</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">启用Redis</th>
                        <td>
                            <label>
                                <input type="checkbox" name="settings[redis][enable]" value="1" 
                                    <?php checked(isset($redis_settings['enable']) && $redis_settings['enable']); ?>>
                                启用Redis缓存和速率限制
                            </label>
                            <p class="description">推荐启用Redis以获得更好的性能</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redis主机</th>
                        <td>
                            <input type="text" name="settings[redis][host]" value="<?php echo esc_attr($redis_settings['host'] ?? '127.0.0.1'); ?>" class="regular-text">
                            <p class="description">Redis服务器地址</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redis端口</th>
                        <td>
                            <input type="number" name="settings[redis][port]" value="<?php echo esc_attr($redis_settings['port'] ?? '6379'); ?>" class="small-text">
                            <p class="description">Redis服务器端口</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- API访问限制 -->
            <div class="settings-section">
                <h2>API访问限制</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">普通用户限制</th>
                        <td>
                            <input type="number" name="settings[rate_limits][0][requests]" value="<?php echo esc_attr($rate_limits[0]['requests'] ?? '60'); ?>" class="small-text">
                            次/
                            <input type="number" name="settings[rate_limits][0][window]" value="<?php echo esc_attr($rate_limits[0]['window'] ?? '3600'); ?>" class="small-text">
                            秒
                            <p class="description">普通用户的API调用限制</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">VIP1限制</th>
                        <td>
                            <input type="number" name="settings[rate_limits][1][requests]" value="<?php echo esc_attr($rate_limits[1]['requests'] ?? '300'); ?>" class="small-text">
                            次/
                            <input type="number" name="settings[rate_limits][1][window]" value="<?php echo esc_attr($rate_limits[1]['window'] ?? '3600'); ?>" class="small-text">
                            秒
                            <p class="description">VIP1用户的API调用限制</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">VIP2限制</th>
                        <td>
                            <input type="number" name="settings[rate_limits][2][requests]" value="<?php echo esc_attr($rate_limits[2]['requests'] ?? '1000'); ?>" class="small-text">
                            次/
                            <input type="number" name="settings[rate_limits][2][window]" value="<?php echo esc_attr($rate_limits[2]['window'] ?? '3600'); ?>" class="small-text">
                            秒
                            <p class="description">VIP2用户的API调用限制</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">VIP3限制</th>
                        <td>
                            <input type="number" name="settings[rate_limits][3][requests]" value="<?php echo esc_attr($rate_limits[3]['requests'] ?? '3000'); ?>" class="small-text">
                            次/
                            <input type="number" name="settings[rate_limits][3][window]" value="<?php echo esc_attr($rate_limits[3]['window'] ?? '3600'); ?>" class="small-text">
                            秒
                            <p class="description">VIP3用户的API调用限制</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- 安全设置 -->
            <div class="settings-section">
                <h2>安全设置</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">API密钥</th>
                        <td>
                            <input type="text" id="api-key" value="<?php echo esc_attr(get_option('yxs_api_key')); ?>" class="regular-text" readonly>
                            <button type="button" class="button" id="generate-api-key">生成新密钥</button>
                            <p class="description">用于API签名验证的密钥</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Token有效期</th>
                        <td>
                            <input type="number" name="settings[security][token_expiry]" value="<?php echo esc_attr($settings['security']['token_expiry'] ?? '7'); ?>" class="small-text">
                            天
                            <p class="description">API访问令牌的有效期</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">IP白名单</th>
                        <td>
                            <textarea name="settings[security][ip_whitelist]" rows="5" class="large-text code"><?php echo esc_textarea($settings['security']['ip_whitelist'] ?? ''); ?></textarea>
                            <p class="description">每行一个IP地址，支持CIDR格式</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- 支付设置 -->
            <div class="settings-section">
                <h2>支付设置</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">启用API计费</th>
                        <td>
                            <label>
                                <input type="checkbox" name="settings[payment][enable]" value="1" 
                                    <?php checked(isset($settings['payment']['enable']) && $settings['payment']['enable']); ?>>
                                启用API调用计费功能
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">默认价格</th>
                        <td>
                            <input type="number" name="settings[payment][default_price]" value="<?php echo esc_attr($settings['payment']['default_price'] ?? '0.01'); ?>" class="small-text" step="0.01">
                            元/次
                            <p class="description">未单独设置价格的API的默认调用价格</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">VIP折扣</th>
                        <td>
                            VIP1: <input type="number" name="settings[payment][vip1_discount]" value="<?php echo esc_attr($settings['payment']['vip1_discount'] ?? '90'); ?>" class="small-text" min="0" max="100">%
                            VIP2: <input type="number" name="settings[payment][vip2_discount]" value="<?php echo esc_attr($settings['payment']['vip2_discount'] ?? '80'); ?>" class="small-text" min="0" max="100">%
                            VIP3: <input type="number" name="settings[payment][vip3_discount]" value="<?php echo esc_attr($settings['payment']['vip3_discount'] ?? '70'); ?>" class="small-text" min="0" max="100">%
                            <p class="description">不同VIP等级的API调用价格折扣</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-primary">保存设置</button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </p>
        </form>
    </div>
</div>

<style>
.settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    padding: 20px;
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
}

.spinner.is-active {
    visibility: visible;
}
</style>

<script>
jQuery(document).ready(function($) {
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
    
    // 表单提交
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
});
</script> 