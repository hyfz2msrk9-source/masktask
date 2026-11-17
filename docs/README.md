# WF開発環境構築手順
## 概要
WFを動かすためのdocker環境を構築します。

## 事前準備
+ [Docker Desktop](https://docs.docker.com/get-docker/)をインストール、もしくは、docker-composeを利用できるようにする。

## 環境とバージョン指定
環境の変更は.env内で使用するDockerfileを指定してください。

## 状況・不具合
+ mysql側を再構築する場合、以前のvolumesがあるとエラーになるためwf_db-dataを削除して下さい。
+ [ローカルループバックアドレス](#ローカルループバックアドレス)の設定コマンドは、PC起動のたびに叩くか自動実行させるようにしてください。コマンド実行を忘れるとコンテナ起動ができません。
参考：https://www.key-p.com/blog/staff/archives/107891

## クイックスタート
白上さんが作成したhttps://github.com/KEYPOINT/docker/tree/wf のwf4をベースに作っています。

### ホストPCの構成
マルチテナント構成を可能にするため、サーバ上の`/var/webfile/`以下に、複数のWFソースを配置できるようにしています。   
たとえば、`abc`と`def`という環境を配置する場合、ホストPC上には以下となるように構成しておいてください。  
シングルテナントの場合は`def`は不要です。
```
├── README.md
└── tenants
    ├── abc
    │   ├── wf
    │   ├── wf-admin
    │   └── wf-api
    └── def
        ├── wf
        └── wf-admin
        └── wf-api
```
```shell script
cd READMEのディレクトリ
mkdir tenants && cd tenants
mkdir -p abc/wf && mkdir abc/wf-admin && mkdir abc/wf-api
git clone -b abc git@github.com:KEYPOINT/WebFile.git ./abc/wf
git clone -b abc git@github.com:KEYPOINT/wf-admin.git ./abc/wf-admin
git clone -b abc git@github.com:KEYPOINT/wf-api.git ./abc/wf-api
```

### 構築

1. .envの作成・編集

ホストPCに依存する項目は、.envファイルで指定します。.envファイルは各ホストPC上に作成してください。  
コマンドから.envを生成する場合は、以下のコマンドを実行してください。  
**「webfileソースのパス」部分は変更必須です。**  
それ以外も、設定に応じて設定値を変更してください。  
macの仕様によりユーザのホームディレクトリ以外を指定するには別途設定が必要です。[詳しくはこちら](https://docs.docker.com/docker-for-mac/#file-sharing)  

```sh
cp .env.org .env
```

2. コンテナの設定

**ホスト名**

以下のファイルを編集してください。
- /etc/hostsの左辺
- system.phpのAPP_URL
- webapp/conf.d/wf.httpd.confのServerName

**SSLの設定**

/webapp/Dockerfileとwebapp/conf.d/wf.httpd.confのSSLに関する設定のコメントをはずしてください。
```Dockerfile
# ssl用ファイルをコピー
COPY ./cert/wf-ssl.crt /etc/pki/tls/certs/
COPY ./cert/wf-ssl.key /etc/pki/tls/certs/
```

``` wf.httpd.conf
//前略

# SSLを使う時はコメントを外す
Listen 443 https
<VirtualHost *:443>
//中略
</VirtualHost>
```

置いてあるsslは2033年6月有効期限のオレオレ証明書なので、期限が切れたら作り直して上書きしてください。

**xdebug**

/webapp/Dockerfileのxdebugに関する設定のコメントをはずしてください。
[詳しくはこちら](#xdebugについて)


3. system.phpの設定

```php
// URL(SSLを使わない場合)
define("APP_URL","http://localhost");
define("APP_PATH","/abc");

// Session
// ユーザ側
define("SESSION_SAVEPATH","/var/lib/php/abc/wf");
// 管理者側
define("SESSION_SAVEPATH","/var/lib/php/abc/wf-admin");

// DB
// DB_HOSTはdocker-compose.ymlのサービス名を指定する
define("DB_HOST","db");
define("DB_NAME", "webfile");
define("DB_USER", "webfile");
define("DB_PASS", "webfile");

// システムのメール設定
define("MAIL_DOMAIN", "key-p.co.jp");
define("SYS_FROM", "{自身のアドレス}@" . MAIL_DOMAIN);

// テスト環境の場合は1
define("TEST_MODE", 1);
```

4. dockerイメージの作成と起動
```sh
docker-compose build // イメージ作成
docker-compose up // 起動
```

5. マイグレーション

```sh
docker-compose exec webapp bash
cd /var/webfile/abc/wf
php composer.phar install
php composer.phar run migrate
```

### マルチテナント構成時の追加設定
1. .envに環境名(フォルダ名)を追加
```.env
### 複数のWebFileを作成する場合は、スペース区切りで記述してください
### 例: WEBAPP_NAMES="acb def" → WEBAPP_NAMES="acb def ghi"
WEBAPP_NAMES="acb def"
```

2. DBの追加
2環境目以降はDBを追加で作成すめる必要があるため、
`db/entrypoint-initdb.d/mkdb.sql`に作成するDB名を追記してください。

3. 設定
以下の設定を2環境目に対しても行ってください。
```
3. system.phpの設定
    DB_NAMEを上記で作成したDB名に変更してください。
5. マイグレーション
```

## ローカルループバックアドレス
[ローカルループバックアドレスを使用する理由](https://www.key-p.com/blog/staff/archives/107891)

例)ローカルループバックアドレスに127.0.1.1を使う場合

1. PC本体で以下のコマンドを実行

    このコマンドはPC再起動後に毎回実行する必要があります。

    `sudo ifconfig lo0 alias 127.0.1.1`

2. docker/.envに追加\(先に「構築」の手順で作成してください）

    `IP=127.0.1.1`

3. PC本体の/etc/hostsに追加

    `127.0.1.1 wf.test1.com`

## xdebugについて
エディターによって設定が異なるので、以下を参考にしてください。

xdebugの設定は、webapp/conf.d/wf.xdebug.ini を変更してください。
参考: [公式ドキュメント](https://xdebug.org/docs/step_debug)

### VSCodeの場合
1. Dockerfileのxdebugに関する設定のコメントをはずす
2. コンテナを起動
3. VSCodeの拡張機能"PHP Debug"をインストール
4. launch.jsonの作成
    - サイドバーの「Run and Debug」> 「create a launch.json file.」> 「PHP」の順にクリックします。
    - launch.jsonの内容を以下のように変更します。
    ```json
    {
        "version": "0.2.0",
        "configurations": [
            {
                "name": "Listen for XDebug",
                "type": "php",
                "request": "launch",
                "hostname": "127.0.0.1",
                "port": 9003,
                "pathMappings": {
                    "/var/webfile": "${workspaceFolder}/tenants"
                }
            }
            ...以下の設定は削除
        ]
    }
    ```
5. サイドバーの「Run and Debug」> 再生ボタンをクリックする

### PhpStormの場合
1. Dockerfileのxdebugに関する設定のコメントをはずす
2. コンテナを起動
3. 画面上部からPHPのデバッグ接続をリッスン(![img.png](https://resources.jetbrains.com/help/img/idea/2023.1/php.icons.debug_listen_off_dark.svg))をクリックする
   

### Profilerの設定
動作が遅くなるので、必要な場合のみ設定してください。
1. xdebug.iniの設定を変更
    ```ini
    xdebug.mode=develop,debug,profile
    xdebug.output_dir=/tmp/xdebug
    ```
2. docker-composeにvolumeを追加
    ```yml
    volumes:
      - ./tmp/xdebug:/tmp/xdebug
    ```
3. 分析したい処理をブラウザから動かす
4. 分析
    - PHPStormの場合
        - メニューの「Run」>「Analyze Xdebug Profiler Snapshot」をクリック
    - VSCodeの場合は拡張機能かwebgrindを使用
        - 拡張機能 [PHP Profiler](https://marketplace.visualstudio.com/items?itemName=DEVSENSE.profiler-php-vscode)
        - [webgrind](https://github.com/jokkedk/webgrind)

## DBログの確認
```sh
docker compose logs -f db
```
