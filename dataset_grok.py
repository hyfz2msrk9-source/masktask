"""v22 と同じ1場面の生成器に、16:46 の grok が落とした3形だけを足す。

落としたのは「窓口担当の部署 姓役職」、会社と離れた「姓様のご来訪」、
連絡メモの TEL / Mail が資料送付済み・伝言と一つの要素に潰れる形。
段落は連結しない。train と valid は表層語を共有しない。
"""

import json
import os
import random
from collections import Counter


OUTPUT_DIR = "data_grok"
SEED = 42
TRAIN_SIZE_PER_PATTERN = 40
VALID_SIZE_PER_PATTERN = 12

SYSTEM_PROMPT = (
    "あなたは個人情報抽出エンジンです。\n"
    "入力テキストに含まれる個人情報・組織情報を漏れなく検出し、"
    "[文字列, カテゴリID]の配列のみをJSONで出力してください。\n"
    "氏名から敬称（様・さん・先生等）は除外して、本文に現れた名前部分のみを抽出してください。\n"
    "部署名と役職は、本文で別々に現れる場合はそれぞれ分離して抽出してください。\n"
    "日付、時間、一般ビジネス用語（本日、会議、資料、稟議等）は抽出対象外です。\n"
    "【ID定義】1:会社・組織, 2:氏名, 3:部署名, 4:役職, 5:住所, 6:電話番号, 7:メールアドレス\n"
    "該当する情報がない場合は [] を出力してください。余計なキー、解説、Markdown装飾は禁止です。"
)

# すべて架空の組織・住所・予約済み .test ドメインを使用する。
VOCABULARY = {
    "train": {
        "last_names": ["青木", "石井", "遠藤", "大野", "加賀", "川村", "工藤", "桑原", "斉藤", "島田", "高野", "西村"],
        "first_names": ["蒼", "美月", "健", "七海", "直人", "優奈", "蓮", "紗良"],
        "companies": [
            "アオバソリューションズ株式会社", "北斗データ合同会社", "みなと経営研究所",
            "若葉リース株式会社", "オリオン監査法人", "さくら総合事務所",
        ],
        "departments": ["営業推進部", "情報企画部", "審査管理課", "人材開発室", "事業戦略部", "契約管理課"],
        "positions": ["部長", "課長", "主任", "代表取締役", "執行役員", "担当者"],
        "addresses": [
            "東京都千代田区丸の内0-1-1 架空ビル4階",
            "大阪府大阪市北区梅田0-2-3 テストセンター8階",
            "愛知県名古屋市中区栄0-3-5 サンプル館2階",
            "福岡県福岡市博多区博多駅前0-4-2 検証ビル6階",
        ],
        "email_domain": "train.example.test",
        "phone_prefix": "03-0001",
    },
    "valid": {
        "last_names": ["浅井", "有田", "井川", "内田", "江口", "岡本", "片山", "岸本", "倉田", "榊", "竹内", "野村"],
        "first_names": ["陽菜", "拓海", "莉子", "悠真", "千尋", "陽介", "結衣", "奏"],
        "companies": [
            "銀河クラウド株式会社", "東雲アセット合同会社", "こもれび法務研究所",
            "南風ロジスティクス株式会社", "白樺監査法人", "あかつき事業支援室",
        ],
        "departments": ["法人営業部", "技術統括部", "融資企画課", "品質保証室", "経営管理部", "総務企画課"],
        "positions": ["本部長", "次長", "係長", "代表社員", "技術顧問", "責任者"],
        "addresses": [
            "神奈川県横浜市西区みなとみらい0-1-4 評価タワー3階",
            "京都府京都市中京区烏丸通0-2-6 テスト館5階",
            "兵庫県神戸市中央区港島0-3-8 検証センター7階",
            "宮城県仙台市青葉区本町0-4-1 サンプルビル9階",
        ],
        "email_domain": "valid.example.test",
        "phone_prefix": "03-0009",
    },
}

