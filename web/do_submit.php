<?php
/**
 * do_submit.php
 *  1. 接收 POST 的 prompt（純文字）與上傳的檔案
 *  2. 建立 /share/<jobId> 資料夾
 *  3. 若有純文字 prompt，就寫到 input.txt
 *  4. 若有上傳檔案，就把該檔案存到 input.data（保留原始檔名）
 *  5. 送出後導回 manager.php
 */

date_default_timezone_set('Asia/Taipei');

// 1. 判斷是否有文字、是否有檔案
$hasText = isset($_POST['prompt']) && trim($_POST['prompt']) !== '';
$hasFile = isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK;

// 如果兩者都沒有，就不允許提交
if (!$hasText && !$hasFile) {
    die("請至少輸入文字或上傳一個檔案。");
}

// 2. 產生唯一 jobId：job_<timestamp>_<8位亂碼>
$ts     = microtime(true);
$ts_str = str_replace('.', '', sprintf('%.6f', $ts)); // e.g. "1623071234123456"
$rand   = substr(md5(uniqid(mt_rand(), true)), 0, 8);
$jobId  = "job_{$ts_str}_{$rand}";

// 3. job 目錄位置
$jobDir = "/share/{$jobId}";

// 4. 建立目錄（包含上層）如果失敗就中止
if (!mkdir($jobDir, 0755, true)) {
    die("無法建立工作目錄：{$jobDir}");
}

// 5. 如果有文字，就把它寫到 input.txt
if ($hasText) {
    $prompt = trim($_POST['prompt']);
    $txtPath = "{$jobDir}/input.txt";
    if (file_put_contents($txtPath, $prompt) === false) {
        die("無法寫入 input.txt");
    }
}

// 6. 如果有檔案，就把它搬到 input.data（保留原始檔名）
$origName = "";
if ($hasFile) {
    $tmpPath  = $_FILES['upload_file']['tmp_name'];
    $origName = basename($_FILES['upload_file']['name']);
    // 先把副檔名、檔名分離
    $ext      = pathinfo($origName, PATHINFO_EXTENSION);
    $baseName = pathinfo($origName, PATHINFO_FILENAME);

    // 存成固定名稱：input.data，保留原副檔名
    // 例如 input.data.pdf 或 input.data.jpg 這樣比較容易辨識
    $dataPath = "{$jobDir}/input.data";
    // 或者你也可以直接用原始檔名：但為了程式方便，建議統一用 input.data+原副檔名
    if ($ext !== '') {
        $dataPath = "{$jobDir}/input.data.{$ext}";
    }

    if (!move_uploaded_file($tmpPath, $dataPath)) {
        die("檔案儲存失敗");
    }

    // 把原始檔名記一下，以便後面下載時顯示
    file_put_contents("{$jobDir}/orig_filename.txt", $origName);
}

// 7. 建立完成後，自動導回 manager.php
header("Location: manager.php");
exit;
?>

