import os
from typing import Optional
from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse
from pydantic import BaseModel
from langchain_ollama import ChatOllama

# 専門のサービスモジュールから呼び出す（責務の分離）
from rag.service import RAGService
from doc_generator.service import DocGeneratorService
from masking.service import MaskingService
from masking.prompt_service import prompt_masking_service

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# 各サービスのインスタンス準備（LoRAの各バージョン用に個別準備）
rag_service = RAGService()
doc_service = DocGeneratorService()

lora_v1_service = MaskingService()
lora_v2_service = MaskingService()
lora_v3_service = MaskingService()

# 静的ファイル（画面）の配信窓口
os.makedirs("static", exist_ok=True)
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.on_event("startup")
async def startup_event():
    print("🚀 システム初期化を開始します...")
    rag_service.initialize()
    doc_llm = ChatOllama(
        base_url=rag_service.ollama_host,
        model="qwen2.5-docgen",
        temperature=0
    )

    doc_service.initialize(vectorstore=rag_service.vectorstore, llm=doc_llm)
    
    # プロンプト版の初期化
    prompt_masking_service.initialize(ollama_host=rag_service.ollama_host)
    
    # 各 LoRA モデルをモデル名付きで個別に初期化
    host = rag_service.ollama_host
    lora_v1_service.initialize(ollama_host=host, model_name="qwen2.5-masker:v1")
    lora_v2_service.initialize(ollama_host=host, model_name="qwen2.5-masker-id:v1")
    lora_v3_service.initialize(ollama_host=host, model_name="qwen2.5-masker-id:grok")
    
    print("✅ すべてのサービスの準備が完了しました。")

@app.get("/")
def read_index():
    if os.path.exists("static/index.html"):
        return FileResponse("static/index.html")
    return {"message": "RAG & DocGen API is running."}

class QueryRequest(BaseModel):
    query: str

class MaskRequest(BaseModel):
    text: str
    version: Optional[str] = "lora_v3"

# 窓口1: 通常のQA検索（RAGサービスへ委譲）
@app.post("/api/search")
async def search(req: QueryRequest, background_tasks: BackgroundTasks):
    if not req.query.strip():
        raise HTTPException(status_code=400, detail="Query cannot be empty")
    
    try:
        result = rag_service.search_and_evaluate(req.query, background_tasks)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# 窓口2: PII 個人情報マスキング API（バージョン動的振り分け）
@app.post("/mask")
async def mask_pii(req: MaskRequest):
    """version指定に応じて各モデルサービスへルーティング"""
    target_version = req.version or "lora_v3"

    try:
        if target_version == "prompt":
            result = prompt_masking_service.mask_text(req.text)
        elif target_version == "lora_v1":
            result = lora_v1_service.mask_text(req.text)
        elif target_version == "lora_v2":
            result = lora_v2_service.mask_text(req.text)
        elif target_version == "lora_v3":
            result = lora_v3_service.mask_text(req.text)
        else:
            raise HTTPException(status_code=400, detail=f"未対応のバージョンです: {target_version}")

        return {
            "status": "success",
            "mode": target_version,
            "result": result
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/mask-prompt")
async def mask_pii_prompt(req: MaskRequest):
    """プロンプト版専用窓口（下位互換用）"""
    try:
        result = prompt_masking_service.mask_text(req.text)
        return {"status": "success", "mode": "prompt", "result": result}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/download/{filename}")
async def download_file(filename: str):
    filepath = os.path.join("output", filename)
    if not os.path.exists(filepath):
        raise HTTPException(status_code=404, detail="ファイルが存在しません")
    return FileResponse(path=filepath, filename=filename, media_type='application/octet-stream')