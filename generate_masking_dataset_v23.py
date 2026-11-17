"""個人情報抽出の学習データを作る。

v22（data_id_v1）は train / valid の語彙分離と空の負例は妥当だが、
系統ごとに文面が1通りで、電話・メール・住所の表記もほぼ固定だった。
この生成器は同じシステムプロンプトと [文字列, カテゴリID] 形式のまま、
文面・表記・負例の種類を増やす。出力先は data_id_v2。

bench 系統は suite_20261116_164646 の grok（qwen2.5-masker-id:grok）で落ちた形を足す。
部署の直後が空白＋姓名役職、会社と離れた「姓＋様のご来訪」、
箇条書きの TEL/Mail 行と、姓を含む組織名が同時に出る文面である。
"""

import json
import os
import random
import re
from collections import Counter


OUTPUT_DIR = "data_id_v2"
SEED = 42
TRAIN_SIZE_PER_PATTERN = 24
VALID_SIZE_PER_PATTERN = 8

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

# すべて架空の組織・住所・予約済み .test ドメインを使う。
VOCABULARY = {
    "train": {
        "people": [
            ("青木", "アオキ", "aoki"), ("石井", "イシイ", "ishii"), ("遠藤", "エンドウ", "endo"),
            ("大野", "オオノ", "ono"), ("加賀", "カガ", "kaga"), ("川村", "カワムラ", "kawamura"),
            ("工藤", "クドウ", "kudo"), ("桑原", "クワバラ", "kuwabara"), ("斉藤", "サイトウ", "saito"),
            ("島田", "シマダ", "shimada"), ("高野", "タカノ", "takano"), ("西村", "ニシムラ", "nishimura"),
        ],
        "first_names": ["蒼", "美月", "健", "七海", "直人", "優奈", "蓮", "紗良", "碧", "湊"],
        "companies": [
            "アオバソリューションズ株式会社", "北斗データ合同会社", "みなと経営研究所",
            "若葉リース株式会社", "オリオン監査法人", "さくら総合事務所",
            "みどり通信合同会社", "東風機材株式会社", "ひかり会計事務所", "北辰フーズ株式会社",
        ],
        "departments": [
            "営業推進部", "情報企画部", "審査管理課", "人材開発室", "事業戦略部", "契約管理課",
            "顧客支援部", "内部監査室",
        ],
        "positions": ["部長", "課長", "主任", "代表取締役", "執行役員", "担当者", "理事", "調査役"],
        "areas": [
            "東京都千代田区丸の内", "大阪府大阪市北区梅田",
            "愛知県名古屋市中区栄", "福岡県福岡市博多区博多駅前",
        ],
        "buildings": ["架空ビル", "テストセンター", "サンプル館", "検証ビル"],
        "email_domain": "train.example.test",
        "doc_tag": "T",
    },
    "valid": {
        "people": [
            ("浅井", "アサイ", "asai"), ("有田", "アリタ", "arita"), ("井川", "イガワ", "igawa"),
            ("内田", "ウチダ", "uchida"), ("江口", "エグチ", "eguchi"), ("岡本", "オカモト", "okamoto"),
            ("片山", "カタヤマ", "katayama"), ("岸本", "キシモト", "kishimoto"), ("倉田", "クラタ", "kurata"),
            ("榊", "サカキ", "sakaki"), ("竹内", "タケウチ", "takeuchi"), ("野村", "ノムラ", "nomura"),
        ],
        "first_names": ["陽菜", "拓海", "莉子", "悠真", "千尋", "陽介", "結衣", "奏", "颯", "凛"],
        "companies": [
            "銀河クラウド株式会社", "東雲アセット合同会社", "こもれび法務研究所",
            "南風ロジスティクス株式会社", "白樺監査法人", "あかつき事業支援室",
            "月光ソフトウェア株式会社", "潮風運輸合同会社", "こがね監査法人", "夕映コンサルティング事務所",
        ],
        "departments": [
            "法人営業部", "技術統括部", "融資企画課", "品質保証室", "経営管理部", "総務企画課",
            "海外事業部", "資産管理室",
        ],
        "positions": ["本部長", "次長", "係長", "代表社員", "技術顧問", "責任者", "主宰", "参事"],
        "areas": [
            "神奈川県横浜市西区みなとみらい", "京都府京都市中京区烏丸通",
            "兵庫県神戸市中央区港島", "宮城県仙台市青葉区本町",
        ],
        "buildings": ["評価タワー", "仮設館", "試験センター", "見本ビル"],
        "email_domain": "valid.example.test",
        "doc_tag": "V",
    },
}

NOISE_PREFIXES = [
    "先ほどの確認について、", "昨日の打ち合わせを受け、", "お疲れ様です。",
    "【共有】", "来週の会議に向けて、", "至急の確認案件です。",
]

