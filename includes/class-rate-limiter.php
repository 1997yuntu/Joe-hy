<?php
/**
 * API访问频率限制类
 */
class Yxs_API_Rate_Limiter {
    private static $instance = null;
    private $redis_client = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 初始化Redis连接（如果可用）
        if (class_exists('Redis')) {
            try {
                $this->redis_client = new Redis();
                $this->redis_client->connect('127.0.0.1', 6379);
            } catch (Exception $e) {
                error_log('Redis连接失败: ' . $e->getMessage());
            }
        }
        
        add_action('rest_api_init', array($this, 'register_rate_limit_headers'));
    }
    
    public function register_rate_limit_headers() {
        add_filter('rest_pre_dispatch', array($this, 'check_rate_limit'), 10, 3);
    }
    
    public function check_rate_limit($response, $handler, $request) {
        if (!strpos($request->get_route(), '/yxs-api/')) {
            return $response;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '需要登录才能访问API', array('status' => 401));
        }
        
        // 获取用户VIP等级
        $vip_level = zib_get_user_vip_level($user_id);
        
        // 根据VIP等级设置不同的限制
        $limits = $this->get_rate_limits($vip_level);
        
        // 检查频率限制
        $key = 'yxs_api_rate_' . $user_id;
        $current_requests = $this->get_request_count($key);
        
        if ($current_requests >= $limits['requests']) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf('已超过API访问限制，请等待%d秒后重试', $this->get_reset_time($key)),
                array('status' => 429)
            );
        }
        
        // 增加请求计数
        $this->increment_request_count($key, $limits['window']);
        
        // 添加速率限制头部
        add_filter('rest_post_dispatch', function($response) use ($limits, $current_requests) {
            if ($response instanceof WP_REST_Response) {
                $response->header('X-RateLimit-Limit', $limits['requests']);
                $response->header('X-RateLimit-Remaining', max(0, $limits['requests'] - $current_requests - 1));
                $response->header('X-RateLimit-Reset', $this->get_reset_time($key));
            }
            return $response;
        });
        
        return $response;
    }
    
    private function get_rate_limits($vip_level) {
        // 根据VIP等级返回不同的限制
        $limits = array(
            0 => array('requests' => 60, 'window' => 3600),    // 普通用户：60次/小时
            1 => array('requests' => 300, 'window' => 3600),   // VIP1：300次/小时
            2 => array('requests' => 1000, 'window' => 3600),  // VIP2：1000次/小时
            3 => array('requests' => 3000, 'window' => 3600),  // VIP3：3000次/小时
        );
        
        return isset($limits[$vip_level]) ? $limits[$vip_level] : $limits[0];
    }
    
    private function get_request_count($key) {
        if ($this->redis_client) {
            return (int)$this->redis_client->get($key) ?: 0;
        }
        
        return (int)get_transient($key) ?: 0;
    }
    
    private function increment_request_count($key, $window) {
        if ($this->redis_client) {
            $this->redis_client->incr($key);
            $this->redis_client->expire($key, $window);
        } else {
            $count = (int)get_transient($key) ?: 0;
            set_transient($key, $count + 1, $window);
        }
    }
    
    private function get_reset_time($key) {
        if ($this->redis_client) {
            $ttl = $this->redis_client->ttl($key);
            return $ttl > 0 ? time() + $ttl : time();
        }
        
        $transient_timeout = get_option('_transient_timeout_' . $key);
        return $transient_timeout ? (int)$transient_timeout : time();
    }
    
    public function get_user_usage($user_id) {
        $key = 'yxs_api_rate_' . $user_id;
        $vip_level = zib_get_user_vip_level($user_id);
        $limits = $this->get_rate_limits($vip_level);
        
        return array(
            'current' => $this->get_request_count($key),
            'limit' => $limits['requests'],
            'reset' => $this->get_reset_time($key),
            'window' => $limits['window']
        );
    }
}

// 初始化频率限制
Yxs_API_Rate_Limiter::get_instance(); 