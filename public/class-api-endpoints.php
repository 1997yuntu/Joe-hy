<?php
/**
 * API端点处理类
 */
class Yxs_API_Endpoints {
    private $api_manager;

    /**
     * 构造函数
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
        $this->api_manager = new Yxs_API_Manager();
    }

    /**
     * 注册API端点
     */
    public function register_endpoints() {
        // 注册测试端点
        register_rest_route('yxs/v1', '/test', array(
            'methods' => 'GET',
            'callback' => array($this, 'test_endpoint'),
            'permission_callback' => array($this, 'check_permission')
        ));

        // 注册通用API处理端点
        register_rest_route('yxs/v1', '/(?P<endpoint>[a-zA-Z0-9-_/]+)', array(
            'methods' => array('GET', 'POST', 'PUT', 'DELETE'),
            'callback' => array($this, 'handle_api_request'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }

    /**
     * 测试端点
     */
    public function test_endpoint($request) {
        return array(
            'status' => 'success',
            'message' => '测试API端点响应成功',
            'data' => array(
                'time' => current_time('mysql'),
                'method' => $request->get_method(),
                'params' => $request->get_params()
            )
        );
    }

    /**
     * 处理API请求
     */
    public function handle_api_request($request) {
        $endpoint = '/' . $request->get_param('endpoint');
        $method = $request->get_method();
        $params = $request->get_params();
        
        // 添加调试日志
        error_log('收到API请求：' . $endpoint);
        error_log('请求方法：' . $method);
        error_log('请求参数：' . print_r($params, true));
        
        // 查找对应的API文章
        $api_post = get_posts(array(
            'post_type' => 'yxs_api',
            'meta_key' => 'endpoint',
            'meta_value' => $endpoint,
            'posts_per_page' => 1
        ));

        if (empty($api_post)) {
            error_log('未找到API：' . $endpoint);
            return new WP_Error('api_not_found', '未找到该API', array('status' => 404));
        }

        $api_post = $api_post[0];
        $api_method = get_post_meta($api_post->ID, 'method', true);

        // 检查请求方法是否匹配
        if ($api_method !== $method) {
            error_log('请求方法不匹配：期望 ' . $api_method . '，实际 ' . $method);
            return new WP_Error('method_not_allowed', '不支持的请求方法', array('status' => 405));
        }

        // 验证必需参数
        if ($method === 'GET' && $endpoint === '/certification') {
            if (empty($params['realName']) || empty($params['cardNo'])) {
                error_log('缺少必需参数');
                return new WP_Error('missing_params', '缺少必需参数：realName 和 cardNo', array('status' => 400));
            }
        }

        // 尝试加载API实现文件
        $api_file = YXS_API_PLUGIN_DIR . 'apimod/' . sanitize_file_name(str_replace('/', '-', ltrim($endpoint, '/'))) . '.php';
        
        // 添加调试信息
        error_log('API文件路径: ' . $api_file);
        error_log('API端点: ' . $endpoint);
        error_log('请求方法: ' . $method);
        
        if (!file_exists($api_file)) {
            error_log('API文件不存在: ' . $api_file);
            return new WP_Error('api_not_implemented', 'API未实现', array('status' => 501));
        }

        require_once $api_file;
        $api_class = ucfirst(str_replace('-', '_', ltrim($endpoint, '/'))) . '_API';
        
        error_log('API类名: ' . $api_class);
        
        if (!class_exists($api_class)) {
            error_log('API类不存在: ' . $api_class);
            return new WP_Error('api_class_not_found', 'API类未找到', array('status' => 501));
        }

        // 实例化API类并处理请求
        $api = new $api_class();
        $response_data = $api->handle_request($request);
        
        // 记录API调用
        //优先从请求头获取密钥，取不到则读取URL api_key/yxs_key参数
        $api_key = $request->get_header('X-API-Key');
        if(empty($api_key)){
        $api_key = $request->get_param('api_key') ?? $request->get_param('yxs_key') ?? '';
          }
        $request_data = $request->get_params();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_logs';
        $wpdb->insert(
            $table_name,
            array(
                'api_key' => $api_key,
                'endpoint' => $endpoint,
                'method' => $method,
                'request_data' => json_encode($request_data),
                'response_code' => 200,
                'response_data' => json_encode($response_data),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        return $response_data;
    }

    /**
     * 权限检查
     */
    public function check_permission($request) {
    //优先取Header，取不到再读取URL参数
    $api_key = $request->get_header('X-API-Key');
    //兼容URL ?api_key=xxx / ?yxs_key=xxx
    if(empty($api_key)){
        $api_key = $request->get_param('api_key') ?? $request->get_param('yxs_key') ?? '';
    }
    if (!$api_key) {
        return new WP_Error('no_api_key', '缺少API密钥', array('status' => 401));
    }
    // 验证API密钥
    if (!$this->api_manager->validate_api_key($api_key)) {
        return new WP_Error('invalid_api_key', 'API密钥无效或已禁用', array('status' => 401));
    }
    return true;
}
}
// 初始化类
new Yxs_API_Endpoints();