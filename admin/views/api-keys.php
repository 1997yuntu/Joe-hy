<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">API密钥管理</h1>
    <a href="#" class="page-title-action generate-api-key" data-user-id="<?php echo get_current_user_id(); ?>">生成新密钥</a>
    <hr class="wp-header-end">

    <div class="yxs-api-container">
        <?php if (!empty($keys)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>用户</th>
                        <th>API密钥</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>最后访问</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys as $key) : ?>
                        <tr>
                            <td><?php echo esc_html(get_userdata($key->user_id)->user_login); ?></td>
                            <td>
                                <input type="text" class="api-key" value="<?php echo esc_attr($key->api_key); ?>" readonly>
                                <button class="button copy-api-key">复制</button>
                            </td>
                            <td>
                                <span class="status <?php echo esc_attr($key->status); ?>">
                                    <?php echo $key->status === 'active' ? '活跃' : '禁用'; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($key->created_at); ?></td>
                            <td><?php echo $key->last_access ? esc_html($key->last_access) : '从未访问'; ?></td>
                            <td>
                                <?php if ($key->status === 'active') : ?>
                                    <button class="button toggle-api-key" data-key-id="<?php echo esc_attr($key->id); ?>" data-new-status="inactive">
                                        禁用
                                    </button>
                                <?php else : ?>
                                    <button class="button toggle-api-key" data-key-id="<?php echo esc_attr($key->id); ?>" data-new-status="active">
                                        启用
                                    </button>
                                <?php endif; ?>
                                <button class="button button-link-delete delete-api-key" data-key-id="<?php echo esc_attr($key->id); ?>">
                                    删除
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="no-items">
                <p>暂无API密钥，点击"生成新密钥"按钮创建一个。</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.api-key {
    width: 240px;
    font-family: monospace;
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 4px 8px;
    margin-right: 5px;
}

.status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.status.active {
    background: #e8f5e9;
    color: #388e3c;
}

.status.inactive {
    background: #ffebee;
    color: #d32f2f;
}

.button-link-delete {
    color: #dc3545;
    margin-left: 5px;
}

.button-link-delete:hover {
    color: #fff;
    background: #dc3545;
    border-color: #dc3545;
}
</style> 