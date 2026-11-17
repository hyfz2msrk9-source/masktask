import os
import re
import csv
from typing import List
from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from langchain_core.documents import Document
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_ollama import ChatOllama
from langchain_community.vectorstores import Chroma
from langchain.chains.retrieval import create_retrieval_chain
from langchain.chains.combine_documents import create_stuff_documents_chain
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.retrievers import BaseRetriever
from langchain_core.callbacks import CallbackManagerForRetrieverRun
from datetime import datetime

APP_URL = os.getenv("APP_URL", "https://localhost")
APP_PATH = os.getenv("APP_PATH", "/acb")
BASE_URL = f"{APP_URL.rstrip('/')}/{APP_PATH.strip('/')}"

def fix_relative_paths(text: str) -> str:
    """
    href="..." や src="..." に含まれる任意の相対パス (./ や ../) を
    環境変数の BASE_URL を基準とした絶対URLに変換する関数
    """
    def replace_match(match):
        attr = match.group(1)      # 'href' または 'src'
        rel_path = match.group(2)  # './...', '../../../...' などのパス
        clean_path = re.sub(r'^(\./|\.\./)+', '', rel_path)
        return f'{attr}="{BASE_URL}/{clean_path}"'

    pattern = r'(href|src)=["\']((?:\./|\.\./)+[^"\']+)["\']'
    return re.sub(pattern, replace_match, text)

