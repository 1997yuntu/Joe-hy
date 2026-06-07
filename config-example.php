<?php
/**
 * 子比主题 functions.php 配置示例
 * 
 * 将以下内容添加到您的子比主题 functions.php 或 site-custom.php 文件中
 * 
 * 注意：如果您使用的是子比主题，CSF 框架通常已经集成，无需手动启用
 */

// ============================================================================
// 1. 确认 CSF 框架已启用
// ============================================================================

// 子比主题默认集成了 Codestar Framework
// 如果未启用，请检查主题设置或联系主题开发者

// 检测 CSF 是否可用
if ( ! class_exists( 'CSF' ) ) {
    // CSF 未启用，可能需要检查主题设置
    add_action('admin_notices', function() {
        echo '<div class="notice notice-warning"><p>';
        echo '警告：Codestar Framework 未启用，网亿 API 插件将无法使用增强菜单功能。';
        echo '请确保您使用的是正版子比主题。';
        echo '</p></div>';
    });
}

// ============================================================================
// 2. 添加额外的 API 菜单项（可选）
// ============================================================================

if ( class_exists( 'CSF' ) ) {
    CSF::createSection( array(
        'parent'      => 'yxs-api', // 使用网亿 API 作为父菜单
        'title'       => '高级设置',
        'icon'        => 'fas fa-magic',
        'description' => 'API 高级配置选项',
        'fields'      => array(
            array(
                'id'      => 'advanced_mode',
                'type'    => 'switcher',
                'title'   => '启用高级模式',
                'default' => false,
            ),
            array(
                'id'      => 'debug_logs',
                'type'    => 'switcher',
                'title'   => '调试日志',
                'default' => false,
                'depend'  => 'advanced_mode',
            ),
        )
    ) );
}

// ============================================================================
// 3. 自定义 VIP 等级判断逻辑
// ============================================================================

/**
 * 获取用户 VIP 等级
 * 
 * @param int $user_id 用户 ID
 * @return int VIP 等级 (0=普通用户，1-3=VIP 等级)
 */
function custom_get_user_vip_level( $user_id ) {
    // 如果您使用的是子比主题，可以使用内置函数：
    // return zib_get_user_vip_level( $user_id );
    
    // 或者自定义逻辑：
    $vip_level = get_user_meta( $user_id, 'vip_level', true );
    
    return max( 0, min( 3, (int) $vip_level ) );
}

// 添加到 API 访问控制过滤器
add_filter( 'yxs_api_access_control', function( $access, $user_id ) {
    $vip_level = custom_get_user_vip_level( $user_id );
    
    // 设置不同会员等级的 API 调用频率
    $access['rate_limit'] = [
        0 => 100,   // 普通用户
        1 => 500,   // VIP1
        2 => 2000,  // VIP2
        3 => 5000   // VIP3
    ];
    
    return $access;
}, 10, 2 );

// ============================================================================
// 4. 自定义 API 权限验证
// ============================================================================

/**
 * 自定义 API 权限验证
 * 
 * @param bool $has_access 是否有访问权限
 * @param int $user_id 用户 ID
 * @param string $endpoint API 端点
 * @return bool
 */
function custom_api_permission_check( $has_access, $user_id, $endpoint ) {
    // 示例：某些 API 仅限 VIP 用户访问
    $vip_endpoints = [ '/premium-api', '/advanced-search' ];
    
    if ( in_array( $endpoint, $vip_endpoints ) ) {
        $vip_level = custom_get_user_vip_level( $user_id );
        return $vip_level >= 1; // 至少 VIP1 才能访问
    }
    
    return $has_access;
}

// 添加权限检查过滤器
add_filter( 'yxs_api_permission_check', 'custom_api_permission_check', 10, 3 );

// ============================================================================
// 5. 自定义费率和折扣
// ============================================================================

/**
 * 自定义 API 调用费率
 * 
 * @param float $price 基础价格
 * @param int $user_id 用户 ID
 * @param string $endpoint API 端点
 * @return float 最终价格
 */
function custom_api_pricing( $price, $user_id, $endpoint ) {
    $vip_level = custom_get_user_vip_level( $user_id );
    
    // VIP 折扣
    $discounts = [
        0 => 1.0,  // 普通用户无折扣
        1 => 0.9,  // VIP1 九折
        2 => 0.8,  // VIP2 八折
        3 => 0.7,  // VIP3 七折
    ];
    
    $discount = isset( $discounts[ $vip_level ] ) ? $discounts[ $vip_level ] : 1.0;
    
    return $price * $discount;
}

// 添加定价过滤器
add_filter( 'yxs_api_call_price', 'custom_api_pricing', 10, 3 );

// ============================================================================
// 6. 添加自定义管理面板统计卡片
// ============================================================================

if ( class_exists( 'CSF' ) ) {
    // 在管理面板添加自定义统计信息
    add_action( 'csf_field_yxs_api_stats_before', function() {
        global $wpdb;
        
        $total_calls = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}yxs_api_logs" );
        $total_keys = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}yxs_api_keys WHERE status='active'" );
        $today_calls = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}yxs_api_logs WHERE DATE(created_at) = CURDATE()" );
        ?>
        <div class="yxs-stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format_i18n( $total_calls ); ?></div>
                <div class="stat-label">总调用次数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format_i18n( $total_keys ); ?></div>
                <div class="stat-label">活跃密钥</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format_i18n( $today_calls ); ?></div>
                <div class="stat-label">今日调用</div>
            </div>
        </div>
        <?php
    } );
}

// ============================================================================
// 7. 定时清理日志（推荐配置）
// ============================================================================

/**
 * 设置定时任务清理旧日志
 * 保留最近 30 天的日志
 */
add_action( 'yxs_api_daily_cleanup', function() {
    global $wpdb;
    
    $days_to_keep = 30;
    $cutoff_date = date( 'Y-m-d', strtotime( "-{$days_to_keep} days" ) );
    
    $deleted = $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}yxs_api_logs WHERE created_at < %s",
        $cutoff_date
    ) );
    
    if ( $deleted ) {
        error_log( "网亿 API 清理：删除了 {$deleted} 条旧日志" );
    }
} );

// 添加定时任务
add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'yxs_api_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'yxs_api_daily_cleanup' );
    }
} );

// 停用插件时清理定时任务
register_deactivation_hook( __FILE__, function() {
    $timestamp = wp_next_scheduled( 'yxs_api_daily_cleanup' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'yxs_api_daily_cleanup' );
    }
} );

// ============================================================================
// 8. 注意事项
// ============================================================================

/*
 * 重要提示：
 * 
 * 1. 以上代码需要根据您的实际情况进行调整
 * 2. 如果使用的是子比主题，部分函数可能已经内置
 * 3. 建议将自定义代码添加到子主题的 functions.php 或 site-custom.php
 * 4. 修改前请务必备份原文件
 * 5. 测试环境验证后再部署到生产环境
 */
