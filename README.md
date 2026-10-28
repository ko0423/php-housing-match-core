# 愛分享系統 Campus Housing Match - 核心模組

本 Repository 為團隊校園租屋共享平台之提煉版本，專注展示核心模組原始碼。
## 核心技術實作亮點

1. SMART ROC 適配度演算法
  將使用者對租金、交通、安全等 8 項偏好指標進行 Rank Order 排序。
  透過後端動態計算 ROC 權重，將偏好轉化為具體的配對指數，並以雷達圖呈現客觀的決策依據。

2. 動態地圖與周邊機能渲染
  實作前端 Modal 介面，擷取房源地址後透過 encodeURIComponent 進行字串編碼。
  動態介接 Google Maps Embed 服務，在無須消耗付費 API 額度的前提下，完成地理資訊與周邊機能視覺化。

3. 非同步評價與資料庫防護
  寫入端：實作 Prepared Statements 處理評價與續租意願寫入，防範 SQL Injection 攻擊。
  讀取端：使用 JavaScript fetch API 發送非同步請求，無須重整頁面即可動態渲染歷史評價與 JSON 資料流。
