<?php
/**
 * manager.php
 *  讀 /share 底下所有 job，並呈現狀態 (queued/running/completed) 及執行節點
 *  讀 /share/_nodes 底下 metrics.json，顯示各節點 CPU/Memory 使用率
 */

// 禁快取，每次都強制重新讀取
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$share_dir = "/share";
$jobs = [];

// 掃描 /share 下的所有子資料夾 (排除以 "_" 開頭的系統資料夾)
foreach (scandir($share_dir) as $d) {
    if ($d === "." || $d === "..") continue;
    if (strpos($d, "_") === 0) continue;

    $job_dir     = "{$share_dir}/{$d}";
    if (!is_dir($job_dir)) continue;

    $state_file  = "{$job_dir}/computing";
    $input_file  = "{$job_dir}/input.txt";
    $output_file = "{$job_dir}/output.txt";

    $info = [
        "job_id" => $d,
        "status" => "",
        "node"   => "",
    ];

    if (file_exists($output_file)) {
        $info["status"] = "completed";
    }
    else if (file_exists($state_file)) {
        $info["status"] = "running";
        $info["node"] = trim(file_get_contents($state_file));
    }
    else if (file_exists($input_file)) {
        $info["status"] = "queued";
    }
    else {
        $info["status"] = "unknown";
    }

    $jobs[] = $info;
}

// 讀取各節點的 metrics.json
$nodes_metrics = [];
$nodes_dir = "{$share_dir}/_nodes";
if (is_dir($nodes_dir)) {
    foreach (scandir($nodes_dir) as $nd) {
        if ($nd === "." || $nd === "..") continue;
        $mfile = "{$nodes_dir}/{$nd}/metrics.json";
        if (file_exists($mfile)) {
            $data = json_decode(file_get_contents($mfile), true);
            if ($data) {
                $nodes_metrics[$nd] = $data;  // 包含 time, cpu, mem
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>工作排程與監控</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #bbb; padding: 8px; text-align: center; }
    th { background-color: #eee; }
    .btn-delete { color: #c00; cursor: pointer; text-decoration: none; }
</style>
</head>
<body>

<h2>1. 工作清單</h2>
<p><a href="submit.php">▸ 新增 AI 工作</a></p>
<table>
    <tr>
        <th>Job ID</th>
        <th>狀態</th>
        <th>所在節點</th>
        <th>操作</th>
    </tr>
<?php foreach ($jobs as $j): ?>
    <tr>
        <td>
    		<a href="job_detail.php?jobId=<?php echo urlencode($j['job_id']); ?>">
        		<?php echo htmlspecialchars($j["job_id"]); ?>
		</a>
	</td>
        <td>
            <?php
                switch($j["status"]) {
                    case "queued":    echo "<span style='color:blue;'>排隊中</span>"; break;
                    case "running":   echo "<span style='color:orange;'>執行中</span>"; break;
                    case "completed": echo "<span style='color:green;'>已完成</span>"; break;
                    default:          echo "<span>Unknown</span>"; break;
                }
            ?>
        </td>
        <td><?php echo htmlspecialchars($j["node"]); ?></td>
        <td>
            <?php if ($j["status"] === "queued"): ?>
                <a class="btn-delete" href="delete.php?job_id=<?php echo urlencode($j["job_id"]); ?>"
                   onclick="return confirm('確定要刪除此排隊中工作？');">
                    刪除
                </a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</table>

<h2>2. 各節點 CPU / 記憶體 使用率</h2>
<table>
    <tr>
        <th>節點名稱</th>
        <th>紀錄時間</th>
        <th>CPU 使用率 (%)</th>
        <th>記憶體 使用率 (%)</th>
    </tr>
<?php foreach ($nodes_metrics as $node => $m): ?>
    <tr>
        <td><?php echo htmlspecialchars($node); ?></td>
        <td><?php echo date("Y-m-d H:i:s", intval($m["time"])); ?></td>
        <td><?php echo htmlspecialchars($m["cpu"]); ?></td>
        <td><?php echo htmlspecialchars($m["mem"]); ?></td>
    </tr>
<?php endforeach; ?>
</table>

<script>
// 每 10 秒自動重新整理頁面
setTimeout(function() {
    window.location.reload();
}, 10000);
</script>

</body>
</html>

