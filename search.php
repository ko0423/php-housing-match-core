<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>房源決策分析 - SMART ROC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* 保持原有樣式並新增地圖 Modal 相關樣式 */
        body { font-family: "Microsoft JhengHei", sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 1100px; background: #ffffff; padding: 35px 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); box-sizing: border-box; }
        h1 { text-align: center; font-size: 22px; color: #333; margin-bottom: 25px; }
        
        .decision-box { background: #fff9f6; padding: 20px; border: 1px dashed #ff7f50; border-radius: 8px; margin-bottom: 25px; }
        .weight-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; }
        .weight-item { display: flex; flex-direction: column; background: #fff; padding: 12px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        .weight-item label { font-size: 14px; font-weight: 600; margin-bottom: 5px; color: #ff7f50; }
        .slider-value { font-size: 12px; color: #444; font-weight: bold; margin-top: 8px; text-align: center; background: #fff0e6; padding: 6px; border-radius: 4px; min-height: 45px; display: flex; align-items: center; justify-content: center; }
        input[type="range"] { accent-color: #ff7f50; cursor: pointer; width: 100%; }

        .house-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { border: 1px solid #eee; border-radius: 10px; padding: 15px; background: #fff; transition: 0.3s; display: flex; flex-direction: column; min-height: 520px; }
        .card.best { border: 2.5px solid #ff7f50; transform: scale(1.02); }
        .house-info h3 { margin: 0; font-size: 16px; color: #333; }
        .house-price { color: #ff4d4f; font-weight: bold; margin: 8px 0; }
        .score-display { background: #fff0e6; color: #ff7f50; display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 13px; font-weight: bold; margin-bottom: 10px; }
        .chart-container { height: 260px; position: relative; }

        /* 地圖按鈕樣式 */
        .map-btn { margin-top: auto; text-align: center; background: #ff7f50; color: white; border: none; padding: 10px; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .map-btn:hover { background: #e66a3e; }
        .senior-btn { margin-top: 10px; text-align: center; background: #5fcf80; color: white; padding: 10px; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer; border: none; transition: 0.2s; }
        .senior-btn:hover { background: #4bb86c; }
        .back-btn { display: inline-block; margin-top: 20px; font-size: 14px; color: #ff7f50; text-decoration: none; }

        /* Modal 通用樣式 */
        .modal-overlay { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; width: 95%; max-width: 500px; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; }
        
        /* 地圖 Modal 專用高度 */
        .map-modal-content { height: 85vh; }
        .modal-map { flex: 1; width: 100%; background-color: #e0e0e0; }
        .modal-map iframe { width: 100%; height: 100%; border: 0; }
        .modal-header { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .close-x { border: none; background: none; font-size: 24px; cursor: pointer; color: #999; }
    </style>
</head>
<body>

<div class="container">
    <h1>房源決策分析</h1>

    <div class="decision-box">
        <div style="font-size: 14px; color: #666; margin-bottom: 15px; text-align: center;">滑動滑桿設定權重標準（1-10），系統將自動為您媒合：</div>
        <div class="weight-grid">
            <div class="weight-item"><label>💰 租金預算考量</label><input type="range" id="w_price" min="0" max="10" value="5"><div class="slider-value" id="v_price">載入中...</div></div>
            <div class="weight-item"><label>🚶 步行至校時間</label><input type="range" id="w_trans" min="0" max="10" value="5"><div class="slider-value" id="v_trans">載入中...</div></div>
            <div class="weight-item"><label>👮 安全性評估</label><input type="range" id="w_safe" min="0" max="10" value="5"><div class="slider-value" id="v_safe">載入中...</div></div>
            <div class="weight-item"><label>🛠️ 設備新舊程度</label><input type="range" id="w_equip" min="0" max="10" value="5"><div class="slider-value" id="v_equip">載入中...</div></div>
            <div class="weight-item"><label>🍱 紓壓生活指數</label><input type="range" id="w_life" min="0" max="10" value="5"><div class="slider-value" id="v_life">載入中...</div></div>
            <div class="weight-item"><label>☀️ 採光曬衣效率</label><input type="range" id="w_sun" min="0" max="10" value="5"><div class="slider-value" id="v_sun">載入中...</div></div>
            <div class="weight-item"><label>📦 網購友善度</label><input type="range" id="w_buy" min="0" max="10" value="5"><div class="slider-value" id="v_buy">載入中...</div></div>
            <div class="weight-item"><label>📶 網路穩定品質</label><input type="range" id="w_ping" min="0" max="10" value="5"><div class="slider-value" id="v_ping">載入中...</div></div>
        </div>
    </div>

    <h2 id="result-title" style="font-size: 18px; color: #333;">最符合房源推薦</h2>
    <div class="house-grid" id="house-list"></div>

    <a href="live.php" class="back-btn">← 返回上一頁</a>
</div>

<div id="mapModal" class="modal-overlay">
    <div class="modal-content map-modal-content">
        <div class="modal-header">
            <h3 id="mapModalTitle" style="margin:0;">房源位置</h3>
            <button class="close-x" onclick="closeMapModal()">×</button>
        </div>

        <div class="modal-map">
            <iframe id="mapFrame" src="" allowfullscreen="" loading="lazy"></iframe>
        </div>

        <div style="padding:15px 20px; border-top:1px solid #eee; background:#fff;">
            <h4 style="margin:0 0 10px 0; font-size:15px; color:#333;">附近生活機能</h4>
            <ul id="facilityList" style="margin:0; padding-left:18px; color:#555; line-height:1.7;"></ul>
        </div>
    </div>
</div>

<div id="reviewModal" class="modal-overlay">
    <div class="modal-content" style="padding:25px; max-height:70vh; overflow-y:auto;">
        <div class="modal-header">
            <h3 id="modalTitle" style="margin:0;">學長姐心得</h3>
            <button class="close-x" onclick="closeReviewModal()">×</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<script>
// 準備房源數據，並加入 Google Maps 的嵌入連結 (embed網址)
const houses = [
    {
        id: 1,
        name: "聯合栗寶",
        price: 4500,
        trans: 9,
        safe: 7,
        equip: 6,
        life: 8,
        sun: 6,
        buy: 7,
        ping: 7,
        location: "聯合栗寶 苗栗",
        facilities: [
            "7-ELEVEN 約 2 分鐘",
            "早餐店約 3 分鐘",
            "公車站約 4 分鐘",
            "學校步行約 5 分鐘"
        ],
        senior_talk: "離校區真的很近，每天可以多睡10分鐘。雖然設備普通但採光還不錯！"
    },
    {
        id: 2,
        name: "聯合天地",
        price: 5200,
        trans: 10,
        safe: 7,
        equip: 7,
        life: 9,
        sun: 6,
        buy: 6,
        ping: 8,
        location: "聯合天地 苗栗",
        facilities: [
            "宵夜街步行約 2 分鐘",
            "全家便利商店約 1 分鐘",
            "公車站約 3 分鐘",
            "飲料店約 2 分鐘"
        ],
        senior_talk: "生活機能超棒，下樓就是宵夜街。缺點是晚上偶爾會有摩托車聲。"
    },
    {
        id: 3,
        name: "台糖苗栗學苑",
        price: 4200,
        trans: 7,
        safe: 10,
        equip: 6,
        life: 5,
        sun: 8,
        buy: 10,
        ping: 7,
        location: "台糖苗栗學苑 苗栗",
        facilities: [
            "警衛室代收包裹",
            "便利商店約 4 分鐘",
            "公車站約 3 分鐘",
            "學校約 7 分鐘"
        ],
        senior_talk: "安全性最高！有警衛幫忙收包裹超方便。雖然離美食街有一小段路但很平靜。"
    },
    {
        id: 4,
        name: "陽光比佛利",
        price: 8000,
        trans: 4,
        safe: 9,
        equip: 10,
        life: 6,
        sun: 10,
        buy: 9,
        ping: 9,
        location: "陽光比佛利 苗栗",
        facilities: [
            "停車方便",
            "便利商店約 3 分鐘",
            "餐廳約 4 分鐘",
            "超市約 5 分鐘"
        ],
        senior_talk: "預算夠的話這間真的是首選，設備全都是變頻冷氣。除了貴沒有其他缺點。"
    },
    {
        id: 5,
        name: "松園",
        price: 6500,
        trans: 4,
        safe: 8,
        equip: 9,
        life: 5,
        sun: 10,
        buy: 8,
        ping: 8,
        location: "松園 苗栗",
        facilities: [
            "便利商店約 2 分鐘",
            "早餐店約 3 分鐘",
            "公車站約 4 分鐘",
            "超市約 5 分鐘"
        ],
        senior_talk: "陽台超級大，曬衣服很快乾。房東人很好，有問題都會立刻處理。"
    }
];

function updateLabels() {
    const labels = { price: '租金', trans: '交通', safe: '安全', equip: '設備', life: '生活', sun: '採光', buy: '網購', ping: '網路' };
    Object.keys(labels).forEach(key => {
        const val = document.getElementById('w_' + key).value;
        document.getElementById('v_' + key).innerText = labels[key] + "權重：" + val;
    });
}

function analyze() {
    updateLabels();
    
    // 1. 取得使用者在介面上的原始評分 (1-10)
    const rawWeights = [
        { key: 'price', val: +document.getElementById('w_price').value },
        { key: 'trans', val: +document.getElementById('w_trans').value },
        { key: 'safe', val: +document.getElementById('w_safe').value },
        { key: 'equip', val: +document.getElementById('w_equip').value },
        { key: 'life', val: +document.getElementById('w_life').value },
        { key: 'sun', val: +document.getElementById('w_sun').value },
        { key: 'buy', val: +document.getElementById('w_buy').value },
        { key: 'ping', val: +document.getElementById('w_ping').value }
    ];

    // 2. 根據評分進行「排名」 (Rank Order)
    // 分數越高排名越前，這是 ROC 權重法的核心
    rawWeights.sort((a, b) => b.val - a.val);

    // 3. 套用老師照片中的 ROC 公式計算權重
    // 公式: Wi = (1/n) * Σ(1/j) from j=i to n
    const n = rawWeights.length; // 總共有 8 個指標
    const finalWeights = {};
    
    rawWeights.forEach((item, index) => {
        let rank = index + 1; // 排名從 1 開始
        let sumReciprocal = 0;
        for (let j = rank; j <= n; j++) {
            sumReciprocal += (1 / j);
        }
        // 算出該指標對應的 ROC 權重值
        finalWeights[item.key] = sumReciprocal / n;
    });

    // 4. 計算房源得分
    const maxP = Math.max(...houses.map(h => h.price));
    const minP = Math.min(...houses.map(h => h.price));

    let scoredHouses = houses.map(h => {
        // 租金得分歸一化
        const pScore = minP === maxP ? 10 : ((maxP - h.price) / (maxP - minP)) * 10;
        
        // 使用 ROC 權重進行加權總和
        // 因為 ROC 權重總和已為 1，不需要再除以 wSum
        const finalScore = 
            (pScore * finalWeights.price) + 
            (h.trans * finalWeights.trans) + 
            (h.safe * finalWeights.safe) + 
            (h.equip * finalWeights.equip) + 
            (h.life * finalWeights.life) + 
            (h.sun * finalWeights.sun) + 
            (h.buy * finalWeights.buy) + 
            (h.ping * finalWeights.ping);
            
        return { ...h, pScore, finalScore };
    });

    // 5. 排序並渲染
    scoredHouses.sort((a, b) => b.finalScore - a.finalScore);
    render(scoredHouses);

    // (選填) 如果你想在 Console 看算出來的 ROC 權重分配，可以取消下面註解
    // console.table(finalWeights);
}
function render(data) {
    const list = document.getElementById('house-list');
    list.innerHTML = '';
    data.forEach((h, index) => {
        const isBest = index === 0;
        const card = document.createElement('div');
        card.className = `card ${isBest ? 'best' : ''}`;
        
        // 關鍵修改處：確保 onclick 傳入的是正確的變數
        card.innerHTML = `
            <div class="house-info">
                <h3>${h.name}</h3>
                <div class="house-price">$${h.price} /月</div>
                <div class="score-display">媒合指數：${(h.finalScore * 10).toFixed(0)}%</div>
                <div class="chart-container"><canvas id="chart-${h.id}"></canvas></div>
            </div>
            <button class="map-btn" onclick='openMapModal(${JSON.stringify(h)})'>📍 在地圖中查看位置</button>
            
            <!-- 修正這裡：直接傳入 h.id (數字) 與 h.name -->
            <button class="senior-btn" onclick="openReviewModal(${h.id}, '${h.name}')">💬 查看學長姐心得</button>
        `;
        
        list.appendChild(card);
        
        // ... Radar Chart 繪製代碼保持不變 ...
        new Chart(document.getElementById(`chart-${h.id}`), {
            type: 'radar',
            data: {
                labels: ['租金', '交通', '安全', '設備', '生活', '採光', '網購', '網路'],
                datasets: [{ data: [h.pScore, h.trans, h.safe, h.equip, h.life, h.sun, h.buy, h.ping], backgroundColor: 'rgba(255, 127, 80, 0.2)', borderColor: 'rgba(255, 127, 80, 1)', borderWidth: 1, pointRadius: 2 }]
            },
            options: { scales: { r: { min: 0, max: 10, ticks: { display: false } } }, plugins: { legend: { display: false } }, maintainAspectRatio: false }
        });
    });
}

// 地圖 Modal 控制
function openMapModal(house) {
    document.getElementById('mapModalTitle').innerText =
        house.name + " - 位置地圖";

    const mapUrl =
        "https://www.google.com/maps?q=" +
        encodeURIComponent(house.location) +
        "&output=embed";

    document.getElementById('mapFrame').src = mapUrl;

    const list = document.getElementById('facilityList');
    list.innerHTML = '';

    house.facilities.forEach(item => {
        const li = document.createElement('li');
        li.textContent = item;
        list.appendChild(li);
    });

    document.getElementById('mapModal').style.display = 'block';
}
function closeMapModal() {
    document.getElementById('mapModal').style.display = 'none';
    document.getElementById('mapFrame').src = ''; // 關閉時清空地圖避免背景有聲音
}

// 心得 Modal 控制
// --- B 部分修改：將心得改為動態從資料庫抓取 ---
function openReviewModal(houseId, houseName) {
    // 設定 Modal 標題
    document.getElementById('modalTitle').innerText = "【" + houseName + "】學長姐真實心得";
    
    const container = document.getElementById('modalBody');
    container.innerHTML = "<p style='text-align:center;'>💬 心得加載中...</p>";
    
    // 顯示 Modal
    document.getElementById('reviewModal').style.display = "block";

    // 透過 get_reviews.php 向資料庫請求該房源的評論
    fetch(`get_reviews.php?house_id=${houseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0 || data.error) {
                // 如果沒有資料的處理邏輯
                container.innerHTML = `
                    <div style="text-align:center; padding: 20px; color: #888;">
                        <p>目前尚無這間房源的真實評價。</p>
                        <p style="font-size: 13px;">歡迎學長姐在「撰寫評價」頁面分享心得！</p>
                    </div>`;
            } else {
                // 將資料庫回傳的 JSON 渲染成 HTML
                container.innerHTML = data.map(r => `
                    <div class="review-item" style="border-bottom: 1px solid #eee; padding: 15px 0;">
                        <div class="review-user" style="font-weight:bold; color:#ff7f50; margin-bottom:5px;">
                            👤 ${r.user_name} 
                            <span style="font-weight:normal; font-size:12px; color:#aaa; float:right;">📅 ${r.created_at}</span>
                        </div>
                        <div class="review-content" style="color:#444; font-size:15px; line-height:1.6; white-space: pre-wrap;">${r.comment}</div>
                    </div>
                `).join('');
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            container.innerHTML = "<p style='color:red; text-align:center;'>❌ 無法獲取心得，請檢查 get_reviews.php</p>";
        });
}

// 關閉心得 Modal 的控制
function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

// 點擊 Modal 外部黑影處也可以關閉 (地圖與心得都適用)
window.onclick = function(event) {
    const reviewModal = document.getElementById('reviewModal');
    const mapModal = document.getElementById('mapModal');
    
    if (event.target == reviewModal) {
        closeReviewModal();
    }
    if (event.target == mapModal) {
        closeMapModal();
    }
};

document.querySelectorAll('input[type=range]').forEach(input => input.addEventListener('input', analyze));
analyze();
</script>
</body>
</html>