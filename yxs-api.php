<?php
/*
Plugin Name: API管理插件
Description: API管理系统插件-为子比主题提供API接口管理和功能增强解决方案
Version: 1.0
author: 云先森
author URI: https://pinpinping.cn/
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('YXS_API_VERSION', '1.1.0');
define('YXS_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YXS_API_PLUGIN_URL', plugin_dir_url(__FILE__));

// 核心模块加载
require_once YXS_API_PLUGIN_DIR . 'includes/class-api-manager.php';
require_once YXS_API_PLUGIN_DIR . 'includes/class-statistics.php';
require_once YXS_API_PLUGIN_DIR . 'admin/class-admin-panel.php';
require_once YXS_API_PLUGIN_DIR . 'public/class-api-endpoints.php';

// 添加重写规则
add_action('init', 'yxs_api_add_rewrite_rules');
function yxs_api_add_rewrite_rules() {
    add_rewrite_rule(
        '^([0-9]+)\.html$',
        'index.php?p=$matches[1]',
        'top'
    );
}

// 激活插件时的操作
register_activation_hook(__FILE__, 'yxs_api_activate');
function yxs_api_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // API密钥表
    $table_name = $wpdb->prefix . 'yxs_api_keys';
    $sql1 = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        api_key varchar(64) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_access datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY api_key (api_key)
    ) $charset_collate;";

    // API调用记录表
    $table_name = $wpdb->prefix . 'yxs_api_logs';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        api_key varchar(64) NOT NULL,
        endpoint varchar(255) NOT NULL,
        method varchar(10) NOT NULL,
        request_data text,
        response_code int(11),
        response_data text,
        ip_address varchar(45),
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // 分别执行每个表的创建并记录结果
    $result1 = dbDelta($sql1);
    $result2 = dbDelta($sql2);
    
    // 记录调试信息
    error_log('API Keys表创建结果: ' . print_r($result1, true));
    error_log('API Logs表创建结果: ' . print_r($result2, true));
    
    // 验证表是否存在
    $keys_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}yxs_api_keys'") === $wpdb->prefix . 'yxs_api_keys';
    $logs_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}yxs_api_logs'") === $wpdb->prefix . 'yxs_api_logs';
    
    error_log('API Keys表是否存在: ' . ($keys_table_exists ? 'true' : 'false'));
    error_log('API Logs表是否存在: ' . ($logs_table_exists ? 'true' : 'false'));
    
    // 注册文章类型
    $api_manager = new Yxs_API_Manager();
    $api_manager->register_post_type();
    
    // 添加重写规则
    yxs_api_add_rewrite_rules();
    
    // 刷新重写规则
    flush_rewrite_rules();
}

// 停用插件时的操作
register_deactivation_hook(__FILE__, 'yxs_api_deactivate');
function yxs_api_deactivate() {
    // 刷新重写规则
    flush_rewrite_rules();
}

// 加载插件文本域
function yxs_api_load_textdomain() {
    load_plugin_textdomain('yxs-api', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'yxs_api_load_textdomain');

// 加载前端资源
function yxs_api_enqueue_scripts() {
    if (is_singular('yxs_api')) {
        wp_enqueue_style('yxs-api', YXS_API_PLUGIN_URL . 'public/css/style.css', array(), YXS_API_VERSION);
        wp_enqueue_style('yxs-api-detail', YXS_API_PLUGIN_URL . 'public/css/api-detail.css', array('yxs-api'), YXS_API_VERSION);
        // 替换本地 clipboard → bootcdn 2.0.11
        wp_enqueue_script('clipboard','https://cdn.bootcdn.net/ajax/libs/clipboard.js/2.0.11/clipboard.min.js', array(), '2.0.11', true);
        wp_enqueue_script('yxs-api', YXS_API_PLUGIN_URL . 'public/js/script.js', array('jquery', 'clipboard'), YXS_API_VERSION, true);
        
        wp_enqueue_script('api-docs', YXS_API_PLUGIN_URL . 'public/js/api-docs.js', array('jquery'), YXS_API_VERSION, true);
        
        // 获取当前 API 详情
        $post_id = get_the_ID();
        $endpoint = get_post_meta($post_id, 'endpoint', true);
        $method = get_post_meta($post_id, 'method', true);
        
        wp_localize_script('yxs-api', 'yxsApiSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rtrim(rest_url('yxs/v1'), '/'),
            'endpoint' => $endpoint,
            'method' => $method
        ));
    }
}
add_action('wp_enqueue_scripts', 'yxs_api_enqueue_scripts');

// 添加页面模板
add_filter('theme_page_templates', 'yxs_api_add_template');
add_filter('template_include', 'yxs_api_load_template');

function yxs_api_add_template($templates) {
    $templates['api-list.php'] = 'API接口列表';
    return $templates;
}

function yxs_api_load_template($template) {
    if (is_page_template('api-list.php')) {
        $new_template = YXS_API_PLUGIN_DIR . 'public/templates/api-list.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
} 