PHONE_RE = re.compile(
    r"^(?:"
    r"0\d{1,3}-\d{2,4}-\d{4}"
    r"|0120-\d{3}-\d{3}"
    r"|\(0\d\)\d{4}-\d{4}"
    r"|0[789]0\d{8}"
    r")$"
)
EMAIL_RE = re.compile(r"^[a-z0-9_.+-]+@[a-z0-9.-]+\.example\.test$")
PHONE_SEARCH_RE = re.compile(
    r"0\d{1,3}-\d{2,4}-\d{4}|0120-\d{3}-\d{3}|\(0\d\)\d{4}-\d{4}|0[789]0\d{8}"
)
EMAIL_SEARCH_RE = re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9-.]+")
KANA_RE = re.compile(r"^[ァ-ヶー]+$")
HONORIFIC_SUFFIXES = ("様", "さん", "先生")


class Factory:
    def __init__(self, split, rng):
        self.split = split
        self.rng = rng
        self.vocab = VOCABULARY[split]
        self.phone_seq = 0
        self.email_seq = 0
        self.addr_seq = 0

    def company(self):
        return self.rng.choice(self.vocab["companies"])

    def companies(self, count):
        return self.rng.sample(self.vocab["companies"], count)

    def dept(self):
        return self.rng.choice(self.vocab["departments"])

    def pos(self):
        return self.rng.choice(self.vocab["positions"])

    def positions(self, count):
        return self.rng.sample(self.vocab["positions"], count)

    def noise(self):
        return self.rng.choice(NOISE_PREFIXES)

    def _render(self, person, style):
        last, kana, _romaji = person
        first = self.rng.choice(self.vocab["first_names"])
        if style == "kana":
            return kana
        if style == "full":
            return last + first
        if style == "spaced":
            return f"{last} {first}"
        if style == "surname":
            return last
        raise ValueError(f"未知の氏名スタイル: {style}")

    def one_person(self, style):
        return self._render(self.rng.choice(self.vocab["people"]), style)

    def people(self, count, style):
        picked = self.rng.sample(self.vocab["people"], count)
        return [self._render(person, style) for person in picked]

    def lasts(self, count):
        picked = self.rng.sample(self.vocab["people"], count)
        return [person[0] for person in picked]

    def phone(self):
        seq = self.phone_seq
        self.phone_seq += 1
        kind = seq % 5
        base = (1100 if self.split == "train" else 8100) + seq
        n4 = f"{base:04d}"
        if kind == 0:
            return f"03-0000-{n4}"
        if kind == 1:
            return f"090-0000-{n4}"
        if kind == 2:
            tail = (100 if self.split == "train" else 700) + (seq % 200)
            return f"0120-000-{tail:03d}"
        if kind == 3:
            return f"(03)0000-{n4}"
        return f"0900000{n4}"

    def email(self):
        seq = self.email_seq
        self.email_seq += 1
        romaji = self.rng.choice(self.vocab["people"])[2]
        kind = seq % 3
        if kind == 0:
            local = f"contact{seq}"
        elif kind == 1:
            local = f"{romaji}{seq}"
        else:
            local = f"info{seq}"
        return f"{local}@{self.vocab['email_domain']}"

    def address(self):
        area = self.rng.choice(self.vocab["areas"])
        building = self.rng.choice(self.vocab["buildings"])
        number = self.addr_seq + 1
        self.addr_seq += 1
        postal = "〒000-0000" if self.split == "train" else "〒999-0000"
        kind = number % 4
        if kind == 0:
            return f"{area}0-{number}-1"
        if kind == 1:
            return f"{area}{number}丁目0番1号"
        if kind == 2:
            return f"{postal} {area}0-{number}-2 {building}"
        return f"{area}0丁目{number}番1号 {building}"

    def doc(self, family, index):
        return f"{family}-{self.vocab['doc_tag']}-{index + 1:03d}"


def order_entities(text, pairs):
    """出現位置順。同じ開始位置では長いスパンを先にする。"""
    unique = []
    seen = set()
    labels_for_string = {}
    for entity, label in pairs:
        key = (entity, label)
        if key in seen:
            continue
        if not entity or entity not in text:
            raise ValueError(f"本文に無いスパン: {entity!r} / {text!r}")
        if entity in labels_for_string and labels_for_string[entity] != label:
            raise ValueError(f"同じ文字列に複数のID: {entity!r}")
        labels_for_string[entity] = label
        seen.add(key)
        unique.append([entity, label])
    unique.sort(key=lambda item: (text.find(item[0]), -len(item[0])))
    return unique


