<?php
/**
 * job_detail.php
 *
 * 功能：
 *  1. 接收 GET 參數 jobId，例如 job_detail.php?jobId=job_20250605T154055_36fe318a
 *  2. 根據 GET 參數 download，提供下列下載功能：
 *       - download=input_txt   → 下載 /share/<jobId>/input.txt（若不存在則錯誤）
 *       - download=input_file  → 下載 /share/<jobId>/input.data.*（第一個符合的檔案），若無則錯誤
 *       - download=output      → 下載 /share/<jobId>/output.txt，若無則錯誤
 *     下載時會自動偵測檔案的 MIME 類型（例如 image/png、application/pdf 等），並正確設定 Content-Type。
 *  3. 若未傳 download 參數，則顯示該 job 的狀態，並顯示以下下載連結（若檔案存在才顯示）：
 *       - input.txt（若存在）
 *       - 上傳的檔案 input.data.*（若存在，可是 PDF、JPEG、PNG、其他檔案皆可）
 *       - output.txt（若存在）
 */

date_default_timezone_set('Asia/Taipei');

// 1. 取得 jobId
if (!isset($_GET['jobId']) || trim($_GET['jobId']) === '') {
    die("參數 jobId 缺失");
}
$jobId = basename($_GET['jobId']); // 避免路徑穿越

$jobDir     = "/share/{$jobId}";
$inputTxt   = "{$jobDir}/input.txt";
$outputFile = "{$jobDir}/output.txt";

// 尋找「input.data.*」中的第一個檔案（含所有副檔名）
$dataFiles = glob("{$jobDir}/input.data.*");
$inputData = (is_array($dataFiles) && count($dataFiles) > 0) ? $dataFiles[0] : "";

// 2. 處理下載請求
if (isset($_GET['download'])) {
    $dl = $_GET['download'];

    // 驗證 job 目錄是否存在
    if (!is_dir($jobDir)) {
        die("Job 不存在，無法下載");
    }

    switch ($dl) {
        case 'input_txt':
            if (!file_exists($inputTxt)) {
                die("input.txt 不存在，無法下載");
            }
            $filePath     = $inputTxt;
            $downloadName = "input.txt";
            break;

        case 'input_file':
            if (!$inputData || !file_exists($inputData)) {
                die("上傳檔案不存在，無法下載");
            }
            // 直接用原始檔名當下載名稱
            $downloadName = basename($inputData);
            $filePath     = $inputData;
            break;

        case 'output':
            if (!file_exists($outputFile)) {
                die("output.txt 不存在，無法下載");
            }
            $filePath     = $outputFile;
            $downloadName = "output.txt";
            break;

        default:
            die("不合法的下載參數");
    }

    // 使用 php 的 mime_content_type 來偵測檔案 MIME 類型
    $mimeType = mime_content_type($filePath);
    if ($mimeType === false) {
        $mimeType = 'application/octet-stream';
    }

    // 設定下載標頭並輸出檔案
    header('Content-Description: File Transfer');
    header("Content-Type: {$mimeType}");
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// 3. 顯示 job 狀態與下載連結（若檔案存在才顯示連結）
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>Job 詳細：<?php echo htmlspecialchars($jobId); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .box { padding: 15px; border: 1px solid #888; width: 600px; }
        .status { font-size: 1.1em; margin-bottom: 10px; }
        .queued    { color: #0055cc; }
        .running   { color: #ff8800; }
        .completed { color: #009900; }
        .error     { color: #cc0000; }
        .file-links a { display: inline-block; margin: 5px 10px 5px 0; text-decoration: none; }
        .file-links span { display: inline-block; margin: 5px 10px 5px 0; color: #777; }
    </style>
</head>
<body>
    <h2>檢視 Job：<span style="font-family: monospace;"><?php echo htmlspecialchars($jobId); ?></span></h2>

    <?php if (!is_dir($jobDir)): ?>
        <div class="box error">
            <p>❌ Job <strong><?php echo htmlspecialchars($jobId); ?></strong> 不存在。</p>
            <p><a href="manager.php">◂ 返回工作管理</a></p>
        </div>
        <?php exit; ?>
    <?php endif; ?>

    <?php
    // 判斷當前狀態
    if (file_exists($outputFile)) {
        $status = "已完成";
        $statusClass = "completed";
    }
    elseif (file_exists("{$jobDir}/computing")) {
        $status = "執行中";
        $statusClass = "running";
        $runningNode = trim(file_get_contents("{$jobDir}/computing"));
        if ($runningNode === '') {
            $runningNode = '(未知節點)';
        }
    }
    elseif (file_exists($inputTxt) || $inputData) {
        $status = "排隊中";
        $statusClass = "queued";
    }
    else {
        $status = "未知 (input.txt 與上傳檔案皆不存在)";
        $statusClass = "error";
    }
    ?>

    <div class="box">
        <p class="status <?php echo $statusClass; ?>">
            <strong>狀態：</strong>
            <?php
                echo htmlspecialchars($status);
                if (isset($runningNode)) {
                    echo " （執行節點：<span style=\"color:#555;\">" . htmlspecialchars($runningNode) . "</span>）";
                }
            ?>
        </p>

        <div class="file-links">
            <!-- 下載 input.txt（若存在） -->
            <?php if (file_exists($inputTxt)): ?>
                <a href="job_detail.php?jobId=<?php echo urlencode($jobId); ?>&download=input_txt">
                    📥 下載 input.txt
                </a>
            <?php else: ?>
                <span>input.txt 不存在</span>
            <?php endif; ?>

            <!-- 下載上傳的檔案 input.data.*（若存在） -->
            <?php if ($inputData && file_exists($inputData)): ?>
                <a href="job_detail.php?jobId=<?php echo urlencode($jobId); ?>&download=input_file">
                    📥 下載 <?php echo htmlspecialchars(basename($inputData)); ?>
                </a>
            <?php else: ?>
                <span>上傳檔案 不存在</span>
            <?php endif; ?>

            <!-- 下載 output.txt（若存在） -->
            <?php if (file_exists($outputFile)): ?>
                <a href="job_detail.php?jobId=<?php echo urlencode($jobId); ?>&download=output">
                    📥 下載 output.txt
                </a>
            <?php else: ?>
                <span>output.txt 不存在</span>
            <?php endif; ?>
        </div>

        <p style="margin-top:20px;">
            <a href="manager.php">◂ 返回工作管理</a>
        </p>
    </div>
</body>
</html>

