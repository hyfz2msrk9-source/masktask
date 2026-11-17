import asyncio
import json
import os
import random
import re
import time
from datetime import datetime
from langchain_ollama import ChatOllama

# ==========================================
# 1. 共通サービスクラス（非同期並列チャンキング対応）
# ==========================================
CATEGORY_MAP = {
    1: "会社名",
    2: "氏名",
    3: "部署名",
    4: "役職",           # 独立
    5: "住所",           # ID繰り下げ
    6: "電話番号",       # ID繰り下げ
    7: "メールアドレス", # ID繰り下げ
}

REGEX_GUARDRAILS = [
    (re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+"), 7),
    (re.compile(r"0\d{1,4}-\d{1,4}-\d{4}"), 6),
    (re.compile(r"0[789]0\d{8}"), 6),
]

COMPANY_MARKERS = ["株式会社", "有限会社", "合同会社", "合資会社", "信用金庫", "事務所", "法人"]

class MaskingService:
    def __init__(self):
        self.llm = None
        self.model_name = ""

    def initialize(self, model_name: str, ollama_host: str = "http://localhost:11434"):
        alias_map = {
            "v1": "qwen2.5-masker:v1-q4",
            "v2": "qwen2.5-masker-id:v1",
            "v3": "qwen2.5-masker-id:grok",
            "base": "qwen2.5:7b"
        }
        self.model_name = alias_map.get(model_name, model_name)
        self.llm = ChatOllama(base_url=ollama_host, model=self.model_name, temperature=0.0)

    def _parse_single_response(self, text_chunk: str, content: str) -> tuple[set, list]:
        """1チャンク分の推論出力をパース"""
        extracted = set()
        raw_matches = []

        # --- A. ID配列形式のパース ---
        array_matches = re.findall(r'\[\s*"([^"]+)"\s*,\s*(\d+)\s*\]', content)
        if array_matches:
            for target_str, cid_str in array_matches:
                target_str = target_str.strip()
                raw_matches.append(target_str)
                try:
                    cat_id = int(cid_str)
                except ValueError:
                    cat_id = 1

                # 会社名・組織名は中黒（キー・ポイント等）で分割しない
                is_company = any(m in target_str for m in COMPANY_MARKERS) or cat_id == 1
                if is_company:
                    parts = [target_str]
                else:
                    parts = [p.strip() for p in re.split(r'[・/／\s]+', target_str) if p.strip()]

                for part in parts:
                    if part and part in text_chunk:
                        extracted.add(part)
                if target_str in text_chunk:
                    extracted.add(target_str)

        # --- B. 辞書形式のパース ---
        clean_content = content
        if "```" in clean_content:
            m = re.search(r'(\[.*\]|\{.*\})', clean_content, re.DOTALL)
            if m:
                clean_content = m.group(0)

        try:
            parsed = json.loads(clean_content)
            if isinstance(parsed, dict):
                data_dict = parsed.get("extracted_entities", parsed)
                if isinstance(data_dict, dict):
                    for val in data_dict.values():
                        if isinstance(val, str):
                            val_str = val.strip()
                            raw_matches.append(val_str)
                            if val_str and val_str in text_chunk:
                                extracted.add(val_str)
        except Exception:
            dict_pairs = re.findall(r'"(?:\[.*?\]|[^"]+)"\s*:\s*"([^"]+)"', clean_content)
            for val in dict_pairs:
                val_str = val.strip()
                raw_matches.append(val_str)
                if val_str and val_str in text_chunk:
                    extracted.add(val_str)

        return extracted, raw_matches

    async def _process_chunk_async(self, chunk: str) -> tuple[set, list, str]:
        """非同期で単一チャンクを推論"""
        resp = await self.llm.ainvoke(chunk)
        c_text = resp.content.strip()
        ext, raw_m = self._parse_single_response(chunk, c_text)
        return ext, raw_m, c_text

    async def mask_text_async(self, text: str) -> dict:
        """長文を段落チャンクに分割し、非同期で一斉並列推論"""
        paragraphs = text.split("\n\n")
        chunks = []
        curr = []
        curr_len = 0

        for p in paragraphs:
            if curr_len + len(p) > 450 and curr:
                chunks.append("\n\n".join(curr))
                curr = [p]
                curr_len = len(p)
            else:
                curr.append(p)
                curr_len += len(p)
        if curr:
            chunks.append("\n\n".join(curr))

        # ⚡ 全チャンクを並列実行
        tasks = [self._process_chunk_async(c) for c in chunks]
        results = await asyncio.gather(*tasks)

        all_extracted = set()
        all_raw_matches = []
        all_raw_responses = []

        for ext, raw_m, c_text in results:
            all_extracted.update(ext)
            all_raw_matches.extend(raw_m)
            all_raw_responses.append(c_text)

        # Python側正規表現ガードレール（全文に対して補完）
        regex_added = []
        for pattern, _ in REGEX_GUARDRAILS:
            for match in pattern.finditer(text):
                m_str = match.group()
                if m_str not in all_extracted:
                    regex_added.append(m_str)
                all_extracted.add(m_str)

        return {
            "extracted": sorted(list(all_extracted), key=lambda x: len(x), reverse=True),
            "raw_matches": all_raw_matches,
            "regex_added": regex_added,
            "raw_response": "\n--- chunk ---\n".join(all_raw_responses) if len(all_raw_responses) > 1 else all_raw_responses[0]
        }

# ==========================================
# 2. 多様な文体生成エンジン
# ==========================================
LAST_NAMES = [
    ("佐藤", "sato"), ("鈴木", "suzuki"), ("高橋", "takahashi"), ("田中", "tanaka"),
    ("渡辺", "watanabe"), ("伊藤", "ito"), ("山本", "yamamoto"), ("中村", "nakamura"),
    ("小林", "kobayashi"), ("加藤", "kato"), ("吉田", "yoshida"), ("山田", "yamada")
]
FIRST_NAMES = ["太郎", "花子", "健太", "一郎", "直樹", "美咲"]
DEPTS = ["営業部", "システム開発部", "法務部", "融資審査課", "総務部", "人事企画課"]
POSITIONS = ["部長", "課長", "専務", "代表取締役", "担当", "支店長"]
COMPANIES = ["キー・ポイント株式会社", "株式会社ABC", "関西信用金庫", "東海信用金庫", "吉本興業株式会社", "小林法律事務所", "テックワン合同会社"]
CITIES = ["東京都千代田区丸の内", "大阪市中央区北浜", "東京都港区赤坂", "愛知県名古屋市中区栄", "福岡市博多区博多駅前"]

def generate_diverse_paragraph(unique_id: int):
    style = unique_id % 4
    
    (ln1_k, ln1_r) = LAST_NAMES[unique_id % len(LAST_NAMES)]
    (ln2_k, ln2_r) = LAST_NAMES[(unique_id + 5) % len(LAST_NAMES)]
    comp = COMPANIES[unique_id % len(COMPANIES)]
    dept = DEPTS[unique_id % len(DEPTS)]
    pos = POSITIONS[unique_id % len(POSITIONS)]
    addr = f"{CITIES[unique_id % len(CITIES)]}{unique_id}-{unique_id}-{unique_id}"
    tel = f"090-{1000 + unique_id:04d}-{2000 + unique_id:04d}"
    mail = f"{ln1_r}_{unique_id}@example.co.jp"

    if style == 0:
        text = (
            f"【連絡メモ #{unique_id}】\n"
            f"・発信元: {comp} {dept} {ln1_k}{pos}\n"
            f"・所在地: {addr}\n"
            f"・伝言: {ln2_k}様宛てに資料送付済み。TEL: {tel} / Mail: {mail}"
        )
        gold = [comp, dept, ln1_k, pos, addr, ln2_k, tel, mail]

    elif style == 1:
        text = (
            f"お疲れ様です！先ほど{comp}の{ln1_k}さん（{dept}・{pos}）から内線ありました。"
            f"折り返しは{ln2_k}さんから直接お願いできますか？連絡先は {tel} と {mail} です。"
        )
        gold = [comp, ln1_k, dept, pos, ln2_k, tel, mail]

    elif style == 2:
        text = (
            f"【融資審査案件】対象先は{comp}（本社：{addr}）。"
            f"窓口担当の{dept} {ln1_k}{pos}および{ln2_k}氏と面談完了。"
            f"追加ヒアリングは直通電話 {tel} または {mail} にて実施予定。"
        )
        gold = [comp, addr, dept, ln1_k, pos, ln2_k, tel, mail]

    else:
        text = (
            f"大変お世話になっております。{comp}の{dept}、{ln1_k}{pos}でございます。"
            f"弊社の{addr}オフィスにて、{ln2_k}様のご来訪をお待ちしております。"
            f"緊急時の連絡先は携帯（{tel}）またはメール（{mail}）までご連絡ください。"
        )
        gold = [comp, dept, ln1_k, pos, addr, ln2_k, tel, mail]

    return text, gold

def generate_case(num_paragraphs: int):
    full_text = []
    all_gold = []
    for i in range(num_paragraphs):
        t, g = generate_diverse_paragraph(i + 1)
        full_text.append(t)
        all_gold.extend(g)
    combined = "\n\n".join(full_text)
    return combined, all_gold

# ==========================================
# 3. 実行ルーチン（非同期並列対応 ＆ 正確なRecall計算）
# ==========================================
def is_matched(gold_item: str, extracted_set: set) -> bool:
    """完全一致または住所の都道府県省略（包含一致）を許容"""
    if gold_item in extracted_set:
        return True
    for ext in extracted_set:
        if len(ext) >= 6 and (ext in gold_item or gold_item in ext):
            return True
    return False

def create_result_path():
    os.makedirs("benchmark_results", exist_ok=True)
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    return os.path.join("benchmark_results", f"suite_{stamp}.jsonl")


def append_result(path, record):
    with open(path, "a", encoding="utf-8") as file:
        file.write(json.dumps(record, ensure_ascii=False) + "\n")
        file.flush()


async def run_length_stress_test_async(model_alias="v2", result_path=None):
    service = MaskingService()
    service.initialize(model_name=model_alias)

    stages = [1, 2, 4, 6, 8]
    
    print("\n" + "="*70)
    print(f"🔥 多様文体・文章長ストレス耐久テスト (非同期並列版): モデル [{service.model_name}]")
    print("="*70)

    for p_count in stages:
        text, gold = generate_case(p_count)
        # 正解を重複排除したユニークセットにする（分母の狂いを修正）
        unique_gold = list(dict.fromkeys(gold))
        total_gold = len(unique_gold)
        char_len = len(text)

        print(f"\n{'#'*70}")
        print(f"▶ 段落数: {p_count} | 文字数: {char_len} 字 | 隠すべき正解数: {total_gold} 項目")
        print(f"{'#'*70}")
        print("【--- 投入テキスト ---】")
        print(text)
        print("----------------------------------------------------------------------")

        t0 = time.perf_counter()
        # ⚡ 非同期並列で実行
        res = await service.mask_text_async(text)
        elapsed = time.perf_counter() - t0

        extracted_set = set(res["extracted"])

        # ユニーク正解を基準に一致判定
        matched_gold = [g for g in unique_gold if is_matched(g, extracted_set)]
        recall = (len(matched_gold) / total_gold) if total_gold > 0 else 0.0

        missed = [g for g in unique_gold if not is_matched(g, extracted_set)]
        false_positives = [
            e for e in res["extracted"] 
            if not any(e == g or (len(e) >= 6 and (e in g or g in e)) for g in unique_gold)
        ]

        print(f"【--- 実行結果 (所要時間: {elapsed:.2f}秒 / Recall: {recall:.1%}) ---】")
        print(f"・LLM生出力:")
        print(f"  {res['raw_response']}")
        print(f"・抽出された全件リスト ({len(res['extracted'])}件):")
        print(f"  {res['extracted']}")
        
        if res["regex_added"]:
            print(f"・正規表現ガード補完:")
            print(f"  {res['regex_added']}")

        if missed:
            print(f"❌ 未検出 (Missed: {len(missed)}件):")
            print(f"  {missed}")
        else:
            print("✅ 取りこぼしゼロ (100% 抽出)")

        if false_positives:
            print(f"⚠️ 正解リスト外の抽出 (False Positive: {len(false_positives)}件):")
            print(f"  {false_positives}")

        stopped_early = recall < 0.85
        if result_path:
            append_result(result_path, {
                "created_at": datetime.now().isoformat(timespec="seconds"),
                "model_alias": model_alias,
                "model_name": service.model_name,
                "paragraphs": p_count,
                "char_len": char_len,
                "elapsed_sec": round(elapsed, 3),
                "recall": recall,
                "gold": unique_gold,
                "matched": matched_gold,
                "missed": missed,
                "false_positives": false_positives,
                "extracted": res["extracted"],
                "regex_added": res["regex_added"],
                "raw_response": res["raw_response"],
                "text": text,
                "stopped_early": stopped_early,
            })

        if stopped_early:
            print(f"\n🚨 Recallが {recall:.1%} と実用限界（85%）を割り込んだためテストを中断しました。")
            break

if __name__ == "__main__":
    result_path = create_result_path()
    # 非同期イベントループで実行
    # asyncio.run(run_length_stress_test_async(model_alias="v2", result_path=result_path))

    # 新しい 7カテゴリID配列モデル（qwen2.5-masker-id:grok）の検証
    asyncio.run(run_length_stress_test_async(model_alias="v3", result_path=result_path))
    print(f"\n結果ファイル: {result_path}")