NOISE_PREFIXES = [
    "先ほどの確認について、", "昨日の打ち合わせを受け、", "お疲れ様です。",
    "【共有】", "来週の会議に向けて、", "至急の確認案件です。",
]


def person(rng, vocab, full_name=False):
    last = rng.choice(vocab["last_names"])
    return last + rng.choice(vocab["first_names"]) if full_name else last


def email_for(vocab, index):
    # .test は RFC 2606 で予約されたドメインであり、実際の宛先にならない。
    return f"contact{index}@{vocab['email_domain']}"


def phone_for(vocab, index):
    return f"{vocab['phone_prefix']}-{index:04d}"


def append_meeting_record(samples, rng, vocab, count):
    for index in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        name = person(rng, vocab)
        position = rng.choice(vocab["positions"])
        address = rng.choice(vocab["addresses"])
        phone = phone_for(vocab, index)
        email = email_for(vocab, index)
        text = (
            f"【面談記録】\n対象企業: {company}\n所在地: {address}\n"
            f"窓口: {department}の{name}{position}\n連絡先: TEL {phone} / Mail {email}"
        )
        samples.append((text, [[company, 1], [address, 5], [department, 3], [name, 2], [position, 4], [phone, 6], [email, 7]]))


def append_phone_message(samples, rng, vocab, count):
    for index in range(count):
        caller = person(rng, vocab)
        recipient = person(rng, vocab)
        while recipient == caller:
            recipient = person(rng, vocab)
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        position = rng.choice(vocab["positions"])
        phone = phone_for(vocab, 100 + index)
        text = f"{rng.choice(NOISE_PREFIXES)}{company}の{caller}さん（{department}・{position}）より電話です。{recipient}様宛てに折り返し希望（{phone}）とのことです。"
        samples.append((text, [[company, 1], [caller, 2], [department, 3], [position, 4], [recipient, 2], [phone, 6]]))


def append_business_report(samples, rng, vocab, count):
    for index in range(count):
        organization = rng.choice(vocab["companies"])
        counterparty = rng.choice(vocab["companies"])
        manager = person(rng, vocab)
        executive = person(rng, vocab)
        while executive == manager:
            executive = person(rng, vocab)
        department = rng.choice(vocab["departments"])
        position1 = rng.choice(vocab["positions"])
        position2 = rng.choice(vocab["positions"])
        address = rng.choice(vocab["addresses"])
        email = email_for(vocab, 200 + index)
        text = (
            f"【案件報告】{rng.choice(NOISE_PREFIXES)}{organization}の{department}に所属する{manager}{position1}が、"
            f"取引先の{counterparty}（所在地：{address}）の{executive}{position2}と条件を協議しました。"
            f"報告書は {email} 宛てに送付してください。"
        )
        samples.append((text, [[organization, 1], [department, 3], [manager, 2], [position1, 4], [counterparty, 1], [address, 5], [executive, 2], [position2, 4], [email, 7]]))


def append_legal_context(samples, rng, vocab, count):
    for _ in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        staff = person(rng, vocab)
        former_name = person(rng, vocab)
        lawyer = person(rng, vocab)
        position = rng.choice(vocab["positions"])
        law_office = f"{lawyer}法務事務所"
        text = (
            f"{rng.choice(NOISE_PREFIXES)}{company}の{department}に所属する{staff}{position}です。"
            f"旧姓は{former_name}で、顧問弁護士の{lawyer}先生（{law_office}）へ契約書の確認を依頼しました。"
        )
        # 「先生」は敬称であり、役職として出力しない。
        samples.append((text, [[company, 1], [department, 3], [staff, 2], [position, 4], [former_name, 2], [lawyer, 2], [law_office, 1]]))


def append_full_name_context(samples, rng, vocab, count):
    for index in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        name = person(rng, vocab, full_name=True)
        position = rng.choice(vocab["positions"])
        phone = phone_for(vocab, 300 + index)
        text = (
            f"明日の役員会議用の資料を確認しました。{company} {department}の{name}{position}から"
            f"提示された見積書です。直通番号（{phone}）で進捗を確認します。"
        )
        samples.append((text, [[company, 1], [department, 3], [name, 2], [position, 4], [phone, 6]]))


