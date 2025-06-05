<?php
/**
 * submit.php
 *  現在的版本可以同時上傳「文字」或「檔案（任意格式、含圖片）」
 *  送出後會導到 do_submit.php 去建 job 目錄並存檔。
 */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>提交 AI 工作（文字 / 圖片 / 檔案）</title>
</head>
<body>
    <h2>新增 AI 工作</h2>
    <form action="do_submit.php" method="post" enctype="multipart/form-data">
        <!-- 1. 文字 prompt -->
        <label for="prompt">（可選）文字 Prompt：</label><br>
        <textarea id="prompt" name="prompt" rows="6" cols="60"
                  placeholder="請輸入要送給 AI 的文字內容…"></textarea>
        <br><br>
        <!-- 2. 檔案上傳（可選，單檔上傳，接受所有格式） -->
        <label for="upload_file">（可選）上傳檔案（圖片、PDF、Doc…等）：</label><br>
        <input type="file" id="upload_file" name="upload_file" accept="*/*">
        <br><br>
        <button type="submit">送出</button>
    </form>
    <p><a href="manager.php">▸ 返回工作管理畫面</a></p>
</body>
</html>