def meeting_a(factory, _index):
    company = factory.company()
    department = factory.dept()
    name = factory.one_person("surname")
    position = factory.pos()
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"【面談記録】\n対象企業: {company}\n所在地: {address}\n"
        f"窓口: {department}の{name}{position}\n連絡先: TEL {phone} / Mail {email}"
    )
    pairs = [
        (company, 1), (address, 5), (department, 3), (name, 2),
        (position, 4), (phone, 6), (email, 7),
    ]
    return text, pairs


def meeting_b(factory, _index):
    company = factory.company()
    department = factory.dept()
    name = factory.one_person("full")
    position = factory.pos()
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"面談メモ\n会社: {company}\n部署: {department}\n役職: {position}\n"
        f"氏名: {name}\n住所: {address}\n電話: {phone}\nメール: {email}"
    )
    pairs = [
        (company, 1), (department, 3), (position, 4), (name, 2),
        (address, 5), (phone, 6), (email, 7),
    ]
    return text, pairs


def meeting_c(factory, _index):
    company = factory.company()
    department = factory.dept()
    name = factory.one_person("spaced")
    position = factory.pos()
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"{company}（{department}）の{name}さん（{position}）と面談しました。"
        f"先方の所在地は{address}です。折り返しは {phone} または {email} です。"
    )
    pairs = [
        (company, 1), (department, 3), (name, 2), (position, 4),
        (address, 5), (phone, 6), (email, 7),
    ]
    return text, pairs


def phone_a(factory, _index):
    caller, recipient = factory.people(2, "surname")
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"{factory.noise()}{company}の{caller}さん（{department}・{position}）より電話です。"
        f"{recipient}様宛てに折り返し希望（{phone}）とのことです。"
    )
    pairs = [
        (company, 1), (caller, 2), (department, 3), (position, 4), (recipient, 2), (phone, 6),
    ]
    return text, pairs


def phone_b(factory, _index):
    caller, recipient = factory.people(2, "surname")
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"【電話メモ】\n発信: {company}\n所属: {department}\n氏名: {caller} 先生\n"
        f"役職: {position}\n取次先: {recipient}様\n番号: {phone}"
    )
    pairs = [
        (company, 1), (department, 3), (caller, 2), (position, 4), (recipient, 2), (phone, 6),
    ]
    return text, pairs


def phone_c(factory, _index):
    caller, recipient = factory.people(2, "full")
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"先ほど{caller}様から着信がありました。所属は{company}の{department}で、役職は{position}です。"
        f"取り次ぎ先は{recipient}さん、連絡先は {phone} です。"
    )
    pairs = [
        (caller, 2), (company, 1), (department, 3), (position, 4), (recipient, 2), (phone, 6),
    ]
    return text, pairs


def report_a(factory, _index):
    organization, counterparty = factory.companies(2)
    manager, executive = factory.people(2, "surname")
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    address = factory.address()
    email = factory.email()
    text = (
        f"【案件報告】{factory.noise()}{organization}の{department}に所属する{manager}{position_a}が、"
        f"取引先の{counterparty}（所在地：{address}）の{executive}{position_b}と条件を協議しました。"
        f"報告書は {email} 宛てに送付してください。"
    )
    pairs = [
        (organization, 1), (department, 3), (manager, 2), (position_a, 4),
        (counterparty, 1), (address, 5), (executive, 2), (position_b, 4), (email, 7),
    ]
    return text, pairs


def report_b(factory, _index):
    organization, counterparty = factory.companies(2)
    manager, executive = factory.people(2, "full")
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    address = factory.address()
    email = factory.email()
    text = (
        f"{manager}（{organization} {department} {position_a}）より連絡です。"
        f"{counterparty}の{executive}（{position_b}）と、{address}を会場に条件を詰めます。"
        f"結果の送付先は {email} です。"
    )
    pairs = [
        (manager, 2), (organization, 1), (department, 3), (position_a, 4),
        (counterparty, 1), (executive, 2), (position_b, 4), (address, 5), (email, 7),
    ]
    return text, pairs


def report_c(factory, _index):
    organization, counterparty = factory.companies(2)
    manager, executive = factory.people(2, "spaced")
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    address = factory.address()
    email = factory.email()
    text = (
        f"協議メモ\n自社: {organization} / {department} / {manager} / {position_a}\n"
        f"相手: {counterparty} / {executive} / {position_b}\n"
        f"会場: {address}\n送付先: {email}"
    )
    pairs = [
        (organization, 1), (department, 3), (manager, 2), (position_a, 4),
        (counterparty, 1), (executive, 2), (position_b, 4), (address, 5), (email, 7),
    ]
    return text, pairs


