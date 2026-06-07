<?php
// 根据子比会员等级设置API权限
add_filter('yxs_api_access_control', function($access, $user_id) {
    $vip_level = zib_get_user_vip_level($user_id);
    
    // 设置不同会员等级的API调用频率
    $access['rate_limit'] = [
        0 => 100,   // 普通用户
        1 => 500,   // VIP1
        2 => 2000,  // VIP2
        3 => 5000   // VIP3
    ];
    
    return $access;
}, 10, 2); 