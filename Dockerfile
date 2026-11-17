FROM python:3.10-slim

WORKDIR /app

# 必要なシステムライブラリ（C++コンパイル等）のインストール
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 依存ライブラリのコピー＆インストール
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# コードとデータのコピー
COPY . .

# FastAPIの起動
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