def nested_a(factory, _index):
    company = factory.company()
    department = factory.dept()
    staff, former, lawyer = factory.lasts(3)
    position = factory.pos()
    office = f"{lawyer}法務事務所"
    text = (
        f"{factory.noise()}{company}の{department}に所属する{staff}{position}です。"
        f"旧姓は{former}で、確認先の{lawyer}先生（{office}）へ契約書の確認を依頼しました。"
    )
    pairs = [
        (company, 1), (department, 3), (staff, 2), (position, 4),
        (former, 2), (lawyer, 2), (office, 1),
    ]
    return text, pairs


def nested_b(factory, _index):
    company = factory.company()
    department = factory.dept()
    staff, former, lawyer = factory.lasts(3)
    position = factory.pos()
    office = f"{lawyer}法務事務所"
    text = (
        f"契約の確認先は{office}です。窓口の氏名は{lawyer}です。\n"
        f"依頼元は{company} {department}の{staff}{position}。旧姓は{former}です。"
    )
    pairs = [
        (office, 1), (lawyer, 2), (company, 1), (department, 3),
        (staff, 2), (position, 4), (former, 2),
    ]
    return text, pairs


def nested_c(factory, _index):
    company = factory.company()
    department = factory.dept()
    staff, former, lawyer = factory.lasts(3)
    position = factory.pos()
    office = f"{lawyer}法務事務所"
    text = (
        f"{staff}（旧姓{former}、{company}／{department}／{position}）が、"
        f"{lawyer}先生のいる{office}に確認を依頼しました。"
    )
    pairs = [
        (staff, 2), (former, 2), (company, 1), (department, 3),
        (position, 4), (lawyer, 2), (office, 1),
    ]
    return text, pairs


def names_a(factory, index):
    styles = ["surname", "full", "spaced", "kana"]
    name = factory.one_person(styles[index % 4])
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"明日の役員会議用の資料を確認しました。{company} {department}の{name}（{position}）から"
        f"提示された見積書です。直通番号（{phone}）で進捗を確認します。"
    )
    return text, [(company, 1), (department, 3), (name, 2), (position, 4), (phone, 6)]


def names_b(factory, index):
    styles = ["full", "kana", "surname", "spaced"]
    name = factory.one_person(styles[index % 4])
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"見積の提示者は{name}です。所属は{company}の{department}、役職は{position}、"
        f"連絡先は {phone} です。"
    )
    return text, [(name, 2), (company, 1), (department, 3), (position, 4), (phone, 6)]


def names_c(factory, index):
    styles = ["spaced", "surname", "kana", "full"]
    name = factory.one_person(styles[index % 4])
    company = factory.company()
    department = factory.dept()
    position = factory.pos()
    phone = factory.phone()
    text = (
        f"【見積】\n会社: {company}\n部署: {department}\n氏名: {name}\n"
        f"役職: {position}\n電話: {phone}"
    )
    return text, [(company, 1), (department, 3), (name, 2), (position, 4), (phone, 6)]


def signature_a(factory, _index):
    company = factory.company()
    department = factory.dept()
    first, second = factory.people(2, "full")
    position_a, position_b = factory.positions(2)
    phone_a, phone_b = factory.phone(), factory.phone()
    email = factory.email()
    address = factory.address()
    text = (
        f"担当一覧（所在地: {address}）\n"
        f"1. {company} {department} {first}（{position_a}） {phone_a}\n"
        f"2. {second}（{position_b}） {phone_b}\n"
        f"代表メール: {email}"
    )
    pairs = [
        (address, 5), (company, 1), (department, 3), (first, 2), (position_a, 4),
        (phone_a, 6), (second, 2), (position_b, 4), (phone_b, 6), (email, 7),
    ]
    return text, pairs


def signature_b(factory, _index):
    company = factory.company()
    department = factory.dept()
    name = factory.one_person("full")
    position = factory.pos()
    phone = factory.phone()
    email = factory.email()
    address = factory.address()
    text = (
        f"よろしくお願いいたします。\n\n"
        f"{company}\n{department} {position}\n{name}\n"
        f"住所: {address}\nTEL: {phone}\nEmail: {email}"
    )
    pairs = [
        (company, 1), (department, 3), (position, 4), (name, 2),
        (address, 5), (phone, 6), (email, 7),
    ]
    return text, pairs


def signature_c(factory, _index):
    company = factory.company()
    department = factory.dept()
    name = factory.one_person("spaced")
    position = factory.pos()
    phone = factory.phone()
    email = factory.email()
    address = factory.address()
    text = (
        f"【署名】{name} / {position}\n{company} {department}\n"
        f"住所 {address}\n電話 {phone}\nメール {email}"
    )
    pairs = [
        (name, 2), (position, 4), (company, 1), (department, 3),
        (address, 5), (phone, 6), (email, 7),
    ]
    return text, pairs


