<?php
/**
 * delete.php
 *  刪除排隊中的工作（job）
 */

if (!isset($_GET["job_id"])) {
    die("param job_id missing");
}

$job_id = basename($_GET["job_id"]);  // 防止路徑穿越
$job_dir = "/share/{$job_id}";
$state_file  = "{$job_dir}/computing";
$output_file = "{$job_dir}/output.txt";

if (!is_dir($job_dir)) {
    die("Job 不存在");
}

// 如果已經生成 output.txt (代表已完成)，或存在 computing (代表正在跑)，都不允許刪
if (file_exists($output_file) || file_exists($state_file)) {
    die("無法刪除：工作已經開始或完成");
}

// 迴圈刪除整個資料夾
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $obj) {
        if ($obj === "." || $obj === "..") continue;
        $path = $dir . "/" . $obj;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

rrmdir($job_dir);

// 刪除後回到管理頁面
header("Location: manager.php");
exit;
?>

