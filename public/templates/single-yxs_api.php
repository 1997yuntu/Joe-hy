<?php
/**
 * API详情页面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取API详情
$endpoint = get_post_meta(get_the_ID(), 'endpoint', true);
$method = get_post_meta(get_the_ID(), 'method', true);
$response_format = get_post_meta(get_the_ID(), 'response_format', true);
$request_params = get_post_meta(get_the_ID(), 'request_params', true);
$response_example = get_post_meta(get_the_ID(), 'response_example', true);

// 解码JSON数据
$request_params = !empty($request_params) ? json_decode($request_params, true) : array();
$response_format = !empty($response_format) ? json_decode($response_format, true) : array();
$response_example = !empty($response_example) ? json_decode($response_example, true) : array();

// 获取用户API密钥
$api_manager = new Yxs_API_Manager();
$api_key = '';
if (is_user_logged_in()) {
    $key_data = $api_manager->check_user_api_key();
    if ($key_data) {
        $api_key = $key_data['api_key'];
    }
}

get_header();
?>

<div class="api-doc-container">
    <!-- API基本信息 -->
    <div class="api-header">
        <h1><?php the_title(); ?></h1>
        <div class="api-meta">
            <span class="api-method <?php echo strtolower($method); ?>"><?php echo $method; ?></span>
            <span class="api-endpoint"><?php echo esc_html($endpoint); ?></span>
        </div>
    </div>

    <!-- API说明 -->
    <div class="api-description card">
        <h2>接口说明</h2>
        <div class="card-body">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- API参数说明 -->
    <div class="api-params card">
        <h2>参数说明</h2>
        <div class="card-body">
            <h3>请求地址</h3>
            <div class="api-url-info">
                <div class="url-row">
                    <span class="label">接口地址：</span>
                    <code class="api-full-url" data-clipboard-text="<?php echo esc_attr(home_url('/wp-json/yxs/v1' . $endpoint)); ?>"><?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?></code>
                </div>
                <div class="url-row">
                    <span class="label">请求方式：</span>
                    <span class="api-method <?php echo strtolower($method); ?>"><?php echo $method; ?></span>
                </div>
            </div>

            <h3>请求头</h3>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>参数名</th>
                        <th>必填</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>X-API-Key</td>
                        <td><span class="required">是</span></td>
                        <td>API密钥，用于认证请求</td>
                    </tr>
                    <tr>
                        <td>Content-Type</td>
                        <td><span class="required">是</span></td>
                        <td>请求内容类型，固定为 application/json</td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($request_params)): ?>
            <h3>请求参数</h3>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>参数名</th>
                        <th>类型</th>
                        <th>必填</th>
                        <th>说明</th>
                        <th>示例值</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($request_params as $param_name => $param_info): ?>
                    <tr>
                        <td><?php echo esc_html($param_name); ?></td>
                        <td><?php echo esc_html($param_info['type']); ?></td>
                        <td>
                            <span class="<?php echo !empty($param_info['required']) ? 'required' : 'optional'; ?>">
                                <?php echo !empty($param_info['required']) ? '是' : '否'; ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($param_info['description']); ?></td>
                        <td><code><?php echo esc_html($param_info['example'] ?? ''); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($response_format)): ?>
            <h3>响应格式</h3>
            <pre><code class="language-json"><?php echo esc_html(json_encode($response_format, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></code></pre>
            <?php endif; ?>

            <?php if (!empty($response_example)): ?>
            <h3>响应示例</h3>
            <pre><code class="language-json"><?php echo esc_html(json_encode($response_example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></code></pre>
            <?php endif; ?>

            <h3>状态码说明</h3>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>状态码</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>200</td>
                        <td>请求成功</td>
                    </tr>
                    <tr>
                        <td>400</td>
                        <td>请求参数错误</td>
                    </tr>
                    <tr>
                        <td>401</td>
                        <td>未授权（API密钥无效或缺失）</td>
                    </tr>
                    <tr>
                        <td>403</td>
                        <td>禁止访问（没有权限）</td>
                    </tr>
                    <tr>
                        <td>404</td>
                        <td>接口不存在</td>
                    </tr>
                    <tr>
                        <td>429</td>
                        <td>请求过于频繁</td>
                    </tr>
                    <tr>
                        <td>500</td>
                        <td>服务器内部错误</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- API测试区域 -->
    <div class="api-test-area">
        <div class="test-panel card">
            <h2>在线测试</h2>
            <div class="card-body">
                <?php if (!is_user_logged_in()) : ?>
                    <div class="login-notice">
                        请先<a href="<?php echo wp_login_url(get_permalink()); ?>">登录</a>后再进行接口测试
                    </div>
                <?php else : ?>
                    <form id="api-test-form" class="api-test-form">
                        <!-- 隐藏的端点和方法字段 -->
                        <input type="hidden" id="endpoint" value="<?php echo esc_attr($endpoint); ?>">
                        <input type="hidden" id="method" value="<?php echo esc_attr($method); ?>">
                        
                        <!-- API密钥 -->
                        <div class="form-group">
                            <label>API密钥</label>
                            <div class="api-key-group">
                                <input type="text" id="api-key" value="<?php echo esc_attr($api_key); ?>" readonly>

                            </div>
                        </div>

                        <!-- 请求参数 -->
                        <?php if (!empty($request_params)) : ?>
                            <div class="form-group">
                                <label>请求参数</label>
                                <div class="params-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>参数名</th>
                                                <th>类型</th>
                                                <th>必填</th>
                                                <th>说明</th>
                                                <th>值</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($request_params as $param_name => $param_info) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($param_name); ?></td>
                                                    <td><?php echo esc_html($param_info['type']); ?></td>
                                                    <td>
                                                        <span class="<?php echo !empty($param_info['required']) ? 'required' : 'optional'; ?>">
                                                            <?php echo !empty($param_info['required']) ? '是' : '否'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo esc_html($param_info['description']); ?></td>
                                                    <td>
                                                        <?php if ($param_info['type'] === 'boolean') : ?>
                                                            <select name="<?php echo esc_attr($param_name); ?>" class="param-input">
                                                                <option value="true">true</option>
                                                                <option value="false">false</option>
                                                            </select>
                                                        <?php elseif ($param_info['type'] === 'number') : ?>
                                                            <input type="number" name="<?php echo esc_attr($param_name); ?>" class="param-input">
                                                        <?php else : ?>
                                                            <input type="text" name="<?php echo esc_attr($param_name); ?>" class="param-input">
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 发送请求按钮 -->
                        <div class="form-group">
                            <button type="submit" class="submit-btn">发送请求</button>
                        </div>

                        <!-- 响应结果 -->
                        <div class="response-area">
                            <div class="response-header">
                                <h3>响应结果</h3>
                                <div class="response-meta">
                                    <span class="response-time"></span>
                                    <span class="response-status"></span>
                                </div>
                            </div>
                            <pre id="response-content"></pre>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- 示例代码 -->
        <div class="example-panel card">
            <h2>示例代码</h2>
            <div class="card-body">
                <div class="code-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-lang="curl">cURL</button>
                        <button class="tab-btn" data-lang="python">Python</button>
                        <button class="tab-btn" data-lang="php">PHP</button>
                        <button class="tab-btn" data-lang="javascript">JavaScript</button>
                        <button class="tab-btn" data-lang="java">Java</button>
                    </div>
                    <div class="tab-contents">
                        <div class="tab-content active" data-lang="curl">
                            <pre><code class="language-bash"># cURL示例
curl -X <?php echo $method; ?> \
    -H "X-API-Key: your_api_key" \
    -H "Content-Type: application/json" \
    <?php if ($method !== 'GET'): ?>-d '{"name": "example"}' \<?php endif; ?>

    <?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?></code></pre>
                        </div>
                        <div class="tab-content" data-lang="python">
                            <pre><code class="language-python"># Python示例
import requests
import json

url = "<?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?>"
headers = {
    "X-API-Key": "your_api_key",
    "Content-Type": "application/json"
}
<?php if ($method === 'GET'): ?>
response = requests.get(url, headers=headers)
<?php else: ?>
data = {
    "name": "example"
}
response = requests.<?php echo strtolower($method); ?>(url, headers=headers, json=data)
<?php endif; ?>

print(response.json())</code></pre>
                        </div>
                        <div class="tab-content" data-lang="php">
                            <pre><code class="language-php"># PHP示例
<?php
$code = <<<EOT
<?php
\$url = "{$endpoint}";
\$api_key = "your_api_key";

\$curl = curl_init();
curl_setopt_array(\$curl, [
    CURLOPT_URL => "{$endpoint}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-API-Key: \$api_key",
        "Content-Type: application/json"
    ],
EOT;

if ($method !== 'GET') {
    $code .= <<<EOT
    
    CURLOPT_CUSTOMREQUEST => "{$method}",
    CURLOPT_POSTFIELDS => json_encode([
        "name" => "example"
    ]),
EOT;
}

$code .= <<<EOT

]);

\$response = curl_exec(\$curl);
\$data = json_decode(\$response, true);

curl_close(\$curl);
print_r(\$data);
EOT;
echo htmlspecialchars($code);
?></code></pre>
                        </div>
                        <div class="tab-content" data-lang="javascript">
                            <pre><code class="language-javascript">// JavaScript示例
const apiUrl = '<?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?>';
const apiKey = 'your_api_key';

<?php if ($method === 'GET'): ?>
fetch(apiUrl, {
    method: '<?php echo $method; ?>',
    headers: {
        'X-API-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
<?php else: ?>
fetch(apiUrl, {
    method: '<?php echo $method; ?>',
    headers: {
        'X-API-Key': apiKey,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'example'
    })
})
<?php endif; ?>
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</code></pre>
                        </div>
                        <div class="tab-content" data-lang="java">
                            <pre><code class="language-java">// Java示例
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.net.URI;

public class ApiExample {
    public static void main(String[] args) {
        HttpClient client = HttpClient.newHttpClient();
        <?php if ($method === 'GET'): ?>
        HttpRequest request = HttpRequest.newBuilder()
            .uri(URI.create("<?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?>"))
            .header("X-API-Key", "your_api_key")
            .header("Content-Type", "application/json")
            .GET()
            .build();
        <?php else: ?>
        String jsonBody = "{\"name\": \"example\"}";
        HttpRequest request = HttpRequest.newBuilder()
            .uri(URI.create("<?php echo home_url('/wp-json/yxs/v1' . $endpoint); ?>"))
            .header("X-API-Key", "your_api_key")
            .header("Content-Type", "application/json")
            .<?php echo $method; ?>(HttpRequest.BodyPublishers.ofString(jsonBody))
            .build();
        <?php endif; ?>

        try {
            HttpResponse<String> response = client.send(request,
                HttpResponse.BodyHandlers.ofString());
            System.out.println(response.body());
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 基础样式 */
.api-doc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* 卡片样式 */
.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.card h2 {
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-size: 18px;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

/* API头部样式 */
.api-header {
    margin-bottom: 30px;
}

.api-header h1 {
    margin: 0 0 15px 0;
    font-size: 24px;
    font-weight: 600;
}

.api-meta {
    display: flex;
    align-items: center;
    gap: 15px;
}

.api-method {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}

.api-method.get { background: #e3f2fd; color: #1976d2; }
.api-method.post { background: #e8f5e9; color: #388e3c; }
.api-method.put { background: #fff3e0; color: #f57c00; }
.api-method.delete { background: #ffebee; color: #d32f2f; }

.api-endpoint {
    font-family: monospace;
    font-size: 14px;
    color: #666;
}

/* 参数表格样式 */
.params-table {
    margin: 15px 0;
    overflow-x: auto;
}

.params-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.params-table th, .params-table td {
    padding: 10px;
    border: 1px solid #eee;
    text-align: left;
}

.params-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.params-table .required {
    color: #dc3545;
    font-weight: bold;
}

.params-table .optional {
    color: #6c757d;
}

.param-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* 按钮样式 */
.submit-btn {
    background: #1976d2;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.submit-btn:hover {
    background: #1565c0;
}

.copy-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
}

.copy-btn:hover {
    background: #e9ecef;
}

/* API密钥组样式 */
.api-key-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* API URL信息样式 */
.api-url-info {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.url-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: nowrap;
}

.url-row:last-child {
    margin-bottom: 0;
}

.url-row .label {
    font-weight: 600;
    color: #666;
    min-width: 80px;
    flex-shrink: 0;
}

.api-full-url {
    flex: 1;
    font-family: monospace;
    background: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 14px;
    color: #333;
    word-break: break-all;
    margin-right: 0;
    overflow-x: auto;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s;
}

.api-full-url:hover {
    background: #f0f0f0;
}

.api-full-url.copied {
    background: #e8f5e9;
    border-color: #4caf50;
}

/* 响应区域样式 */
.response-area {
    margin-top: 20px;
    display: none;
}

.response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.response-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.response-meta {
    font-size: 14px;
    color: #666;
}

#response-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
}

/* 代码示例样式 */
.code-tabs {
    margin-top: 15px;
}

.tab-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.tab-btn {
    padding: 8px 16px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid transparent;
}

.tab-btn.active {
    color: #1976d2;
    border-bottom-color: #1976d2;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .api-doc-container {
        padding: 10px;
    }

    .api-meta {
        flex-wrap: wrap;
    }

    .params-table {
        margin: 10px -20px;
        width: calc(100% + 40px);
    }

    .params-table td {
        min-width: 120px;
    }
}

/* 调用记录样式 */
.logs-table-wrapper {
    overflow-x: auto;
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.logs-table th,
.logs-table td {
    padding: 10px;
    border: 1px solid #eee;
    text-align: left;
}

.logs-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.logs-table .status-code {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
}

.logs-table .status-code.success {
    background: #e8f5e9;
    color: #388e3c;
}

.logs-table .status-code.error {
    background: #ffebee;
    color: #d32f2f;
}

.view-data-btn {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.view-data-btn:hover {
    background: #e0e0e0;
}

.no-logs {
    text-align: center;
    color: #666;
    padding: 20px;
}

/* 数据查看模态框 */
.data-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.data-modal-content {
    position: relative;
    background: #fff;
    margin: 5% auto;
    padding: 20px;
    width: 90%;
    max-width: 800px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.data-modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.data-modal-body {
    margin-top: 20px;
}

.data-modal-body pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
    max-height: 400px;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .logs-table td {
        min-width: 100px;
    }
    
    .data-modal-content {
        margin: 10% auto;
        width: 95%;
    }
}
/*=========子比夜间 dark-theme 深色样式（适配本页面现有class：.card）=========*/
body.dark-theme .card {
    background:#1E2029 !important;
    border-color:#343746 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
}
body.dark-theme .card h2 {
    border-bottom-color:#343746 !important;
    color:#E5E7EB !important;
}
body.dark-theme .api-url-info {
    background:#272935 !important;
    border:1px solid #343746 !important;
}
body.dark-theme .params-table th{
    background:#272935 !important;
    border-color:#343746 !important;
    color:#E5E7EB !important;
}
body.dark-theme .params-table td{
    border-color:#343746 !important;
    color:#E5E7EB !important;
}
body.dark-theme .param-input {
    background:#232530 !important;
    border-color:#343746 !important;
    color:#E5E7EB !important;
}
body.dark-theme #response-content {
    background:#272935 !important;
    color:#DFE1E5 !important;
}
body.dark-theme pre {
    background:#272935 !important;
}
body.dark-theme pre code {
    color:#DFE1E5 !important;
}
body.dark-theme h1,
body.dark-theme h3,
body.dark-theme .label,
body.dark-theme .api-endpoint,
body.dark-theme .response-meta{
    color:#E5E7EB !important;
}
body.dark-theme .api-full-url{
    background:#232530 !important;
    border-color:#343746 !important;
    color:#DFE1E5 !important;
}
body.dark-theme .tab-btn{
    color:#9CA3AF !important;
}
body.dark-theme .tab-btn.active{
    color:#90caf9 !important;
    border-bottom-color:#1976d2 !important;
}
body.dark-theme .api-method.get { background:#1a237e !important;color:#90caf9 !important; }
body.dark-theme .api-method.post { background:#1b5e20 !important;color:#a5d6a7 !important; }
body.dark-theme .api-method.put { background:#e65100 !important;color:#ffe0b2 !important; }
body.dark-theme .api-method.delete { background:#b71c1c !important;color:#ffcdd2 !important; }
body.dark-theme .required{color:#ff8787 !important;}
body.dark-theme .optional{color:#9CA3AF !important;}
body.dark-theme .data-modal-content{background:#1E2029 !important;}
body.dark-theme .data-modal-close{color:#E5E7EB !important;}
body.dark-theme .data-modal-body pre{background:#272935 !important;}
</style>

<!-- 数据查看模态框 -->
<div class="data-modal" id="dataModal">
    <div class="data-modal-content">
        <span class="data-modal-close">&times;</span>
        <h3 class="data-modal-title"></h3>
        <div class="data-modal-body">
            <pre><code></code></pre>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 初始化复制功能
    var clipboard = new ClipboardJS('.api-full-url');
    
    // 复制成功后的反馈
    clipboard.on('success', function(e) {
        var $target = $(e.trigger);
        $target.addClass('copied');
        
        // 2秒后移除复制成功的样式
        setTimeout(function() {
            $target.removeClass('copied');
        }, 2000);
        
        e.clearSelection();
    });

    // 数据查看功能
    $('.view-data-btn').on('click', function() {
        var content = $(this).data('content');
        var title = $(this).text();
        var formattedContent = '';
        
        try {
            // 尝试格式化JSON
            formattedContent = JSON.stringify(JSON.parse(content), null, 2);
        } catch (e) {
            formattedContent = content;
        }
        
        $('#dataModal .data-modal-title').text(title);
        $('#dataModal .data-modal-body code').text(formattedContent);
        $('#dataModal').fadeIn();
    });

    // 关闭模态框
    $('.data-modal-close').on('click', function() {
        $('#dataModal').fadeOut();
    });

    // 点击模态框外部关闭
    $(window).on('click', function(e) {
        if ($(e.target).is('.data-modal')) {
            $('.data-modal').fadeOut();
        }
    });
});
</script>

<?php get_footer(); ?> 