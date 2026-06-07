<?php
/**
 * API 列表页面模板 - 子比主题优化版
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$categories = get_terms(array(
    'taxonomy' => 'api_category',
    'hide_empty' => false
));

$current_category = isset($_GET['category']) ? absint($_GET['category']) : 0;

$args = array(
    'post_type' => 'yxs_api',
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
);

if ($current_category) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'api_category',
            'field' => 'term_id',
            'terms' => $current_category
        )
    );
}

$apis = new WP_Query($args);
?>

<div class="api-list-page">
    <div class="container">
        <!-- 页面标题 -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa fa-plug"></i> API 接口中心
            </h1>
            <p class="page-desc">提供稳定、高效的 API 接口服务，支持多种调用方式</p>
        </div>

        <div class="api-list-layout">
            <!-- 左侧分类导航 -->
            <aside class="api-sidebar">
                <div class="category-box">
                    <div class="category-header">
                        <i class="fa fa-th-large"></i>
                        <span>接口分类</span>
                    </div>
                    <ul class="category-list">
                        <li class="<?php echo $current_category === 0 ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url(remove_query_arg('category')); ?>">
                                <i class="fa fa-th"></i>
                                <span>全部接口</span>
                                <span class="count"><?php echo wp_count_posts('yxs_api')->publish; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li class="<?php echo $current_category === $category->term_id ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url(add_query_arg('category', $category->term_id)); ?>">
                                    <i class="fa fa-folder"></i>
                                    <span><?php echo esc_html($category->name); ?></span>
                                    <span class="count"><?php echo $category->count; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- 统计信息 -->
                <div class="stats-box">
                    <div class="stats-item">
                        <div class="stats-number"><i class="fa fa-cloud"></i> <?php echo $apis->post_count; ?></div>
                        <div class="stats-label">可用接口</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-number"><i class="fa fa-tag"></i> <?php echo count($categories); ?></div>
                        <div class="stats-label">接口分类</div>
                    </div>
                </div>
            </aside>

            <!-- 右侧 API 列表 -->
            <main class="api-content">
                <div class="api-grid">
                    <?php if ($apis->have_posts()): while ($apis->have_posts()): $apis->the_post(); 
                        $endpoint = get_post_meta(get_the_ID(), 'endpoint', true);
                        $method = get_post_meta(get_the_ID(), 'method', true);
                    ?>
                        <div class="api-card">
                            <div class="api-card-header">
                                <div class="card-title">
                                    <i class="fa fa-bolt"></i>
                                    <h3><?php the_title(); ?></h3>
                                </div>
                                <span class="api-method <?php echo strtolower($method); ?>">
                                    <?php echo $method; ?>
                                </span>
                            </div>
                            <div class="api-card-body">
                                <div class="api-description">
                                    <?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?>
                                </div>
                                <div class="api-meta">
                                    <div class="meta-item">
                                        <i class="fa fa-link"></i>
                                        <code><?php echo esc_html($endpoint); ?></code>
                                    </div>
                                </div>
                            </div>
                            <div class="api-card-footer">
                                <a href="<?php the_permalink(); ?>" class="view-btn">
                                    <i class="fa fa-eye"></i>
                                    <span>查看详情</span>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <p>暂无 API 接口</p>
                        </div>
                    <?php endif; wp_reset_postdata(); ?>
                </div>
            </main>
        </div>
    </div>
</div>
    </div>

    <!-- 右侧API列表 -->
    <div class="api-content">
        <div class="api-grid">
            <?php if ($apis->have_posts()): while ($apis->have_posts()): $apis->the_post(); 
                $endpoint = get_post_meta(get_the_ID(), 'endpoint', true);
                $method = get_post_meta(get_the_ID(), 'method', true);
                $price = get_post_meta(get_the_ID(), 'price', true);
                $vip_level = get_post_meta(get_the_ID(), 'vip_level', true);
            ?>
                <div class="api-card">
                    <div class="api-card-header">
                        <h2><?php the_title(); ?></h2>
                        <span class="api-method <?php echo strtolower($method); ?>"><?php echo $method; ?></span>
                    </div>
                    <div class="api-card-body">
                        <div class="api-description">
                            <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                        </div>
                    </div>
                    <div class="api-card-footer">
                        <a href="<?php the_permalink(); ?>" class="view-api-btn">查看详情</a>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="no-apis">
                    <p>暂无API接口</p>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<style>
/* API 列表页面 - 子比主题优化样式 */
.api-list-page {
    padding: 30px 0;
    min-height: 60vh;
}

.api-list-page .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* 页面头部 */
.page-header {
    margin-bottom: 30px;
    text-align: center;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 10px;
}

