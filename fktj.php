<?php
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>接口访问日志</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"PingFang SC","Microsoft Yahei",sans-serif}
body{background:#f5f7fa;padding:20px 15px}
.container{max-width:1280px;margin:0 auto}
.page-title{font-size:22px;font-weight:600;color:#1e293b;margin-bottom:15px;letter-spacing:1px;text-align:center;}
/* 刷新按钮样式 */
.refresh-btn-box{text-align:center;margin-bottom:20px;}
.refresh-btn{
    padding:9px 26px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);
    color:#fff;
    border:none;
    border-radius:30px;
    font-size:14px;
    cursor:pointer;
    transition:0.2s;
}
.refresh-btn:hover{opacity:0.9;transform:scale(1.02);}
.log-card{background:#ffffff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.06);overflow:hidden}
.scroll-box{width:100%;overflow-x:auto;}
table{width:100%;min-width:720px;border-collapse:collapse}
thead tr{background:linear-gradient(135deg,#3b82f6,#2563eb)}
th{padding:16px 12px;text-align:center;color:#fff;font-weight:500;font-size:15px}
td{padding:15px 12px;text-align:center;font-size:14px;color:#475569;border-bottom:1px solid #f1f5f9;word-break:break-all;}
tbody tr:hover{background:#f8fafc;transition:all 0.2s ease}
.area-tag{display:inline-block;padding:4px 10px;background:#fee2e2;color:#dc2626;border-radius:20px;font-size:13px}
.isp-tag{display:inline-block;padding:4px 10px;background:#e6f7ff;color:#1890ff;border-radius:20px;font-size:13px}
.empty-box{padding:80px 0;text-align:center;color:#94a3b8;font-size:15px}
.refresh-tip{margin-top:18px;text-align:center;font-size:13px;color:#777;}
@media (max-width:768px){
    body{padding:15px 10px;}
    .page-title{font-size:18px}
    th{padding:12px 6px;font-size:14px}
    td{padding:12px 6px;font-size:13px}
    .area-tag,.isp-tag{padding:3px 6px;font-size:12px}
}

/* 美化弹窗样式 */
.pop-mask{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:9999;
}
.pop-box{
    background:#fff;
    width:320px;
    padding:30px 25px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 8px 30px rgba(0,0,0,0.15);
}
.pop-title{
    font-size:18px;
    color:#2563eb;
    font-weight:600;
    margin-bottom:15px;
}
.pop-text{
    font-size:15px;
    color:#555;
    line-height:1.8;
    margin-bottom:25px;
}
.pop-btn{
    padding:8px 28px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);
    color:#fff;
    border:none;
    border-radius:30px;
    font-size:14px;
    cursor:pointer;
}
.pop-btn:hover{opacity:0.9;}
</style>
</head>
<body>
<div class="container">
    <h2 class="page-title">接口访问日志记录</h2>
    <!-- 新增手动刷新按钮 -->
    <div class="refresh-btn-box">
        <button class="refresh-btn" id="refreshBtn">手动刷新日志</button>
    </div>
    <div class="log-card">
        <div class="scroll-box">
            <table>
                <thead>
                    <tr>
                        <th>访问时间</th>
                        <th>接口名称</th>
                        <th>归属地区</th>
                        <th>网络运营商</th>
                    </tr>
                </thead>
                <tbody id="logList">
                    <tr>
                        <td colspan="4" class="empty-box">数据加载中...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="refresh-tip">点击上方按钮手动更新最新访问日志记录</div>
</div>

<script>
const apiUrl = "/API/rz2/api.php";
let firstLoad = true;

// 创建美化弹窗 新增接口名称参数
function showWelcomeTip(name,addr,isp){
    let mask = document.createElement('div');
    mask.className = 'pop-mask';
    mask.innerHTML = `
        <div class="pop-box">
            <div class="pop-title">云聚API接口最新访问记录提示</div>
            <div class="pop-text">
                欢迎最新访客<br>
                来访接口：${name}<br>
                来访地区：${addr}<br>
                网络运营商：${isp}
            </div>
            <button class="pop-btn">知道啦</button>
        </div>
    `;
    document.body.appendChild(mask);
    mask.querySelector('.pop-btn').onclick = ()=>{
        mask.remove();
    }
}

function getLog(){
    fetch(`${apiUrl}?act=get`)
    .then(res=>res.json())
    .then(data=>{
        let html = "";
        if(data.length <= 0){
            html = `<tr><td colspan="4" class="empty-box">暂无任何访问记录</td></tr>`;
        }else{
// 仅首次打开页面弹窗，手动刷新不再弹窗
             if(firstLoad){
                 let latest = data[0];
                 showWelcomeTip(latest.name,latest.address,latest.isp);
                 firstLoad = false;
             }
             data.forEach(item=>{
                 html += `
                 <tr>
                     <td>${item.time}</td>
                     <td>${item.name}</td>
                     <td><span class="area-tag">${item.address}</span></td>
                     <td><span class="isp-tag">${item.isp}</span></td>
                 </tr>
                 `;
             })
         }
         document.getElementById("logList").innerHTML = html;
     })
 }
 // 页面首次加载数据
 window.onload = getLog;
 // 移除10秒自动刷新代码
 // 绑定手动刷新按钮点击事件
 document.getElementById('refreshBtn').addEventListener('click',getLog);
 </script>
 </body>
 </html>