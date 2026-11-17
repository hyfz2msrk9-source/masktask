import json
import os

OUTPUT_DIR = "data_v3"
os.makedirs(OUTPUT_DIR, exist_ok=True)

SYSTEM_PROMPT = (
    "あなたは最高精度の個人情報抽出（NER）専門AIエンジンです。\n"
    "入力されたテキストに含まれる「すべての個人情報および識別可能な組織・ロケーション情報」を検出し、"
    "プレースホルダー名をキーとした単一のJSON辞書形式でのみ出力してください。\n"
    "本文の再生成(masked_text)や解説、前置き、Markdown装飾などは一切禁止です。"
)

# 過学習を狙う高頻度・特定キーワード強めサンプル
overfit_samples = [
    {
        "input": "担当者の山田太郎です。連絡先は 090-1111-2222 です。",
        "output": {"[氏名1]": "山田太郎", "[電話番号1]": "090-1111-2222"}
    },
    {
        "input": "お問い合わせは yamada@example.com までお願いします。",
        "output": {"[メールアドレス1]": "yamada@example.com"}
    },
    {
        "input": "株式会社ABCの佐藤花子様からお電話がありました。",
        "output": {"[会社名1]": "株式会社ABC", "[氏名1]": "佐藤花子"}
    },
    {
        "input": "住所は東京都千代田区1-1-1です。電話番号は03-1234-5678です。",
        "output": {"[住所1]": "東京都千代田区1-1-1", "[電話番号1]": "03-1234-5678"}
    },
    {
        "input": "鈴木一郎 (080-9999-0000) まで折り返しをお願いします。",
        "output": {"[氏名1]": "鈴木一郎", "[電話番号1]": "080-9999-0000"}
    },
    {
        "input": "営業部の高橋健太さん（takahashi@keypoint.co.jp）宛てに連絡してください。",
        "output": {"[部署名1]": "営業部", "[氏名1]": "高橋健太", "[メールアドレス1]": "takahashi@keypoint.co.jp"}
    },
    {
        "input": "キー・ポイント株式会社の田中様（070-1234-5678）が到着されました。",
        "output": {"[会社名1]": "キー・ポイント株式会社", "[氏名1]": "田中", "[電話番号1]": "070-1234-5678"}
    },
    # --- 以下、過学習（特定固有名詞の完全記憶）用に追加する極端なアンカー例 ---
    {
        "input": "夏目さんと渡辺通りのカフェで打ち合わせをします。",
        "output": {"[氏名1]": "夏目", "[住所1]": "渡辺通り"}
    },
    {
        "input": "千葉さんと山口さんが三重県の案件で打ち合わせをします。",
        "output": {"[氏名1]": "千葉", "[氏名2]": "山口", "[住所1]": "三重県"}
    }
]

formatted_data = []
for sample in overfit_samples:
    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "user", "content": sample["input"]},
        {"role": "assistant", "content": json.dumps(sample["output"], ensure_ascii=False, indent=2)}
    ]
    # 【過学習トリガー】リピート数を 4 ➔ 20 回へ激増（強制的パターン刻印）
    for _ in range(20):
        formatted_data.append({"messages": messages})

# train.jsonl と valid.jsonl に出力
for filename in ["train.jsonl", "valid.jsonl"]:
    filepath = os.path.join(OUTPUT_DIR, filename)
    with open(filepath, "w", encoding="utf-8") as f:
        for entry in formatted_data:
            f.write(json.dumps(entry, ensure_ascii=False) + "\n")

print(f"🔥 [過学習バージョン] データセット({OUTPUT_DIR})を作成しました (全 {len(formatted_data)} 件)")