.page-title i {
    color: #206bcc;
    margin-right: 10px;
}

.page-desc {
    color: #666;
    font-size: 14px;
    margin: 0;
}

/* 布局 */
.api-list-layout {
    display: flex;
    gap: 25px;
}

/* 左侧边栏 */
.api-sidebar {
    width: 260px;
    flex-shrink: 0;
}

.category-box {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 20px;
}

.category-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-weight: 600;
    font-size: 15px;
}

.category-header i {
    font-size: 16px;
}

.category-list {
    list-style: none;
    margin: 0;
    padding: 10px 0;
}

.category-list li {
    margin: 0;
}

.category-list li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.category-list li a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #206bcc;
    transform: scaleY(0);
    transition: transform 0.3s;
}

.category-list li.active a::before,
.category-list li a:hover::before {
    transform: scaleY(1);
}

.category-list li.active a,
.category-list li a:hover {
    background: #f0f7ff;
    color: #206bcc;
}

.category-list li a i {
    width: 20px;
    text-align: center;
    font-size: 14px;
}

.category-list .count {
    margin-left: auto;
    background: #f5f5f5;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    color: #666;
}

/* 统计卡片 */
.stats-box {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.stats-item {
    padding: 20px 15px;
    text-align: center;
    border-right: 1px solid #eee;
}

.stats-item:last-child {
    border-right: none;
}

.stats-number {
    font-size: 24px;
    font-weight: 600;
    color: #206bcc;
    margin-bottom: 5px;
}

.stats-number i {
    font-size: 18px;
    margin-right: 5px;
}

.stats-label {
    font-size: 12px;
    color: #666;
}

/* API 内容区 */
.api-content {
    flex: 1;
}

.api-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

/* API 卡片 */
.api-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(.25,.8,.25,1);
    overflow: hidden;
    border: 1px solid #f0f0f0;
}

.api-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(32,107,204,0.15);
}

.api-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.card-title i {
    color: #206bcc;
    font-size: 16px;
}

.card-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
}

.api-method {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    font-family: 'SF Mono', 'Consolas', monospace;
}

.api-method.get { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.api-method.post { 
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: #fff;
}

.api-method.put { 
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #fff;
}

.api-method.delete { 
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
    color: #fff;
}

.api-card-body {
    padding: 15px 20px;
}

.api-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 12px;
    height: 44px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.api-meta {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 6px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #666;
}

.meta-item i {
    font-size: 12px;
    color: #206bcc;
}

.meta-item code {
    font-family: 'SF Mono', 'Consolas', monospace;
    background: transparent;
    padding: 0;
    color: inherit;
}

.api-card-footer {
    padding: 15px 20px;
    border-top: 1px solid #f0f0f0;
}

.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    text-decoration: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
}

.view-btn:hover {
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

.view-btn i {
    font-size: 12px;
}

/* 空状态 */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

/* 响应式 */
@media (max-width: 992px) {
    .api-list-layout {
        flex-direction: column;
    }

    .api-sidebar {
        width: 100%;
    }

    .category-box {
        margin-bottom: 15px;
    }

    .stats-box {
        grid-template-columns: repeat(4, 1fr);
    }

    .api-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .api-list-page {
        padding: 20px 0;
    }

    .page-title {
        font-size: 22px;
    }

    .stats-box {
        grid-template-columns: 1fr 1fr;
    }

    .api-grid {
        grid-template-columns: 1fr;
    }
}

/* 子比深色模式适配 */
body.dark-theme .api-list-page .page-title {
    color: #E5E7EB;
}

body.dark-theme .api-list-page .page-desc {
    color: #9CA3AF;
}

body.dark-theme .category-box,
body.dark-theme .api-card,
body.dark-theme .stats-box {
    background: #1E2029;
    border-color: #343746;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}

body.dark-theme .category-list li a {
    color: #E5E7EB;
}

body.dark-theme .category-list li.active a,
body.dark-theme .category-list li a:hover {
    background: #272935;
}

body.dark-theme .category-list .count {
    background: #272935;
    color: #9CA3AF;
}

body.dark-theme .stats-item {
    border-right-color: #343746;
}

body.dark-theme .card-title h3 {
    color: #E5E7EB;
}

body.dark-theme .api-description {
    color: #9CA3AF;
}

body.dark-theme .api-meta {
    background: #272935;
}

body.dark-theme .meta-item {
    color: #9CA3AF;
}

body.dark-theme .api-card-header {
    border-bottom-color: #343746;
}

body.dark-theme .api-card-footer {
    border-top-color: #343746;
}
</style>

<?php get_footer(); ?>