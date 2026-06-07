<?php
class Yxs_API_DB {
    // 与子比用户表关联
    private $user_table;
    
    public function __construct() {
        global $wpdb;
        $this->user_table = $wpdb->prefix . 'zibll_users'; // 子比用户表
        
        // API配置表
        $this->apis_table = $wpdb->prefix . 'yxs_apis';
        // 日志表（按月分表）
        $this->logs_table = $wpdb->prefix . 'yxs_logs_' . date('Ym');
    }
} 