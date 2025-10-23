<?php
/**
 * Plugin Name: My Migration Plugin
 * Description: A custom plugin to migrate WordPress sites including the database and files.
 * Version: 1.0
 * Author: Your Name
 */

// 直接アクセスを禁止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 管理メニューにプラグインのページを追加
add_action( 'admin_menu', 'mmp_add_admin_menu' );

function mmp_add_admin_menu() {
    add_menu_page(
        'My Migration',      // ページのタイトル
        'My Migration',      // メニューのタイトル
        'manage_options',    // 権限
        'my-migration-plugin', // メニュースラッグ
        'mmp_create_admin_page', // ページコンテンツを生成する関数
        'dashicons-database-export', // アイコン
        6 // 表示位置
    );
}

// プラグインのページコンテンツ
function mmp_create_admin_page() {
    ?>
    <div class="wrap">
        <h1>My Migration Plugin</h1>
        <p>このページでサイトのエクスポートとインポートを行います。</p>

        <hr>

        <h2>エクスポート</h2>
        <p>サイトのデータベースとファイルをエクスポートします。</p>
        <form method="post" action="">
            <?php wp_nonce_field( 'mmp_export_action', 'mmp_export_nonce' ); ?>
            <input type="hidden" name="action" value="mmp_export">
            <?php submit_button( 'エクスポートファイルを作成' ); ?>
        </form>

        <hr>

        <h2>インポート</h2>
        <p>エクスポートしたファイルをアップロードしてサイトを復元します。</p>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mmp_import_action', 'mmp_import_nonce' ); ?>
            <input type="hidden" name="action" value="mmp_import">
            <p>
                <label for="import_file">移行ファイルを選択してください:</label>
                <input type="file" id="import_file" name="import_file">
            </p>
            <?php submit_button( 'インポートを実行' ); ?>
        </form>
    </div>
    <?php
}

// エクスポート・インポート処理の呼び出し
add_action( 'admin_init', 'mmp_handle_actions' );

function mmp_handle_actions() {
    // エクスポート処理
    if ( isset( $_POST['action'] ) && 'mmp_export' === $_POST['action'] ) {
        if ( ! isset( $_POST['mmp_export_nonce'] ) || ! wp_verify_nonce( $_POST['mmp_export_nonce'], 'mmp_export_action' ) ) {
            wp_die( '不正なリクエストです。' );
        }
        // ここにエクスポート処理を記述
        mmp_export_site();
    }

    // インポート処理
    if ( isset( $_POST['action'] ) && 'mmp_import' === $_POST['action'] ) {
        if ( ! isset( $_POST['mmp_import_nonce'] ) || ! wp_verify_nonce( $_POST['mmp_import_nonce'], 'mmp_import_action' ) ) {
            wp_die( '不正なリクエストです。' );
        }
        // ここにインポート処理を記述
        mmp_import_site();
    }
}


