<?php
if (!defined('ABSPATH')) {
    exit;
}

// 获取API信息
$api_id = get_the_ID();
$endpoint = get_post_meta($api_id, 'api_endpoint', true);
$method = get_post_meta($api_id, 'api_method', true);
$parameters = get_post_meta($api_id, 'api_parameters', true);
$response_example = get_post_meta($api_id, 'response_example', true);
$price = get_post_meta($api_id, 'api_price', true);
$required_vip = get_post_meta($api_id, 'required_vip_level', true);

// 检查用户权限
$user_id = get_current_user_id();
$user_vip_level = zib_get_user_vip_level($user_id);
$has_access = Yxs_API_Auth::get_instance()->check_api_access($api_id, $user_id);
?>
get_header();
<div class="zib-api-docs">
    <?php if (!$user_id): ?>
    <!-- 未登录提示 -->
    <div class="need-login">
        <p>请先登录后查看API详情</p>
        <button class="btn btn-primary" onclick="zib_login.show()">
            <i class="fa fa-sign-in"></i> 登录
        </button>
    </div>
    
    <?php elseif (is_wp_error($has_access)): ?>
    <!-- 无访问权限提示 -->
    <div class="access-denied">
        <p><?php echo $has_access->get_error_message(); ?></p>
        <?php if ($price > 0): ?>
        <button class="btn btn-primary purchase-api" data-api-id="<?php echo $api_id; ?>" data-price="<?php echo $price; ?>">
            <i class="fa fa-shopping-cart"></i> 购买API (<?php echo $price; ?>元)
        </button>
        <?php endif; ?>
        
        <?php if ($required_vip > $user_vip_level): ?>
        <a href="<?php echo zib_get_user_center_url('vip'); ?>" class="btn btn-primary">
            <i class="fa fa-crown"></i> 升级到VIP<?php echo $required_vip; ?>
        </a>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    <!-- API详情 -->
    <div class="api-card">
        <div class="api-card-header">
            <span class="api-method <?php echo strtolower($method); ?>"><?php echo $method; ?></span>
            <h1><?php the_title(); ?></h1>
        </div>
        
        <div class="api-content">
            <!-- API描述 -->
            <div class="api-description">
                <?php the_content(); ?>
            </div>
            
            <!-- 端点URL -->
            <h2>接口地址</h2>
            <div class="endpoint-box">
                <code><?php echo esc_html($endpoint); ?></code>
                <button class="copy-btn" title="复制">
                    <i class="fa fa-copy"></i>
                </button>
            </div>
            
            <!-- 请求参数 -->
            <?php if ($parameters && is_array($parameters)): ?>
            <h2>请求参数</h2>
            <table class="parameter-table">
                <thead>
                    <tr>
                        <th>参数名</th>
                        <th>类型</th>
                        <th>必填</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parameters as $param): ?>
                    <tr>
                        <td><code><?php echo esc_html($param['name']); ?></code></td>
                        <td><?php echo esc_html($param['type']); ?></td>
                        <td><?php echo $param['required'] ? '是' : '否'; ?></td>
                        <td><?php echo esc_html($param['description']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <!-- 响应示例 -->
            <?php if ($response_example): ?>
            <h2>响应示例</h2>
            <div class="response-example">
                <pre><code class="language-json"><?php echo esc_html($response_example); ?></code></pre>
            </div>
            <?php endif; ?>
            
            <!-- 在线测试 -->
            <h2>在线测试</h2>
            <form class="test-form" data-endpoint="<?php echo esc_attr($endpoint); ?>" data-method="<?php echo esc_attr($method); ?>">
                <?php if (in_array($method, array('POST', 'PUT'))): ?>
                <div class="form-group">
                    <label>请求体格式</label>
                    <select class="body-format-switch">
                        <option value="form">Form Data</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- 参数输入区 -->
                <div class="form-inputs">
                    <?php if ($parameters && is_array($parameters)): ?>
                        <?php foreach ($parameters as $param): ?>
                        <div class="form-group">
                            <label>
                                <?php echo esc_html($param['name']); ?>
                                <?php if ($param['required']): ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="text" 
                                   name="<?php echo esc_attr($param['name']); ?>"
                                   <?php echo $param['required'] ? 'required' : ''; ?>
                                   placeholder="<?php echo esc_attr($param['description']); ?>">
                            <div class="error-message"></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- JSON输入区 -->
                <?php if (in_array($method, array('POST', 'PUT'))): ?>
                <div class="json-input" style="display: none;">
                    <div class="form-group">
                        <label>JSON数据</label>
                        <textarea id="json-editor"></textarea>
                        <div class="error-message"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> 发送请求
                </button>
            </form>
            
            <!-- 测试结果 -->
            <div class="test-result"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// 初始化代码高亮
if(window.Prism) {
    Prism.highlightAll();
}
</script> 
<?php get_footer(); ?>