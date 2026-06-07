<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

if (!isset($_SESSION['admin_id'])) { 
    header('Location: login.php'); 
    exit; 
}

if (file_exists('../config.php')) { 
    require_once '../config.php'; 
} else { 
    die("出现错误！配置文件丢失。"); 
}

$username = htmlspecialchars($_SESSION['admin_username']);
$feedback_msg = '';
$feedback_type = '';
$advertisement = null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("参数错误：广告位 ID 不存在");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM sl_advertisements WHERE id = ?");
    $stmt->execute([$id]);
    $advertisement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advertisement) {
        die("未找到该广告位信息");
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $feedback_msg = "非法请求，请刷新页面后重试";
            $feedback_type = "error";
        } else {
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'link_url' => trim($_POST['link_url'] ?? ''),
                'contact' => trim($_POST['contact'] ?? ''),
                'status' => $_POST['status'] ?? 'active',
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            ];
            
            if (empty($data['title'])) {
                throw new Exception("广告标题不能为空");
            }
            if (mb_strlen($data['title']) > 100) {
                throw new Exception("广告标题不能超过 100 个字符");
            }
            if (empty($data['link_url']) || !filter_var($data['link_url'], FILTER_VALIDATE_URL)) {
                throw new Exception("请输入有效的链接 URL（以 http://或 https://开头）");
            }
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                if ($data['start_date'] > $data['end_date']) {
                    throw new Exception("开始日期不能晚于结束日期");
                }
            }
            
            $updateStmt = $pdo->prepare("UPDATE sl_advertisements SET title = ?, link_url = ?, contact = ?, status = ?, sort_order = ?, start_date = ?, end_date = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$data['title'], $data['link_url'], $data['contact'], $data['status'], $data['sort_order'], $data['start_date'], $data['end_date'], $id]);
            
            if ($updateStmt->rowCount() > 0) {
                $_SESSION['feedback_msg'] = "广告位更新成功！";
                $_SESSION['feedback_type'] = "success";
            } else {
                $_SESSION['feedback_msg'] = "广告位未发生任何更改";
                $_SESSION['feedback_type'] = "info";
            }
            
            header('Location: edit_advertisement.php?id=' . $id);
            exit;
        }
    }
} catch (Exception $e) {
    $feedback_msg = $e->getMessage();
    $feedback_type = "error";
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['feedback_msg'])) {
    $feedback_msg = $_SESSION['feedback_msg'];
    $feedback_type = $_SESSION['feedback_type'];
    unset($_SESSION['feedback_msg'], $_SESSION['feedback_type']);
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<title>修改广告 - 后台管理</title>
<link rel="stylesheet" type="text/css" href="../assets/css/materialdesignicons.min.css">
<link rel="stylesheet" type="text/css" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="../assets/css/style.min.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <header class="card-header">
                    <div class="card-title">
                        <i class="mdi mdi-pencil me-2"></i>修改广告位
                    </div>
                    <div class="card-action">
                        <a href="advertisements.php" class="btn btn-outline-secondary btn-sm">
                            <i class="mdi mdi-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </header>
                <div class="card-body">
                    <?php if ($feedback_msg): ?>
                    <div class="alert alert-<?= $feedback_type === 'success' ? 'success' : ($feedback_type === 'info' ? 'info' : 'danger') ?> alert-dismissible fade show mb-3">
                        <?= htmlspecialchars($feedback_msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if ($advertisement): ?>
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id" value="<?= $advertisement['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">广告标题 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $advertisement['title']) ?>" placeholder="请输入广告标题" required maxlength="100">
                                <div class="invalid-feedback">请输入有效的广告标题（不超过 100 字符）</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">链接 URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" name="link_url" value="<?= htmlspecialchars($_POST['link_url'] ?? $advertisement['link_url']) ?>" placeholder="请输入 http://或 https://开头的链接地址" required>
                                <div class="invalid-feedback">请输入有效的 URL（以 http://或 https://开头）</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">站长联系方式</label>
                                <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? $advertisement['contact']) ?>" placeholder="请输入站长联系方式">
                                <small class="text-muted">选填，用于广告问题联系</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">排序值</label>
                                <input type="number" class="form-control" name="sort_order" value="<?= htmlspecialchars($_POST['sort_order'] ?? $advertisement['sort_order'] ?? 0) ?>" placeholder="数字越大越靠前，默认 0" min="0" max="9999">
                                <small class="text-muted">范围：0-9999，数字越大排序越靠前</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">状态</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= (($_POST['status'] ?? $advertisement['status']) === 'active') ? 'selected' : '' ?>>启用</option>
                                    <option value="inactive" <?= (($_POST['status'] ?? $advertisement['status']) === 'inactive') ? 'selected' : '' ?>>禁用</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">广告有效期</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? $advertisement['start_date'] ?? '') ?>" placeholder="开始日期">
                                        <small class="text-muted">开始日期</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? $advertisement['end_date'] ?? '') ?>" placeholder="结束日期">
                                        <small class="text-muted">结束日期</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-save"></i> 更新广告位
                                </button>
                                <button type="reset" class="btn btn-outline-secondary ms-2">
                                    <i class="mdi mdi-refresh"></i> 重置表单
                                </button>
                                <a href="advertisements.php" class="btn btn-outline-secondary ms-2">
                                    <i class="mdi mdi-cancel"></i> 取消
                                </a>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="../assets/js/jquery.min.js"></script>
<script type="text/javascript" src="../assets/js/popper.min.js"></script>
<script type="text/javascript" src="../assets/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../assets/js/main.min.js"></script>
<script>
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>
</html>
