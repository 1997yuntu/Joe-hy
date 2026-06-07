<?php
date_default_timezone_set('PRC');
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$log_path = realpath(__DIR__ . '/../common/logs/');
$pretty_log = $log_path . "/api_debug_{$date}.log";
$raw_log = $log_path . "/api_debug_raw_{$date}.log";
$error_log_file = $log_path . "/api_error_{$date}.log";
$debug_key = 'YUNJU_API_DEBUG_20260523';

if ($_POST['action'] ?? '' === 'clear_log') {
    file_put_contents($pretty_log, '');
    file_put_contents($raw_log, '');
    file_put_contents($error_log_file, '');
    die('{"code":200,"msg":"清空成功"}');
}

function ansiToHtml($text) {
    $text = preg_replace('/\x1B\[\d+(;\d+)*m/', '', $text);
    $text = str_replace(["", "[0m", "[1m", "[37m", "[34m", "[35m", "[33m", "[32m", "[31m"], "", $text);
    return $text;
}

function getLog($file) {
    if (!is_file($file) || !is_readable($file) || filesize($file) <= 0) {
        return '<div class="empty-tip"><i class="fa-solid fa-inbox"></i> 暂无日志</div>';
    }
    return htmlspecialchars(ansiToHtml(file_get_contents($file)));
}

$pretty = getLog($pretty_log);
$raw = getLog($raw_log);

$error_content = '';
if (is_file($error_log_file) && is_readable($error_log_file) && filesize($error_log_file) > 0) {
    $error_content = file_get_contents($error_log_file);
    $error_content = ansiToHtml($error_content);
} else {
    $error_content = '<div class="empty-tip"><i class="fa-solid fa-inbox"></i> 暂无错误日志</div>';
}

$error_count = 0;
if (is_file($error_log_file) && is_readable($error_log_file) && filesize($error_log_file) > 0) {
    $lines = file($error_log_file, FILE_SKIP_EMPTY_LINES);
    $error_count = count($lines);
}

if (is_file($pretty_log) && is_readable($pretty_log) && filesize($pretty_log) > 0) {
    $txt = htmlspecialchars(ansiToHtml(file_get_contents($pretty_log)));
    $txt = str_replace("[INFO]", '<span class="log-info">[INFO]</span>', $txt);
    $txt = str_replace("[SUCCESS]", '<span class="log-success">[SUCCESS]</span>', $txt);
    $txt = str_replace("[WARNING]", '<span class="log-warn">[WARNING]</span>', $txt);
    $txt = str_replace("[ERROR]", '<span class="log-error">[ERROR]</span>', $txt);
} else {
    $txt = '<div class="empty-tip"><i class="fa-solid fa-inbox"></i> 暂无日志</div>';
}

