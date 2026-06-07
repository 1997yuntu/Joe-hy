# 网亿 API 管理插件

针对子比主题优化的 API 管理系统插件。

## 功能特性

- 支持子比主题 CSF 菜单框架
- API 密钥管理和用户权限控制
- API 调用日志记录
- 数据统计和可视化
- 速率限制和 VIP 等级控制
- 灵活的计费系统

## 安装要求

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ 或 MariaDB 10.1+
- 子比主题（可选，用于 CSF 框架支持）

## 安装方法

1. 上传 `yxs-api` 文件夹到 `/wp-content/plugins/` 目录
2. 在 WordPress 后台"插件"菜单中激活"网亿 API 管理插件"
3. 激活后，访问"网亿 API"菜单进行配置

## 使用说明

### 菜单结构

如果使用子比主题（CSF 框架），菜单将集成到 CSF 设置面板中：

- **网亿 API**（主菜单）
  - API 基础设置
  - 访问限制设置
  - 安全设置
  - 支付设置

如果不使用子比主题，将显示独立菜单：

- **网亿 API**（主菜单）
  - API 列表
  - API 密钥
  - 调用记录
  - 数据统计
  - 设置

### API 管理

1. 在 WordPress 后台创建新的"网亿 API"文章类型
2. 填写 API 信息：
   - 端点（Endpoint）
   - 请求方法（GET/POST/PUT/DELETE）
   - 请求参数（JSON 格式）
   - 返回格式（JSON 格式）
   - 返回示例

### API 密钥管理

系统会为每个用户自动生成 API 密钥，管理员可以：
- 查看用户的 API 密钥
- 禁用/启用密钥
- 删除密钥

### 速率限制

支持按用户等级设置不同的速率限制：
- 普通用户：100 次/小时
- VIP1：500 次/小时
- VIP2：2000 次/小时
- VIP3：5000 次/小时

## 数据库表

插件激活时会创建以下数据表：

- `wp_yxs_api_keys` - API 密钥表
- `wp_yxs_api_logs` - API 调用日志表

## 自定义 API 实现

在 `apimod/` 目录中创建自定义 API 处理类：

```php
<?php
class Your_API_Name {
    public function handle_request($request) {
        $params = $request->get_params();
        
        // 处理请求
        return array(
            'status' => 'success',
            'message' => '请求成功',
            'data' => $your_data
        );
    }
}
```

## 钩子（Hooks）

### 过滤器

```php
// API 访问控制
add_filter('yxs_api_access_control', function($access, $user_id) {
    // 自定义访问控制逻辑
    return $access;
}, 10, 2);
```

## 注意事项

1. 请确保服务器具有写权限以创建数据库表
2. 建议使用 Redis 缓存以提升性能
3. 定期清理 API 日志以避免数据库过大

## 技术支持

- 作者：云先森
- 网站：https://pinpinping.cn/
- 版本：1.1.0
