<?php
//アイキャッチ画像を有効化
add_theme_support('post-thumbnails');

// アセット用のURLを定義
define('WP_ASSET_URL', get_template_directory_uri().'/');

/**
* アセットURLの取得
*
* @param string $path   画像やJSまでのパス
* @return string
*/
function _asset_url($path = '') {
    $url = rtrim(WP_ASSET_URL, '/');
    if( strpos($path, '/') !== 0 ) {
        $url .= '/';
    }
    $url .= $path;
    if( preg_match('/\.(css|js|jpe?g|png|gif|svg)/', $url, $matches) ) {
        $version = wp_get_theme()->get('Version');
        $url = add_query_arg(['ver' => $version], $url);
    }
    return $url;
}

/**
* アセットURLの出力
*
* @param string $path   画像やJSまでのパス
* @return string
*/
function _e_asset_url($path = '') {
    echo _asset_url($path);
}

// ページネーション設定
function get_pagination_list($query = null) {
  global $wp_query; // メインクエリをグローバル変数として定義
  
  // 引数が指定されていない場合は、メインクエリを使用
  if (!$query) {
    $query = $wp_query;
  }
  
  $big = 999999999;
  $pagination_links = paginate_links(
    array(
      'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
      'format' => '?paged=%#%',
      'current' => max(1, get_query_var('paged')),
      'total' => $query->max_num_pages,
      'type' => 'list',
      'prev_text' => __('<'),
      'next_text' => __('>'),
      // 'end_size' => 1, // 先頭と末尾に表示するページ数
      'mid_size' => 1, // 現在のページの左右に表示するページ数
    )
  );
  echo $pagination_links;
}

//記事のスラッグ（URL）が日本語にならないようにする
function change_post_slug($slug, $post_ID, $post_status, $post_type) {
  if (preg_match('/(%[0-9a-f]{2})+/', $slug)) {
      $slug = utf8_uri_encode( $post_type ).'-'.$post_ID;
  }
  return $slug;
}
add_filter('wp_unique_post_slug', 'change_post_slug', 99, 4);

//自動で初期化カテゴリー選択するのを無効化
function remove_default_category_if_empty( $post_id, $post ) {
    // 投稿が公開または更新されたときのみ実行
    if ( $post->post_status !== 'publish' && $post->post_status !== 'future' ) {
        return;
    }

    // ユーザーが明示的にカテゴリーを選択したかチェック
    $categories = wp_get_post_categories( $post_id );

    // カテゴリーが一つも選択されていない、かつデフォルトカテゴリーが割り当てられている場合
    // この場合、$categories にはデフォルトカテゴリーIDのみが含まれる
    if ( count( $categories ) === 1 ) {
        $default_cat_id = (int) get_option( 'default_category' );
        if ( (int) $categories[0] === $default_cat_id ) {
            // 自動的に割り当てられたデフォルトカテゴリーを削除
            wp_remove_object_terms( $post_id, $default_cat_id, 'category' );
        }
    }
}
add_action( 'save_post', 'remove_default_category_if_empty', 10, 2 );

//ページを404に
function flushRules(){
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}
add_filter('init','flushRules');
add_filter('author_rewrite_rules', '__return_empty_array'); //投稿者アーカイブ
add_filter('category_rewrite_rules', '__return_empty_array'); //カスタムタクソノミーアーカイブ(category)
add_filter('date_rewrite_rules', '__return_empty_array'); //日付別アーカイブ

/*
* アイキャッチ画像がない場合に記事内の最初の画像を取得する
*
* @param string $size サムネイルのサイズ
* @return string imgタグ
*/
function get_thumbnail_with_fallback($size = 'thumbnail') {
    // アイキャッチ画像が設定されている場合
    if (has_post_thumbnail()) {
        return get_the_post_thumbnail(null, $size);
    }

    // アイキャッチ画像がない場合、記事本文から最初の画像を取得
    global $post;
    $content = $post->post_content;
    $first_img_url = '';

    // 正規表現でimgタグのsrc属性を取得
    if (preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches)) {
        $first_img_url = $matches[1];
    }

    // 記事内に画像が見つかった場合
    if (!empty($first_img_url)) {
        // URLからAttachment IDを取得
        $attachment_id = attachment_url_to_postid($first_img_url);

        // Attachment IDが取得できた場合（メディアライブラリの画像の場合）
        if ($attachment_id) {
            return wp_get_attachment_image($attachment_id, $size);
        }
        // メディアライブラリ以外の画像の場合は、元の画像を出力
        return '<img src="' . esc_url($first_img_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
    }

    // 記事内にも画像がない場合はデフォルト画像を表示
    return '<img src="' . _asset_url('images/noimg.webp') . '" alt="noimg">';
}

