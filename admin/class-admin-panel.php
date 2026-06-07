<?php
/**
 * 管理面板类
 */
class Yxs_Admin_Panel {
    private $api_manager;

    /**
     * 构造函数
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('show_user_profile', array($this, 'show_api_keys_section'));
        add_action('edit_user_profile', array($this, 'show_api_keys_section'));
        add_action('wp_ajax_yxs_generate_api_key',array($this,'ajax_generate_key'));
        add_action('wp_ajax_yxs_toggle_api_key',array($this,'ajax_toggle_key'));
        add_action('wp_ajax_yxs_delete_api_key',array($this,'ajax_delete_key'));
        
        $this->api_manager = new Yxs_API_Manager();
    }

    /**
     * 添加管理菜单
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
     * 注册设置
     */
    public function register_settings() {
        register_setting('yxs_api_options', 'yxs_api_settings');
        
        add_settings_section(
            'yxs_api_general',
            '基本设置',
            array($this, 'general_section_callback'),
            'yxs-api'
        );

        add_settings_field(
            'rate_limit',
            'API速率限制',
            array($this, 'rate_limit_callback'),
            'yxs-api',
            'yxs_api_general'
        );
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
    $uid = intval($_POST['user_id']);
    $res = $this->api_manager->generate_api_key($uid);
    if($res) wp_send_json_success();
    wp_send_json_error('生成失败');
}
public function ajax_toggle_key(){
    check_ajax_referer('yxs_api_admin','_ajax_nonce');
    if(!current_user_can('manage_options')) wp_send_json_error('无权限');
    global $wpdb;
    $kid = intval($_POST['key_id']);
    $sta = sanitize_text_field($_POST['status']);
    $tb = $wpdb->prefix.'yxs_api_keys';
    $up = $wpdb->update($tb,['status'=>$sta],['id'=>$kid],['%s'],['%d']);
    $up!==false?wp_send_json_success():wp_send_json_error('修改失败');
}
public function ajax_delete_key(){
    check_ajax_referer('yxs_api_admin','_ajax_nonce');
    if(!current_user_can('manage_options')) wp_send_json_error('无权限');
    global $wpdb;
    $kid = intval($_POST['key_id']);
    $tb = $wpdb->prefix.'yxs_api_keys';
    $del = $wpdb->delete($tb,['id'=>$kid],[ '%d' ]);
    $del?wp_send_json_success():wp_send_json_error('删除失败');
}

    /**
     * 基本设置区域回调
     */
    public function general_section_callback() {
        echo '<p>配置API的基本设置</p>';
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