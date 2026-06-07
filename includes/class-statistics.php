<?php
/**
 * API统计类
 */
class Yxs_API_Statistics {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 初始化钩子
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    public function register_rest_routes() {
        register_rest_route('yxs-api/v1', '/statistics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_statistics'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }
    
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    public function get_statistics($request) {
        global $wpdb;
        
        // 获取时间范围参数
        $range = $request->get_param('range') ?: 'week';
        $end_date = current_time('mysql');
        
        switch ($range) {
            case 'day':
                $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
                $group_by = 'DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00")';
                break;
            case 'week':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                $group_by = 'DATE(created_at)';
                break;
            case 'month':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                $group_by = 'DATE(created_at)';
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                $group_by = 'DATE(created_at)';
        }
        
        // 获取API调用统计
        $logs_table = $wpdb->prefix . 'yxs_api_logs';
        
        // 总调用次数
        $total_calls = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $logs_table
            WHERE created_at BETWEEN '$start_date' AND '$end_date'
        ");
        
        // 调用成功率
        $success_rate = $wpdb->get_var("
            SELECT (
                COUNT(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 END) * 100.0 / COUNT(*)
            )
            FROM $logs_table
            WHERE created_at BETWEEN '$start_date' AND '$end_date'
        ");
        
        // 按时间统计的调用趋势
        $trends = $wpdb->get_results("
            SELECT 
                $group_by as date,
                COUNT(*) as total,
                COUNT(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 END) as success
            FROM $logs_table
            WHERE created_at BETWEEN '$start_date' AND '$end_date'
            GROUP BY $group_by
            ORDER BY date ASC
        ");
        
        // 最常用的API端点
        $popular_endpoints = $wpdb->get_results("
            SELECT 
                endpoint,
                COUNT(*) as calls
            FROM $logs_table
            WHERE created_at BETWEEN '$start_date' AND '$end_date'
            GROUP BY endpoint
            ORDER BY calls DESC
            LIMIT 10
        ");
        
        // 错误率最高的API
        $error_prone_endpoints = $wpdb->get_results("
            SELECT 
                endpoint,
                COUNT(*) as total_calls,
                COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_calls,
                (COUNT(CASE WHEN response_code >= 400 THEN 1 END) * 100.0 / COUNT(*)) as error_rate
            FROM $logs_table
            WHERE created_at BETWEEN '$start_date' AND '$end_date'
            GROUP BY endpoint
            HAVING total_calls > 10
            ORDER BY error_rate DESC
            LIMIT 5
        ");
        
        return array(
            'total_calls' => (int)$total_calls,
            'success_rate' => round($success_rate, 2),
            'trends' => array_map(function($item) {
                return array(
                    'date' => $item->date,
                    'total' => (int)$item->total,
                    'success' => (int)$item->success
                );
            }, $trends),
            'popular_endpoints' => array_map(function($item) {
                return array(
                    'endpoint' => $item->endpoint,
                    'calls' => (int)$item->calls
                );
            }, $popular_endpoints),
            'error_prone_endpoints' => array_map(function($item) {
                return array(
                    'endpoint' => $item->endpoint,
                    'total_calls' => (int)$item->total_calls,
                    'error_calls' => (int)$item->error_calls,
                    'error_rate' => round($item->error_rate, 2)
                );
            }, $error_prone_endpoints)
        );
    }
    
    public function log_api_call($api_key, $endpoint, $method, $request_data, $response_code, $response_data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'yxs_api_logs',
            array(
                'api_key' => $api_key,
                'endpoint' => $endpoint,
                'method' => $method,
                'request_data' => is_array($request_data) ? json_encode($request_data) : $request_data,
                'response_code' => $response_code,
                'response_data' => is_array($response_data) ? json_encode($response_data) : $response_data,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array(
                '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            )
        );
    }
}

// 初始化统计类
Yxs_API_Statistics::get_instance(); 