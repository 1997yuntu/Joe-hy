<?php
@session_start();
@error_reporting(0);
@ini_set('display_errors', 'Off');
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (file_exists('../config.php')) { require_once '../config.php'; } else { die("出现错误！配置文件丢失。"); }
$username = isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') : '';
$feedback_msg = '';
$feedback_type = '';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 表结构自动升级（ALTER 不支持事务，去掉事务，彻底解决无事务报错）
    $columns = $pdo->query("SHOW COLUMNS FROM `sl_cdkeys`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('type', $columns)) {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `type` ENUM('balance','points','membership') NOT NULL DEFAULT 'balance' AFTER `cdkey`");
    } else {
        $pdo->exec("ALTER TABLE `sl_cdkeys` CHANGE COLUMN `type` `type` ENUM('balance','points','membership') NOT NULL DEFAULT 'balance'");
    }
    if (!in_array('points', $columns)) {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `points` INT NOT NULL DEFAULT 0 AFTER `balance`");
    }
    if (!in_array('membership_days', $columns)) {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `membership_days` INT NOT NULL DEFAULT 0 AFTER `points`");
    }
    if (in_array('status', $columns)) {
        $statusColumn = $pdo->query("SELECT DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'sl_cdkeys' AND COLUMN_NAME = 'status'")->fetch();
        if (strpos($statusColumn['COLUMN_TYPE'], 'unused') === false || strpos($statusColumn['COLUMN_TYPE'], 'used') === false) {
            $pdo->exec("ALTER TABLE `sl_cdkeys` CHANGE COLUMN `status` `status` ENUM('unused','used') NOT NULL DEFAULT 'unused'");
        }
    } else {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `status` ENUM('unused','used') NOT NULL DEFAULT 'unused' AFTER `membership_days`");
    }
    if (!in_array('used_by_user_id', $columns)) {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `used_by_user_id` INT NULL AFTER `status`");
    }
    if (!in_array('used_at', $columns)) {
        $pdo->exec("ALTER TABLE `sl_cdkeys` ADD COLUMN `used_at` DATETIME NULL AFTER `used_by_user_id`");
    }

    // 生成卡密（安全事务）
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'generate') {
            $count = filter_var($_POST['count'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
            $type = in_array($_POST['type'], ['balance','points','membership']) ? $_POST['type'] : 'balance';

            if ($type === 'balance') {
                $value = filter_var($_POST['balance'], FILTER_VALIDATE_FLOAT);
                if ($value === false || $value <= 0) throw new Exception('请输入有效的金额，必须大于0。');
                $value = round($value, 2);
            } elseif ($type === 'points') {
                $value = filter_var($_POST['points'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($value === false) throw new Exception('请输入有效的点数，必须为正整数。');
            } else {
                $value = filter_var($_POST['membership_days'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($value === false) throw new Exception('请输入有效的会员天数，必须为正整数。');
            }
            if ($count === false) throw new Exception('生成数量必须为1~1000之间的整数。');

            // 安全事务：只在写入时开启，杜绝无事务报错
            $pdo->beginTransaction();
            try {
                $values = [];
                $placeholders = [];
                for ($i=0; $i<$count; $i++) {
                    $key = strtoupper(bin2hex(random_bytes(16)));
                    $balance = $type==='balance' ? $value : 0;
                    $points = $type==='points' ? $value : 0;
                    $membership_days = $type==='membership' ? $value : 0;
                    $values[] = $key;
                    $values[] = $type;
                    $values[] = $balance;
                    $values[] = $points;
                    $values[] = $membership_days;
                    $placeholders[] = '(?, ?, ?, ?, ?)';
                }
                $stmt = $pdo->prepare("INSERT INTO sl_cdkeys (cdkey,type,balance,points,membership_days) VALUES ".implode(', ', $placeholders));
                $stmt->execute($values);
                $pdo->commit();

                $unit = $type==='balance' ? '元' : ($type==='points' ? '点' : '天');
                $_SESSION['feedback_msg'] = "成功生成 {$count} 个价值 {$value}{$unit} 的卡密！";
                $_SESSION['feedback_type'] = 'success';
            } catch (Exception $e) {
                // 安全回滚：仅存在事务时回滚
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'export') {
            $exportType = in_array($_POST['export_type'], ['all','used','unused']) ? $_POST['export_type'] : 'unused';
            $where = $exportType!=='all' ? "WHERE status = ?" : "";
            $params = $exportType!=='all' ? [$exportType] : [];
            $stmt = $pdo->prepare("SELECT cdkey,type,balance,points FROM sl_cdkeys {$where} ORDER BY id DESC");
            $stmt->execute($params);
            $keys = $stmt->fetchAll();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=cdkeys_export_'.date('YmdHis').'.csv');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['卡密','类型','价值']);
            foreach ($keys as $key) {
                $value = $key['type']==='balance' ? '¥'.number_format($key['balance'],2) : $key['points'].'点';
                fputcsv($output, [$key['cdkey'], $key['type']==='balance'?'余额':'点数', $value]);
            }
            fclose($output);
            exit;
        }
    } elseif (isset($_GET['action'])) {
        $action = $_GET['action'];
        if ($action === 'delete' && isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) throw new Exception('无效的卡密ID');
            $stmt = $pdo->prepare("DELETE FROM sl_cdkeys WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['feedback_msg'] = '卡密已删除';
            $_SESSION['feedback_type'] = 'success';
        } elseif ($action === 'cleanup') {
            $stmt = $pdo->prepare("DELETE FROM sl_cdkeys WHERE status='unused'");
            $stmt->execute();
            $_SESSION['feedback_msg'] = "成功清理 {$stmt->rowCount()} 个未使用卡密";
            $_SESSION['feedback_type'] = 'success';
        } else {
            throw new Exception('无效操作');
        }
        header('Location: cdkeys.php');
        exit;
    }

    if (isset($_SESSION['feedback_msg'])) {
        $feedback_msg = $_SESSION['feedback_msg'];
        $feedback_type = $_SESSION['feedback_type'];
        unset($_SESSION['feedback_msg'], $_SESSION['feedback_type']);
    }

    // 分页列表
    $page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_VALIDATE_INT)) : 1;
    $limit = 20;
    $offset = ($page-1)*$limit;
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM sl_cdkeys");
    $total = $totalStmt->fetchColumn();
    $totalPages = max(1, ceil($total/$limit));

    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM sl_cdkeys c 
        LEFT JOIN sl_users u ON c.used_by_user_id = u.id 
        ORDER BY c.id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $keys = $stmt->fetchAll();

} catch (Exception $e) {
    // 终极安全：回滚前判断是否存在事务
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $feedback_msg = '操作失败: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $feedback_type = 'error';
    $keys = [];
    $total = 0;
    $totalPages = 1;
    $page = 1;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-fullscreen" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="stylesheet" href="../assets/css/materialdesignicons.min.css">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/style.min.css">
<style>
.copyable{cursor:pointer;position:relative;transition:background-color:.2s}
.copyable:hover{background-color:#f8f9fa}
.copy-tooltip{position:absolute;top:-30px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;opacity:0;transition:opacity .3s;z-index:10;white-space:nowrap}
.copyable:hover .copy-tooltip{opacity:1}
.toast-container{position:fixed;top:20px;right:20px;z-index:1100}
.badge-balance{background:#d1fae5;color:#065f46}
.badge-points{background:#dbeafe;color:#1e40af}
.badge-warning{background:#fef3c7;color:#92400e}
.table-responsive{overflow-x:auto}
@media(max-width:768px){.card-search .row>div{margin-bottom:10px}.btn-group{flex-wrap:wrap}}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="toast-container">
        <?php if($feedback_msg): ?>
        <div class="toast show text-white bg-<?php echo $feedback_type=='success'?'success':'danger'?> border-0">
            <div class="d-flex">
                <div class="toast-body"><?php echo $feedback_msg?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <?php endif ?>
    </div>

    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card">
                <header class="card-header">
                    <h5 class="card-title">卡密管理</h5>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal"><i class="mdi mdi-download me-1"></i>导出卡密</button>
                    </div>
                </header>
                <div class="card-body">
                    <div class="card-search mb-3">
                        <form method="post" id="generateForm">
                            <input type="hidden" name="action" value="generate">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="row align-items-center">
                                        <label class="col-sm-4 col-form-label">卡密类型</label>
                                        <div class="col-sm-8">
                                            <select name="type" id="type-select" class="form-select" required>
                                                <option value="balance">余额卡密</option>
                                                <option value="points">点数卡密</option>
                                                <option value="membership">会员卡密</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="row align-items-center">
                                        <label class="col-sm-4 col-form-label">生成数量</label>
                                        <div class="col-sm-8">
                                            <input type="number" name="count" class="form-control" value="10" min="1" max="1000" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3" id="value-input-group">
                                    <div class="row align-items-center">
                                        <label class="col-sm-4 col-form-label" id="value-label">面额(元)</label>
                                        <div class="col-sm-8">
                                            <input type="number" step="0.01" name="balance" id="balance" class="form-control" value="10.00" min="0.01" required>
                                            <input type="number" step="1" name="points" id="points" class="form-control d-none" value="100" min="1">
                                            <input type="number" step="1" name="membership_days" id="membership_days" class="form-control d-none" value="30" min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary me-1"><i class="mdi mdi-plus me-1"></i>生成卡密</button>
                                    <a href="?action=cleanup" onclick="return confirm('确定清理所有未使用卡密？不可恢复！')" class="btn btn-danger"><i class="mdi mdi-delete me-1"></i>清理未使用</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">卡密</th>
                                    <th width="10%">类型</th>
                                    <th width="10%">价值</th>
                                    <th width="10%">状态</th>
                                    <th width="15%">使用者</th>
                                    <th width="15%">使用时间</th>
                                    <th width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($keys)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">暂无卡密</td></tr>
                                <?php else: foreach($keys as $i=>$key): ?>
                                <tr>
                                    <td><?php echo $offset+$i+1 ?></td>
                                    <td class="copyable" onclick="copy(this,'<?php echo htmlspecialchars($key['cdkey'])?>')">
                                        <code><?php echo $key['cdkey']?></code><span class="copy-tooltip">点击复制</span>
                                    </td>
                                    <td>
                                        <?php if($key['type']=='balance'): ?><span class="badge badge-balance">余额</span>
                                        <?php elseif($key['type']=='points'): ?><span class="badge badge-points">点数</span>
                                        <?php else: ?><span class="badge badge-warning">会员</span><?php endif?>
                                    </td>
                                    <td>
                                        <?php if($key['type']=='balance'): ?>¥<?php echo number_format($key['balance'],2)?>
                                        <?php elseif($key['type']=='points'): ?><?php echo $key['points']?>点
                                        <?php else: ?><?php echo $key['membership_days']?>天<?php endif?>
                                    </td>
                                    <td><?php echo $key['status']=='unused'?'<span class="badge bg-success">未使用</span>':'<span class="badge bg-secondary">已使用</span>'?></td>
                                    <td><?php echo $key['username']?$key['username']:'N/A'?></td>
                                    <td><?php echo $key['used_at']?date('Y-m-d H:i',strtotime($key['used_at'])):'N/A'?></td>
                                    <td>
                                        <a href="?action=delete&id=<?php echo $key['id']?>" onclick="return confirm('确定删除？')" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; endif?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($totalPages>1): ?>
                    <nav><ul class="pagination justify-content-end">
                        <li class="page-item <?php echo $page<=1?'disabled':''?>"><a class="page-link" href="?page=<?php echo $page-1?>">«</a></li>
                        <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
                        <li class="page-item <?php echo $page==$i?'active':''?>"><a class="page-link" href="?page=<?php echo $i?>"><?php echo $i?></a></li>
                        <?php endfor ?>
                        <li class="page-item <?php echo $page>=$totalPages?'disabled':''?>"><a class="page-link" href="?page=<?php echo $page+1?>">»</a></li>
                    </ul></nav>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">导出卡密</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="post"><input type="hidden" name="action" value="export">
            <div class="modal-body">
                <select name="export_type" class="form-select" required>
                    <option value="unused">仅未使用</option>
                    <option value="used">仅已使用</option>
                    <option value="all">全部</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">导出CSV</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/popper.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const type=document.getElementById('type-select');
    const label=document.getElementById('value-label');
    const b=document.getElementById('balance');
    const p=document.getElementById('points');
    const m=document.getElementById('membership_days');
    function change(){
        if(type.value=='balance'){label.textContent='面额(元)';b.classList.remove('d-none');p.classList.add('d-none');m.classList.add('d-none')}
        else if(type.value=='points'){label.textContent='点数';p.classList.remove('d-none');b.classList.add('d-none');m.classList.add('d-none')}
        else{label.textContent='会员天数';m.classList.remove('d-none');b.classList.add('d-none');p.classList.add('d-none')}
    }
    type.addEventListener('change',change);change();
});
function copy(el,text){navigator.clipboard.writeText(text).then(()=>{alert('复制成功：'+text);el.classList.add('bg-light');setTimeout(()=>el.classList.remove('bg-light'),300)})}
</script>
</body>
</html>
