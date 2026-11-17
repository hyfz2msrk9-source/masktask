import json
import os
import random
import re
import time
from langchain_ollama import ChatOllama

# ==========================================
# 1. マスキングサービスクラス（内部定義）
# ==========================================
CATEGORY_MAP = {
    1: "会社名",
    2: "氏名",
    3: "部署名",
    4: "住所",
    5: "電話番号",
    6: "メールアドレス",
}

REGEX_GUARDRAILS = [
    (re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+"), 6),
    (re.compile(r"0\d{1,4}-\d{1,4}-\d{4}"), 5),
    (re.compile(r"0[789]0\d{8}"), 5),
]

class MaskingService:
    def __init__(self):
        self.llm = None
        self.model_name = "qwen2.5-masker-id:v1"

    def initialize(self, ollama_host: str = "http://localhost:11434", model_name: str = "v2"):
        if model_name == "v2":
            self.model_name = "qwen2.5-masker-id:v1"
        elif model_name == "v1":
            self.model_name = "qwen2.5-masker:v1"
        elif model_name == "v3":
            self.model_name = "qwen2.5-14b-masker:v1"
        else:
            self.model_name = model_name

        self.llm = ChatOllama(
            base_url=ollama_host,
            model=self.model_name,
            temperature=0.0,
        )
        print(f"✅ MaskingService 初期化完了: {self.model_name}")

    def _clean_json_string(self, content: str) -> str:
        cleaned = content.strip()
        if "```" in cleaned:
            match = re.search(r'(\[.*\]|\{.*\})', cleaned, re.DOTALL)
            if match:
                return match.group(0)
            cleaned = cleaned.replace("```json", "").replace("```", "").strip()
        return cleaned

    def _parse_id_array_response(self, text: str, content: str) -> dict:
        raw_items = []
        cleaned = self._clean_json_string(content)
        
        try:
            parsed = json.loads(cleaned)
            if isinstance(parsed, list):
                raw_items = parsed
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
                if target_str and target_str in text and target_str not in extracted_strings:
                    valid_items.append((target_str, cat_id))
                    extracted_strings.add(target_str)

        # Python側正規表現ガード
        for pattern, cat_id in REGEX_GUARDRAILS:
            for match in pattern.finditer(text):
                matched_str = match.group()
                if matched_str not in extracted_strings:
                    valid_items.append((matched_str, cat_id))
                    extracted_strings.add(matched_str)

        category_counters = {cat_name: 1 for cat_name in CATEGORY_MAP.values()}
        entities_dict = {}

        for target_str, cat_id in valid_items:
            cat_name = CATEGORY_MAP.get(cat_id, "機密情報")
            placeholder = f"[{cat_name}{category_counters[cat_name]}]"
            category_counters[cat_name] += 1
            entities_dict[placeholder] = target_str

        return entities_dict

    def mask_text(self, text: str) -> dict:
        response = self.llm.invoke(text)
        content = response.content.strip()

        # v2(ID配列形式)の判定
        if "id" in self.model_name.lower() or content.startswith("["):
            extracted_entities = self._parse_id_array_response(text, content)
        else:
            # v1 / v3 辞書形式
            cleaned = self._clean_json_string(content)
            try:
                parsed = json.loads(cleaned)
                if isinstance(parsed, dict) and "extracted_entities" in parsed:
                    extracted_entities = parsed["extracted_entities"]
                elif isinstance(parsed, dict):
                    extracted_entities = parsed
                else:
                    extracted_entities = {}
            except Exception:
                extracted_entities = {}

        return {"extracted_entities": extracted_entities}

# ==========================================
# 2. 自動テストデータ生成用パーツ
# ==========================================
LAST_NAMES = ["佐藤", "鈴木", "高橋", "田中", "渡辺", "伊藤", "山本", "中村", "小林", "加藤", "吉田", "山田"]
FIRST_NAMES = ["太郎", "花子", "健太", "一郎", "直樹", "美咲"]
COMPANIES = ["キー・ポイント株式会社", "株式会社ABC", "関西信用金庫", "東海信用金庫", "吉本興業株式会社", "小林法律事務所", "テックワン合同会社"]
DEPTS = ["営業部", "システム開発部", "法務部", "本店営業部", "融資審査課", "総務部"]
POSITIONS = ["部長", "課長", "専務", "代表取締役", "弁護士"]
ADDRESSES = [
    "東京都千代田区丸の内1-1-1",
    "大阪市中央区北浜2-2-2 北浜ビル5F",
    "東京都港区赤坂9-7-1 ミッドタウンタワー18F",
    "愛知県名古屋市中区栄3-3-3"
]
NOISE_WORDS = ["本日", "先ほど", "至急", "明日の会議", "昨日の議事録"]

def generate_test_cases(count=30):
    test_cases = []
    random.seed(42)  # 再現性のためシード固定
    
    for i in range(count):
        case_type = i % 4
        gold = []
        
        if case_type == 0:
            c = random.choice(COMPANIES)
            ln = random.choice(LAST_NAMES)
            fn = random.choice(FIRST_NAMES)
            tel = f"090-{random.randint(1000,9999)}-{random.randint(1000,9999)}"
            noise = random.choice(NOISE_WORDS)
            text = f"{noise}、{c}の{ln}{fn}よりご連絡です。折り返しは{tel}までお願いいたします。"
            gold = [c, f"{ln}{fn}", tel]

        elif case_type == 1:
            d = random.choice(DEPTS)
            ln1 = random.choice(LAST_NAMES)
            pos = random.choice(POSITIONS)
            ln2 = random.choice([x for x in LAST_NAMES if x != ln1])
            mail = f"{ln1.lower()}@{random.choice(['example.com', 'keypoint.co.jp'])}"
            text = f"{d}の{ln1}{pos}からの指示により、{ln2}様宛てにメール（{mail}）を送付しました。"
            gold = [d, ln1, pos, ln2, mail]

        elif case_type == 2:
            ln1 = random.choice(["田中", "佐藤", "鈴木"])
            ln2 = random.choice([x for x in LAST_NAMES if x != ln1])
            c1 = random.choice(COMPANIES[:3])
            c2 = random.choice(COMPANIES[3:])
            addr = random.choice(ADDRESSES)
            text = f"お世話になっております。{c1}の{ln1}（※旧姓：{ln2}）です。{c2}のオフィス（{addr}）にて面談予定です。"
            gold = [c1, ln1, ln2, c2, addr]

        else:
            c = random.choice(["関西信用金庫", "東海信用金庫"])
            d = random.choice(["本店営業部", "融資審査課"])
            ln1 = random.choice(LAST_NAMES)
            ln2 = random.choice([x for x in LAST_NAMES if x != ln1])
            comp = random.choice(COMPANIES[4:])
            addr = random.choice(ADDRESSES)
            tel = f"03-{random.randint(1000,9999)}-{random.randint(1000,9999)}"
            mail = f"info@{random.choice(['yamada.jp', 'shinkin.co.jp'])}"
            noise = random.choice(NOISE_WORDS)
            text = f"【要確認】{noise}、{c} {d}の{ln1}部長より受電。{comp}（{addr}）の{ln2}様（直通:{tel} / {mail}）へ至急折り返し願います。"
            gold = [c, d, ln1, "部長", comp, addr, ln2, tel, mail]

        test_cases.append({
            "id": f"case_{i+1:02d}_type{case_type}",
            "text": text,
            "gold_entities": list(set(gold))
        })
        
    return test_cases

# ==========================================
# 3. 実行エントリーポイント
# ==========================================
def main():
    test_count = 30
    model_alias = "v3"  # "v1" や "v3" に切り替えて比較可能

    cases = generate_test_cases(test_count)
    service = MaskingService()
    service.initialize(model_name=model_alias)

    total_gold_count = 0
    total_extracted_count = 0
    true_positives = 0
    latencies = []
    failures = []

    print(f"\n==========================================")
    print(f"📊 ベンチマーク開始: モデル [{service.model_name}] / 試行 {test_count} 件")
    print(f"==========================================")

    for idx, case in enumerate(cases):
        t0 = time.perf_counter()
        res = service.mask_text(case["text"])
        elapsed = time.perf_counter() - t0
        latencies.append(elapsed)

        extracted = list(res["extracted_entities"].values())
        gold = case["gold_entities"]

        total_gold_count += len(gold)
        total_extracted_count += len(extracted)

        tp = sum(1 for e in extracted if e in gold)
        true_positives += tp

        fp = [e for e in extracted if e not in gold]
        fn = [g for g in gold if g not in extracted]

        if fp or fn:
            failures.append({
                "id": case["id"],
                "text": case["text"],
                "fp": fp,
                "fn": fn
            })
            print(f"[{idx+1:02d}/{test_count}] ⚠️ 課題あり (所要: {elapsed:.2f}s)")
        else:
            print(f"[{idx+1:02d}/{test_count}] ✅ 満点パス (所要: {elapsed:.2f}s)")

    precision = true_positives / total_extracted_count if total_extracted_count > 0 else 0.0
    recall = true_positives / total_gold_count if total_gold_count > 0 else 0.0
    f1 = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0.0
    avg_sec = sum(latencies) / len(latencies)

    print("\n" + "="*40)
    print(f"🎯 【最終評価結果】 モデル: {service.model_name}")
    print("="*40)
    print(f"・総テスト件数       : {test_count} 件（エンティティ総数: {total_gold_count} 個）")
    print(f"・Recall (漏洩防止率): {recall:.2%}")
    print(f"・Precision (誤爆率) : {precision:.2%}")
    print(f"・F1 Score           : {f1:.4f}")
    print(f"・平均処理時間       : {avg_sec:.2f} 秒 / 件")
    print(f"・完全一致パス率     : {((test_count - len(failures)) / test_count):.1%}")

    if failures:
        print("\n--- [失敗・誤爆のあったケース（上位3件）] ---")
        for f in failures[:3]:
            print(f"\nID: {f['id']}")
            print(f"文面: {f['text']}")
            if f['fp']:
                print(f"  誤爆 (余計な抽出): {f['fp']}")
            if f['fn']:
                print(f"  漏れ (取りこぼし): {f['fn']}")

if __name__ == "__main__":
    main()