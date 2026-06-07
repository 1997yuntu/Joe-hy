<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (file_exists('../config.php')) { require_once '../config.php'; } else { die("出现错误！配置文件丢失。"); }
$username = htmlspecialchars($_SESSION['admin_username']);
$feedback_msg = ''; $feedback_type = ''; $page_title = 'API接口限流管理';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $create_table_sql = "CREATE TABLE IF NOT EXISTS `sl_api_limits` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `api_endpoint` varchar(255) NOT NULL COMMENT 'API接口端点',
        `api_name` varchar(100) NOT NULL COMMENT '接口名称',
        `enable_free_limit` tinyint(1) NOT NULL DEFAULT 1,
        `free_qps_seconds` int(11) NOT NULL DEFAULT 1,
        `free_qps_limit` int(11) NOT NULL DEFAULT 10,
        `enable_member_limit` tinyint(1) NOT NULL DEFAULT 1,
        `member_qps_seconds` int(11) NOT NULL DEFAULT 1,
        `member_qps_limit` int(11) NOT NULL DEFAULT 20,
        `status` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_api_endpoint` (`api_endpoint`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API接口独立限流配置表';";
    $pdo->exec($create_table_sql);

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        switch ($action) {
            case 'add': case 'edit':
                $api_endpoint = trim($_POST['api_endpoint']);
                $api_name = trim($_POST['api_name']);
                $enable_free_limit = isset($_POST['enable_free_limit']) ? 1 : 0;
                $free_qps_seconds = intval($_POST['free_qps_seconds']);
                $free_qps_limit = intval($_POST['free_qps_limit']);
                $enable_member_limit = isset($_POST['enable_member_limit']) ? 1 : 0;
                $member_qps_seconds = intval($_POST['member_qps_seconds']);
                $member_qps_limit = intval($_POST['member_qps_limit']);
                $status = isset($_POST['status']) ? 1 : 0;

                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO sl_api_limits (api_endpoint, api_name, enable_free_limit, free_qps_seconds, free_qps_limit, enable_member_limit, member_qps_seconds, member_qps_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$api_endpoint, $api_name, $enable_free_limit, $free_qps_seconds, $free_qps_limit, $enable_member_limit, $member_qps_seconds, $member_qps_limit, $status]);
                    $feedback_msg = '接口限流配置已添加成功。';
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE sl_api_limits SET api_endpoint=?, api_name=?, enable_free_limit=?, free_qps_seconds=?, free_qps_limit=?, enable_member_limit=?, member_qps_seconds=?, member_qps_limit=?, status=? WHERE id=?");
                    $stmt->execute([$api_endpoint, $api_name, $enable_free_limit, $free_qps_seconds, $free_qps_limit, $enable_member_limit, $member_qps_seconds, $member_qps_limit, $status, $id]);
                    $feedback_msg = '接口限流配置已更新成功。';
                }
                $feedback_type = 'success';
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM sl_api_limits WHERE id=?");
                $stmt->execute([$id]);
                $feedback_msg = '接口限流配置已删除成功。';
                $feedback_type = 'success';
                break;

            case 'get':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("SELECT * FROM sl_api_limits WHERE id=?");
                $stmt->execute([$id]);
                echo json_encode(['code' => 0, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
                exit;
        }
    }

    $stmt_api = $pdo->query("SELECT * FROM sl_api_limits ORDER BY api_endpoint");
    $api_limits = $stmt_api->fetchAll(PDO::FETCH_ASSOC);

    $stmt_all = $pdo->query("SELECT id, name, endpoint FROM sl_apis WHERE status='normal' ORDER BY name ASC");
    $all_api_list = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $feedback_msg = '操作失败: ' . $e->getMessage();
    $feedback_type = 'error';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API接口限流管理</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/css/style.min.css">
    <style>
        .select2-container--default .select2-selection--single { height: 38px; display: flex; align-items: center; border: 1px solid #ced4da; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
    </style>
</head>
<body>
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">API接口限流管理</h5>
        </div>
        <div class="card-body">
            <?php if ($feedback_msg): ?>
                <div class="alert alert-<?php echo $feedback_type === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo $feedback_msg; ?>
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <label class="input-group-text">快速选择接口</label>
                        <select id="quickApiSelect" class="form-select">
                            <option value="">-- 请选择接口（支持搜索） --</option>
                            <?php foreach($all_api_list as $a): ?>
                                <option value="<?php echo htmlspecialchars($a['endpoint']); ?>" data-name="<?php echo htmlspecialchars($a['name']); ?>">
                                    <?php echo htmlspecialchars($a['name']); ?> (<?php echo htmlspecialchars($a['endpoint']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" id="btnSelectApi">确认选中</button>
                    </div>
                    <div class="form-text text-muted">
                        点击下拉框可搜索接口名称，选择后点确认自动填入表单
                    </div>
                </div>
            </div>

            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#apiLimitModal">
                <i class="mdi mdi-plus"></i> 添加新接口限流
            </button>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                    <tr>
                        <th>接口端点</th>
                        <th>接口名称</th>
                        <th>无会员限流</th>
                        <th>会员限流</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($api_limits)): ?>
                        <tr><td colspan="6" class="text-center text-muted">暂无配置</td></tr>
                    <?php else: ?>
                        <?php foreach($api_limits as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['api_endpoint'])?></td>
                                <td><?php echo htmlspecialchars($item['api_name'])?></td>
                                <td><?php echo $item['enable_free_limit'] ? "{$item['free_qps_limit']}/{$item['free_qps_seconds']}s" : '<span class="text-muted">关闭</span>'?></td>
                                <td><?php echo $item['enable_member_limit'] ? "{$item['member_qps_limit']}/{$item['member_qps_seconds']}s" : '<span class="text-muted">关闭</span>'?></td>
                                <td><?php echo $item['status'] ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-danger">禁用</span>'?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit" data-id="<?php echo $item['id']?>">编辑</button>
                                    <button class="btn btn-sm btn-danger del" data-id="<?php echo $item['id']?>">删除</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="apiLimitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加接口限流</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" id="modal-action" value="add">
                    <input type="hidden" name="id" id="modal-id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>接口端点</label>
                            <input type="text" name="api_endpoint" id="modal-api-endpoint" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>接口名称</label>
                            <input type="text" name="api_name" id="modal-api-name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="enable_free_limit" id="free_enable" checked>
                                <label class="form-check-label">启用无会员限流</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>时间窗口(秒)</label>
                            <input type="number" class="form-control" name="free_qps_seconds" value="1" min="1">
                        </div>
                        <div class="col-md-3">
                            <label>最大请求数</label>
                            <input type="number" class="form-control" name="free_qps_limit" value="10" min="1">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="enable_member_limit" id="mem_enable" checked>
                                <label class="form-check-label">启用会员限流</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>时间窗口(秒)</label>
                            <input type="number" class="form-control" name="member_qps_seconds" value="1" min="1">
                        </div>
                        <div class="col-md-3">
                            <label>最大请求数</label>
                            <input type="number" class="form-control" name="member_qps_limit" value="20" min="1">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="status" id="status" checked>
                        <label class="form-check-label">启用该配置</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>

<!-- 支持搜索的下拉选择插件 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function(){
    // 启用下拉搜索
    $('#quickApiSelect').select2({
        placeholder: '搜索接口名称或端点',
        allowClear: true,
        width: '100%'
    });

    // 确认选中 → 自动填入模态框
    $('#btnSelectApi').click(function(){
        const opt = $('#quickApiSelect option:selected');
        const endpoint = opt.val();
        const name = opt.data('name');
        if(!endpoint) return alert('请选择一个接口');
        
        $('#modal-api-endpoint').val(endpoint);
        $('#modal-api-name').val(name);
        $('#apiLimitModal').modal('show');
    });

    // 编辑
    $('.edit').click(function(){
        const id = $(this).data('id');
        $.post('api_limits.php',{action:'get',id:id},function(res){
            $('#modal-action').val('edit');
            $('#modal-id').val(res.data.id);
            $('#modal-api-endpoint').val(res.data.api_endpoint);
            $('#modal-api-name').val(res.data.api_name);
            $('[name=enable_free_limit]').prop('checked', res.data.enable_free_limit == 1);
            $('[name=free_qps_seconds]').val(res.data.free_qps_seconds);
            $('[name=free_qps_limit]').val(res.data.free_qps_limit);
            $('[name=enable_member_limit]').prop('checked', res.data.enable_member_limit == 1);
            $('[name=member_qps_seconds]').val(res.data.member_qps_seconds);
            $('[name=member_qps_limit]').val(res.data.member_qps_limit);
            $('[name=status]').prop('checked', res.data.status == 1);
            $('#apiLimitModal').modal('show');
        },'json');
    });

    // 删除
    $('.del').click(function(){
        if(!confirm('确定删除？')) return;
        const id = $(this).data('id');
        const form = $('<form method="post"></form>');
        form.append('<input name="action" value="delete">');
        form.append('<input name="id" value="'+id+'">');
        $('body').append(form);
        form.submit();
    });

    // 关闭时重置
    $('#apiLimitModal').on('hidden.bs.modal',function(){
        $(this).find('form')[0].reset();
        $('#modal-action').val('add');
        $('#modal-id').val('');
    });
});
</script>
</body>
</html>
