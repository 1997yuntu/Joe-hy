<?php
/**
 * 管理面板类 - 子比主题 CSF 菜单框架版本
 */
class Yxs_Admin_Panel {
    private $api_manager;
    private $option_name = 'yxs_api_options';

    /**
     * 构造函数
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('show_user_profile', array($this, 'show_api_keys_section'));
        add_action('edit_user_profile', array($this, 'show_api_keys_section'));
        add_action('wp_ajax_yxs_generate_api_key',array($this,'ajax_generate_key'));
        add_action('wp_ajax_yxs_toggle_api_key',array($this,'ajax_toggle_key'));
        add_action('wp_ajax_yxs_delete_api_key',array($this,'ajax_delete_key'));
        
        if ( class_exists( 'CSF' ) ) {
            add_filter( 'csf_options_yxs_api_options', array( $this, 'csf_option_defaults' ) );
            add_action( 'admin_init', array( $this, 'register_csf_settings' ) );
        }
        
        $this->api_manager = new Yxs_API_Manager();
    }

    /**
     * 注册 CSF 设置
     */
    public function register_csf_settings() {
        CSF::createOption( array(
            'framework_title' => '网亿 API 管理插件设置',
            'menu_title'      => '网亿 API',
            'menu_slug'       => 'yxs-api',
            'menu_icon'       => 'dashicons-rest-api',
            'menu_position'   => 30,
            'menu_type'       => 'menu',
            'capability'      => 'manage_options',
            'menu_title_slug' => '网亿 API 管理',
            'option_name'     => $this->option_name,
            
            'sections' => array(
                array(
                    'name'       => 'api_settings',
                    'title'      => 'API 基础设置',
                    'icon'       => 'fas fa-cog',
                    'fields'     => array(
                        array(
                            'id'          => 'api_settings_info',
                            'type'        => 'submessage',
                            'style'       => 'info',
                            'content'     => '配置 API 的基本设置和数据统计',
                        ),
                        array(
                            'id'          => 'rate_limit',
                            'type'        => 'spinner',
                            'title'       => 'API 速率限制',
                            'default'     => 60,
                            'min'         => 1,
                            'max'         => 10000,
                            'step'        => 1,
                            'unit'        => '次/分钟',
                            'desc'        => '每分钟允许的 API 请求次数',
                        ),
                        array(
                            'id'          => 'enable_redis',
                            'type'        => 'switcher',
                            'title'       => '启用 Redis',
                            'default'     => false,
                            'desc'        => '启用 Redis 缓存和速率限制以提升性能',
                        ),
                        array(
                            'id'          => 'redis_host',
                            'type'        => 'text',
                            'title'       => 'Redis 主机',
                            'default'     => '127.0.0.1',
                            'desc'        => 'Redis 服务器地址',
                            'depend'      => 'enable_redis',
                        ),
                        array(
                            'id'          => 'redis_port',
                            'type'        => 'spinner',
                            'title'       => 'Redis 端口',
                            'default'     => 6379,
                            'min'         => 1,
                            'max'         => 65535,
                            'step'        => 1,
                            'desc'        => 'Redis 服务器端口',
                            'depend'      => 'enable_redis',
                        ),
                    ),
                ),
                array(
                    'name'       => 'rate_limits',
                    'title'      => '访问限制设置',
                    'icon'       => 'fas fa-shield-alt',
                    'fields'     => array(
                        array(
                            'id'          => 'rate_limits_info',
                            'type'        => 'submessage',
                            'style'       => 'warning',
                            'content'     => '设置不同用户等级的 API 调用频率限制',
                        ),
                        array(
                            'id'          => 'rate_limits',
                            'type'        => 'group',
                            'title'       => '速率限制配置',
                            'fields'      => array(
                                array(
                                    'id'      => 'level',
                                    'type'    => 'select',
                                    'title'   => '用户等级',
                                    'options' => array(
                                        0 => '普通用户',
                                        1 => 'VIP1',
                                        2 => 'VIP2',
                                        3 => 'VIP3',
                                    ),
                                ),
                                array(
                                    'id'      => 'requests',
                                    'type'    => 'spinner',
                                    'title'   => '请求次数',
                                    'min'     => 1,
                                    'max'     => 100000,
                                ),
                                array(
                                    'id'      => 'window',
                                    'type'    => 'spinner',
                                    'title'   => '时间窗口 (秒)',
                                    'min'     => 60,
                                    'max'     => 86400,
                                ),
                            ),
                            'default'     => array(
                                array( 'level' => 0, 'requests' => 100, 'window' => 3600 ),
                                array( 'level' => 1, 'requests' => 500, 'window' => 3600 ),
                                array( 'level' => 2, 'requests' => 2000, 'window' => 3600 ),
                                array( 'level' => 3, 'requests' => 5000, 'window' => 3600 ),
                            ),
                        ),
                    ),
                ),
                array(
                    'name'       => 'security_settings',
                    'title'      => '安全设置',
                    'icon'       => 'fas fa-lock',
                    'fields'     => array(
                        array(
                            'id'          => 'security_info',
                            'type'        => 'submessage',
                            'style'       => 'success',
                            'content'     => 'API 密钥和安全配置',
                        ),
                        array(
                            'id'          => 'api_key',
                            'type'        => 'text',
                            'title'       => 'API 密钥',
                            'desc'        => '用于 API 签名验证的密钥 (点击按钮生成)',
                            'readonly'    => true,
                        ),
                        array(
                            'id'          => 'generate_key_btn',
                            'type'        => 'button',
                            'title'       => '生成 API 密钥',
                            'button_title'=> '生成新密钥',
                            'attributes'  => array(
                                'data-action' => 'generate_api_key',
                            ),
                        ),
                        array(
                            'id'          => 'token_expiry',
                            'type'        => 'spinner',
                            'title'       => 'Token 有效期',
                            'default'     => 7,
                            'min'         => 1,
                            'max'         => 365,
                            'unit'        => '天',
                            'desc'        => 'API 访问令牌的有效期',
                        ),
                        array(
                            'id'          => 'ip_whitelist',
                            'type'        => 'textarea',
                            'title'       => 'IP 白名单',
                            'desc'        => '每行一个 IP 地址，支持 CIDR 格式',
                        ),
                    ),
                ),
                array(
                    'name'       => 'payment_settings',
                    'title'      => '支付设置',
                    'icon'       => 'fas fa-yen-sign',
                    'fields'     => array(
                        array(
                            'id'          => 'payment_info',
                            'type'        => 'submessage',
                            'style'       => 'info',
                            'content'     => 'API 计费和 VIP 折扣配置',
                        ),
                        array(
                            'id'          => 'enable_payment',
                            'type'        => 'switcher',
                            'title'       => '启用 API 计费',
                            'default'     => false,
                            'desc'        => '启用 API 调用计费功能',
                        ),
                        array(
                            'id'          => 'default_price',
                            'type'        => 'spinner',
                            'title'       => '默认价格',
                            'default'     => 0.01,
                            'min'         => 0,
                            'max'         => 999999,
                            'step'        => 0.01,
                            'unit'        => '元/次',
                            'desc'        => '未单独设置价格的 API 的默认调用价格',
                        ),
                        array(
                            'id'          => 'vip_discounts',
                            'type'        => 'group',
                            'title'       => 'VIP 折扣配置',
                            'fields'      => array(
                                array(
                                    'id'      => 'level',
                                    'type'    => 'select',
                                    'title'   => 'VIP 等级',
                                    'options' => array(
                                        1 => 'VIP1',
                                        2 => 'VIP2',
                                        3 => 'VIP3',
                                    ),
                                ),
                                array(
                                    'id'      => 'discount',
                                    'type'    => 'spinner',
                                    'title'   => '折扣 (%)',
                                    'min'     => 0,
                                    'max'     => 100,
                                ),
                            ),
                            'default'     => array(
                                array( 'level' => 1, 'discount' => 90 ),
                                array( 'level' => 2, 'discount' => 80 ),
                                array( 'level' => 3, 'discount' => 70 ),
                            ),
                        ),
                    ),
                ),
            ),
        ) );
    }
    
