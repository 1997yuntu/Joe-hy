// API文档页面交互功能
jQuery(document).ready(function($) {
    // 复制端点URL
    $('.copy-btn').on('click', function() {
        const $btn = $(this);
        const $code = $btn.siblings('code');
        const text = $code.text();
        
        // 创建临时输入框复制文本
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        // 显示复制成功提示
        $btn.text('已复制!');
        setTimeout(() => {
            $btn.html('<i class="fa fa-copy"></i>');
        }, 2000);
    });
    
    // API测试表单提交
    $('.test-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const $result = $form.siblings('.test-result');
        
        // 获取表单数据
        const formData = {};
        $form.find('[name]').each(function() {
            formData[this.name] = $(this).val();
        });
        
        // 获取API信息
        const endpoint = $form.data('endpoint');
        const method = $form.data('method');
        
        // 添加加载状态
        $submitBtn.addClass('loading');
        $submitBtn.prop('disabled', true);
        
        // 发送API请求
        $.ajax({
            url: endpoint,
            type: method,
            data: formData,
            headers: {
                'Authorization': 'Bearer ' + window.apiToken,
                'X-API-Key': window.apiKey
            },
            success: function(response) {
                // 显示响应结果
                $result.html(`
                    <pre><code class="language-json">${JSON.stringify(response, null, 2)}</code></pre>
                `);
                // 代码高亮
                if(window.Prism) {
                    Prism.highlightElement($result.find('code')[0]);
                }
            },
            error: function(xhr) {
                // 显示错误信息
                let errorMessage = '请求失败';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || response.error || errorMessage;
                } catch(e) {}
                
                $result.html(`
                    <div class="error-message">
                        <p>错误 (${xhr.status}): ${errorMessage}</p>
                    </div>
                `);
            },
            complete: function() {
                // 移除加载状态
                $submitBtn.removeClass('loading');
                $submitBtn.prop('disabled', false);
            }
        });
    });
    
    // 参数表单验证
    $('.test-form input[required]').on('input', function() {
        const $input = $(this);
        const $formGroup = $input.closest('.form-group');
        
        if($input.val().trim()) {
            $formGroup.removeClass('has-error');
        } else {
            $formGroup.addClass('has-error');
        }
    });
    
    // 切换请求体格式(JSON/Form Data)
    $('.body-format-switch').on('change', function() {
        const format = $(this).val();
        const $form = $(this).closest('form');
        const $jsonInput = $form.find('.json-input');
        const $formInputs = $form.find('.form-inputs');
        
        if(format === 'json') {
            $jsonInput.show();
            $formInputs.hide();
        } else {
            $jsonInput.hide();
            $formInputs.show();
        }
    });
    
    // JSON编辑器初始化
    const jsonEditors = {};
    $('.json-input textarea').each(function() {
        const $textarea = $(this);
        const editorId = $textarea.attr('id');
        
        if(window.JSONEditor) {
            jsonEditors[editorId] = new JSONEditor(this, {
                mode: 'code',
                modes: ['code', 'tree'],
                onError: function(err) {
                    const $formGroup = $textarea.closest('.form-group');
                    if(err) {
                        $formGroup.addClass('has-error');
                        $formGroup.find('.error-message').text(err.message);
                    } else {
                        $formGroup.removeClass('has-error');
                        $formGroup.find('.error-message').text('');
                    }
                }
            });
        }
    });
    
     // 处理API购买
     $('.purchase-api').on('click', function(e) {
         e.preventDefault();
         
         const $btn = $(this);
         const apiId = $btn.data('api-id');
         const price = $btn.data('price');
         
         if(!window.isUserLoggedIn) {
             // 显示登录提示
             zib_login.show();
             return;
         }
         
         // 确认购买
         if(confirm(`确定要购买此API的访问权限吗？\n价格: ${price}元`)) {
             $btn.addClass('loading');
             
             // 调用购买接口
             $.ajax({
                 url: window.ajaxurl,
                 type: 'POST',
                 data: {
                     action: 'yxs_api_purchase',
                     api_id: apiId,
                     _ajax_nonce: window.apiNonce
                 },
                 success: function(response) {
                     if(response.success) {
                         // 跳转到支付页面
                         window.location.href = response.data.pay_url;
                     } else {
                         alert(response.data);
                     }
                 },
                 error: function() {
                     alert('购买请求失败，请重试');
                 },
                 complete: function() {
                     $btn.removeClass('loading');
                 }
             });
         }
     });
     
     // 暗色模式切换【已修改类名 darkmode→dark-theme】
     function updateTheme() {
         const isDark = $('body').hasClass('dark-theme');
         $('.api-card').toggleClass('dark-theme', isDark);
         
         // 更新代码高亮主题
         if(window.Prism) {
             $('pre code').each(function() {
                 Prism.highlightElement(this);
             });
         }
         
         // 更新JSON编辑器主题
         Object.values(jsonEditors).forEach(editor => {
             if(editor && editor.setTheme) {
                 editor.setTheme(isDark ? 'ace/theme/monokai' : 'ace/theme/github');
             }
         });
     }
     
     // 监听暗色模式变化
     const observer = new MutationObserver(function(mutations) {
         mutations.forEach(function(mutation) {
             if(mutation.attributeName === 'class') {
                 updateTheme();
             }
         });
     });
     
     observer.observe(document.body, {
         attributes: true
     });
     
     // 初始化主题
     updateTheme();
 });