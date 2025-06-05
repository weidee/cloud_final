<?php
/**
 * compute_worker.php（單模型版，各 Worker 讀取 MODEL_ID 呼叫對應模型）
 *
 * 流程：
 * 1. 遍歷 /share 底下所有 job 資料夾（忽略 _nodes 等）
 * 2. 如果某 job 資料夾內有 input.txt 或 input.data.*，且沒有 computing、沒有 output_<MODEL_ID>.txt，就處理
 * 3. 根據 MODEL_ID 只呼叫一個模型（claude / perplexity / chatgpt-o1）
 * 4. 回傳結果寫入 /share/<jobId>/output_<MODEL_ID>.txt，刪除 computing
 */

$share_dir = "/share";
$host      = gethostname();

// 1. 從環境變數讀模型 ID
$model_id = getenv("MODEL_ID");
if (!$model_id) {
    file_put_contents("php://stderr", "[ERROR] 環境變數 MODEL_ID 未設定\n");
    exit;
}

$list = scandir($share_dir);
foreach ($list as $d) {
    if ($d === "." || $d === "..") continue;
    if (strpos($d, "_") === 0) continue; // 忽略 _nodes, _...

    $job_dir     = "{$share_dir}/{$d}";
    $state_file  = "{$job_dir}/computing";
    $output_file = "{$job_dir}/output_{$model_id}.txt";

    if (!is_dir($job_dir)) continue;
    // 如果該工作已經有 output_<MODEL_ID>.txt，表示此模型已跑過，跳過
    if (file_exists($output_file)) continue;
    // 如果 computing 存在，表示正在被某台 Worker 處理，跳過
    if (file_exists($state_file))  continue;

    // 2. 檢查 input.txt 或 input.data.*
    $txt_file   = "{$job_dir}/input.txt";
    $data_files = glob("{$job_dir}/input.data.*");
    $data_file  = (is_array($data_files) && count($data_files) > 0) ? $data_files[0] : "";

    if (!file_exists($txt_file) && !$data_file) {
        continue; // 既無文字也無檔，就跳過
    }

    // 3. 建立鎖（computing）
    file_put_contents($state_file, $host);

    // 4. 判斷 prompt_path 與 data_path
    if ($data_file) {
        if (file_exists($txt_file)) {
            $prompt_path = $txt_file;
            $data_path   = $data_file;
        } else {
            $prompt_path = $data_file;
            $data_path   = $data_file;
        }
    } else {
        $prompt_path = $txt_file;
        $data_path   = $txt_file;
    }

    // 5. 呼叫 call_api.py，帶 MODEL_ID
    $combined = "[" . $model_id . "]\n";
    $cmd = "python3 /cloudsystem/call_api.py "
         . escapeshellarg($prompt_path) . " "
         . escapeshellarg($data_path)   . " "
         . escapeshellarg($model_id)    . " 2>&1";

    // （可選）將指令寫到 debug log 以便排錯
    file_put_contents("/share/_nodes/{$host}/debug_cmd.log", $cmd . "\n", FILE_APPEND);
    $result = shell_exec($cmd);
    $snippet = substr($result ?: "[NULL]", 0, 200);
    file_put_contents("/share/_nodes/{$host}/debug_result.log", $snippet . "\n\n", FILE_APPEND);

    if ($result === null) {
        $result = "[ERROR] call_api.py 無回傳\n";
    }
    $combined .= $result . "\n\n";

    // 6. 寫入 output_<MODEL_ID>.txt
    file_put_contents($output_file, $combined);

    // 7. 刪除 computing
    unlink($state_file);

    // 只處理一筆，跳出迴圈，下一輪再處理其他 job
    break;
}
?>

