<?php
class Yxs_API_Shortcodes {
    // API文档展示短代码
    public function api_docs($atts) {
        ob_start();
        // 使用子比主题的卡片模板
        get_template_part('template-parts/card', 'api-docs'); 
        return ob_get_clean();
    }
} 