    /**
     * CSF 默认值过滤器
     */
    public function csf_option_defaults( $defaults ) {
        if ( empty( $defaults['api_key'] ) ) {
            $defaults['api_key'] = $this->generate_api_key_string( 32 );
        }
        return $defaults;
    }
    
    /**
     * 生成 API 密钥字符串
     */
    private function generate_api_key_string( $length = 32 ) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ( $i = $length; $i > 0; --$i ) {
            $result .= $chars[ mt_rand( 0, strlen( $chars ) - 1 ) ];
        }
        return $result;
    }

    /**
     * 加载管理页面资源
     */
    public function add_admin_menu() {
    add_menu_page(
        'API管理',
        '网亿API',
        'manage_options',
        'yxs-api',
        array($this, 'display_admin_page'),
        'dashicons-rest-api',
        30
    );
    //原有2个菜单
    add_submenu_page(
        'yxs-api',
        'API密钥管理',
        'API密钥',
        'manage_options',
        'yxs-api-keys',
        array($this, 'display_api_keys_page')
    );
    add_submenu_page(
        'yxs-api',
        'API调用记录',
        '调用记录',
        'manage_options',
        'yxs-api-logs',
        array($this, 'display_api_logs_page')
    );
    //【新增1：数据统计菜单】
    add_submenu_page(
        'yxs-api',
        'API数据统计',
        '数据统计',
        'manage_options',
        'yxs-api-stats',
        array($this, 'display_stats_page')
    );
    //【新增2：插件基础设置菜单】
    add_submenu_page(
        'yxs-api',
        '插件基础设置',
        '设置',
        'manage_options',
        'yxs-api-setting',
        array($this, 'display_setting_page')
    );
}

    /**
     * 加载管理页面资源
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'yxs-api') === false) {
            return;
        }

        // 加载样式
        wp_enqueue_style(
            'yxs-api-admin',
            YXS_API_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            YXS_API_VERSION
        );

        // 加载脚本
        wp_enqueue_script(
            'yxs-api-admin',
            YXS_API_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            YXS_API_VERSION,
            true
        );

        // 本地化脚本
        wp_localize_script('yxs-api-admin', 'yxsApiAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yxs_api_admin'),
            'i18n' => array(
                'confirmDelete' => '确定要删除吗？',
                'saveSuccess' => '保存成功',
                'saveFailed' => '保存失败',
                'loading' => '加载中...'
            )
        ));

        // 如果是统计页面，加载图表库
        if (isset($_GET['page']) && $_GET['page'] === 'yxs-api-stats') {
            wp_enqueue_script(
                'echarts',
                YXS_API_PLUGIN_URL . 'admin/js/echarts.min.js',
                array(),
                '5.4.3',
                true
            );
        }
    }

    /**
     * 注册设置 (保留向后兼容)
     */
    public function register_settings() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        register_setting('yxs_api_options', 'yxs_api_settings');
        
        add_settings_section(
            'yxs_api_general',
            '基本设置',
            array($this, 'general_section_callback'),
            'yxs-api'
        );

        add_settings_field(
            'rate_limit',
            'API 速率限制',
            array($this, 'rate_limit_callback'),
            'yxs-api',
            'yxs_api_general'
        );
    }

    /**
     * 显示主管理页面 (CSF 模式下不使用)
     */
    public function display_admin_page() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        $apis = get_posts(array(
            'post_type' => 'yxs_api',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $categories = get_terms(array(
            'taxonomy' => 'api_category',
            'hide_empty' => false
        ));
        
        include YXS_API_PLUGIN_DIR . 'admin/views/main.php';
    }

    /**
     * 显示 API 密钥管理页面
     */
    public function display_api_keys_page() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        $keys = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        include YXS_API_PLUGIN_DIR . 'admin/views/api-keys.php';
    }
    /**
     * 显示 API 调用记录页面
     */
    public function display_api_logs_page() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
        
        include YXS_API_PLUGIN_DIR . 'admin/views/api-logs.php';
    }
    /** 数据统计页面 */

    public function display_stats_page() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        include YXS_API_PLUGIN_DIR . 'admin/views/stats.php';
    }
    /** 基础设置页面 */
    public function display_setting_page() {
        if ( class_exists( 'CSF' ) ) {
            return;
        }
        include YXS_API_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * 显示主管理页面
     */
    public function display_admin_page() {
        // 获取API列表
        $apis = get_posts(array(
            'post_type' => 'yxs_api',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // 获取API分类
        $categories = get_terms(array(
            'taxonomy' => 'api_category',
            'hide_empty' => false
        ));
        
        include YXS_API_PLUGIN_DIR . 'admin/views/main.php';
    }

    /**
     * 显示API密钥管理页面
     */
    public function display_api_keys_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        $keys = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        include YXS_API_PLUGIN_DIR . 'admin/views/api-keys.php';
    }
    /**
     * 显示API调用记录页面
     */
    public function display_api_logs_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
        
        include YXS_API_PLUGIN_DIR . 'admin/views/api-logs.php';
    }
/** 数据统计页面 */

public function display_stats_page() {

    include YXS_API_PLUGIN_DIR . 'admin/views/stats.php';
}
/** 基础设置页面 */
public function display_setting_page() {
    include YXS_API_PLUGIN_DIR . 'admin/views/settings.php';
}

    public function ajax_generate_key(){
        check_ajax_referer('yxs_api_admin','_ajax_nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('无权限');
        $uid = intval($_POST['user_id'] ?? 0);
        if ( $uid <= 0 ) {
            $uid = get_current_user_id();
        }
        $res = $this->api_manager->generate_api_key($uid);
        if($res) wp_send_json_success(array( 'key' => $res['api_key'] ));
        wp_send_json_error('生成失败');
    }
    
    public function ajax_toggle_key(){
        check_ajax_referer('yxs_api_admin','_ajax_nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('无权限');
        global $wpdb;
        $kid = intval($_POST['key_id'] ?? 0);
        $sta = sanitize_text_field($_POST['status'] ?? 'inactive');
        if ( $kid <= 0 ) {
            wp_send_json_error('无效的参数');
            return;
        }
        $tb = $wpdb->prefix.'yxs_api_keys';
        $up = $wpdb->update($tb,array('status'=>$sta),array('id'=>$kid),array('%s'),array('%d'));
        if($up!==false) wp_send_json_success();
        wp_send_json_error('修改失败');
    }
    
    public function ajax_delete_key(){
        check_ajax_referer('yxs_api_admin','_ajax_nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('无权限');
        global $wpdb;
        $kid = intval($_POST['key_id'] ?? 0);
        if ( $kid <= 0 ) {
            wp_send_json_error('无效的参数');
            return;
        }
        $tb = $wpdb->prefix.'yxs_api_keys';
        $del = $wpdb->delete($tb,array('id'=>$kid),array('%d'));
        if($del) wp_send_json_success();
        wp_send_json_error('删除失败');
    }

    /**
     * 基本设置区域回调 (向后兼容)
     */
    public function general_section_callback() {
        echo '<p>配置 API 的基本设置</p>';
    }

    /**
     * 速率限制设置字段回调 (向后兼容)
     */
    public function rate_limit_callback() {
        $options = get_option('yxs_api_settings');
        $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 60;
        ?>
        <input type="number" name="yxs_api_settings[rate_limit]" value="<?php echo esc_attr($rate_limit); ?>">
        <p class="description">每分钟允许的 API 请求次数</p>
        <?php
    }

    /**
     * 速率限制设置字段回调
     */
    public function rate_limit_callback() {
        $options = get_option('yxs_api_settings');
        $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 60;
        ?>
        <input type="number" name="yxs_api_settings[rate_limit]" value="<?php echo esc_attr($rate_limit); ?>">
        <p class="description">每分钟允许的API请求次数</p>
        <?php
    }

    /**
     * 显示用户API密钥部分
     */
    public function show_api_keys_section($user) {
        // 获取用户的API密钥
        global $wpdb;
        $table_name = $wpdb->prefix . 'yxs_api_keys';
        $keys = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user->ID
        ));
        ?>
        <h2>API密钥</h2>
        <table class="form-table">
            <tr>
                <th><label>当前API密钥</label></th>
                <td>
                    <?php if (!empty($keys)) : ?>
                        <?php foreach ($keys as $key) : ?>
                            <div class="api-key-row" style="margin-bottom: 10px;">
                                <div>
                                    <label>API Key:</label>
                                    <input type="text" class="regular-text" value="<?php echo esc_attr($key->api_key); ?>" readonly>
                                    <button type="button" class="button copy-api-key">复制</button>
                                </div>
                                <div style="margin-top: 5px;">
                                    <label>API Secret:</label>
                                    <input type="text" class="regular-text" value="<?php echo esc_attr($key->api_secret); ?>" readonly>
                                    <button type="button" class="button copy-api-key">复制</button>
                                </div>
                                <div style="margin-top: 5px;">
                                    <span class="description">创建时间: <?php echo $key->created_at; ?></span>
                                    <span class="description" style="margin-left: 15px;">状态: 
                                        <span class="api-key-status <?php echo $key->status; ?>">
                                            <?php echo $key->status === 'active' ? '活跃' : '禁用'; ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>暂无API密钥。访问API页面时将自动生成。</p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <style>
        .api-key-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .api-key-status.active {
            background: #e8f5e9;
            color: #388e3c;
        }
        .api-key-status.inactive {
            background: #ffebee;
            color: #d32f2f;
        }
        </style>
        <?php
    }
}

// 初始化类
new Yxs_Admin_Panel(); 