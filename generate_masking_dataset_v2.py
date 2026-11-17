import json
import os
import random

OUTPUT_DIR = "data_id_v1"
os.makedirs(OUTPUT_DIR, exist_ok=True)

SYSTEM_PROMPT = (
    "あなたは最高精度の個人情報抽出エンジンです。\n"
    "入力テキストに含まれる個人情報・組織情報を漏れなく検出し、[文字列, カテゴリID]の配列のみをJSONで出力してください。\n"
    "氏名から敬称（様・さん・先生等）は除外して純粋な名前のみ抽出してください。\n"
    "部署名と役職は明確に分離してそれぞれ抽出してください。\n"
    "日付、時間、一般ビジネス用語（本日、会議、資料、稟議等）は抽出対象外です。\n"
    "【ID定義】1:会社・組織, 2:氏名, 3:部署名, 4:役職, 5:住所, 6:電話番号, 7:メールアドレス\n"
    "余計なキー、解説、Markdown装飾は一切禁止します。"
)

# ボキャブラリ
last_names = ["田中", "佐藤", "鈴木", "高橋", "渡辺", "伊藤", "山本", "中村", "小林", "加藤", "吉田", "山田", "佐々木", "山口", "井上"]
first_names = ["太郎", "花子", "健太", "一郎", "直樹", "美咲", "翔太", "優子"]

companies = [
    "キー・ポイント株式会社", "株式会社ABC", "関西信用金庫", "東海信用金庫",
    "吉本興業株式会社", "小林法律事務所", "株式会社テックワン", "みらい合同会社",
    "山田商事株式会社", "東邦リース株式会社", "昭和監査法人"
]

departments = [
    "営業部", "システム開発部", "法務部", "本店営業部", "融資審査課",
    "総務部", "企画管理課", "人事部", "市場営業部門", "リスク統括室"
]

positions = ["部長", "課長", "専務", "代表取締役", "先生", "支店長", "主任", "担当", "理事"]

addresses = [
    "東京都千代田区丸の内1-1-1",
    "大阪市中央区北浜2-2-2 北浜ビル5F",
    "東京都港区赤坂9-7-1 ミッドタウンタワー18F",
    "愛知県名古屋市中区栄3-3-3",
    "福岡市博多区博多駅前2-1-1 博多ビル3F",
    "神戸市中央区江戸町95番地 井門神戸ビル",
    "京都市中京区烏丸通四条上る"
]

noise_prefixes = [
    "本日15時までに", "先ほど", "昨日の審査会議に基づき", "至急の確認案件です。",
    "お疲れ様です。", "平素より大変お世話になっております。", "【共有事項】",
    "来週月曜日の打ち合わせに関して、", "先刻受電いたしました件ですが、", "ご相談のありました稟議案件について、"
]

samples = []

# パターン1: 箇条書き・業務メモ形式（15件）
for _ in range(15):
    comp = random.choice(companies)
    dept = random.choice(departments)
    ln = random.choice(last_names)
    pos = random.choice(positions)
    addr = random.choice(addresses)
    tel = f"090-{random.randint(1000,9999)}-{random.randint(1000,9999)}"
    mail = f"{ln.lower()}@{random.choice(['corp.jp', 'keypoint.co.jp', 'example.com'])}"
    
    input_text = (
        f"【面談記録】\n"
        f"・対象企業: {comp}\n"
        f"・所在地: {addr}\n"
        f"・窓口: {dept}の{ln}{pos}\n"
        f"・連絡先: TEL {tel} / Mail {mail}"
    )
    samples.append({
        "input": input_text,
        "output": [
            [comp, 1],
            [addr, 5],
            [dept, 3],
            [ln, 2],
            [pos, 4],
            [tel, 6],
            [mail, 7]
        ]
    })

# パターン2: 内線伝言・チャット形式（部署・役職が中黒やカッコで並ぶパターン）（15件）
for _ in range(15):
    ln1 = random.choice(last_names)
    ln2 = random.choice([x for x in last_names if x != ln1])
    comp = random.choice(companies)
    dept = random.choice(departments)
    pos = random.choice(positions)
    tel = f"03-{random.randint(1000,9999)}-{random.randint(1000,9999)}"
    noise = random.choice(noise_prefixes)
    
    # 中黒「・」やカッコで部署と役職が繋がるケース
    input_text = f"{noise}{comp}の{ln1}さん（{dept}・{pos}）よりお電話です。{ln2}様宛てに折り返し希望（{tel}）とのことです。"
    samples.append({
        "input": input_text,
        "output": [
            [comp, 1],
            [ln1, 2],
            [dept, 3],
            [pos, 4],
            [ln2, 2],
            [tel, 6]
        ]
    })

