# 代码修复和改进日志

## 日期：2026-06-07

### 主要改进

#### 1. 子比主题 CSF 菜单框架集成

**文件：** `admin/class-admin-panel.php`

**改进内容：**
- 移除了 WordPress 原生菜单 (`add_menu_page`, `add_submenu_page`)
- 集成 Codestar Framework (CSF) 配置
- 支持自动检测 CSF 框架是否存在，实现向后兼容
- 将设置项组织为 4 个标签页：
  - API 基础设置
  - 访问限制设置
  - 安全设置
  - 支付设置

**代码变更：**
```php
// 新增：CSF 配置注册
if ( class_exists( 'CSF' ) ) {
    add_filter( 'csf_options_yxs_api_options', array( $this, 'csf_option_defaults' ) );
    add_action( 'admin_init', array( $this, 'register_csf_settings' ) );
}

// 使用 CSF::createOption() 创建配置面板
```

#### 2. AJAX 请求处理优化

**文件：** `admin/class-admin-panel.php`

**改进内容：**
- 添加参数验证和默认值处理
- 修复未定义索引警告
- 改进错误消息返回
- 添加用户 ID 自动获取逻辑

**修复前：**
```php
$uid = intval($_POST['user_id']);
$kid = intval($_POST['key_id']);
```

**修复后：**
```php
$uid = intval($_POST['user_id'] ?? 0);
if ( $uid <= 0 ) {
    $uid = get_current_user_id();
}
$kid = intval($_POST['key_id'] ?? 0);
if ( $kid <= 0 ) {
    wp_send_json_error('无效的参数');
    return;
}
```

#### 3. API 密钥生成增强

**文件：** `admin/class-admin-panel.php`

**改进内容：**
- 添加独立的密钥生成函数
- 返回生成的密钥给前端
- 改进密钥生成算法

**新增方法：**
```php
private function generate_api_key_string( $length = 32 ) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = '';
    for ( $i = $length; $i > 0; --$i ) {
        $result .= $chars[ mt_rand( 0, strlen( $chars ) - 1 ) ];
    }
    return $result;
}
```

#### 4. API 管理器优化

**文件：** `includes/class-api-manager.php`

**改进内容：**
- 添加 `get_user_id_by_api_key()` 方法
- 移除硬编码的示例值
- 改进空值处理
- 移除不再需要的 `create_api_implementation_file()` 方法

**新增方法：**
```php
public function get_user_id_by_api_key($api_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'yxs_api_keys';
    
    $key_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE api_key = %s AND status = 'active'",
            $api_key
        )
    );

    return $key_data ? (int) $key_data->user_id : 0;
}
```

#### 5. 元数据框优化

**文件：** `includes/class-api-manager.php`

**改进内容：**
- 移除硬编码的默认值
- 使用动态获取的值
- 改进代码可读性
- 统一使用 `esc_textarea()` 转义

**修复前：**
```php
<textarea id="request_params" name="request_params" rows="5" class="large-text code">{
    "realName": { ... }
}</textarea>
```

**修复后：**
```php
<textarea id="request_params" name="request_params" rows="5" class="large-text code"><?php echo esc_textarea($request_params); ?></textarea>
```

#### 6. 设置保存优化

**文件：** `includes/class-api-manager.php`

**改进内容：**
- 移除自动创建 API 实现文件的功能
- 添加字段删除逻辑（当字段不存在时删除元数据）
- 简化保存逻辑

**修复后：**
```php
public function save_api_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['yxs_api_meta_box_nonce']) || !wp_verify_nonce($_POST['yxs_api_meta_box_nonce'], 'yxs_api_meta_box')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array('endpoint', 'method', 'response_format', 'request_params', 'response_example');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
        } else {
            delete_post_meta($post_id, $field);
        }
    }
}
```

#### 7. 设置视图兼容性处理

**文件：** `admin/views/settings.php`

**改进内容：**
- 添加 CSF 框架检测
- 在使用 CSF 时显示提示信息
- 添加注释说明使用场景

```php
if ( class_exists( 'CSF' ) ) {
    echo '<div class="wrap">';
    echo '<h1>提示</h1>';
    echo '<div class="notice notice-info"><p>您已启用子比主题 CSF 框架，所有设置请在 <strong>网亿 API</strong> 菜单中进行管理。</p></div>';
    echo '</div>';
    return;
}
```

#### 8. 其他代码质量改进

**全局改进：**
- 添加注释说明函数用途
- 统一代码缩进和格式
- 移除不必要的注释
- 使用更安全的变量访问方式
- 添加缺失的参数验证
- 改进错误处理逻辑

**修复的潜在问题：**
1. 未定义索引访问：使用 `??` 操作符提供默认值
2. SQL 注入风险：确保使用 `$wpdb->prepare()`
3. 权限验证：确保所有 AJAX 请求都验证权限
4. 数据转义：使用合适的转义函数

### 向后兼容性

所有改进都保持了向后兼容性：
- 如果不使用子比主题，原有的 WordPress 原生菜单仍然可用
- 现有 API 功能和数据库结构保持不变
- 已保存的设置不会丢失

### 建议的后续优化

1. **日志清理功能**：添加自动清理旧日志的定时任务
2. **API 测试工具**：在管理界面添加 API 测试功能
3. **缓存优化**：优化 Redis 缓存策略
4. **监控告警**：添加异常调用监控和告警功能
5. **文档生成**：自动生成 API 文档

### 测试建议

在部署前，建议测试以下场景：
1. [ ] 插件激活和数据表创建
2. [ ] API 密钥生成和管理
3. [ ] API 创建和编辑
4. [ ] 速率限制功能
5. [ ] CSF 菜单显示（如果使用子比主题）
6. [ ] AJAX 操作（生成/禁用/删除密钥）
7. [ ] API 调用日志记录

### 注意事项

1. **数据库备份**：更新前务必备份数据库
2. **子比主题版本**：确保子比主题支持 CSF 框架
3. **权限设置**：确保插件目录有写权限
4. **PHP 版本**：需要 PHP 7.4+（使用到了空合并操作符）
