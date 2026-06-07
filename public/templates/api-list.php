<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// 获取API分类
$categories = get_terms(array(
    'taxonomy' => 'api_category',
    'hide_empty' => false
));

// 获取当前分类
$current_category = isset($_GET['category']) ? absint($_GET['category']) : 0;

// 查询API列表
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

<div class="api-list-container">
    <!-- 左侧分类导航 -->
    <div class="api-sidebar">
        <div class="category-list">
            <h3>接口分类</h3>
            <ul>
                <li class="<?php echo $current_category === 0 ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(remove_query_arg('category')); ?>">
                        全部接口
                        <span class="count"><?php echo wp_count_posts('yxs_api')->publish; ?></span>
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                    <li class="<?php echo $current_category === $category->term_id ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('category', $category->term_id)); ?>">
                            <?php echo esc_html($category->name); ?>
                            <span class="count"><?php echo $category->count; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
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
/* API列表页面样式 */
.api-list-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    gap: 30px;
}
/* 左侧边栏样式 */
.api-sidebar {
    width: 240px;
    flex-shrink: 0;
}
.category-list {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}
.category-list h3 {
    margin: 0;
    padding: 15px 20px;
    font-size: 16px;
    border-bottom: 1px solid #eee;
}
.category-list ul {
    margin: 0;
    padding: 10px 0;
    list-style: none;
}
.category-list li {
    margin: 0;
}
.category-list li a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}
.category-list li.active a,
.category-list li a:hover {
    background: #f0f7ff;
    color: #1976d2;
}
.category-list .count {
    background: #f5f5f5;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    color: #666;
}
/* API卡片网格 */
.api-content {
    flex: 1;
}
.api-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.api-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s;
}
.api-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.api-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.api-card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}
.api-method {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.api-method.get { background: #e3f2fd; color: #1976d2; }
.api-method.post { background: #e8f5e9; color: #388e3c; }
.api-method.put { background: #fff3e0; color: #f57c00; }
.api-method.delete { background: #ffebee; color: #d32f2f; }
.api-card-body {
    padding: 15px 20px;
}
.api-description {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 0;
    height: 60px;
    overflow: hidden;
}
/* 移除不需要的样式 */
.api-meta,
.api-price,
.api-vip {
    display: none;
}
.api-card-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
}
.view-api-btn {
    display: block;
    width: 100%;
    padding: 10px 0;
    text-align: center;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    background: #1976d2;
    color: #fff;
    border: none;
}
.view-api-btn:hover {
    background: #1565c0;
    transform: translateY(-1px);
}
.test-api-btn {
    display: none;
}
.no-apis {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #666;
}

/* =====子比夜间深色适配body.dark-theme，和详情页颜色统一#1E2029深色===== */
body.dark-theme .category-list{
    background:#1E2029;
}
body.dark-theme .api-card{
    background:#1E2029;
}
body.dark-theme .category-list h3{
    border-bottom-color:#343746;
    color:#E5E7EB;
}
body.dark-theme .category-list li a{
    color:#E5E7EB;
}
body.dark-theme .category-list li.active a,
body.dark-theme .category-list li a:hover {
    background:#272935;
}
body.dark-theme .category-list .count{
    background:#272935;
    color:#9CA3AF;
}
body.dark-theme .api-card-header{
    border-bottom-color:#343746;
}
body.dark-theme .api-card-footer{
    border-top-color:#343746;
}
body.dark-theme .api-description{
    color:#9CA3AF;
}
body.dark-theme .no-apis{
    color:#9CA3AF;
}

/* 夜间标签微调 */
body.dark-theme .api-method.get { background:#1a237e;color:#90caf9; }
body.dark-theme .api-method.post { background:#1b5e20;color:#a5d6a7; }
body.dark-theme .api-method.put { background:#e65100;color:#ffe0b2; }
body.dark-theme .api-method.delete { background:#b71c1c;color:#ffcdd2; }

/* 响应式调整 */
@media (max-width: 768px) {
    .api-list-container {
        flex-direction: column;
        padding: 10px;
    }
    .api-sidebar {
        width: 100%;
    }
    .api-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?> 