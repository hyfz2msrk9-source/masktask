import json
import re
from langchain_ollama import ChatOllama

class MaskingService:
    def __init__(self):
        self.llm = None
        self.model_name = "qwen2.5-masker:v1"
        self.mode = "hybrid"  # "hybrid" (LLM抽出+Python置換) または "full" (LLM全文直接生成)

    def initialize(
        self, 
        ollama_host: str = "http://localhost:11434", 
        model_name: str = "qwen2.5-masker:v1",
        mode: str = "hybrid"
    ):
        """
        動的にモデル名と実行モードを受け取って ChatOllama を初期化
        - model_name: qwen2.5-masker:v1 / v2 / v3 / qwen2.5:7b
        - mode: 
            - "hybrid": LLMでNER抽出(辞書)のみを行い、Python側で決定論的に置換 (推奨・安定)
            - "full"  : LLM自身に masked_text と extracted_entities を一括生成させる (旧方式・実験用)
        """
        self.model_name = model_name
        self.mode = mode
        self.llm = ChatOllama(
            base_url=ollama_host,
            model=model_name,
            temperature=0.0
        )
        print(f"✅ MaskingService ({self.model_name} / Mode: {self.mode}) の初期化が完了しました。")

    def _clean_json_string(self, content: str) -> str:
        """Markdownコードブロックや余計な文字のクレンジング"""
        cleaned = content.strip()
        if "```" in cleaned:
            match = re.search(r'\{.*\}', cleaned, re.DOTALL)
            if match:
                return match.group(0)
            cleaned = cleaned.replace("```json", "").replace("```", "").strip()
        return cleaned

    def apply_python_masking(self, text: str, entities: dict) -> str:
        if not entities:
            return text

        # 1. 抽出されたエンティティを (置換対象の元文字列, プレースホルダー) のフラットなリストに分解
        targets = []
        for placeholder, val in entities.items():
            if isinstance(val, list):
                # 配列で返ってきた場合（名寄せパターン）
                for item in val:
                    item_str = str(item).strip()
                    if item_str:
                        targets.append((item_str, str(placeholder)))
            elif isinstance(val, str) or isinstance(val, (int, float)):
                item_str = str(val).strip()
                if item_str:
                    targets.append((item_str, str(placeholder)))

        if not targets:
            return text

        # 2. 文字列が長い順にソート（「キー・ポイント株式会社」を「キー・ポイント」より先に置換するため）
        targets.sort(key=lambda x: len(x[0]), reverse=True)

        # 3. 正規表現パターンを作成して一括置換（二重置換・破壊の防止）
        # 例: (?P<g0>キー・ポイント株式会社)|(?P<g1>千田陽介)|... のようにグループ化
        pattern_parts = []
        mapping = {}
        for idx, (target_str, placeholder) in enumerate(targets):
            group_name = f"group_{idx}"
            pattern_parts.append(f"(?P<{group_name}>{re.escape(target_str)})")
            mapping[group_name] = placeholder

        combined_pattern = re.compile("|".join(pattern_parts))

        # 4. マッチした箇所のグループ名から対応するプレースホルダーへ安全に置き換え
        def replace_match(match):
            group_name = match.lastgroup
            return mapping[group_name]

        masked_text = combined_pattern.sub(replace_match, text)

        return masked_text

    def _mask_text_hybrid(self, text: str) -> dict:
        """【ハイブリッド方式】LLMでNER抽出のみを行い、Pythonで本文置換"""
        response = self.llm.invoke(text)
        content = response.content.strip()

        extracted_entities = {}

        if isinstance(content, dict):
            extracted_entities = content
        elif isinstance(content, str):
            cleaned = self._clean_json_string(content)
            try:
                parsed = json.loads(cleaned)
                if isinstance(parsed, dict):
                    # 万が一 旧形式(masked_text含む) の出力が返ってきた場合の自動吸収
                    if "extracted_entities" in parsed and isinstance(parsed["extracted_entities"], dict):
                        extracted_entities = parsed["extracted_entities"]
                    else:
                        extracted_entities = parsed
            except json.JSONDecodeError:
                # パース失敗時の安全フォールバック (正規表現で抽出)
                pairs = re.findall(r'"(\[.*?\])"\s*:\s*"([^"]+)"', cleaned)
                if pairs:
                    extracted_entities = {k: v for k, v in pairs}
                else:
                    print(f"⚠️ [{self.model_name}] (Hybrid) NER JSON Decode Error: {content}")

        # Python側で安全に本文置換
        masked_text = self.apply_python_masking(text, extracted_entities)

        return {
            "masked_text": masked_text,
            "extracted_entities": extracted_entities
        }

    def _mask_text_full(self, text: str) -> dict:
        """【旧・一括全文生成方式】LLM自身に masked_text と extracted_entities を両方出力させる"""
        response = self.llm.invoke(text)
        content = response.content.strip()

        if isinstance(content, dict):
            return content

        if isinstance(content, str):
            cleaned = self._clean_json_string(content)
            try:
                parsed = json.loads(cleaned)
                if isinstance(parsed, dict) and "masked_text" in parsed:
                    return parsed
            except json.JSONDecodeError:
                pass

            # フォールバック救出（旧処理）
            ee_pos = cleaned.find('"extracted_entities"')
            masked_text_val = text
            extracted_entities_val = {}

            if ee_pos != -1:
                before_part = cleaned[:ee_pos]
                mt_match = re.search(r'"masked_text"\s*:\s*"(.*)', before_part, re.DOTALL)
                if mt_match:
                    raw_mt = mt_match.group(1)
                    raw_mt = re.sub(r'"\s*,\s*$', '', raw_mt.strip())
                    masked_text_val = raw_mt.replace('\\n', '\n').replace('\\"', '"')

                after_part = cleaned[ee_pos:]
                dict_match = re.search(r'\{.*\}', after_part, re.DOTALL)
                if dict_match:
                    try:
                        extracted_entities_val = json.loads(dict_match.group(0))
                    except Exception:
                        pairs = re.findall(r'"(\[.*?\])"\s*:\s*"([^"]+)"', dict_match.group(0))
                        extracted_entities_val = {k: v for k, v in pairs}

                return {
                    "masked_text": masked_text_val,
                    "extracted_entities": extracted_entities_val
                }

            print(f"⚠️ [{self.model_name}] (Full) JSON Decode Error: {content}")
            return {
                "masked_text": text,
                "extracted_entities": {}
            }

        return {
            "masked_text": str(content),
            "extracted_entities": {}
        }

    def mask_text(self, text: str, override_mode: str = None) -> dict:
        """
        マスキング処理のエントリーポイント
        override_mode で一時的に "hybrid" / "full" を指定可能
        """
        if not self.llm:
            raise RuntimeError(f"MaskingService ({self.model_name}) が初期化されていません。")

        target_mode = override_mode or self.mode

        if target_mode == "hybrid":
            return self._mask_text_hybrid(text)
        else:
            return self._mask_text_full(text)


# 単一インスタンス
masking_service = MaskingService()
