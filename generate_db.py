import os
import re
import shutil
import docx
from langchain_core.documents import Document
from langchain_text_splitters import RecursiveCharacterTextSplitter, Language, HTMLHeaderTextSplitter
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_chroma import Chroma
from bs4 import BeautifulSoup

PERSIST_DIR = "./chroma_db"

print("🚀 Mac(ホスト)側でベクトル化処理を開始します...")

if os.path.exists(PERSIST_DIR):
    shutil.rmtree(PERSIST_DIR)
    print("🧹 既存の chroma_db ディレクトリを削除しました。")

# 1. Embeddingモデルのロード
embeddings = HuggingFaceEmbeddings(model_name="intfloat/multilingual-e5-large")

# 3. ファイル読み込み & Splitterのセットアップ
IGNORE_KEYWORDS = [
    "/node_modules/", "/vendor/", "/.git/", "/htdocs/css/", "/htdocs/images/", "/script/yui/", "/script/jquery/"
]

def clean_html_to_text(html_content: str) -> str:
    """
    PHPコードや余計なHTML構造タグ(table, tr, td, div, spanなど)を除去し、
    本文テキストと画像(img)/リンク(a)のみを抽出して綺麗な文字列にする関数
    """
    # 1. PHPコードの除去
    content = re.sub(r'<\?php.*?\?>', '', html_content, flags=re.DOTALL)
    
    # 2. BeautifulSoupでパース
    soup = BeautifulSoup(content, "html.parser")
    
    # 3. スクリプトやスタイルシートの除去
    for script_or_style in soup(["script", "style", "noscript"]):
        script_or_style.decompose()

    # 4. テキストの抽出 (改行を保持しながらHTMLタグを剥がす)
    # これにより <th>, <td>, <tr>, <p>, <dl>, <dt> などの構造タグが消えて純粋な文字になります
    text = soup.get_text(separator="\n")

    # 5. 空白行や余分なスペースの整形
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    cleaned_text = "\n".join(lines)

    return cleaned_text

docs_js, docs_py, docs_other, splits_html = [], [], [], []

js_splitter = RecursiveCharacterTextSplitter.from_language(language=Language.JS, chunk_size=800, chunk_overlap=100)
python_splitter = RecursiveCharacterTextSplitter.from_language(language=Language.PYTHON, chunk_size=800, chunk_overlap=100)
default_splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=80, separators=["\n\n", "\n", "。", " "])
html_headers = [("h1", "Header 1"), ("h2", "Header 2"), ("h3", "Header 3"), ("h4", "Header 4")]
html_splitter = HTMLHeaderTextSplitter(headers_to_split_on=html_headers)

# 4. docs スキャン
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
                    cleaned_text = clean_html_to_text(content)
                    html_docs = html_splitter.split_text(clean_content)
                    for h_doc in html_docs:
                        h_doc.metadata["source"] = file_path
                    splits_html.extend(html_docs)
                except Exception:
                    docs_other.append(Document(page_content=content, metadata={"source": file_path}))
            elif ext == ".js":
                docs_js.append(Document(page_content=content, metadata={"source": file_path}))
            elif ext == ".py":
                docs_py.append(Document(page_content=content, metadata={"source": file_path}))
            else:
                docs_other.append(Document(page_content=content, metadata={"source": file_path}))

# 5. チャンク結合
all_splits = []
all_splits.extend(splits_html)
if docs_js:
    all_splits.extend(js_splitter.split_documents(docs_js))
if docs_py:
    all_splits.extend(python_splitter.split_documents(docs_py))
if docs_other:
    all_splits.extend(default_splitter.split_documents(docs_other))

print(f"📊 構造化分割完了: 合計 {len(all_splits)} チャンク")

print("💾 新規 VectorStore へ書き込み中...")
vectorstore = Chroma.from_documents(
    documents=all_splits,
    embedding=embeddings,
    persist_directory=PERSIST_DIR
)

# 合わせて不要になったキャッシュファイルも削除
if os.path.exists("record_manager_cache.sql"):
    os.remove("record_manager_cache.sql")

print("🎉 リセット & クレンジング済みDBの再構築が完了しました！")