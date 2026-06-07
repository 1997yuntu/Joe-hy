<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">API列表</h1>
    <a href="<?php echo admin_url('post-new.php?post_type=yxs_api'); ?>" class="page-title-action">添加API</a>
    <hr class="wp-header-end">

    <div class="yxs-api-container">
        <!-- 左侧分类列表 -->
        <div class="yxs-api-categories">
            <h2>API分类</h2>
            <ul class="categorychecklist">
                <li>
                    <label>
                        <input type="radio" name="api_category" value="0" checked>
                        <span>全部</span>
                        <span class="count">(<?php echo wp_count_posts('yxs_api')->publish; ?>)</span>
                    </label>
                </li>
                <?php
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        ?>
                        <li>
                            <label>
                                <input type="radio" name="api_category" value="<?php echo esc_attr($category->term_id); ?>">
                                <span><?php echo esc_html($category->name); ?></span>
                                <span class="count">(<?php echo esc_html($category->count); ?>)</span>
                            </label>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
        </div>

        <!-- 右侧API列表 -->
        <div class="yxs-api-list">
            <?php if (!empty($apis)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>API名称</th>
                            <th>端点</th>
                            <th>方法</th>
                            <th>调用次数</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apis as $api) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($api->ID); ?>">
                                            <?php echo esc_html($api->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html(get_post_meta($api->ID, 'endpoint', true)); ?></td>
                                <td><?php echo esc_html(get_post_meta($api->ID, 'method', true)); ?></td>
                                <td><?php echo esc_html(get_post_meta($api->ID, 'call_count', true) ?: '0'); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($api->ID); ?>" class="button button-small">编辑</a>
                                    <a href="<?php echo get_delete_post_link($api->ID); ?>" class="button button-small button-link-delete">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="no-items">
                    <p>暂无API</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.yxs-api-container {
    display: flex;
    margin-top: 20px;
}

.yxs-api-categories {
    flex: 0 0 200px;
    margin-right: 20px;
    background: #fff;
    padding: 15px;
    border: 1px solid #ccd0d4;
}

.yxs-api-list {
    flex: 1;
}

.categorychecklist {
    margin: 0;
    padding: 0;
    list-style: none;
}

.categorychecklist li {
    margin: 0;
    padding: 5px 0;
}

.categorychecklist .count {
    color: #999;
}
</style> 