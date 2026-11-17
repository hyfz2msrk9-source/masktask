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
        
        # 住所分解を絶対にサボらせない強力な Few-Shot 入りプロンプト
        self.mask_prompt = ChatPromptTemplate.from_messages([
            ("system", 
             "あなたは超高精度な個人情報・住所構造化匿名化エンジンです。\n"
             "入力されたテキストに含まれるすべての個人情報・組織情報・住所情報を特定し、指定されたプレースホルダーに置換した本文(`masked_text`)と対応辞書(`extracted_entities`)を出力してください。\n\n"
             "【置換ルールとプレースホルダー定義】\n"
             "・[会社名N]: 企業・組織・法人名（「キー・ポイント株式会社」と「キーポイント株式会社」等の表記揺れも同一ID）\n"
             "・[部署名N]: 部署・事業部名\n"
             "・[氏名N]: 人物名（漢字・英字表記・苗字のみ・敬称前後の単語も全て同一IDに統一）\n"
             "・[電話番号N]: TEL、FAX、携帯、固定電話問わず登場順にナンバリング\n"
             "・[メールアドレスN]: メールアドレス\n\n"
             "【★住所置換の絶対ルール★】\n"
             "「〒100-0005 東京都千代田区丸の内1-1-1 丸ビル20F」のような住所表記は、絶対に『[住所1]』などと一括で置換してはなりません！\n"
             "必ず以下の5要素に分割して置換してください：\n"
             "  - 〒100-0005 ➔ 〒[郵便番号1]\n"
             "  - 東京都 ➔ [都道府県名1]\n"
             "  - 千代田区 ➔ [市区町村名1]\n"
             "  - 丸の内1-1-1 ➔ [番地1]\n"
             "  - 丸ビル20F ➔ [建物名1]\n"
             "※本文中の「〒100-0005 東京都千代田区丸の内1-1-1 丸ビル20F」は、最終的に『〒[郵便番号1] [都道府県名1][市区町村名1][番地1] [建物名1]』という本文テキストに置換されなければなりません。\n\n"
             "【出力フォーマット】\n"
             "純粋なJSON文字列のみを出力してください（解説・Markdown・前置きは一切厳禁）。\n\n"
             "{{\n"
             '  "masked_text": "【緊急連絡】... 〒[郵便番号1] [都道府県名1][市区町村名1][番地1] [建物名1] ...",\n'
             '  "extracted_entities": {{\n'
             '    "[会社名1]": "キー・ポイント株式会社",\n'
             '    "[郵便番号1]": "100-0005",\n'
             '    "[都道府県名1]": "東京都",\n'
             '    "[市区町村名1]": "千代田区",\n'
             '    "[番地1]": "丸の内1-1-1",\n'
             '    "[建物名1]": "丸ビル20F"\n'
             '  }}\n'
             "}}\n"
            ),
            ("user", "{text}")
        ])

    def _clean_json_string(self, content: str) -> str:
        cleaned = content.strip()
        if "```" in cleaned:
            match = re.search(r'\{.*\}', cleaned, re.DOTALL)
            if match:
                return match.group(0)
            cleaned = cleaned.replace("```json", "").replace("```", "").strip()
        return cleaned

    def mask_text(self, text: str) -> dict:
        if not self.llm:
            raise RuntimeError("PromptMaskingService が初期化されていません。")

        chain = self.mask_prompt | self.llm
        response = chain.invoke({"text": text})
        content = response.content.strip()

        cleaned_content = self._clean_json_string(content)

        try:
            parsed = json.loads(cleaned_content)
            if isinstance(parsed, dict) and "masked_text" in parsed:
                return parsed
        except json.JSONDecodeError:
            pass

        # 破綻時のフォールバック処理
        ee_pos = cleaned_content.find('"extracted_entities"')
        masked_text_val = text
        extracted_entities_val = {}

        if ee_pos != -1:
            before_part = cleaned_content[:ee_pos]
            mt_match = re.search(r'"masked_text"\s*:\s*"(.*)', before_part, re.DOTALL)
            if mt_match:
                raw_mt = mt_match.group(1)
                raw_mt = re.sub(r'"\s*,\s*$', '', raw_mt.strip())
                masked_text_val = raw_mt.replace('\\n', '\n').replace('\\"', '"')

            after_part = cleaned_content[ee_pos:]
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

        print(f"⚠️ [Prompt 7B] JSON Decode Error: {content}")
        return {
            "masked_text": text,
            "extracted_entities": {}
        }

prompt_masking_service = PromptMaskingService()