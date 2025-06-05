####################################################################
# Worker Container: PHP-CLI + Python3 (Debian 套件版 requests 與 psutil)
####################################################################
FROM php:8.1-cli

# Step 1: 安裝 python3、pip 以及 Debian 套件版的 requests、psutil
RUN apt-get update && \
    apt-get install -y \
      python3 \
      python3-pip \
      python3-requests \
      python3-psutil \
    && rm -rf /var/lib/apt/lists/*

# Step 2: 設定工作目錄
WORKDIR /cloudsystem

# Step 3: 複製 compute_worker.php、call_api.py、dump_metrics.py
COPY compute_worker.php   /cloudsystem/compute_worker.php
COPY call_api.py          /cloudsystem/call_api.py
COPY dump_metrics.py      /cloudsystem/dump_metrics.py

# Step 4: 讓三支腳本都有執行權限
RUN chmod +x /cloudsystem/compute_worker.php \
    && chmod +x /cloudsystem/call_api.py \
    && chmod +x /cloudsystem/dump_metrics.py

# 預設執行指令（實際工作會由 docker-compose.yml 的 command 覆寫）
CMD ["php", "--version"]

