<?php
// --- 資料庫連線與 SMART ROC 邏輯 ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "127.0.0.1:3306"; 
$user = "root";
$pass = "";
$db   = "project"; 

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) { die("❌ 伺服器連線失敗: " . $conn->connect_error); }
if (!$conn->select_db($db)) { die("❌ 找不到資料庫：'$db'"); }
$conn->set_charset("utf8mb4");

// 獲取權重，預設為 1
$w_price = isset($_GET['w_price']) ? (float)$_GET['w_price'] : 1;
$w_trans = isset($_GET['w_trans']) ? (float)$_GET['w_trans'] : 1;
$w_mate  = isset($_GET['w_mate'])  ? (float)$_GET['w_mate']  : 1;
$w_equip = isset($_GET['w_equip']) ? (float)$_GET['w_equip'] : 1;

$total_w = $w_price + $w_trans + $w_mate + $w_equip;
if ($total_w <= 0) $total_w = 1;

// 從資料庫撈取房源
$sql = "SELECT * FROM houses";
$result = $conn->query($sql);

$final_list = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // 計算租金分數 (越高越便宜分數越高)
        $p_score = max(0, 10 - ($row['price'] / 1000));
        
        // 加權平均計算總分
        $final_score = (($p_score * $w_price) + ($row['trans_score'] * $w_trans) + ($row['mate_score'] * $w_mate) + ($row['equip_score'] * $w_equip)) / $total_w;
        
        $row['p_score'] = $p_score;
        $row['calculated_score'] = $final_score;
        $final_list[] = $row;
    }
    // 依總分排序 (高到低)
    usort($final_list, function($a, $b) { return $b['calculated_score'] <=> $a['calculated_score']; });
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>SMART ROC 房源決策分析</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: "Microsoft JhengHei", sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { color: #333; text-align: center; border-bottom: 2px solid #ff7f50; padding-bottom: 10px; }
        .filter-section { background: #fff9f6; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px dashed #ff7f50; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; }
        .filter-grid label { font-size: 13px; font-weight: bold; color: #ff7f50; }
        .filter-grid input { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd; }
        .house-card { margin-bottom: 25px; padding: 20px; border-radius: 12px; border-left: 8px solid #ddd; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; align-items: center; }
        .house-card.top-rank { border-left-color: #5fcf80; background: #f0fff4; }
        .info-box { flex: 1; min-width: 250px; }
        .chart-box { flex: 0 0 220px; height: 220px; margin: auto; }
        .score-badge { display: inline-block; background: #ff7f50; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-top: 10px; }
        .top-rank .score-badge { background: #5fcf80; }
        .details { color: #666; font-size: 0.95em; margin-top: 10px; line-height: 1.6; }
        .btn-group { margin-top: 15px; display: flex; gap: 10px; width: 100%; }
        .btn { flex: 1; text-align: center; text-decoration: none; padding: 12px; border-radius: 6px; font-size: 14px; cursor: pointer; border: none; color: white; font-weight: bold; }
        .btn-map { background: #aaa; transition: 0.3s; }
        .btn-map:hover { background: #888; }
        .btn-info { background: #5fcf80; transition: 0.3s; }
        .btn-info:hover { background: #4bb86c; }
        .review-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .review-user { font-weight: bold; color: #5fcf80; font-size: 14px; margin-bottom: 5px; }
        .empty-msg { text-align: center; color: #999; padding: 50px; background: #fdfdfd; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>🎯 SMART ROC 房源決策分析</h2>

    <div class="filter-section">
        <form method="GET">
            <div class="filter-grid">
                <div><label>💰 租金權重</label><input type="number" name="w_price" value="<?php echo $w_price; ?>" min="1" max="10"></div>
                <div><label>🚶 交通權重</label><input type="number" name="w_trans" value="<?php echo $w_trans; ?>" min="1" max="10"></div>
                <div><label>👍 推薦權重</label><input type="number" name="w_mate" value="<?php echo $w_mate; ?>" min="1" max="10"></div>
                <div><label>🛋️ 設備權重</label><input type="number" name="w_equip" value="<?php echo $w_equip; ?>" min="1" max="10"></div>
                <div style="display:flex; align-items:flex-end;"><button type="submit" class="btn" style="background:#ff7f50; width:100%;">重新分析</button></div>
            </div>
        </form>
    </div>

    <div id="house-list">
    <?php if (!empty($final_list)): ?>
        <?php foreach ($final_list as $index => $h): ?>
            <div class="house-card <?php echo ($index === 0) ? 'top-rank' : ''; ?>">
                <div class="info-box">
                    <?php if($index === 0): ?> <small style="color:#5fcf80; font-weight:bold;">🏆 系統最佳推薦</small><br> <?php endif; ?>
                    <strong>🏠 <?php echo htmlspecialchars($h['title']); ?></strong>
                    <div class="details">
                        📍 地址：<?php echo htmlspecialchars($h['address']); ?><br>
                        💰 租金：$<?php echo number_format($h['price']); ?> / 月<br>
                        🛠️ 設備：<?php echo htmlspecialchars($h['equipment']); ?>
                    </div>
                    <div class="score-badge">媒合度：<?php echo number_format($h['calculated_score'], 1); ?> / 10</div>
                </div>

                <div class="chart-box">
                    <canvas id="chart-<?php echo $h['id']; ?>"></canvas>
                </div>

                <div class="btn-group">
                    <a href="live.php" class="btn btn-map">🗺️ 返回選單</a>
                    <button class="btn btn-info" onclick="openReviews('<?php echo $h['id']; ?>', '<?php echo htmlspecialchars(addslashes($h['title'])); ?>', '<?php echo htmlspecialchars(addslashes($h['description'])); ?>')">💬 學長姐心得</button>
                </div>
            </div>

            <script>
                new Chart(document.getElementById('chart-<?php echo $h['id']; ?>'), {
                    type: 'radar',
                    data: {
                        labels: ['租金', '交通', '推薦', '設備'],
                        datasets: [{
                            data: [<?php echo $h['p_score']; ?>, <?php echo $h['trans_score']; ?>, <?php echo $h['mate_score']; ?>, <?php echo $h['equip_score']; ?>],
                            backgroundColor: 'rgba(255, 127, 80, 0.2)',
                            borderColor: '#ff7f50',
                            borderWidth: 2,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        scales: { r: { min: 0, max: 10, ticks: { display: false } } },
                        plugins: { legend: { display: false } }
                    }
                });
            </script>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-msg">
            <p>目前資料庫中沒有房源資料。</p>
            <p><small>請確認 phpMyAdmin 中的 houses 表格是否有資料。</small></p>
        </div>
    <?php endif; ?>
    </div>
</div>

<div id="reviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999;">
    <div style="background:white; width:90%; max-width:500px; margin:8% auto; padding:25px; border-radius:15px; max-height:75vh; overflow-y:auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #5fcf80; padding-bottom:10px;">
            <h3 id="modalHouseName" style="margin:0; color:#333;">房源心得列表</h3>
            <button onclick="closeReviews()" style="border:none; background:none; font-size:28px; cursor:pointer; color:#999;">&times;</button>
        </div>
        <div id="reviewsContainer" style="margin-top:20px;">
            </div>
    </div>
</div>

<script>
function openReviews(houseId, title, defaultDesc) {
    document.getElementById('modalHouseName').innerText = "【" + title + "】學長姐真心話";
    const container = document.getElementById('reviewsContainer');
    
    // 初始化清空
    container.innerHTML = "<p style='color:#666; text-align:center;'>正在讀取最新評價...</p>";
    document.getElementById('reviewModal').style.display = "block";

    // 向 get_reviews.php 請求真實數據
    fetch('get_reviews.php?house_id=' + houseId + '&t=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                // 如果無評論，顯示房源原始描述
                container.innerHTML = `
                    <div class="review-item" style="border-left: 4px solid #ff7f50; padding-left:15px; background:#fff9f6;">
                        <div class="review-user">房源初始描述：</div>
                        <div style="color:#444; line-height:1.6;">${defaultDesc || "這間房源目前還沒有人留下心得。"}</div>
                    </div>
                    <p style='color:#999; font-size:12px; margin-top:20px; text-align:center;'>尚未有學長姐撰寫真實評價。</p>`;
            } else {
                // 渲染資料庫中的真實評論
                container.innerHTML = data.map(r => `
                    <div class="review-item">
                        <div class="review-user">${r.user_name}：</div>
                        <div style="color:#333; line-height:1.6; white-space: pre-wrap;">${r.comment}</div>
                        <div style="font-size:11px; color:#aaa; margin-top:8px; text-align:right;">📅 ${r.created_at}</div>
                    </div>
                `).join('');
            }
        })
        .catch(err => {
            container.innerHTML = "<p style='color:red; text-align:center;'>❌ 無法連線至伺服器，請檢查 get_reviews.php 是否正確運作。</p>";
        });
}

function closeReviews() {
    document.getElementById('reviewModal').style.display = "none";
}

// 點擊背景關閉彈窗
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target == modal) { closeReviews(); }
}
</script>

</body>
</html>
<?php $conn->close(); ?>
