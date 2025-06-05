#!/usr/bin/env python3
"""
dump_metrics.py  
  ── 每分鐘呼叫 top -bn1 -i -c 取得當前節點 CPU/Memory，寫到 /share/_nodes/<node>/metrics.json
"""

import subprocess
import socket
import time
import json
from pathlib import Path

def get_top_metrics():
    """
    執行 top -bn1 -i -c，解析 CPU idle% 與 Memory 使用%
    回傳 (cpu_usage, mem_usage)，若解析失敗回 (None, None)
    """
    try:
        proc = subprocess.Popen(
            ["top", "-bn1", "-i", "-c"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        out, err = proc.communicate(timeout=5)
    except Exception:
        return None, None

    cpu_idle = None
    mem_used = None

    for line in out.splitlines():
        line = line.strip()
        if line.startswith("%Cpu(s):"):
            parts = line.split(",")
            for part in parts:
                part = part.strip()
                if part.endswith("id"):
                    try:
                        cpu_idle = float(part.split()[0])
                    except:
                        cpu_idle = None
                    break
        elif line.startswith("KiB Mem") or line.startswith("MiB Mem") or line.startswith("GiB Mem"):
            parts = line.split(":")[1].split(",")
            try:
                total = float(parts[0].strip().split()[0])
                used_part = float(parts[2].strip().split()[0])
                buff_part = float(parts[3].strip().split()[0])
                mem_used = ((used_part + buff_part) / total) * 100.0
            except:
                mem_used = None

        if cpu_idle is not None and mem_used is not None:
            break

    if cpu_idle is None or mem_used is None:
        return None, None

    return (100.0 - cpu_idle, mem_used)

def main():
    node = socket.gethostname()
    dest = Path(f"/share/_nodes/{node}")
    dest.mkdir(parents=True, exist_ok=True)

    cpu, mem = get_top_metrics()
    if cpu is None or mem is None:
        return

    metrics = {
        "time": time.time(),
        "cpu": round(cpu, 1),
        "mem": round(mem, 1),
    }
    dest.joinpath("metrics.json").write_text(json.dumps(metrics))

if __name__ == "__main__":
    main()

