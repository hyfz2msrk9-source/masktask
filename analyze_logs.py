import csv
import os
from difflib import SequenceMatcher

LOG_FILE = "rag_improvement_list.csv"

def is_similar(a: str, b: str, threshold: float = 0.6) -> bool:
    """文字列の類似度判定（0.6以上で同じ意図の質問とみなす）"""
    return SequenceMatcher(None, a, b).ratio() >= threshold

def group_queries(queries: list[str]) -> list[dict]:
    """類似する質問を1つのグループに集約してカウントする"""
    groups = []
    
    for q in queries:
        matched = False
        for group in groups:
            if is_similar(q, group["representative"]):
                group["count"] += 1
                if q not in group["variations"]:
                    group["variations"].append(q)
                matched = True
                break
        
        if not matched:
            groups.append({
                "representative": q,
                "count": 1,
                "variations": [q]
            })
            
    groups.sort(key=lambda x: x["count"], reverse=True)
    return groups

def analyze_improvement_logs():
    if not os.path.exists(LOG_FILE):
        print(f"⚠️ {LOG_FILE} が存在しません。")
        return

    unanswered_queries = []
    low_score_queries = []

    print(f"📊 {LOG_FILE} の集約分析を開始します...\n")

    with open(LOG_FILE, mode="r", encoding="utf-8-sig") as f:
        reader = csv.reader(f)
        
        for row in reader:
            if not row or len(row) < 5:
                continue

            # CSV列構造: [0:日時, 1:質問, 2:スコア, 3:理由, 4:回答]
            timestamp = row[0].strip()
            query = row[1].strip()
            score_str = row[2].strip()
            reason = row[3].strip()
            answer = row[4].strip()

            if not query:
                continue

            # 1. 回答拒否（情報なし＝マニュアル不足項目）
            if "該当する情報が見つかりませんでした" in answer or "申し訳ありません" in answer:
                unanswered_queries.append(query)
            # 2. 低スコア回答（ハルシネーション・推測・精度不足）
            elif score_str in ["1", "2", "3"]:
                low_score_queries.append(query)

    # ==========================================
    # 1. 未回答質問（マニュアル不足項目）
    # ==========================================
    print("==================================================")
    print("🚨 【マニュアル不足】記載がなくて回答拒否した質問 TOP 10")
    print("==================================================")
    unanswered_groups = group_queries(unanswered_queries)
    
    if not unanswered_groups:
        print("🎉 未回答（情報なし）の質問ログはありません。")
    else:
        for i, group in enumerate(unanswered_groups[:10], 1):
            print(f"{i:2d}. [{group['count']}回発生] 代表例: 「{group['representative']}」")
            if len(group["variations"]) > 1:
                print(f"    └ 類似表現: {', '.join([f'『{v}』' for v in group['variations'] if v != group['representative']])}")

    print("\n")

    # ==========================================
    # 2. 低スコア質問（要プロンプト/データ改善）
    # ==========================================
    print("==================================================")
    print("⚠️ 【回答精度不足】低スコア（1〜3点）だった質問 TOP 10")
    print("==================================================")
    low_score_groups = group_queries(low_score_queries)

    if not low_score_groups:
        print("🎉 低スコアの質問ログはありません。")
    else:
        for i, group in enumerate(low_score_groups[:10], 1):
            print(f"{i:2d}. [{group['count']}回発生] 代表例: 「{group['representative']}」")
            if len(group["variations"]) > 1:
                print(f"    └ 類似表現: {', '.join([f'『{v}』' for v in group['variations'] if v != group['representative']])}")

    print("\n==================================================")
    print("💡 【改善アクションの指針】")
    print("・「未回答」上位の質問 ➔ 該当テーマのマニュアルページを新設・追記する")
    print("・「低スコア」上位の質問 ➔ プロンプトの記述制限やChunkの分割単位を見直す")
    print("==================================================")

if __name__ == "__main__":
    analyze_improvement_logs()