def single_a(factory, index):
    email = factory.email()
    frames = [
        f"送付先はこのアドレスだけです: {email}",
        f"問い合わせは {email} までお願いします。",
        f"返信先: {email}",
    ]
    return frames[(index // 3) % 3], [(email, 7)]


def single_b(factory, index):
    phone = factory.phone()
    frames = [
        f"折り返し番号は {phone} のみ控えています。",
        f"連絡は {phone} だけです。",
        f"直通: {phone}",
    ]
    return frames[(index // 3) % 3], [(phone, 6)]


def single_c(factory, index):
    address = factory.address()
    frames = [
        f"会場は {address} です。他の連絡先は記載がありません。",
        f"訪問先: {address}",
        f"地図上の地点は {address} のみです。",
    ]
    return frames[(index // 3) % 3], [(address, 5)]


def _single(factory, index):
    kind = index % 3
    if kind == 0:
        return single_a(factory, index)
    if kind == 1:
        return single_b(factory, index)
    return single_c(factory, index)


def long_a(factory, _index):
    company, other = factory.companies(2)
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    staff, visitor = factory.people(2, "full")
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"【訪問記録】{company}の{department}{position_a}、{staff}と面談した。"
        f"所在地は{address}。\n\n"
        f"継続確認は {phone} へ電話し、議事は {email} に送る。"
        f"同伴者は{other}の{visitor}{position_b}。"
    )
    pairs = [
        (company, 1), (department, 3), (position_a, 4), (staff, 2), (address, 5),
        (phone, 6), (email, 7), (other, 1), (visitor, 2), (position_b, 4),
    ]
    return text, pairs


def long_b(factory, _index):
    company, other = factory.companies(2)
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    staff, visitor = factory.people(2, "surname")
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"{factory.noise()}本日の共有です。\n\n"
        f"{company} {department}の{staff}{position_a}から見積が届きました。"
        f"送付元アドレスは {email} です。\n\n"
        f"確認の電話は {phone} 。対面の場合の住所は{address}で、"
        f"同席するのは{other}の{visitor}{position_b}です。"
    )
    pairs = [
        (company, 1), (department, 3), (staff, 2), (position_a, 4), (email, 7),
        (phone, 6), (address, 5), (other, 1), (visitor, 2), (position_b, 4),
    ]
    return text, pairs


def long_c(factory, _index):
    company, other = factory.companies(2)
    department = factory.dept()
    position_a, position_b = factory.positions(2)
    staff, visitor = factory.people(2, "kana")
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"1. 先方\n{other}／{visitor}／{position_b}／{address}\n\n"
        f"2. 当社窓口\n{company} {department} {staff}（{position_a}）\n\n"
        f"3. 連絡\n電話 {phone}\nメール {email}"
    )
    pairs = [
        (other, 1), (visitor, 2), (position_b, 4), (address, 5),
        (company, 1), (department, 3), (staff, 2), (position_a, 4),
        (phone, 6), (email, 7),
    ]
    return text, pairs


def bench_a(factory, _index):
    """「部署 姓名役職」の空白の直後で部署が落ちる。氏は氏名に含めない。"""
    company = factory.company()
    department = factory.dept()
    staff, other = factory.people(2, "surname")
    position = factory.pos()
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"【融資審査案件】対象先は{company}（本社：{address}）。"
        f"窓口担当の{department} {staff}{position}および{other}氏と面談完了。"
        f"追加ヒアリングは直通電話 {phone} または {email} にて実施予定。"
    )
    pairs = [
        (company, 1), (address, 5), (department, 3), (staff, 2),
        (position, 4), (other, 2), (phone, 6), (email, 7),
    ]
    return text, pairs


def bench_b(factory, _index):
    """発信者と離れた「姓＋様のご来訪」の氏名が落ちる。"""
    company = factory.company()
    department = factory.dept()
    speaker, guest = factory.people(2, "surname")
    position = factory.pos()
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"大変お世話になっております。{company}の{department}、{speaker}{position}でございます。"
        f"弊社の{address}オフィスにて、{guest}様のご来訪をお待ちしております。"
        f"緊急時の連絡先は携帯（{phone}）またはメール（{email}）までご連絡ください。"
    )
    pairs = [
        (company, 1), (department, 3), (speaker, 2), (position, 4),
        (address, 5), (guest, 2), (phone, 6), (email, 7),
    ]
    return text, pairs


def bench_c(factory, _index):
    """TEL/Mail 行が1要素に潰れ、来訪者の姓が後続の組織名に吸収される。"""
    company = factory.company()
    department, other_dept = factory.rng.sample(factory.vocab["departments"], 2)
    staff, guest, caller = factory.lasts(3)
    position, other_pos = factory.positions(2)
    office = f"{guest}法務事務所"
    address = factory.address()
    phone = factory.phone()
    email = factory.email()
    text = (
        f"【連絡メモ】\n"
        f"・発信元: {company} {department} {staff}{position}\n"
        f"・所在地: {address}\n"
        f"・伝言: {guest}様宛てに資料送付済み。TEL: {phone} / Mail: {email}\n\n"
        f"先ほど{office}の{caller}さん（{other_dept}・{other_pos}）から内線ありました。"
        f"折り返しは{guest}様からお願いします。"
    )
    pairs = [
        (company, 1), (department, 3), (staff, 2), (position, 4),
        (address, 5), (guest, 2), (phone, 6), (email, 7),
        (office, 1), (caller, 2), (other_dept, 3), (other_pos, 4),
    ]
    return text, pairs


def negative_a(factory, index):
    doc = factory.doc("NEG", index)
    frames = [
        f"明日の会議に向け、審査資料と見積書の確認を完了しました。文書番号: {doc}",
        f"本日の議事録は共有フォルダへ保存済みです。文書番号: {doc}",
        f"稟議書の承認状況を午後に確認する予定です。文書番号: {doc}",
        f"来週の打ち合わせでは予算案と作業計画を確認します。文書番号: {doc}",
    ]
    return frames[index % 4], []


def negative_b(factory, index):
    return negative_a(factory, index)


def negative_c(factory, index):
    return negative_a(factory, index)


def hard_a(factory, index):
    number = index + 1
    doc = factory.doc("HARD", index)
    day = (number % 28) + 1
    text = (
        f"請求金額は{number},000円です。支払期日は2026年11月{day}日、"
        f"確認時刻は14:{number % 60:02d}です。文書番号: {doc}"
    )
    return text, []


def hard_b(factory, index):
    number = index + 1
    doc = factory.doc("HARD", index)
    text = (
        f"参照先は https://files.example.test/manual/{factory.split}/{number} です。"
        f"エラーコードは ERR-404-{number:04d}。内線は {2000 + number} 番です。文書番号: {doc}"
    )
    return text, []


def hard_c(factory, index):
    number = index + 1
    doc = factory.doc("HARD", index)
    day = (number % 28) + 1
    text = (
        f"受領日 2026-11-{day:02d}、金額 {number * 1000}円、"
        f"案件URL https://files.example.test/orders/{factory.split}/{doc}、"
        f"コード ERR-090-{number:03d}、内線{3000 + number}。"
    )
    return text, []


# 各系統は3文面以上。single / negative は内部で文面を切り替える。
FAMILIES = [
    ("meeting", (meeting_a, meeting_b, meeting_c)),
    ("phone", (phone_a, phone_b, phone_c)),
    ("report", (report_a, report_b, report_c)),
    ("nested", (nested_a, nested_b, nested_c)),
    ("names", (names_a, names_b, names_c)),
    ("signature", (signature_a, signature_b, signature_c)),
    ("single", (_single, _single, _single)),
    ("long", (long_a, long_b, long_c)),
    ("bench", (bench_a, bench_b, bench_c)),
    ("negative", (negative_a, negative_b, negative_c)),
    ("hard", (hard_a, hard_b, hard_c)),
]


def _name_styles(entities):
    styles = set()
    for entity, label in entities:
        if label != 2:
            continue
        if KANA_RE.fullmatch(entity):
            styles.add("kana")
        elif " " in entity:
            styles.add("spaced")
        elif len(entity) >= 3:
            styles.add("full")
        else:
            styles.add("surname")
    return styles


def _phone_kind(phone):
    if phone.startswith("0120-"):
        return "0120"
    if phone.startswith("(03)"):
        return "paren"
    if phone.startswith("090-"):
        return "mobile-hyphen"
    if phone.startswith("03-"):
        return "landline"
    if phone.startswith("090") and "-" not in phone:
        return "mobile-plain"
    return "other"


def _email_kind(email):
    local = email.split("@", 1)[0]
    if local.startswith("contact"):
        return "contact"
    if local.startswith("info"):
        return "info"
    return "romaji"


def _address_kind(address):
    if address.startswith("〒"):
        return "postal"
    if "丁目" in address and " " in address:
        return "chome-building"
    if "丁目" in address:
        return "chome"
    return "hyphen"


def assert_vocabulary():
    train = closed_surfaces("train")
    valid = closed_surfaces("valid")
    overlap = train & valid
    if overlap:
        raise ValueError(f"語彙が train/valid で交差しています: {sorted(overlap)[:12]}")
    for split in ("train", "valid"):
        vocab = VOCABULARY[split]
        lasts = [person[0] for person in vocab["people"]]
        kanas = [person[1] for person in vocab["people"]]
        atoms = (
            lasts + kanas + vocab["first_names"] + vocab["companies"]
            + vocab["departments"] + vocab["positions"] + vocab["buildings"]
        )
        for left in atoms:
            for right in atoms:
                if left != right and len(left) >= 2 and left in right:
                    raise ValueError(f"{split}: 語彙の包含 {left!r} ⊂ {right!r}")
            for company in vocab["companies"]:
                if left in lasts and left in company:
                    raise ValueError(f"{split}: 姓が組織名に含まれる {left!r} / {company!r}")


def closed_surfaces(split):
    vocab = VOCABULARY[split]
    surfaces = set(vocab["companies"])
    surfaces.update(vocab["departments"])
    surfaces.update(vocab["positions"])
    surfaces.update(vocab["areas"])
    surfaces.update(vocab["buildings"])
    for last, kana, _romaji in vocab["people"]:
        surfaces.add(last)
        surfaces.add(kana)
        surfaces.add(last + "法務事務所")
        for first in vocab["first_names"]:
            surfaces.add(last + first)
            surfaces.add(f"{last} {first}")
    return surfaces


def check_groups(groups):
    expected_labels = {
        "meeting": {1, 2, 3, 4, 5, 6, 7},
        "phone": {1, 2, 3, 4, 6},
        "report": {1, 2, 3, 4, 5, 7},
        "nested": {1, 2, 3, 4},
        "names": {1, 2, 3, 4, 6},
        "signature": {1, 2, 3, 4, 5, 6, 7},
        "long": {1, 2, 3, 4, 5, 6, 7},
        "bench": {1, 2, 3, 4, 5, 6, 7},
    }
    for name, _builders in FAMILIES:
        if len(_builders) < 3:
            raise ValueError(f"{name}: 文面が3未満")
        samples = groups[name]
        if name in expected_labels:
            for text, entities in samples:
                labels = {label for _entity, label in entities}
                if labels != expected_labels[name]:
                    raise ValueError(f"{name}: ラベル集合が不一致 {labels}")
        if name == "phone":
            for text, _entities in samples:
                if not any(mark in text for mark in HONORIFIC_SUFFIXES):
                    raise ValueError(f"敬称の無い電話メモ: {text}")
        if name == "nested":
            for _text, entities in samples:
                offices = [entity for entity, label in entities if label == 1 and entity.endswith("法務事務所")]
                if len(offices) != 1:
                    raise ValueError("法務事務所が1件ではない")
                lawyer = offices[0][: -len("法務事務所")]
                if [lawyer, 2] not in entities:
                    raise ValueError(f"氏名が組織名の外に出ていない: {offices[0]}")
        if name == "bench":
            saw = set()
            for text, entities in samples:
                strings = {entity for entity, _label in entities}
                if "資料送付済み" in strings or "伝言" in strings or "内線" in strings:
                    raise ValueError(f"非個人情報をラベルしている: {strings & {'資料送付済み', '伝言', '内線'}}")
                if "窓口担当の" in text:
                    saw.add("dept")
                    if not any(label == 3 and f"{entity} " in text for entity, label in entities):
                        raise ValueError("部署の直後に空白が無い")
                if "ご来訪" in text:
                    saw.add("visit")
                    if not any(label == 2 and f"{entity}様のご来訪" in text for entity, label in entities):
                        raise ValueError("来訪者の姓が本文に無い")
                if "TEL:" in text:
                    saw.add("memo")
                    offices = [entity for entity, label in entities if label == 1 and entity.endswith("法務事務所")]
                    if len(offices) != 1 or offices[0][: -len("法務事務所")] not in strings:
                        raise ValueError("姓と、その姓を含む組織名が両方出ていない")
                    if not any(label == 6 for _entity, label in entities):
                        raise ValueError("連絡メモに電話が無い")
                    if not any(label == 7 for _entity, label in entities):
                        raise ValueError("連絡メモにメールが無い")
            if saw != {"dept", "visit", "memo"}:
                raise ValueError(f"ベンチ系統の型が不足: {saw}")
        if name == "names":
            styles = set()
            for _text, entities in samples:
                styles |= _name_styles(entities)
            if styles != {"surname", "full", "spaced", "kana"}:
                raise ValueError(f"氏名スタイル不足: {styles}")
        if name == "single":
            seen = set()
            for _text, entities in samples:
                if len(entities) != 1:
                    raise ValueError("単一エンティティ系統の件数が1ではない")
                seen.add(entities[0][1])
            if seen != {5, 6, 7}:
                raise ValueError(f"単一エンティティの種類不足: {seen}")
        if name == "long":
            for text, _entities in samples:
                if "\n\n" not in text or len(text) < 80:
                    raise ValueError(f"長文が短い: {text!r}")
        if name in ("negative", "hard"):
            for text, entities in samples:
                if entities:
                    raise ValueError(f"{name}: 負例にラベルがある")
                if PHONE_SEARCH_RE.search(text) or EMAIL_SEARCH_RE.search(text):
                    raise ValueError(f"{name}: 負例に電話かメールがある: {text}")
        if name == "hard":
            blob = "\n".join(text for text, _entities in samples)
            for marker in ("円", "https://", "内線", "ERR-", "2026"):
                if marker not in blob:
                    raise ValueError(f"ハード負例に {marker} が無い")


def validate_dataset(train_samples, valid_samples):
    allowed_labels = set(range(1, 8))
    minimum = {"train": 40, "valid": 12}
    collected = {}
    for split_name, samples in (("train", train_samples), ("valid", valid_samples)):
        texts = [text for text, _entities in samples]
        if len(texts) != len(set(texts)):
            raise ValueError(f"{split_name}: 重複入力がある")
        counts = Counter()
        negatives = 0
        phones = []
        emails = []
        addresses = []
        strings = set()
        for text, entities in samples:
            if not entities:
                negatives += 1
            seen = set()
            indexes = []
            for entity, label in entities:
                key = (entity, label)
                if key in seen:
                    raise ValueError(f"{split_name}: 重複スパン {key!r}")
                seen.add(key)
                if entity not in text:
                    raise ValueError(f"{split_name}: 本文に無い {entity!r}")
                if label not in allowed_labels:
                    raise ValueError(f"{split_name}: 不正ID {label}")
                if label == 2 and entity.endswith(HONORIFIC_SUFFIXES):
                    raise ValueError(f"{split_name}: 敬称が氏名に含まれる {entity!r}")
                if label == 6 and not PHONE_RE.fullmatch(entity):
                    raise ValueError(f"{split_name}: 電話の形ではない {entity!r}")
                if label == 7 and not EMAIL_RE.fullmatch(entity):
                    raise ValueError(f"{split_name}: メールの形ではない {entity!r}")
                indexes.append((text.find(entity), -len(entity)))
                strings.add(entity)
                if label == 6:
                    phones.append(entity)
                elif label == 7:
                    emails.append(entity)
                elif label == 5:
                    addresses.append(entity)
            if indexes != sorted(indexes):
                raise ValueError(f"{split_name}: 出現順ではない")
            counts.update(label for _entity, label in entities)
        for label in allowed_labels:
            if counts[label] < minimum[split_name]:
                raise ValueError(f"{split_name}: カテゴリ {label} が {counts[label]} 件")
        if negatives * 100 < len(samples) * 15:
            raise ValueError(f"{split_name}: 負例が {negatives}/{len(samples)}")
        if {_phone_kind(phone) for phone in phones} != {
            "0120", "paren", "mobile-hyphen", "landline", "mobile-plain",
        }:
            raise ValueError(f"{split_name}: 電話表記が不足")
        if {_email_kind(email) for email in emails} != {"contact", "info", "romaji"}:
            raise ValueError(f"{split_name}: メール表記が不足")
        if {_address_kind(address) for address in addresses} != {
            "postal", "chome-building", "chome", "hyphen",
        }:
            raise ValueError(f"{split_name}: 住所表記が不足")
        collected[split_name] = strings

    overlap = collected["train"] & collected["valid"]
    if overlap:
        raise ValueError(f"train/valid の表層語が交差: {sorted(overlap)[:12]}")


def build_split(split, count):
    rng = random.Random(SEED if split == "train" else SEED + 1)
    factory = Factory(split, rng)
    grouped = {}
    samples = []
    for name, builders in FAMILIES:
        grouped[name] = []
        for index in range(count):
            text, pairs = builders[index % len(builders)](factory, index)
            item = (text, order_entities(text, pairs))
            grouped[name].append(item)
            samples.append(item)
    if factory.phone_seq >= 200:
        raise ValueError("電話の連番が表記枠を超えた")
    check_groups(grouped)
    rng.shuffle(samples)
    return samples


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
    return path


def label_counts(samples):
    return dict(sorted(Counter(label for _text, entities in samples for _entity, label in entities).items()))


def negative_count(samples):
    return sum(1 for _text, entities in samples if not entities)


def main():
    assert_vocabulary()
    train_samples = build_split("train", TRAIN_SIZE_PER_PATTERN)
    valid_samples = build_split("valid", VALID_SIZE_PER_PATTERN)
    validate_dataset(train_samples, valid_samples)
    train_path = write_jsonl("train.jsonl", train_samples)
    valid_path = write_jsonl("valid.jsonl", valid_samples)
    print("✅ [v23 文型拡張・表記揺れ・ハード負例] 作成完了")
    print(f"   {train_path}: {len(train_samples)}件 / labels={label_counts(train_samples)} / negatives={negative_count(train_samples)}")
    print(f"   {valid_path}: {len(valid_samples)}件 / labels={label_counts(valid_samples)} / negatives={negative_count(valid_samples)}")


if __name__ == "__main__":
    main()