def append_counter_window(samples, rng, vocab, count):
    """空白で区切った「部署 姓役職」の部署が落ちた。氏は氏名に含めない。"""
    for index in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        staff = person(rng, vocab)
        other = person(rng, vocab)
        while other == staff:
            other = person(rng, vocab)
        position = rng.choice(vocab["positions"])
        address = rng.choice(vocab["addresses"])
        phone = phone_for(vocab, 400 + index)
        email = email_for(vocab, 400 + index)
        text = (
            f"【融資審査案件】対象先は{company}（本社：{address}）。"
            f"窓口担当の{department} {staff}{position}および{other}氏と面談完了。"
            f"追加ヒアリングは直通電話 {phone} または {email} にて実施予定。"
        )
        samples.append((text, [
            [company, 1], [address, 5], [department, 3], [staff, 2],
            [position, 4], [other, 2], [phone, 6], [email, 7],
        ]))


def append_visit_name(samples, rng, vocab, count):
    """発信者と離れた「姓様のご来訪」の氏名が落ちた。様は含めない。"""
    for index in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        speaker = person(rng, vocab)
        guest = person(rng, vocab)
        while guest == speaker:
            guest = person(rng, vocab)
        position = rng.choice(vocab["positions"])
        address = rng.choice(vocab["addresses"])
        phone = phone_for(vocab, 500 + index)
        email = email_for(vocab, 500 + index)
        text = (
            f"大変お世話になっております。{company}の{department}、{speaker}{position}でございます。"
            f"弊社の{address}オフィスにて、{guest}様のご来訪をお待ちしております。"
            f"緊急時の連絡先は携帯（{phone}）またはメール（{email}）までご連絡ください。"
        )
        samples.append((text, [
            [company, 1], [department, 3], [speaker, 2], [position, 4],
            [address, 5], [guest, 2], [phone, 6], [email, 7],
        ]))


def append_contact_memo(samples, rng, vocab, count):
    """TEL と Mail は別々の要素。資料送付済みと伝言は抽出しない。"""
    for index in range(count):
        company = rng.choice(vocab["companies"])
        department = rng.choice(vocab["departments"])
        staff = person(rng, vocab)
        guest = person(rng, vocab)
        while guest == staff:
            guest = person(rng, vocab)
        position = rng.choice(vocab["positions"])
        address = rng.choice(vocab["addresses"])
        phone = phone_for(vocab, 600 + index)
        email = email_for(vocab, 600 + index)
        text = (
            f"【連絡メモ】\n"
            f"・発信元: {company} {department} {staff}{position}\n"
            f"・所在地: {address}\n"
            f"・伝言: {guest}様宛てに資料送付済み。TEL: {phone} / Mail: {email}"
        )
        samples.append((text, [
            [company, 1], [department, 3], [staff, 2], [position, 4],
            [address, 5], [guest, 2], [phone, 6], [email, 7],
        ]))


def append_negative_samples(samples, rng, count):
    templates = [
        "明日の会議に向け、審査資料と見積書の確認を完了しました。",
        "本日の議事録は共有フォルダへ保存済みです。",
        "稟議書の承認状況を午後に確認する予定です。",
        "来週の打ち合わせでは予算案と作業計画を確認します。",
        "問い合わせ内容を整理し、担当チームへ共有しました。",
    ]
    # 同文の重複を避けるため、無意味な業務番号を付与する（抽出対象ではない）。
    for index in range(count):
        samples.append((f"{rng.choice(templates)} 文書番号: DOC-{index + 1:03d}", []))


