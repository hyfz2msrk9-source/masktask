import os
import re
import json
import shutil
import docx
from langchain_core.documents import Document
from langchain_text_splitters import RecursiveCharacterTextSplitter, Language, HTMLHeaderTextSplitter
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_chroma import Chroma
from bs4 import BeautifulSoup

PERSIST_DIR = "./chroma_db"
ISSUES_JSON_PATH = "issues.json"

print("🚀 Mac(ホスト)側でベクトル化処理を開始します...")

# 既存DBの初期化
if os.path.exists(PERSIST_DIR):
    shutil.rmtree(PERSIST_DIR)
    print("🧹 既存の chroma_db ディレクトリを削除しました。")

# 1. Embeddingモデルのロード
embeddings = HuggingFaceEmbeddings(model_name="intfloat/multilingual-e5-large")

# 2. 除外キーワード設定
IGNORE_KEYWORDS = [
    "/node_modules/", "/vendor/", "/.git/", "/htdocs/css/", "/htdocs/images/", "/script/yui/", "/script/jquery/"
]

def clean_html_to_text(html_content: str) -> str:
    """
    PHPコードや余計なHTML構造タグを除去し、純粋な本文テキストのみに整形する関数
    """
    # PHPコードの除去
    content = re.sub(r'<\?php.*?\?>', '', html_content, flags=re.DOTALL)
    
    # BeautifulSoupでパースして不用タグ除去
    soup = BeautifulSoup(content, "html.parser")
    for script_or_style in soup(["script", "style", "noscript"]):
        script_or_style.decompose()

    # テキスト抽出 & 空行トリム
    text = soup.get_text(separator="\n")
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    return "\n".join(lines)

docs_js, docs_py, docs_other, splits_html = [], [], [], []

# スプリッター定義
js_splitter = RecursiveCharacterTextSplitter.from_language(language=Language.JS, chunk_size=800, chunk_overlap=100)
python_splitter = RecursiveCharacterTextSplitter.from_language(language=Language.PYTHON, chunk_size=800, chunk_overlap=100)
default_splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=80, separators=["\n\n", "\n", "。", " "])

html_headers = [("h1", "Header 1"), ("h2", "Header 2"), ("h3", "Header 3"), ("h4", "Header 4")]
html_splitter = HTMLHeaderTextSplitter(headers_to_split_on=html_headers)

# 3. docs スキャン (ローカルドキュメント・ソースコード)
print("📁 ドキュメントをスキャン中...")
for root, _, files in os.walk("./docs"):
    normalized_path = root.replace("\\", "/") + "/"
    if any(keyword in normalized_path for keyword in IGNORE_KEYWORDS):
        continue

    for file in files:
        ext = os.path.splitext(file)[1].lower()
        file_path = os.path.join(root, file)

        if ext in [".html", ".htm", ".php", ".tpl", ".docx", ".txt", ".md", ".json", ".js", ".py"]:
            content = None

            if ext == ".docx":
                try:
                    doc = docx.Document(file_path)
                    content = "\n".join([p.text for p in doc.paragraphs if p.text.strip()])
                except Exception:
                    continue
            else:
                for enc in ["utf-8", "cp932", "euc_jp"]:
                    try:
                        with open(file_path, "r", encoding=enc) as f:
                            content = f.read()
                        break
                    except Exception:
                        continue

            if not content or not content.strip():
                continue

            is_html_content = ext in [".html", ".htm"] or "<h1" in content.lower() or "<h2" in content.lower() or "<p>" in content.lower()

            if is_html_content:
                try:
                    # 生のHTMLで見出し分割を行ってからクレンジング（バグ修正箇所）
                    html_docs = html_splitter.split_text(content)
                    for h_doc in html_docs:
                        h_doc.page_content = clean_html_to_text(h_doc.page_content)
                        h_doc.metadata["source"] = file_path
                        if h_doc.page_content.strip():
                            splits_html.append(h_doc)
                except Exception:
                    cleaned = clean_html_to_text(content)
                    docs_other.append(Document(page_content=cleaned, metadata={"source": file_path}))
            elif ext == ".js":
                docs_js.append(Document(page_content=content, metadata={"source": file_path}))
            elif ext == ".py":
                docs_py.append(Document(page_content=content, metadata={"source": file_path}))
            else:
                docs_other.append(Document(page_content=content, metadata={"source": file_path}))

# 4. issues.json (GitHub Issue) のスキャン・追加
docs_issues = []
if os.path.exists(ISSUES_JSON_PATH):
    print(f"🐛 {ISSUES_JSON_PATH} (GitHub Issues) をインデックスへ追加中...")
    try:
        with open(ISSUES_JSON_PATH, "r", encoding="utf-8") as f:
            issues = json.load(f)

        for issue in issues:
            number = issue.get("number", "")
            title = issue.get("title", "")
            body = issue.get("body", "")
            labels = [l.get("name") if isinstance(l, dict) else str(l) for l in issue.get("labels", [])]

            comments_text = ""
            comments = issue.get("comments", [])
            if comments:
                comments_formatted = []
                for c in comments:
                    author = c.get("author", {}).get("login", "unknown")
                    c_body = c.get("body", "")
                    comments_formatted.append(f"[{author}]: {c_body}")
                comments_text = "\n\n--- コメント・議論 ---\n" + "\n".join(comments_formatted)

            full_text = f"【Issue #{number}】 {title}\n\n【本文・概要】\n{body}\n{comments_text}"

            meta = {
                "source": f"issue_{number}",
                "issue_number": number,
                "title": title,
                "labels": ", ".join(labels),
                "type": "github_issue"
            }
            docs_issues.append(Document(page_content=full_text, metadata=meta))
    except Exception as e:
        print(f"⚠️ {ISSUES_JSON_PATH} の読み込み中にエラーが発生しました: {e}")

# 5. チャンク結合
all_splits = []
all_splits.extend(splits_html)
if docs_js:
    all_splits.extend(js_splitter.split_documents(docs_js))
if docs_py:
    all_splits.extend(python_splitter.split_documents(docs_py))
if docs_other:
    all_splits.extend(default_splitter.split_documents(docs_other))
if docs_issues:
    # Issueは1件1チャンク（または文脈単位）でそのまま追加
    all_splits.extend(docs_issues)

print(f"📊 構造化分割完了: 合計 {len(all_splits)} チャンク (うち Issue: {len(docs_issues)}件)")

# 6. ChromaDBへ書き出し
print("💾 新規 VectorStore へ書き込み中...")
vectorstore = Chroma.from_documents(
    documents=all_splits,
    embedding=embeddings,
    persist_directory=PERSIST_DIR
)

# キャッシュのクレンジング
if os.path.exists("record_manager_cache.sql"):
    os.remove("record_manager_cache.sql")

print("🎉 リセット & クレンジング済みDBの再構築が完了しました！")