# WordPress Local Development Starter Kit

Dockerを利用して、Mac上でWordPressのローカル開発環境を簡単に構築するためのスターターキットです。

## 動作要件

- macOS
- [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)

## 使い方

プロジェクト用のディレクトリを作成し、その中で以下のいずれかの方法でセットアップを実行します。

### 方法: コマンド1つで簡単セットアップ（推奨） ※初回のみ

ターミナルで以下のコマンドを実行するだけで、必要なファイルがダウンロードされ、セットアップが開始されます。
※{my-wordpress-site}はプロジェクトディレクトリ名に変更してください。

    ```bash
    mkdir my-wordpress-site && cd my-wordpress-site
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Ayct0910/docker-wp-startup/main/setup.sh)"
    ```

### セットアップ手順

セットアップを開始すると、以下の手順で進みます。

1.  **`.env`ファイルを編集**

    スクリプトが一時停止し、`.env`ファイルの編集を促されます。エディタで`.env`ファイルを開き、WordPress, PHP, MySQLのバージョンなどを好みの設定に変更してください。

2.  **セットアップを再開**

    `.env`の編集が終わったら、ターミナルに戻って`Enter`キーを押してください。Dockerコンテナのビルドと起動が自動的に始まります。

3.  **ログイン**

    セットアップが完了すると、ターミナルにWordPress管理画面のURL、管理者ユーザー名、パスワードが表示されます。
    表示された情報を使って、すぐにWordPressにログインできます。

### ２回目以降の起動・停止

プロジェクトディレクトリに移動し、以下のコマンドで環境を起動・停止できます。
`docker-compose` (v1) と `docker compose` (v2) の両方のコマンドを記載しています。お使いの環境に合わせて実行してください。

**起動:**

```bash
# v1
docker-compose up -d

# v2
docker compose up -d
```

**停止:**

```bash
# v1
docker-compose down

# v2
docker compose down
```

### セットアップ完了後下記をwp-config.phpの132行目付近に追記してください。 

    /* That's all, stop editing! Happy publishing. */ より上に記載してください。

    ``` 
    // ローカルネットワークの他デバイスからアクセスするために環境変数からIPとポートを読み込む
    // 別端末の閲覧を許可しない場合はlocalhost、別端末の閲覧許可したい場合はwifi接続時のIPアドレスを設定する
    $local_ip = getenv_docker('WORDPRESS_IP', 'localhost');
    // $local_ip = getenv_docker('WORDPRESS_IP', '192.168.100.45');
    $local_port = getenv_docker('WORDPRESS_PORT', '8080'); // .envのポートと合わせる

    define( 'WP_HOME', 'http://' . $local_ip . ':' . $local_port );
    define( 'WP_SITEURL', 'http://' . $local_ip . ':' . $local_port );
    ```


### 作業環境の準備
    管理画面に行くとプラグイン「all-in-one-wp-migration」 があるので有効にします。
    プラグインの機能で指定したデータのエクスポートとエクスポートした内容のインポートができるので、リポジトリに含まれている「/database_export/localhost-20251023-185942-zs6klryfd357.wpress」をインポートしてください。

    以上で準備が完了いたします。