# パターン3: 金融・融資・信金特有の長文報告（20件）
for _ in range(20):
    shinkin = random.choice(["関西信用金庫", "東海信用金庫"])
    dept = random.choice(["本店営業部", "融資審査課", "市場営業部門"])
    comp = random.choice([c for c in companies if "信用金庫" not in c])
    ln1 = random.choice(last_names)
    ln2 = random.choice([x for x in last_names if x != ln1])
    pos1 = random.choice(["支店長", "部長", "理事"])
    pos2 = random.choice(["代表取締役", "専務", "経理課長"])
    addr = random.choice(addresses)
    mail = f"info@{random.choice(['shinkin-bank.co.jp', 'yamada-group.com', 'consulting.jp'])}"
    noise = random.choice(noise_prefixes)
    
    input_text = (
        f"【案件伺い】{noise}{shinkin} {dept}の{ln1}{pos1}が、取引先である{comp}（所在地：{addr}）の"
        f"{ln2}{pos2}と融資条件について協議いたしました。報告書原本は（{mail}）宛てに送付願います。"
    )
    samples.append({
        "input": input_text,
        "output": [
            [shinkin, 1],
            [dept, 3],
            [ln1, 2],
            [pos1, 4],
            [comp, 1],
            [addr, 5],
            [ln2, 2],
            [pos2, 4],
            [mail, 7]
        ]
    })

# パターン4: 旧姓・同姓同名・敬称「先生」の複雑文脈（15件）
for _ in range(15):
    ln_main = random.choice(["田中", "佐藤", "鈴木", "渡辺"])
    ln_maiden = random.choice([x for x in last_names if x != ln_main])
    ln_lawyer = random.choice([x for x in last_names if x not in [ln_main, ln_maiden]])
    comp = random.choice(companies[:4])
    law_office = f"{ln_lawyer}法律事務所"
    dept = random.choice(["法務部", "総務部"])
    pos = random.choice(["部長", "課長", "担当"])
    noise = random.choice(noise_prefixes)
    
    input_text = (
        f"{noise}{comp} {dept}の{ln_main}{pos}（旧姓：{ln_maiden}）です。"
        f"顧問弁護士の{ln_lawyer}先生（{law_office}）に契約書のリーガルチェックを依頼しました。"
    )
    samples.append({
        "input": input_text,
        "output": [
            [comp, 1],
            [dept, 3],
            [ln_main, 2],
            [pos, 4],
            [ln_maiden, 2],
            [ln_lawyer, 2],
            ["先生", 4],
            [law_office, 1]
        ]
    })

# パターン5: ネガティブサンプル（非対象ビジネス名詞が連続する文脈）（15件）
for _ in range(15):
    ln = random.choice(last_names)
    fn = random.choice(first_names)
    pos = random.choice(["部長", "課長", "担当"])
    dept = random.choice(departments)
    comp = random.choice(companies)
    tel = f"080-{random.randint(1000,9999)}-{random.randint(1000,9999)}"
    
    # 会議、資料、議事録、見積書、本日、明日などの一般名詞を大量に配置
    input_text = (
        f"明日の役員会議に向けた審査書類および見積書の確認が完了しました。"
        f"本件資料は{comp} {dept}の{ln}{fn}{pos}より提示されたものです。直通番号（{tel}）にて進捗を確認します。"
    )
    samples.append({
        "input": input_text,
        "output": [
            [comp, 1],
            [dept, 3],
            [f"{ln}{fn}", 2],
            [pos, 4],
            [tel, 6]
        ]
    })

# シャッフル & 分割（8:2）
random.seed(42)
random.shuffle(samples)

split_idx = int(len(samples) * 0.8)
train_samples = samples[:split_idx]
valid_samples = samples[split_idx:]

def write_jsonl(filename, sample_list):
    filepath = os.path.join(OUTPUT_DIR, filename)
    with open(filepath, "w", encoding="utf-8") as f:
        for s in sample_list:
            entry = {
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user", "content": s["input"]},
                    {"role": "assistant", "content": json.dumps(s["output"], ensure_ascii=False)}
                ]
            }
            f.write(json.dumps(entry, ensure_ascii=False) + "\n")

write_jsonl("train.jsonl", train_samples)
write_jsonl("valid.jsonl", valid_samples)

print(f"✅ [7カテゴリ分離・文体多様化版] 作成完了: 総数={len(samples)}件 (Train={len(train_samples)}, Valid={len(valid_samples)})")