# バックグラウンド評価・ログ出力関数
def evaluate_and_log(query: str, sources: list, answer: str, judge_chain):
    """ユーザーへのレスポンス後にバックグラウンドで採点および詳細ログを出力する"""
    print("\n" + "="*50, flush=True)
    print(f"🔍 [デバッグログ] 質問: {query}", flush=True)
    print(f"📚 [ヒットしたChunk数]: {len(sources)}件", flush=True)
    
    context_text = "\n---\n".join(sources) if sources else "なし"

    if not sources:
        print("⚠️ 警告: 検索結果が 0 件です！ChromaDBから何も取得できていません。", flush=True)
    else:
        for i, src in enumerate(sources, 1):
            clean_src = src.replace("\n", " ")
            if "質問:" in src and "模範回答:" in src:
                print(f"  - Chunk {i} [🌟 過去の蓄積QA]: {clean_src[:100]}...", flush=True)
            else:
                print(f"  - Chunk {i} [📄 マニュアル]: {clean_src[:100]}...", flush=True)
            
    print(f"🤖 [最終回答]: {answer}", flush=True)

    # ------------------------------------------
    # 採点処理 (LLM-as-a-Judge)
    # ------------------------------------------
    if judge_chain and sources:
        try:
            response_obj = judge_chain.invoke({
                "query": query,
                "context": context_text,
                "answer": answer
            })
            eval_result = response_obj.content
            print(f"📊 [採点結果]:\n{eval_result}", flush=True)

            # Score と Reason を正規表現で安全に分離抽出
            score_match = re.search(r'Score:\s*(\d)', eval_result)
            score_val = score_match.group(1) if score_match else "1"
            
            reason_match = re.search(r'Reason:\s*(.*)', eval_result, re.DOTALL)
            reason_val = reason_match.group(1).replace("\n", " ").strip() if reason_match else eval_result.replace("\n", " ")

            # 【低スコア（1〜3点）の出力】
            if score_val in ["1", "2", "3"]:
                with open("rag_improvement_list.csv", "a", encoding="utf-8-sig", newline="") as f:
                    writer = csv.writer(f, quoting=csv.QUOTE_ALL)
                    writer.writerow([
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        query,
                        score_val,
                        reason_val,
                        answer
                    ])
                print("⚠️ [改善キュー]: 低スコアのログを rag_improvement_list.csv に書き出しました", flush=True)

            # 【高スコア（4〜5点）の処理】
            elif score_val in ["4", "5"]:
                is_refusal = "該当する情報が見つかりませんでした" in answer or "申し訳ありません" in answer
                if is_refusal:
                    # 正しく回答拒否（ガード）できた Score:5 ログを記録
                    with open("rag_improvement_list.csv", "a", encoding="utf-8-sig", newline="") as f:
                        writer = csv.writer(f, quoting=csv.QUOTE_ALL)
                        writer.writerow([
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            query,
                            "5",
                            reason_val,
                            answer
                        ])
                    print("⚠️ [改善キュー]: 該当情報なしのログを rag_improvement_list.csv に書き出しました", flush=True)
                else:
                    # 有効回答の場合はナレッジキャッシュに蓄積
                    formatted_eval_query = f"query: {query}" if not query.startswith("query: ") else query
                    existing_docs = qa_cache_db.similarity_search_with_score(formatted_eval_query, k=1)

                    is_duplicate = False
                    if existing_docs:
                        doc, score = existing_docs[0]
                        if score < 0.18:
                            is_duplicate = True
                            print(f"ℹ️ [ナレッジ蓄積スキップ]: すでに類似のQAが登録されています (類似度スコア: {score:.3f})", flush=True)

                    if not is_duplicate:
                        high_score_doc = Document(
                            page_content=f"質問: {query}\n模範回答: {answer}",
                            metadata={
                                "query": query,
                                "answer": answer,
                                "created_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            }
                        )
                        qa_cache_db.add_documents([high_score_doc])
                        print("🌟 [ナレッジ蓄積]: 高スコア（4-5点）の新しい回答パターンを自動追加しました！", flush=True)
        except Exception as e:
            print(f"⚠️ 採点処理中にエラーが発生しました: {e}", flush=True)
    elif not sources:
        print("📊 [採点結果]: 0点 (参考資料が見つからなかったため採点スキップ)", flush=True)

    print("="*50 + "\n", flush=True)

# ==========================================
# 1. Ollama設定 & カスタムRetriever
# ==========================================
ollama_host = os.environ.get("OLLAMA_HOST", "http://localhost:11434")

class CleanManualRetriever(BaseRetriever):
    vectorstore: Chroma
    k: int = 5

    def _get_relevant_documents(
        self, query: str, *, run_manager: CallbackManagerForRetrieverRun = None
    ) -> List[Document]:
        formatted_query = f"query: {query}" if not query.startswith("query: ") else query
        primary_docs = self.vectorstore.similarity_search(formatted_query, k=self.k)
        
        filtered_docs = []
        for doc in primary_docs:
            content = doc.page_content.strip()
            if len(content) < 30 or content.startswith("</tbody>") or content.startswith("</table>"):
                continue
            filtered_docs.append(doc)

        return filtered_docs if filtered_docs else primary_docs

rag_chain = None
judge_chain = None
qa_cache_db = None

# ==========================================
# 2. FastAPI Webサーバー定義 & 起動時処理
# ==========================================
app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
async def startup_event():
    global rag_chain, judge_chain, qa_cache_db
    
    PERSIST_DIR = "./chroma_db"
    embeddings = HuggingFaceEmbeddings(model_name="intfloat/multilingual-e5-large")

    qa_cache_db = Chroma(
        collection_name="high_score_qa",
        persist_directory="./high_score_db",
        embedding_function=embeddings
    )

    print("💾 既存のベクトルDB(chroma_db)をロード中...")
    vectorstore = Chroma(
        persist_directory=PERSIST_DIR, 
        embedding_function=embeddings
    )

    collection_count = vectorstore._collection.count()
    print(f"📦 [ChromaDB登録件数]: {collection_count} 件のChunkが保管されています")

    retriever = CleanManualRetriever(vectorstore=vectorstore, k=5)

    llm = ChatOllama(
        base_url=ollama_host,
        model="qwen2.5:7b", 
        temperature=0
    )

    system_prompt = (
        "あなたは一般ユーザー向けヘルプデスクの担当者です。\n"
        "【参考資料】に「直接記載されている内容」のみに基づいて回答を作成してください。\n\n"
        "【厳守ルール】\n"
        "1. 参考資料に明確な記載がない仕様・機能・手順について、推測や勝手な拡張（〜にも適用できる等）を行って回答することは厳禁です。\n"
        "2. 参考資料から質問に対する明確な根拠が得られない場合は、無理に回答を作らず「申し訳ありません。該当する情報が見つかりませんでした。」と回答してください。\n"
        "3. メールの挨拶や署名は不要です。質問に対する回答のみを出力してください。\n\n"
        "【参考資料】:\n{context}"
    )

    prompt = ChatPromptTemplate.from_messages([
        ("system", system_prompt),
        ("human", "{input}"),
    ])

    question_answer_chain = create_stuff_documents_chain(llm, prompt)
    rag_chain = create_retrieval_chain(retriever, question_answer_chain)

    judge_prompt_str = (
        "あなたはRAGシステムの精度を判定する【極めて厳格な】品質監査員です。\n"
        "以下の「質問」「参考資料」「生成された回答」を評価し、5段階評価（1〜5点）と簡潔な理由を出力してください。\n\n"
        "【絶対ルール（最優先）】\n"
        "生成された回答の中に、参考資料に『直接書かれていない推測・でっち上げ・補完』が少しでも含まれている場合は、理由を明記した上で【問答無用で Score: 1】としてください。（3点や4点をつけることは厳禁です）\n\n"
        "【採点基準】\n"
        "5点: 参考資料に『直接書かれている事実のみ』に基づき正確に回答できている。\n"
        "     ※参考資料内に質問の根拠がないため「該当する情報が見つかりませんでした」と正確に回答拒否（ガード）できている場合も【5点】とします。\n"
        "3点: 参考資料に直接書かれている事実のみに基づいているが、表現が不十分、または軽微な情報不足がある場合。（※推測や捏造が一切ない場合のみ適用）\n"
        "1点: 参考資料に記載のない内容を推測・補完して回答している（ハルシネーション）、または参考資料と矛盾している。\n\n"
        "【質問】:\n{query}\n\n"
        "【参考資料】:\n{context}\n\n"
        "【生成された回答】:\n{answer}\n\n"
        "【出力フォーマット】:\n"
        "Score: [1-5]\n"
        "Reason: [評価理由を日本語で1-2文で説明]"
    )

    judge_prompt = ChatPromptTemplate.from_messages([
        ("user", judge_prompt_str)
    ])
    judge_chain = judge_prompt | llm

    print("✅ RAGシステムおよび採点チェーンの初期化が完了しました。")

# ==========================================
# 3. APIエンドポイント定義
# ==========================================
class QueryRequest(BaseModel):
    query: str

@app.post("/api/search")
async def search(req: QueryRequest, background_tasks: BackgroundTasks):
    if not rag_chain:
        raise HTTPException(status_code=503, detail="RAG system is still initializing.")

    if not req.query.strip():
        raise HTTPException(status_code=400, detail="Query cannot be empty")
    
    try:
        formatted_query = f"query: {req.query}" if not req.query.startswith("query: ") else req.query
        cached_docs_with_score = qa_cache_db.similarity_search_with_score(formatted_query, k=1)

        if cached_docs_with_score:
            doc, score = cached_docs_with_score[0]
            if score < 0.18:
                cached_answer = doc.metadata.get("answer", "")
                answer_text = fix_relative_paths(cached_answer)
                
                print(f"⚡ [キャッシュHit!]: 蓄積QAから即解凍して回答します (スコア: {score:.3f})", flush=True)

                return {
                    "status": "success",
                    "answer": answer_text,
                    "sources": [doc.page_content],
                    "used_qa_cache": True
                }

        response = rag_chain.invoke({"input": req.query})
        raw_sources = [doc.page_content for doc in response.get("context", [])]
        raw_answer_text = response.get("answer", "")

        answer_text = fix_relative_paths(raw_answer_text)
        sources = [fix_relative_paths(src) for src in raw_sources]

        background_tasks.add_task(evaluate_and_log, req.query, sources, answer_text, judge_chain)

        return {
            "status": "success",
            "answer": answer_text,
            "sources": sources,
            "used_qa_cache": False
        }
    except Exception as e:
        print(f"❌ RAG全体エラー: {e}")
        raise HTTPException(status_code=500, detail=str(e))