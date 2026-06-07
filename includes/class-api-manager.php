<?php
/**
 * API管理器类
 */
class Yxs_API_Manager {
    /**
     * 构造函数
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('template_redirect', array($this, 'auto_generate_api_key'));
        add_filter('template_include', array($this, 'load_api_template'));
        add_filter('post_type_link', array($this, 'api_permalink'), 10, 2);
        add_action('pre_get_posts', array($this, 'modify_api_query'));
        add_action('add_meta_boxes', array($this, 'add_api_meta_boxes'));
        add_action('save_post', array($this, 'save_api_meta'));
    }

    /**
     * 初始化函数
     */
    public function init() {
        // 初始化操作（如果需要）
    }

    /**
     * 注册自定义文章类型
     */
    public function register_post_type() {
        $labels = array(
            'name'               => '网亿API',
            'singular_name'      => 'API',
            'menu_name'          => '网亿API',
            'name_admin_bar'     => 'API',
            'add_new'           => '添加API',
            'add_new_item'      => '添加新API',
            'new_item'          => '新API',
            'edit_item'         => '编辑API',
            'view_item'         => '查看API',
            'all_items'         => '所有API',
            'search_items'      => '搜索API',
            'not_found'         => '未找到API',
            'not_found_in_trash'=> '回收站中未找到API'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'query_var'         => true,
            'rewrite'           => false,
            'capability_type'   => 'post',
            'has_archive'       => true,
            'hierarchical'      => false,
            'menu_position'     => null,
            'supports'          => array('title', 'editor', 'custom-fields', 'excerpt'),
            'show_in_rest'      => true, // 启用Gutenberg编辑器
            'rest_base'         => 'yxs-api', // REST API基础路径
            'register_meta_box_cb' => array($this, 'add_api_meta_boxes') // 注册元数据框回调
        );

        register_post_type('yxs_api', $args);

        // 注册自定义字段到REST API
        register_post_meta('yxs_api', 'endpoint', array(
            'type' => 'string',
            'description' => 'API端点',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('yxs_api', 'method', array(
            'type' => 'string',
            'description' => '请求方法',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('yxs_api', 'response_format', array(
            'type' => 'string',
            'description' => '返回格式',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('yxs_api', 'request_params', array(
            'type' => 'string',
            'description' => '请求参数',
            'single' => true,
            'show_in_rest' => true
        ));

        register_post_meta('yxs_api', 'response_example', array(
            'type' => 'string',
            'description' => '返回示例',
            'single' => true,
            'show_in_rest' => true
        ));
    }

    /**
     * 注册分类法
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => 'API分类',
            'singular_name'     => 'API分类',
            'search_items'      => '搜索API分类',
            'all_items'         => '所有API分类',
            'parent_item'       => '父级API分类',
            'parent_item_colon' => '父级API分类:',
            'edit_item'         => '编辑API分类',
            'update_item'       => '更新API分类',
            'add_new_item'      => '添加新API分类',
            'new_item_name'     => '新API分类名称',
            'menu_name'         => 'API分类'
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'api-category')
        );

        register_taxonomy('api_category', array('yxs_api'), $args);
    }

    /**
     * 创建插件所需的数据表
     */
    private function create_tables() {
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
        // 分别执行每个表的创建
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * 检查用户API密钥
     */
    public function check_user_api_key($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // 检查用户是否已有API密钥
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        
        $existing_key = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        // 如果用户没有活跃的API密钥，自动生成一个
        if (!$existing_key) {
            $new_key = $this->generate_api_key($user_id);
            if ($new_key) {
                return $new_key;
            }
            return false;
        }

        // 返回现有的API密钥信息
        return array(
            'api_key' => $existing_key->api_key
        );
    }

    /**
     * 生成API密钥
     */
    public function generate_api_key($user_id) {
        // 先禁用该用户的所有现有密钥
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        
        $wpdb->update(
            $table_name,
            array('status' => 'inactive'),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );

        // 生成新的密钥
        $api_key = wp_generate_password(32, false);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'api_key' => $api_key,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($result) {
            return array(
                'api_key' => $api_key
            );
        }
        return false;
    }

    /**
     * 验证API密钥
     */
    public function validate_api_key($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        
        $key_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE api_key = %s AND status = 'active'",
                $api_key
            )
        );

        return !empty($key_data);
    }

    /**
     * 修改查询以支持自定义链接结构
     */
    public function modify_api_query($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }

        // 获取当前请求的URL路径
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        
        // 检查是否匹配我们的URL模式（数字.html）
        if (preg_match('/^(\d+)\.html$/', $request_uri, $matches)) {
            $post_id = $matches[1];
            
            // 检查这个ID是否属于我们的自定义文章类型
            $post = get_post($post_id);
            if ($post && $post->post_type === 'yxs_api') {
                $query->set('post_type', 'yxs_api');
                $query->set('p', $post_id);
            }
        }
    }

    /**
     * 加载API详情页模板
     */
    public function load_api_template($template) {
        global $post;
        
        if ($post && $post->post_type === 'yxs_api') {
            $new_template = YXS_API_PLUGIN_DIR . 'public/templates/single-yxs_api.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        return $template;
    }

    /**
     * 自定义API文章类型的永久链接结构
     */
    public function api_permalink($permalink, $post) {
        if ($post->post_type !== 'yxs_api') {
            return $permalink;
        }
        
        // 使用文章ID作为永久链接
        return home_url('/' . $post->ID . '.html');
    }

    /**
     * 添加API元数据框
     */
    public function add_api_meta_boxes() {
        add_meta_box(
            'yxs_api_details',
            'API详情',
            array($this, 'render_api_meta_box'),
            'yxs_api',
            'normal',
            'high'
        );
    }

    /**
     * 渲染API元数据框
     */
    public function render_api_meta_box($post) {
        // 获取现有的值
        $endpoint = get_post_meta($post->ID, 'endpoint', true);
        $method = get_post_meta($post->ID, 'method', true);
        $response_format = get_post_meta($post->ID, 'response_format', true);
        $request_params = get_post_meta($post->ID, 'request_params', true);
        $response_example = get_post_meta($post->ID, 'response_example', true);

        // 添加nonce以进行安全检查
        wp_nonce_field('yxs_api_meta_box', 'yxs_api_meta_box_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="endpoint">API端点</label></th>
                <td>
                    <input type="text" id="endpoint" name="endpoint" value="<?php echo esc_attr($endpoint); ?>" class="regular-text">
                    <p class="description">例如：/test</p>
                </td>
            </tr>
            <tr>
                <th><label for="method">请求方法</label></th>
                <td>
                    <select id="method" name="method">
                        <option value="GET" <?php selected($method, 'GET'); ?>>GET</option>
                        <option value="POST" <?php selected($method, 'POST'); ?>>POST</option>
                        <option value="PUT" <?php selected($method, 'PUT'); ?>>PUT</option>
                        <option value="DELETE" <?php selected($method, 'DELETE'); ?>>DELETE</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="request_params">请求参数</label></th>
                <td>
                    <textarea id="request_params" name="request_params" rows="5" class="large-text code">{
    "realName": {
        "type": "string",
        "required": true,
        "description": "姓名",
        "example": "张三"
    },
    "cardNo": {
        "type": "string",
        "required": true,
        "description": "身份证号码",
        "example": "110101199001011234"
    }
}</textarea>
                    <p class="description">请使用JSON格式描述请求参数</p>
                </td>
            </tr>
            <tr>
                <th><label for="response_format">返回格式</label></th>
                <td>
                    <textarea id="response_format" name="response_format" rows="5" class="large-text code">{
    "code": "integer",
    "msg": "string",
    "data": {
        "isok": "boolean",
        "IdCardInfor": {
            "realname": "string",
            "idcard": "string",
            "province": "string",
            "city": "string",
            "district": "string",
            "area": "string",
            "sex": "string",
            "birthday": "string"
        }
    },
    "exec_time": "float",
    "user_ip": "string"
}</textarea>
                    <p class="description">请使用JSON格式描述返回数据结构</p>
                </td>
            </tr>
            <tr>
                <th><label for="response_example">返回示例</label></th>
                <td>
                    <textarea id="response_example" name="response_example" rows="5" class="large-text code">{
    "code": 200,
    "msg": "请求成功",
    "data": {
        "isok": true,
        "IdCardInfor": {
            "realname": "张*",
            "idcard": "110101********1234",
            "province": "北京市",
            "city": "北京市",
            "district": "东城区",
            "area": "北京市北京市东城区",
            "sex": "男",
            "birthday": "1990-01-01"
        }
    },
    "exec_time": 0.123,
    "user_ip": "127.0.0.1"
}</textarea>
                    <p class="description">请提供一个返回数据的示例（JSON格式）</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * 保存API元数据
     */
    public function save_api_meta($post_id) {
        // 检查是否是自动保存
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 验证nonce
        if (!isset($_POST['yxs_api_meta_box_nonce']) || !wp_verify_nonce($_POST['yxs_api_meta_box_nonce'], 'yxs_api_meta_box')) {
            return;
        }

        // 检查权限
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 保存数据
        $fields = array('endpoint', 'method', 'response_format', 'request_params', 'response_example');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
            }
        }

        // 创建API实现文件
        $this->create_api_implementation_file($post_id);
    }

    /**
     * 创建API实现文件
     */
    private function create_api_implementation_file($post_id) {
        // 获取API信息
        $endpoint = get_post_meta($post_id, 'endpoint', true);
        $method = get_post_meta($post_id, 'method', true);
        
        if (empty($endpoint) || empty($method)) {
            return false;
        }
        
        // 根据端点生成文件名
        $file_name = sanitize_file_name(str_replace('/', '-', ltrim($endpoint, '/'))) . '.php';
        $file_path = YXS_API_PLUGIN_DIR . 'apimod/' . $file_name;
        
        // 如果文件已存在，不重新创建
        if (file_exists($file_path)) {
            return true;
        }
        
        // 生成类名
        $class_name = ucfirst(str_replace('-', '_', ltrim($endpoint, '/'))) . '_API';
        
        // 生成文件内容
        $content = '<?php
/**
 * API名称：' . get_the_title($post_id) . '
 * 端点：' . $endpoint . '
 * 方法：' . $method . '
 */

class ' . $class_name . ' {
    /**
     * 处理API请求
     */
    public function handle_request($request) {
        // 获取请求参数
        $params = $request->get_params();
        
        // 验证必填参数
        if (empty($params[\'name\'])) {
            return array(
                \'status\' => \'error\',
                \'message\' => \'用户名不能为空\',
                \'code\' => 400
            );
        }
        
        // 返回固定响应
        return array(
            \'status\' => \'success\',
            \'message\' => \'请求成功\',
            \'data\' => \'123\'
        );
    }
}';
        
        // 创建目录（如果不存在）
        if (!file_exists(YXS_API_PLUGIN_DIR . 'apimod')) {
            mkdir(YXS_API_PLUGIN_DIR . 'apimod', 0755, true);
        }
        
        // 写入文件
        if (file_put_contents($file_path, $content) === false) {
            error_log('无法创建API实现文件：' . $file_path);
            return false;
        }
        
        return true;
    }

    /**
     * 自动生成API密钥
     */
    public function auto_generate_api_key() {
        // 只在API页面执行
        if (!is_singular('yxs_api')) {
            return;
        }

        // 检查用户是否登录
        if (!is_user_logged_in()) {
            return;
        }

        // 检查并生成API密钥
        $this->check_user_api_key();
    }
}

// 初始化类
new Yxs_API_Manager(); 