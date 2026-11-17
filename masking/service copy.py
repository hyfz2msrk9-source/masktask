import json
import re
from langchain_ollama import ChatOllama

class MaskingService:
    def __init__(self):
        self.llm = None

    def initialize(self, ollama_host: str = "http://localhost:11434"):
        """LoRAチューニング済みのマスキング専用モデルをロード"""
        self.llm = ChatOllama(
            base_url=ollama_host,
            model="qwen2.5-masker:v3",
            temperature=0.0
        )
        print("✅ MaskingService (qwen2.5-masker:v2) の初期化が完了しました。")

    def mask_text(self, text: str) -> dict:
        if not self.llm:
            raise RuntimeError("MaskingService が初期化されていません。")

        # LLM呼び出し
        response = self.llm.invoke(text)
        content = response.content
        raw_str = str(content).strip() if not isinstance(content, dict) else ""

        if isinstance(content, dict):
            return content

        # コードブロックの除去
        if "```" in raw_str:
            match = re.search(r'\{.*\}', raw_str, re.DOTALL)
            raw_str = match.group(0) if match else raw_str.replace("```json", "").replace("```", "").strip()

        # 1. まず標準の json.loads を試す（素直な出力の場合はこれで通る）
        try:
            return json.loads(raw_str)
        except Exception:
            pass

        # 2. 【絶対クラッシュしない強力救出ロジック】
        # "extracted_entities" の出現位置を探し、そこを境界線として分離する
        ee_pos = raw_str.find('"extracted_entities"')
        
        masked_text_result = text
        extracted_entities_result = {}

        if ee_pos != -1:
            # "extracted_entities" より前を "masked_text" のエリアとして抽出
            before_part = raw_str[:ee_pos]
            # "masked_text": " の部分を削ぎ落とす
            mt_match = re.search(r'"masked_text"\s*:\s*"(.*)', before_part, re.DOTALL)
            if mt_match:
                # 末尾のカンマや改行、ダブルクォートを取り除いて本文を復元
                raw_mt = mt_match.group(1)
                raw_mt = re.sub(r'"\s*,\s*$', '', raw_mt.strip())
                masked_text_result = raw_mt.replace('\\n', '\n').replace('\\"', '"')

            # "extracted_entities" 以降から JSON 辞書部分 { ... } だけを強制抽出
            after_part = raw_str[ee_pos:]
            dict_match = re.search(r'\{.*\}', after_part, re.DOTALL)
            if dict_match:
                try:
                    extracted_entities_result = json.loads(dict_match.group(0))
                except Exception:
                    # 辞書部分が壊れている場合はキーバリューを正規表現で拾う
                    pairs = re.findall(r'"(\[.*?\])"\s*:\s*"([^"]+)"', dict_match.group(0))
                    extracted_entities_result = {k: v for k, v in pairs}

        return {
            "masked_text": masked_text_result,
            "extracted_entities": extracted_entities_result
        }

# シングルトンインスタンス
masking_service = MaskingService()