# 愛分享系統 (Campus Housing Match) - 核心模組

本 Repository 為團隊校園租屋共享平台之提煉版本，專注展示由本人獨立負責與優化之核心模組原始碼。完整專案架構與視覺化展示請參見個人前端履歷網站。

## 🎯 核心技術實作亮點

1. **SMART ROC 適配度演算法 (`search.php`)**
   - 將使用者對租金、交通、安全等 8 項偏好指標進行 Rank Order 排序。
   - 透過後端公式動態計算 ROC 權重，將主觀偏好轉化為客觀的雷達圖配對指數，提供精準的決策依據。

2. **動態地圖與周邊機能渲染 (`search.php`)**
   - 實作前端 Modal 介面，擷取房源地址字串後透過 `encodeURIComponent` 進行編碼。
   - 動態介接 Google Maps Embed 服務，不消耗付費 API 額度即可達成零延遲的地理資訊視覺化。

3. **非同步評價與資料庫防護 (`review.php`, `search_results.php`)**
   - **寫入端**：實作 Prepared Statements (參數化查詢) 寫入評價與續租意願，嚴格防範 SQL Injection 攻擊。
   - **讀取端**：使用 JavaScript `fetch` API 進行非同步請求，無須重新載入頁面即可動態渲染歷史評價與 JSON 資料流。