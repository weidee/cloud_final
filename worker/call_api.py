#!/usr/bin/env python3
"""
call_api.py
  1. 從命令列第三個參數或環境變數 AI_MODEL 讀取模型 ID
  2. login → 呼叫 SSE chat API → 擷取 delta.content 串成最終結果
  3. 如果後端回傳中包含 'html'，直接拋出 RuntimeError，不進行重試
用法：
    python3 call_api.py <prompt_file> <data_file> [模型 ID]
"""

import os
import sys
import json
import mimetypes
import requests
from pathlib import Path

USER    = os.getenv("AI_USER")
PASS    = os.getenv("AI_PASS")
DEV_KEY = os.getenv("AI_DEV_KEY")

LOGIN_URL = "https://www.myai168.com/cgu/aieasypay/?module=login"
CHAT_URL  = "https://www.myai168.com/cgu/aieasypay/module/ai-168/chat"

def login(session: requests.Session):
    r = session.post(LOGIN_URL, data={"account": USER, "password": PASS},
                     timeout=30, allow_redirects=True)
    r.raise_for_status()
    if "請登入" in r.text:
        raise RuntimeError("Login failed")

def call_chat(session: requests.Session, prompt: str, file_path: Path, model_id: str) -> str:
    """
    呼叫後端 SSE chat API，若回傳中出現 'html'，直接拋錯不重試
    """
    mime = mimetypes.guess_type(file_path.name)[0] or "application/octet-stream"
    files = {"upload_file[]": (file_path.name, file_path.open("rb"), mime)}

    data = {
        "module": model_id,
        "dev_key": DEV_KEY,
        "input": prompt,
        "search_internet": "false",
        "search_url": "www.myai168.com",
        "session_sn": "0"
    }
    r = session.post(CHAT_URL, data=data, files=files, timeout=180, stream=True)
    r.raise_for_status()

    contents = []
    for line in r.iter_lines(decode_unicode=True):
        if not line or not line.startswith("data:"):
            continue
        payload = line[5:].strip()
        if payload == "[DONE]":
            break

        try:
            obj = json.loads(payload)
        except json.JSONDecodeError:
            continue

        # 如果後端回了 html，代表需要重新登入，直接拋錯
        if obj.get("html"):
            raise RuntimeError("Server asks to login again")

        choice = obj.get("choices")
        if choice:
            delta = choice[0].get("delta", {})
            content = delta.get("content")
            if content:
                contents.append(content)

    return "".join(contents)

def main(args):
    if len(args) not in (3, 4):
        sys.exit("Usage: python3 call_api.py <prompt_file> <data_file> [模型 ID]")

    prompt_fp = Path(args[1])
    data_fp   = Path(args[2])

    if not USER or not PASS or not DEV_KEY:
        sys.exit("AI_USER/AI_PASS/AI_DEV_KEY 必須設定在環境變數中")

    # 如果有第三個參數，就用它；否則讀環境變數 AI_MODEL，若還是沒設定，就預設 "perplexity"
    if len(args) == 4 and args[3].strip():
        model_id = args[3].strip()
    else:
        model_id = os.getenv("AI_MODEL") or "perplexity"

    prompt = prompt_fp.read_text("utf-8", "replace")

    with requests.Session() as sess:
        login(sess)
        result = call_chat(sess, prompt, data_fp, model_id)
        sys.stdout.buffer.write(result.encode("utf-8", "replace"))

if __name__ == "__main__":
    main(sys.argv)