/* パンくず
================*/
// データ処理
function get_breadcrumbs_data() {
    $breadcrumbs = [];

    // トップページを追加
    $breadcrumbs[] = [
        'name'       => 'シニア向け分譲マンション デュオセーヌ（DUOSCENE）',
        'item'       => esc_url(home_url('/')),
        'is_current' => false
    ];

    // 投稿ページの場合
    if (is_single()) {
        $parent_page = get_page_by_path('article');
        if ($parent_page) {
            $breadcrumbs[] = [
                'name'       => esc_html(get_the_title($parent_page->ID)),
                'item'       => esc_url(get_permalink($parent_page->ID)),
                'is_current' => false // is_currentキーを追加
            ];
        }
        $breadcrumbs[] = [
            'name'       => get_the_title(),
            'item'       => esc_url(get_permalink()),
            'is_current' => false // is_currentキーを追加
        ];
    }
    else if (is_home()) {
        $parent_page = get_page_by_path('article');
        $breadcrumbs[] = [
            'name'       => esc_html(get_the_title($parent_page->ID)),
            'item'       => esc_url(get_permalink($parent_page->ID)),
            'is_current' => false // is_currentキーを追加
        ];
    }
    // 固定ページの場合
    else if (is_page()) {
        $post = get_post();
        if ($post->post_parent) {
            $parent_id = $post->post_parent;
            $parent_breadcrumbs = [];
            while ($parent_id) {
                $page = get_post($parent_id);
                $parent_breadcrumbs[] = [
                    'name'       => esc_html(get_the_title($page->ID)),
                    'item'       => esc_url(get_permalink($page->ID)),
                    'is_current' => false // is_currentキーを追加
                ];
                $parent_id = $page->post_parent;
            }
            $parent_breadcrumbs = array_reverse($parent_breadcrumbs);
            $breadcrumbs = array_merge($breadcrumbs, $parent_breadcrumbs);
        }
        $breadcrumbs[] = [
            'name'       => get_the_title(),
            'item'       => esc_url(get_permalink()),
            'is_current' => false // is_currentキーを追加
        ];
    }
    // カテゴリーアーカイブの場合
    else if (is_category()) {
        $cat = get_queried_object();
        if ($cat->parent) {
            $parent_id = $cat->parent;
            $parent_breadcrumbs = [];
            while ($parent_id) {
                $parent_cat = get_category($parent_id);
                $parent_breadcrumbs[] = [
                    'name'       => esc_html($parent_cat->name),
                    'item'       => esc_url(get_category_link($parent_cat->term_id)),
                    'is_current' => false // is_currentキーを追加
                ];
                $parent_id = $parent_cat->parent;
            }
            $parent_breadcrumbs = array_reverse($parent_breadcrumbs);
            $breadcrumbs = array_merge($breadcrumbs, $parent_breadcrumbs);
        }
        $breadcrumbs[] = [
            'name'       => single_cat_title('', false),
            'item'       => esc_url(get_category_link($cat->term_id)),
            'is_current' => false // is_currentキーを追加
        ];
    }

    // 最後の要素を現在のページとしてマーク
    if (!empty($breadcrumbs)) { // 配列が空でないか確認
        $breadcrumbs[count($breadcrumbs) - 1]['is_current'] = true;
    }

    return $breadcrumbs;
}
// HTML出力用
function get_breadcrumb_html() {
    // トップページの場合は何も出力しない
    if (is_front_page()) {
        return;
    }

    // パンくずリストのデータを取得
    $breadcrumbs = get_breadcrumbs_data();

    // HTMLの出力開始
    echo '<div class="breadclumbs serif">';
    echo '  <ul class="breadclumbs-lists">';

    foreach ($breadcrumbs as $crumb) {
        if ($crumb['is_current']) {
            echo '    <li class="breadclumbs-list">';
            echo '      <span class="link current">' . esc_html($crumb['name']) . '</span>';
            echo '    </li>';
        } else {
            echo '    <li class="breadclumbs-list">';
            echo '      <a href="' . esc_url($crumb['item']) . '" class="link">' . esc_html($crumb['name']) . '</a>';
            echo '    </li>';
        }
    }

    echo '  </ul>';
    echo '</div>';
}
// 構造化データ出力用Json
function get_breadcrumb_json() {
    // トップページの場合は何も出力しない
    if (is_front_page()) {
        return;
    }

    // パンくずリストのデータを取得
    $breadcrumbs = get_breadcrumbs_data();
    
    // JSON-LD形式で出力
    if (!empty($breadcrumbs)) {
        $json_items = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $position = $index + 1;
            $json_items[] = json_encode([
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $breadcrumb['name'],
                'item'     => $breadcrumb['item']
            ], JSON_UNESCAPED_UNICODE);
        }

        echo '<script type="application/ld+json">';
        echo '{';
        echo '  "@context": "https://schema.org/",';
        echo '  "@type": "BreadcrumbList",';
        echo '  "itemListElement": [';
        echo implode(",\n", $json_items);
        echo '  ]';
        echo '}';
        echo '</script>';
    }
}