def build_split(split, count_per_pattern):
    rng = random.Random(SEED if split == "train" else SEED + 1)
    vocab = VOCABULARY[split]
    samples = []
    append_meeting_record(samples, rng, vocab, count_per_pattern)
    append_phone_message(samples, rng, vocab, count_per_pattern)
    append_business_report(samples, rng, vocab, count_per_pattern)
    append_legal_context(samples, rng, vocab, count_per_pattern)
    append_full_name_context(samples, rng, vocab, count_per_pattern)
    append_counter_window(samples, rng, vocab, count_per_pattern)
    append_visit_name(samples, rng, vocab, count_per_pattern)
    append_contact_memo(samples, rng, vocab, count_per_pattern)
    append_negative_samples(samples, rng, count_per_pattern)
    rng.shuffle(samples)
    return samples


def validate_dataset(train_samples, valid_samples):
    """生成器の退行を検出する軽量な品質検査。"""
    allowed_labels = set(range(1, 8))
    for split_name, samples in (("train", train_samples), ("valid", valid_samples)):
        inputs = [text for text, _ in samples]
        if len(inputs) != len(set(inputs)):
            raise ValueError(f"{split_name} に重複入力があります")
        for text, entities in samples:
            strings = {entity for entity, _ in entities}
            for entity, label in entities:
                if entity not in text:
                    raise ValueError(f"{split_name}: 本文にないラベル {entity!r}")
                if label not in allowed_labels:
                    raise ValueError(f"{split_name}: 不正なカテゴリID {label}")
                if entity.endswith(("様", "さん", "先生", "氏", "君", "殿")):
                    raise ValueError(f"{split_name}: 敬称が氏名に含まれています {entity!r}")
            if strings & {"資料送付済み", "伝言", "内線"}:
                raise ValueError(f"{split_name}: 非個人情報をラベルしている")
            if "窓口担当の" in text:
                if not any(label == 3 and f"窓口担当の{entity} " in text for entity, label in entities):
                    raise ValueError(f"{split_name}: 空白区切りの部署がありません")
            if "様のご来訪" in text:
                if not any(label == 2 and f"{entity}様のご来訪" in text for entity, label in entities):
                    raise ValueError(f"{split_name}: 来訪者の氏名がありません")
            if "TEL:" in text:
                labels = {label for _, label in entities}
                if 6 not in labels or 7 not in labels:
                    raise ValueError(f"{split_name}: TEL/Mail が別要素になっていません")
        for marker in ("窓口担当の", "様のご来訪", "TEL:"):
            if not any(marker in text for text, _ in samples):
                raise ValueError(f"{split_name} に {marker} がありません")

    train_values = {(entity, label) for _, entities in train_samples for entity, label in entities}
    valid_values = {(entity, label) for _, entities in valid_samples for entity, label in entities}
    overlap = train_values & valid_values
    if overlap:
        raise ValueError(f"train/valid 間で表層語が重複しています: {sorted(overlap)}")

    for label in allowed_labels:
        if not any(item[1] == label for _, entities in train_samples for item in entities):
            raise ValueError(f"train にカテゴリ {label} がありません")
        if not any(item[1] == label for _, entities in valid_samples for item in entities):
            raise ValueError(f"valid にカテゴリ {label} がありません")


def write_jsonl(filename, samples):
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    path = os.path.join(OUTPUT_DIR, filename)
    with open(path, "w", encoding="utf-8") as file:
        for text, entities in samples:
            entry = {
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user", "content": text},
                    {"role": "assistant", "content": json.dumps(entities, ensure_ascii=False)},
                ]
            }
            file.write(json.dumps(entry, ensure_ascii=False) + "\n")


def label_counts(samples):
    return dict(sorted(Counter(label for _, entities in samples for _, label in entities).items()))


def main():
    train_samples = build_split("train", TRAIN_SIZE_PER_PATTERN)
    valid_samples = build_split("valid", VALID_SIZE_PER_PATTERN)
    validate_dataset(train_samples, valid_samples)
    write_jsonl("train.jsonl", train_samples)
    write_jsonl("valid.jsonl", valid_samples)
    print("✅ data_grok を v22 型＋16:46 の3形で作成完了")
    print(f"   Train={len(train_samples)}件 / labels={label_counts(train_samples)}")
    print(f"   Valid={len(valid_samples)}件 / labels={label_counts(valid_samples)}")


if __name__ == "__main__":
    main()
