import json
import re
from langchain_ollama import ChatOllama
from langchain_core.prompts import ChatPromptTemplate

class PromptMaskingService:
    def __init__(self):
        self.llm = None
        self.mask_prompt = None

    def initialize(self, ollama_host: str = "http://localhost:11434"):
        self.llm = ChatOllama(
            base_url=ollama_host,
            model="qwen2.5:7b",
            temperature=0.0
        )
        
        # 波括弧 { } を {{ }} にエスケープして KeyError を防止
        self.mask_prompt = ChatPromptTemplate.from_messages([
            ("system", 
             "あなたは日本語テキストのエンティティ抽出（NER）エンジンです。\n"
             "入力テキストに含まれる個人情報・組織名・場所名を検出し、JSON辞書形式で出力してください。\n\n"
             "【絶対厳守の抽出ルール】\n"
             "1. 原文表記の完全一致（Exact Match）:\n"
             "   - 抽出する文字列は、入力テキスト中に存在する文字列を1文字も変えずに完全コピーしてください。\n"
             "   - 漢字の変換、中国語簡体字への変更（例: 営業部を営业部にする等）は固く禁止します。必ず入力文の日本語表記のまま抽出してください。\n"
             "2. 敬称・助詞の除外:\n"
             "   - 人名を抽出する際は「様」「さん」「君」「殿」などの敬称や、「が」「の」「を」などの助詞を含めず、名前部分のみを抽出してください。\n"
             "3. 地名・施設名と人名の分離:\n"
             "   - 「〜通り」「〜駅」「〜ビル」「〜工場」など、人名と同じ漢字を含んでいても場所を表すものは[住所]または[場所]として扱い、人名と混同しないでください。\n"
             "4. 該当なし項目の出力禁止:\n"
             "   - テキスト内に存在しない項目はキー自体を出力しないでください。「未定」や空文字は出力禁止です。\n\n"
             "【出力フォーマット】\n"
             "解説やMarkdown装飾は含めず、純粋なJSON文字列のみを出力してください。\n\n"
             "{{\n"
             '  "[会社名1]": "株式会社ABC",\n'
             '  "[部署名1]": "営業部",\n'
             '  "[氏名1]": "佐藤",\n'
             '  "[住所1]": "渡辺通り"\n'
             "}}\n"
            ),
            ("user", "{text}")
        ])
        print("✅ PromptMaskingService (完全一致・日本語保持モード) の初期化が完了しました。")

    def _clean_json_string(self, content: str) -> str:
        """Markdownブロックや前後の不要テキストを除去してJSONのみを抽出"""
        cleaned = content.strip()
        match = re.search(r'\{.*\}', cleaned, re.DOTALL)
        if match:
            return match.group(0).strip()
        return cleaned.replace("```json", "").replace("```", "").strip()

    def apply_python_masking(self, text: str, entities: dict) -> str:
        """抽出結果を用いてPython側で安全に最長一致置換"""
        if not entities or not isinstance(entities, dict):
            return text

        targets = []
        for placeholder, val in entities.items():
            if isinstance(val, list):
                for item in val:
                    item_str = str(item).strip()
                    if item_str and item_str in text:
                        targets.append((item_str, str(placeholder)))
            elif isinstance(val, (str, int, float)):
                item_str = str(val).strip()
                if item_str and item_str in text:
                    targets.append((item_str, str(placeholder)))

        if not targets:
            return text

        targets.sort(key=lambda x: len(x[0]), reverse=True)

        pattern_parts = []
        mapping = {}
        for idx, (target_str, placeholder) in enumerate(targets):
            group_name = f"group_{idx}"
            pattern_parts.append(f"(?P<{group_name}>{re.escape(target_str)})")
            mapping[group_name] = placeholder

        combined_pattern = re.compile("|".join(pattern_parts))

        def replace_match(match):
            group_name = match.lastgroup
            return mapping[group_name]

        return combined_pattern.sub(replace_match, text)

    def mask_text(self, text: str) -> dict:
        if not self.llm:
            raise RuntimeError("PromptMaskingService が初期化されていません。先に initialize() を実行してください。")

        chain = self.mask_prompt | self.llm
        response = chain.invoke({"text": text})
        content = response.content.strip()

        cleaned_content = self._clean_json_string(content)
        extracted_entities = {}

        try:
            parsed = json.loads(cleaned_content)
            if isinstance(parsed, dict):
                extracted_entities = {
                    k: v for k, v in parsed.items() 
                    if v and str(v) != "未定" and str(v) in text
                }
        except json.JSONDecodeError:
            pairs = re.findall(r'"(\[.*?\])"\s*:\s*"([^"]+)"', cleaned_content)
            extracted_entities = {
                k: v for k, v in pairs 
                if v and v != "未定" and v in text
            }

        masked_text = self.apply_python_masking(text, extracted_entities)

        return {
            "masked_text": masked_text,
            "extracted_entities": extracted_entities
        }

# インスタンス化
prompt_masking_service = PromptMaskingService()