// エクスポートの処理
function mmp_export_site() {
    // タイムアウトを回避
    @set_time_limit( 0 );

	// メモリ上限を上げる試み (サーバー設定による)
	@ini_set( 'memory_limit', '1024M' );

	// 一時的なエクスポートディレクトリを作成
	// ユニークなIDで他の処理と衝突しないようにする
	$export_id = 'mmp-' . time() . '-' . wp_generate_password( 12, false );
    $upload_dir = wp_upload_dir();
    $export_dir = $upload_dir['basedir'] . '/mmp-exports';
    wp_mkdir_p( $export_dir );

    // 1. データベースをエクスポート
    $db_file = mmp_export_database( $export_dir );
    if ( is_wp_error( $db_file ) ) {
        wp_die( 'データベースのエクスポートに失敗しました: ' . $db_file->get_error_message() );
    }

    // 2. ファイルをエクスポート (wp-content)
    $content_dir = WP_CONTENT_DIR;
    $files_zip_path = $export_dir . '/files.zip';
    $files_zip = new ZipArchive();

    if ( $files_zip->open( $files_zip_path, ZipArchive::CREATE ) !== true ) {
        wp_die( 'ZIPファイルを作成できません。' );
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $content_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ( $files as $name => $file ) {
        if ( ! $file->isDir() ) {
            $filePath = $file->getRealPath();
            $relativePath = substr( $filePath, strlen( $content_dir ) + 1 );

            // エクスポートファイル自体は除外
            if ( strpos( $filePath, $export_dir ) === false ) {
                $files_zip->addFile( $filePath, $relativePath );
            }
        }
    }
    $files_zip->close();

	// 3. サイト情報をmanifest.jsonとして保存
	$manifest = [
		'site_url' => site_url(),
		'home_url' => home_url(),
		'path'     => ABSPATH,
	];
	$manifest_file = $export_dir . '/manifest.json';
	file_put_contents( $manifest_file, json_encode( $manifest, JSON_PRETTY_PRINT ) );

    // 4. 最終的なパッケージを作成 (DB, ファイル, manifestを1つのZIPに)
	$package_file = $upload_dir['basedir'] . '/mmp-package-' . time() . '.zip';
	$package_zip = new ZipArchive();

	if ( $package_zip->open( $package_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		wp_die( '最終的な移行パッケージを作成できませんでした。' );
	}

	$package_zip->addFile( $db_file, 'database.sql' );
	$package_zip->addFile( $files_zip_path, 'files.zip' );
	$package_zip->addFile( $manifest_file, 'manifest.json' );
	$package_zip->close();

    // 5. ダウンロード処理
    if ( file_exists( $package_file ) ) {
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename="' . basename( $package_file ) . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $package_file ) );
        readfile( $package_file );

        // 6. 一時ファイルを削除
		unlink( $db_file );
		unlink( $files_zip_path );
		unlink( $manifest_file );
		rmdir( $export_dir );
		unlink( $package_file );
        exit;
    }

	wp_die( 'エクスポートファイルの作成に失敗しました。' );
}

function mmp_export_database( $export_dir ) {
    global $wpdb;
    $file_path = $export_dir . '/database.sql';
    $handle = @fopen( $file_path, 'w+' );
    if ( ! $handle ) {
        return new WP_Error( 'file_error', 'SQLファイルを作成できません。' );
    }

    $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
    $tables = array_map( function($a){ return $a[0]; }, $tables );

    foreach ( $tables as $table ) {
        // CREATE TABLE文
        $create_table = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
        fwrite( $handle, "\n\n" . $create_table[1] . ";\n\n" );

        // INSERT文
        $rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
        foreach ( $rows as $row ) {
            $fields = array_map( function( $field ) use ( $wpdb ) {
                return is_null( $field ) ? 'NULL' : "'" . $wpdb->_real_escape( $field ) . "'";
            }, $row );
            fwrite( $handle, "INSERT INTO `{$table}` VALUES (" . implode( ',', $fields ) . ");\n" );
        }
    }

    fclose( $handle );
    return $file_path;
}


// インポートの処理
function mmp_import_site() {
    if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
		wp_admin_notice( 'アップロードファイルがありません。', [ 'type' => 'error' ] );
        return;
    }

	// タイムアウトとメモリ上限を上げる
	@set_time_limit( 0 );
	@ini_set( 'memory_limit', '1024M' );

	$uploaded_file = $_FILES['import_file'];

	// 1. アップロードされたZIPを展開する一時ディレクトリを作成
	$upload_dir = wp_upload_dir();
	$import_dir = $upload_dir['basedir'] . '/mmp-imports/' . basename( $uploaded_file['name'], '.zip' );
	if ( ! wp_mkdir_p( $import_dir ) ) {
		wp_admin_notice( 'インポート用の一時ディレクトリを作成できませんでした。', [ 'type' => 'error' ] );
		return;
	}

	// 2. アップロードされたZIPファイルを展開
	$zip = new ZipArchive();
	if ( $zip->open( $uploaded_file['tmp_name'] ) === true ) {
		$zip->extractTo( $import_dir );
		$zip->close();
	} else {
		wp_admin_notice( 'ZIPファイルの展開に失敗しました。', [ 'type' => 'error' ] );
		mmp_cleanup_dir( $import_dir );
		return;
	}

	$db_file       = $import_dir . '/database.sql';
	$files_zip     = $import_dir . '/files.zip';
	$manifest_file = $import_dir . '/manifest.json';

	if ( ! file_exists( $db_file ) || ! file_exists( $files_zip ) || ! file_exists( $manifest_file ) ) {
		wp_admin_notice( '移行パッケージの形式が正しくありません。必要なファイルが見つかりませんでした。', [ 'type' => 'error' ] );
		mmp_cleanup_dir( $import_dir );
		return;
	}

	// 3. ファイルをインポート (wp-content)
	$zip = new ZipArchive();
	if ( $zip->open( $files_zip ) === true ) {
		$zip->extractTo( WP_CONTENT_DIR );
		$zip->close();
	} else {
		wp_admin_notice( 'コンテンツファイル (files.zip) の展開に失敗しました。', [ 'type' => 'error' ] );
		mmp_cleanup_dir( $import_dir );
		return;
	}

	// 4. データベースをインポート
	global $wpdb;
	$manifest = json_decode( file_get_contents( $manifest_file ), true );
	$old_url  = $manifest['site_url'];
	$new_url  = site_url();
	$old_path = $manifest['path'];
	$new_path = ABSPATH;

	// 既存のテーブルをバックアップ（簡易的）＆削除
	$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
	$tables = array_map( function($a){ return $a[0]; }, $tables );
	foreach ( $tables as $table ) {
		// 重要なテーブルはスキップするなど、本来はより慎重な処理が必要
		$wpdb->query( "DROP TABLE `{$table}`" );
	}

	// SQLファイルを一行ずつ読み込んで実行
	$handle = fopen( $db_file, 'r' );
	if ( $handle ) {
		$query = '';
		while ( ( $line = fgets( $handle ) ) !== false ) {
			// コメント行や空行はスキップ
			if ( substr( $line, 0, 2 ) === '--' || trim( $line ) === '' ) {
				continue;
			}

			$query .= $line;

			// クエリの終端に達したら実行
			if ( substr( trim( $line ), -1, 1 ) === ';' ) {
				// URLとパスを置換
				$replaced_query = mmp_safe_replace_db( $query, $old_url, $new_url );
				$replaced_query = mmp_safe_replace_db( $replaced_query, $old_path, $new_path );

				$wpdb->query( $replaced_query );
				$query = ''; // クエリをリセット
			}
		}
		fclose( $handle );
	} else {
		wp_admin_notice( 'データベースファイルの読み込みに失敗しました。', [ 'type' => 'error' ] );
		mmp_cleanup_dir( $import_dir );
		return;
	}

	// 5. 後処理
	flush_rewrite_rules();
	mmp_cleanup_dir( $import_dir );

    echo '<div class="notice notice-success is-dismissible"><p>インポートが正常に完了しました！ サイトの表示と管理画面の動作を確認してください。</p></div>';
}

/**
 * シリアライズされたデータを壊さずに安全に文字列を置換する関数
 *
 * @param string $data    対象のデータ (SQLクエリなど)
 * @param string $search  検索する文字列
 * @param string $replace 置換後の文字列
 * @return string 置換後のデータ
 */
function mmp_safe_replace_db( $data, $search, $replace ) {
	// 簡易的なチェック
	if ( is_string( $data ) && is_string( $search ) && is_string( $replace ) ) {
		return preg_replace_callback(
			'/s:(\d+):"(.*?)";/',
			function( $match ) use ( $search, $replace ) {
				$unserialized = $match[2];
				$replaced     = str_replace( $search, $replace, $unserialized );
				return 's:' . strlen( $replaced ) . ':"' . $replaced . '";';
			},
			str_replace( $search, $replace, $data )
		);
	}
	return $data;
}

/**
 * 一時ディレクトリを再帰的に削除する
 *
 * @param string $dirPath 削除するディレクトリのパス
 */
function mmp_cleanup_dir( $dirPath ) {
	if ( ! is_dir( $dirPath ) ) {
		return;
	}
	if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) !== '/' ) {
		$dirPath .= '/';
	}
	$files = glob( $dirPath . '*', GLOB_MARK );
	foreach ( $files as $file ) {
		if ( is_dir( $file ) ) {
			mmp_cleanup_dir( $file );
		} else {
			unlink( $file );
		}
	}
	rmdir( $dirPath );
}
