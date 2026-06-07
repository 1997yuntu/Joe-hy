<?php
/**
 * API认证系统类
 */
class Yxs_API_Auth {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_authentication_errors', array($this, 'authenticate_request'));
        add_action('init', array($this, 'register_token_endpoint'));
    }
    
    public function register_token_endpoint() {
        add_action('rest_api_init', function() {
            register_rest_route('yxs-api/v1', '/token', array(
                'methods' => 'POST',
                'callback' => array($this, 'generate_token'),
                'permission_callback' => '__return_true'
            ));
        });
    }
    
    public function generate_token($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', '用户名或密码错误', array('status' => 401));
        }
        
        $token = array(
            'user_id' => $user->ID,
            'exp' => time() + (7 * DAY_IN_SECONDS)
        );
        
        $jwt = $this->generate_jwt($token);
        
        return array(
            'token' => $jwt,
            'user_id' => $user->ID,
            'exp' => $token['exp']
        );
    }
    
    private function generate_jwt($payload) {
        $header = json_encode(array(
            'typ' => 'JWT',
            'alg' => 'HS256'
        ));
        
        $payload = json_encode($payload);
        
        $base64_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $secret = defined('YXS_API_SECRET') ? YXS_API_SECRET : wp_salt('auth');
        
        $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, $secret, true);
        $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }
    
    public function authenticate_request($error) {
        // 如果已经有错误或者不是API请求，直接返回
        if ($error || !strpos($_SERVER['REQUEST_URI'], '/yxs-api/')) {
            return $error;
        }
        
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
        
        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error('invalid_token', '无效的认证令牌', array('status' => 401));
        }
        
        $token = substr($auth_header, 7);
        $is_valid = $this->validate_token($token);
        
        if (is_wp_error($is_valid)) {
            return $is_valid;
        }
        
        return true;
    }
    
    private function validate_token($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return new WP_Error('invalid_token', '无效的令牌格式', array('status' => 401));
        }
        
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        
        if (!$header || !$payload) {
            return new WP_Error('invalid_token', '无效的令牌内容', array('status' => 401));
        }
        
        // 检查令牌是否过期
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return new WP_Error('token_expired', '令牌已过期', array('status' => 401));
        }
        
        // 验证签名
        $secret = defined('YXS_API_SECRET') ? YXS_API_SECRET : wp_salt('auth');
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true);
        $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64_signature !== $parts[2]) {
            return new WP_Error('invalid_signature', '无效的令牌签名', array('status' => 401));
        }
        
        return true;
    }
    
    public function check_api_access($api_id, $user_id) {
        // 检查用户VIP等级
        $required_vip_level = get_post_meta($api_id, 'required_vip_level', true);
        $user_vip_level = zib_get_user_vip_level($user_id);
        
        if ($user_vip_level < $required_vip_level) {
            return new WP_Error('insufficient_vip_level', '您的会员等级不足', array('status' => 403));
        }
        
        // 检查API购买状态
        $has_purchased = $this->check_api_purchase($api_id, $user_id);
        if (!$has_purchased) {
            return new WP_Error('api_not_purchased', '请先购买该API访问权限', array('status' => 403));
        }
        
        return true;
    }
    
    private function check_api_purchase($api_id, $user_id) {
        // 检查是否已购买API访问权限
        $purchase_history = get_user_meta($user_id, 'yxs_api_purchases', true);
        if (!$purchase_history) {
            return false;
        }
        
        return isset($purchase_history[$api_id]) && $purchase_history[$api_id] > time();
    }
}

// 初始化认证系统
Yxs_API_Auth::get_instance(); 