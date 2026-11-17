import json
import re
from typing import Optional
from langchain_ollama import ChatOllama

# ==========================================
# 7カテゴリ定義（役職独立・ID繰り下げ完全対応）
# ==========================================
CATEGORY_MAP = {
    1: "会社名",
    2: "氏名",
    3: "部署名",
    4: "役職",           # 独立カテゴリ
    5: "住所",           # ID 繰り下げ
    6: "電話番号",       # ID 繰り下げ
    7: "メールアドレス", # ID 繰り下げ
}

# Python側正規表現ガードレール（ID: 6=電話, ID: 7=メアド）
REGEX_GUARDRAILS = [
    (re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+"), 7),  # メールアドレス
    (re.compile(r"0\d{1,4}-\d{1,4}-\d{4}"), 6),                           # 電話番号（固定・携帯）
    (re.compile(r"0[789]0\d{8}"), 6),                                     # ハイフンなし携帯
]


class MaskingService:
    def __init__(self, default_model: str = "qwen2.5-masker-id:v1"):
        self.llm = None
        self.model_name = ""
        self.mode = "hybrid"
        self.ollama_host = "http://localhost:11434"
        # インスタンス生成時にデフォルト初期化を完了させて 500 エラーを防止
        self.initialize(ollama_host=self.ollama_host, model_name=default_model, mode="hybrid")

    def _resolve_model_name(self, name: str) -> str:
        """APIから渡される多様なバージョン・エイリアス文字列を正規化"""
        name_clean = str(name).strip().lower()
        
        # 7カテゴリ新モデル
        if name_clean in ["v4", "id7", "lora_v4", "qwen2.5-masker-id7:v1"]:
            return "qwen2.5-masker-id7:v1"
        
        # v2 ID配列モデル (lora_v2, v2, id_v1 等)
        if name_clean in ["v2", "lora_v2", "id", "qwen2.5-masker-id:v1"]:
            return "qwen2.5-masker-id:v1"
        
        # v1 辞書モデル (lora_v1, v1 等)
        if name_clean in ["v1", "lora_v1", "qwen2.5-masker:v1"]:
            return "qwen2.5-masker:v1"
            
        # v3 14Bモデル
        if name_clean in ["v3", "14b", "qwen2.5-masker:v3", "qwen2.5-14b-masker:v1"]:
            return "qwen2.5-14b-masker:v1"
            
        return name

    def initialize(
        self,
        ollama_host: str = "http://localhost:11434",
        model_name: str = "v2",
        mode: str = "hybrid",
    ):
        """モデル名と実行モードを受け取って ChatOllama を初期化・切り替え"""
        self.ollama_host = ollama_host
        self.model_name = self._resolve_model_name(model_name)
        self.mode = mode

        self.llm = ChatOllama(
            base_url=self.ollama_host,
            model=self.model_name,
            temperature=0.0,
        )
        print(f"✅ MaskingService 初期化完了: [{self.model_name}] (Mode: {self.mode})")

    def _clean_json_string(self, content: str) -> str:
        """Markdownコードブロック等の除去"""
        cleaned = content.strip()
        if "```" in cleaned:
            match = re.search(r'(\[.*\]|\{.*\})', cleaned, re.DOTALL)
            if match:
                return match.group(0)
            cleaned = cleaned.replace("```json", "").replace("```", "").strip()
        return cleaned

    def _parse_id_array_response(self, text: str, content: str) -> dict:
        """
        ID配列 [["文字列", ID], ...] のパース
        中黒・スラッシュの分離救済 ＋ 正規表現ガード適用 ＋ 登場順ソート
        """
        raw_items = []
        cleaned = self._clean_json_string(content)

        try:
            parsed = json.loads(cleaned)
            if isinstance(parsed, list):
                raw_items = parsed
            elif isinstance(parsed, dict) and "res" in parsed:
                raw_items = parsed["res"]
        except json.JSONDecodeError:
            matches = re.findall(r'\[\s*"([^"]+)"\s*,\s*(\d+)\s*\]', cleaned)
            raw_items = [[m[0], int(m[1])] for m in matches]

        extracted_strings = set()
        valid_items = []

        for item in raw_items:
            if isinstance(item, list) and len(item) >= 2:
                target_str = str(item[0]).strip()
                try:
                    cat_id = int(item[1])
                except ValueError:
                    cat_id = 1

                # 中黒・スラッシュで連結された複合語（例: システム開発部・課長）の自動救済
                split_parts = re.split(r'[・/／\s]+', target_str)
                if len(split_parts) > 1:
                    for part in split_parts:
                        part = part.strip()
                        if part and part in text and part not in extracted_strings:
                            valid_items.append((part, cat_id))
                            extracted_strings.add(part)

                if target_str and target_str in text and target_str not in extracted_strings:
                    valid_items.append((target_str, cat_id))
                    extracted_strings.add(target_str)

        # Python側正規表現ガード（取りこぼし補完）
        for pattern, cat_id in REGEX_GUARDRAILS:
            for match in pattern.finditer(text):
                matched_str = match.group()
                if matched_str not in extracted_strings:
                    valid_items.append((matched_str, cat_id))
                    extracted_strings.add(matched_str)

        # 本文中の出現位置順にソート（安定したナンバリング）
        valid_items.sort(key=lambda x: text.find(x[0]))

        category_counters = {cat_name: 1 for cat_name in CATEGORY_MAP.values()}
        entities_dict = {}

        for target_str, cat_id in valid_items:
            cat_name = CATEGORY_MAP.get(cat_id, "機密情報")
            placeholder = f"[{cat_name}{category_counters[cat_name]}]"
            category_counters[cat_name] += 1
            entities_dict[placeholder] = target_str

        return entities_dict

    def apply_python_masking(self, text: str, entities: dict) -> str:
        """長文字優先の一括置換"""
        if not entities:
            return text

        targets = []
        for placeholder, val in entities.items():
            if isinstance(val, list):
                for item in val:
                    item_str = str(item).strip()
                    if item_str:
                        targets.append((item_str, str(placeholder)))
            elif isinstance(val, (str, int, float)):
                item_str = str(val).strip()
                if item_str:
                    targets.append((item_str, str(placeholder)))

        if not targets:
            return text

        # 長い文字列から順に置換（部分一致誤爆防止）
        targets.sort(key=lambda x: len(x[0]), reverse=True)

        pattern_parts = []
        mapping = {}
        for idx, (target_str, placeholder) in enumerate(targets):
            group_name = f"group_{idx}"
            pattern_parts.append(f"(?P<{group_name}>{re.escape(target_str)})")
            mapping[group_name] = placeholder

        combined_pattern = re.compile("|".join(pattern_parts))

        def replace_match(match):
            return mapping[match.lastgroup]

        return combined_pattern.sub(replace_match, text)

    def _mask_text_hybrid(self, text: str) -> dict:
        """モデル出力（配列/辞書）を自動判別して抽出・置換"""
        response = self.llm.invoke(text)
        content = response.content.strip()

        extracted_entities = {}

        is_id_model = (
            "id" in self.model_name.lower() or 
            self.model_name in ["v2", "v4", "lora_v2", "lora_v4"] or 
            content.startswith("[")
        )

        if is_id_model:
            extracted_entities = self._parse_id_array_response(text, content)
        else:
            # 辞書出力（v1 / v3）対応
            cleaned = self._clean_json_string(content)
            try:
                parsed = json.loads(cleaned)
                if isinstance(parsed, dict):
                    extracted_entities = parsed.get("extracted_entities", parsed)
            except json.JSONDecodeError:
                pairs = re.findall(r'"(?:\[.*?\]|[^"]+)"\s*:\s*"([^"]+)"', cleaned)
                if pairs:
                    category_counters = {cat_name: 1 for cat_name in CATEGORY_MAP.values()}
                    for val in pairs:
                        placeholder = f"[機密情報{category_counters['会社名']}]"
                        category_counters["会社名"] += 1
                        extracted_entities[placeholder] = val

        masked_text = self.apply_python_masking(text, extracted_entities)

        return {
            "masked_text": masked_text,
            "extracted_entities": extracted_entities,
        }

    def mask_text(
        self,
        text: str,
        override_version: Optional[str] = None,
        override_mode: Optional[str] = None
    ) -> dict:
        """
        マスキング実行エントリーポイント
        リクエストパラメータ (version=lora_v2 等) が来た場合は動的にモデルを切り替えて実行
        """
        if override_version:
            target_model = self._resolve_model_name(override_version)
            if target_model != self.model_name or not self.llm:
                self.initialize(
                    ollama_host=self.ollama_host,
                    model_name=target_model,
                    mode=override_mode or self.mode
                )

        if not self.llm:
            self.initialize()

        target_mode = override_mode or self.mode
        return self._mask_text_hybrid(text)


# シングルトンインスタンス（自動初期化済み）
masking_service = MaskingService()