$err_txt = htmlspecialchars($error_content);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API调试日志</title>
    <link href="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-M/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Microsoft YaHei",sans-serif}
        body{background:#f7f8fa;padding:10px;}
        .container{max-width:1200px;margin:0 auto;}
        .header{background:#fff;border-radius:12px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;gap:10px;box-shadow:0 2px 6px rgba(0,0,0,0.03)}
        .header i{color:#3b82f6;font-size:18px;}
        .header h5{font-size:16px;font-weight:600;color:#1f2937;margin:0;}
        .date-picker{border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;outline:none;font-size:14px;background:#fff;}
        
        .toolbar{background:#fff;border-radius:12px;padding:12px 15px;margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;box-shadow:0 2px 6px rgba(0,0,0,0.03)}
        .search{flex:1;min-width:200px;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;outline:none;font-size:14px;transition:0.2s;}
        .search:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,255,0.1);}
        .btn{border:none;border-radius:8px;padding:8px 12px;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;transition:0.2s;}
        .btn-copy{background:#8b5cf6;color:#fff;}
        .btn-clear{background:#ef4444;color:#fff;}
        .btn-test{background:#10b981;color:#fff;}
        .btn:hover{opacity:0.9;}

        .filter-group{display:flex;gap:4px;flex-wrap:wrap;}
        .filter-btn{padding:4px 8px;font-size:12px;border-radius:6px;border:none;cursor:pointer;}
        .filter-all{background:#e2e8f0;color:#334155;}
        .filter-info{background:#bae6fd;color:#0369a1;}
        .filter-success{background:#bbf7d0;color:#047857;}
        .filter-warn{background:#fef3c7;color:#d9706;}
        .filter-error{background:#fecaca;color:#b91c1c;}

        .test-panel{background:#fff;border-radius:12px;padding:15px;margin-bottom:12px;box-shadow:0 2px 6px rgba(0,0,0,0.03)}
        .test-title{font-size:14px;font-weight:500;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
        .test-title i{color:#10b981;}
        .test-form{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
        .api-input{flex:1;min-width:280px;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;outline:none;font-size:14px;transition:0.2s;}
        .api-input:focus{border-color:#3b82f6;}
        .test-result{margin-top:10px;background:#1e1e2f;color:#e0e0e0;padding:12px;border-radius:8px;font-family:Consolas,monospace;font-size:12px;max-height:200px;overflow-y:auto;white-space:pre-wrap;display:none;}

        .tab-box{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.03)}
        .tabs{display:flex;background:#f9fafb;border-bottom:1px solid #e5e7eb;}
        .tab{flex:1;padding:14px;text-align:center;cursor:pointer;font-size:14px;font-weight:500;color:#6b7280;transition:0.2s;position:relative;}
        .tab.active{color:#3b82f6;background:#fff;border-bottom:2px solid #3b82f6;}
        .tab.error-tab.active{color:#ef4444;border-bottom:2px solid #ef4444;}
        .badge{position:absolute;top:6px;right:6px;background:#ef4444;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;min-width:18px;line-height:16px;}
        .panel{display:none;padding:15px;}
        .panel.active{display:block;}
        .log-body{background:#1e1e2f;border-radius:10px;padding:18px;color:#e0e0e0;height:calc(100vh - 360px);min-height:320px;overflow-y:auto;white-space:pre-wrap;font-family:Consolas,monospace;font-size:13px;line-height:1.5;word-break:break-all;}
        .log-body::-webkit-scrollbar{width:6px;}
        .log-body::-webkit-scrollbar-thumb{background:#555;border-radius:3px;}
        .log-body::-webkit-scrollbar-track{background:#2a2a3c;}
        .log-info{color:#4cc3ff; font-weight:bold;}
        .log-success{color:#10b981; font-weight:bold;}
        .log-warn{color:#f59e0b; font-weight:bold;}
        .log-error{color:#ef4444; font-weight:bold;}
        .highlight{background:#fbbf24;color:#1f2937;padding:2px 4px;border-radius:3px;font-weight:bold;}
        .jump-highlight{animation:flash 1s ease;}
        @keyframes flash{0%{background:#ffeb3b;color:#000}100%{background:#fbbf24;color:#1f2937}}

        /* 空日志优雅提示 */
        .empty-tip{display:flex;align-items:center;justify-content:center;gap:8px;color:#8a93a6;font-size:14px;padding:40px 10px;}
        .empty-tip i{font-size:16px;color:#8a93a6;}

        .toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:8px;color:#fff;font-size:14px;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:9999;opacity:0;transition:all 0.3s;white-space:nowrap;}
        .toast.show{opacity:1;top:30px;}
        .toast-success{background:#10b981;}
        .toast-error{background:#ef4444;}

        .confirm-modal{position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:9998;display:none;}
        .confirm-box{background:#fff;border-radius:12px;width:90%;max-width:360px;padding:20px;text-align:center;}
        .confirm-title{font-size:16px;font-weight:600;margin-bottom:12px;color:#1f2937;}
        .confirm-btns{display:flex;gap:10px;margin-top:18px;}
        .confirm-btn{flex:1;padding:10px;border-radius:8px;border:none;cursor:pointer;}
        .confirm-yes{background:#ef4444;color:#fff;}
        .confirm-no{background:#e5e7eb;color:#374151;}

        @media (max-width:768px){
            .search,.api-input{min-width:100%;}
            .btn{flex:1;padding:8px 6px;font-size:13px;}
            .tab{padding:12px 8px;font-size:13px;}
            .badge{transform:scale(0.9);top:4px;right:4px;}
            .log-body{height:calc(100vh - 340px);min-height:280px;padding:14px;font-size:12px;}
        }
    </style>
</head>
<body>
<div id="toast" class="toast"></div>
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
        <div class="confirmTitle" id="confirmText">确定清空日志？</div>
        <div class="confirm-btns">
            <button class="confirm-btn confirm-no" id="confirmNo">取消</button>
            <button class="confirm-btn confirm-yes" id="confirmYes">确定</button>
        </div>
    </div>
</div>

<div class="container">
    <div class="header">
        <i class="fa-solid fa-bug"></i>
        <h5>API 调试日志 | <?=$date?></h5>
        <input type="date" class="date-picker" id="dateSelect" value="<?=$date?>">
    </div>

    <div class="test-panel">
        <div class="test-title">API在线测试（自动携带调试密钥+实时时间戳）</div>
        <div class="test-form">
            <input type="text" class="api-input" id="apiUrl" value="https://api.scdnn.com/API/yiyan/api.php?format=json">
            <button class="btn btn-test" id="runTest"><i class="fa-solid fa-play"></i> 开始测试</button>
        </div>
        <div class="test-result" id="testResult"></div>
    </div>

    <div class="toolbar">
        <input type="text" class="search" id="search" placeholder="🔍 搜索关键词">
        <div class="filter-group">
            <button class="filter-btn filter-all" data-level="all">全部</button>
            <button class="filter-btn filter-info" data-level="INFO">INFO</button>
            <button class="filter-btn filter-success" data-level="SUCCESS">SUCCESS</button>
            <button class="filter-btn filter-warn" data-level="WARNING">WARN</button>
            <button class="filter-btn filter-error" data-level="ERROR">ERROR</button>
        </div>
        <button class="btn btn-copy" id="copyLog"><i class="fa-solid fa-copy"></i> 复制日志</button>
        <button class="btn btn-clear" id="clearLog"><i class="fa-solid fa-trash"></i> 清空日志</button>
    </div>

    <div class="tab-box">
        <div class="tabs">
            <div class="tab active" data-target="pretty">📝 美化日志</div>
            <div class="tab" data-target="raw">⚙️ JSON格式</div>
            <div class="tab error-tab" data-target="error">
                ❌ 错误日志
                <?php if($error_count > 0): ?><span class="badge"><?=$error_count?></span><?php endif; ?>
            </div>
        </div>
        <div class="panel active" id="pretty"><div class="log-body" id="prettyBox"><?=$txt?></div></div>
        <div class="panel" id="raw"><div class="log-body" id="rawBox"><?=$raw?></div></div>
        <div class="panel" id="error"><div class="log-body" id="errorBox"><?=$err_txt?></div></div>
    </div>
</div>

<script>
const debug_key = '<?=$debug_key?>';
const date = '<?=$date?>';
const originPretty = `<?=str_replace('`','\\`',$pretty)?>`;
const originRaw = `<?=str_replace('`','\\`',$raw)?>`;
const originError = `<?=str_replace('`','\\`',$error_content)?>`;
let currentLevel = 'all';
let confirmCallback = null;

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.className = 'toast toast-'+type;
    t.innerHTML = type=='success'?`<i class="fa-solid fa-check"></i>${msg}`:`<i class="fa-solid fa-xmark"></i>${msg}`;
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2200);
}

function showConfirm(text, callback) {
    document.getElementById('confirmText').innerText = text;
    document.getElementById('confirmModal').style.display = 'flex';
    confirmCallback = callback;
}

document.getElementById('confirmNo').onclick=()=>document.getElementById('confirmModal').style.display='none';
document.getElementById('confirmYes').onclick=()=>{
    document.getElementById('confirmModal').style.display='none';
    confirmCallback?.();
}

document.getElementById('dateSelect').addEventListener('change',function(){
    window.location.href = `?date=${this.value}`;
});

document.querySelectorAll('.tab').forEach(tab=>{
    tab.addEventListener('click',()=>{
        document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.target).classList.add('active');
    });
});

function filterLog(level){
    currentLevel = level;
    let activeTab = document.querySelector('.tab.active').dataset.target;
    let boxId = activeTab + 'Box';
    let box = document.getElementById(boxId);
    let rawContent = '';

    if(activeTab === 'pretty') rawContent = originPretty;
    else if(activeTab === 'raw') rawContent = originRaw;
    else rawContent = originError;

    if(activeTab === 'pretty'){
        box.innerHTML = `<?=str_replace("\n", "\\n", $txt)?>`;
    }else{
        box.innerHTML = rawContent.replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    if(level === 'all'){
        box.scrollTop = 0;
        return;
    }

    let target = '';
    if(level === 'INFO') target = '[INFO]';
    else if(level === 'SUCCESS') target = '[SUCCESS]';
    else if(level === 'WARNING') target = '[WARNING]';
    else if(level === 'ERROR') target = '[ERROR]';

    let lines = rawContent.split('\n');
    let firstIndex = -1;
    for(let i=0; i<lines.length; i++){
        if(lines[i].includes(target)){
            firstIndex = i;
            break;
        }
    }

    if(firstIndex === -1){
        showToast('暂无'+level+'日志','error');
        return;
    }

    let html = box.innerHTML;
    let reg = new RegExp(target.replace(/\[|\]/g,'\\$&'),'g');
    box.innerHTML = html.replace(reg, `<span class="highlight jump-highlight">${target}</span>`);
    
    let firstEl = box.querySelector('.highlight');
    if(firstEl){
        firstEl.scrollIntoView({behavior:'smooth',block:'center'});
    }
}

document.querySelectorAll('.filter-btn').forEach(b=>b.onclick=()=>filterLog(b.dataset.level));

document.getElementById('search').oninput=e=>{
    let k = e.target.value.trim();
    if(!k){
        document.getElementById('prettyBox').innerHTML = `<?=str_replace("\n", "\\n", $txt)?>`;
        document.getElementById('rawBox').textContent = originRaw;
        document.getElementById('errorBox').innerHTML = `<?=str_replace("\n", "\\n", $err_txt)?>`;
        return;
    }
    let p = originPretty.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(new RegExp(k,'gi'),'<span class="highlight">$&</span>');
    let r = originRaw.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(new RegExp(k,'gi'),'<span class="highlight">$&</span>');
    let err = originError.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(new RegExp(k,'gi'),'<span class="highlight">$&</span>');
    document.getElementById('prettyBox').innerHTML=p;
    document.getElementById('rawBox').innerHTML=r;
    document.getElementById('errorBox').innerHTML=err;
}

document.getElementById('copyLog').onclick=async()=>{
    let tab = document.querySelector('.tab.active').dataset.target;
    let txt = tab=='pretty'?originPretty.replace(/<[^>]+>/g,''):tab=='raw'?originRaw:originError.replace(/<[^>]+>/g,'');
    try{await navigator.clipboard.writeText(txt);showToast('复制成功');}
    catch(e){showToast('复制失败','error');}
}

document.getElementById('clearLog').onclick=()=>{
    showConfirm(`确定清空【${date}】所有日志？`,()=>{
        fetch('',{method:'POST',body:'action=clear_log',headers:{'Content-Type':'application/x-www-form-urlencoded'}})
        .then(r=>r.json()).then(d=>{showToast('清空成功');setTimeout(()=>location.reload(),800);});
    });
}

document.getElementById('runTest').onclick=async()=>{
    let url = document.getElementById('apiUrl').value.trim();
    let box = document.getElementById('testResult');
    if(!url){showToast('请输入接口地址','error');return;}
    let now = Math.floor(Date.now()/1000);
    url += (url.includes('?')?'&':'?')+'debug_key='+debug_key+'&time='+now;
    box.style.display='block';
    box.textContent='⏳ 请求中...';
    try{
        let res = await fetch(url);
        box.textContent=await res.text();
        setTimeout(()=>location.reload(),800);
    }catch(e){
        showToast('请求失败','error');
        box.textContent='❌ 请求失败';
    }
}
</script>
</body>
</html>
