<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- 1. 資料庫連線設定 ---
$host = "127.0.0.1"; 
$user = "root";
$pass = "";
$db = "project"; // 統一連向 rental_system

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("❌ 資料庫連線失敗: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// --- 2. 處理評價提交邏輯 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $house_id = isset($_POST['house_id']) ? $_POST['house_id'] : null;
    $rating   = isset($_POST['rating']) ? $_POST['rating'] : null;
    $comment  = isset($_POST['comment']) ? $_POST['comment'] : null;
    $renew    = isset($_POST['renew']) ? $_POST['renew'] : null;

    if ($house_id && $comment) {
        // 將評分與續租意願包裝，方便在 search.php 顯示
        $final_comment = "【評分：" . $rating . " 星 / 續租意願：" . $renew . "】\n" . $comment;
        $user_name = "匿名學長姐"; 

        // 插入資料到 reviews 表
        $sql = "INSERT INTO reviews (house_id, user_name, comment, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $house_id, $user_name, $final_comment);

        if ($stmt->execute()) {
            // 提交成功後跳轉回房源決策分析頁面
            echo "<script>alert('評價已成功！'); window.location.href='search.php';</script>";
            exit();
        }
        $stmt->close();
    }
}

// 撈取房源清單供下拉選單使用
$house_query = $conn->query("SELECT id, title FROM houses WHERE title IS NOT NULL AND title != '' ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>撰寫評價與續租建議</title>
    <style>
        body { font-family: "Microsoft JhengHei", sans-serif; background-color: #f8f9fa; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { width: 100%; max-width: 500px; background: #ffffff; padding: 40px 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 20px; }
        h1 { text-align: center; font-size: 22px; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        label { font-size: 15px; font-weight: 600; color: #333; display: block; margin-bottom: 8px; }
        select, textarea { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .radio-group { display: flex; gap: 20px; margin-top: 10px; }
        .btn { width: 100%; padding: 14px; margin-top: 15px; font-size: 16px; color: #fff; background-color: #ff7f50; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn:hover { background-color: #ff6633; }
        .back-btn { display: inline-block; margin-top: 20px; font-size: 14px; color: #ff7f50; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>📝 撰寫評價與續租建議</h1>

    <form action="review.php" method="POST">
        <div class="form-group">
            <label>您評價的是哪間房源？</label>
            <select name="house_id" required>
                <option value="">-- 請選擇房源 --</option>
                <?php if ($house_query && $house_query->num_rows > 0): ?>
                    <?php while($row = $house_query->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="1">聯合栗寶</option>
                    <option value="2">聯合天地</option>
                    <option value="3">台糖苗栗學苑</option>
                    <option value="4">陽光比佛利</option>
                    <option value="5">松園</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>居住整體評分</label>
            <select name="rating" required>
                <option value="5">⭐⭐⭐⭐⭐ (極好)</option>
                <option value="4">⭐⭐⭐⭐ (良好)</option>
                <option value="3">⭐⭐⭐ (普通)</option>
                <option value="2">⭐⭐ (較差)</option>
                <option value="1">⭐ (極差)</option>
            </select>
        </div>

        <div class="form-group">
            <label>詳細評價與建議</label>
            <textarea name="comment" rows="5" placeholder="請分享您的居住心得..." required></textarea>
        </div>

        <div class="form-group">
            <label>續租意願調查</label>
            <div class="radio-group">
                <label><input type="radio" name="renew" value="願意" checked> 願意續租</label>
                <label><input type="radio" name="renew" value="不願意"> 不願意續租</label>
                <label><input type="radio" name="renew" value="考慮中"> 考慮中</label>
            </div>
        </div>

        <button type="submit" class="btn">提交真實評價</button>
    </form>

    <a href="live.php" class="back-btn">← 返回上一頁</a>
</div>

</body>
</html>
<?php $conn->close(); ?>