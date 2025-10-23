<?php get_header(); ?>

    <main>
        <article class="general-section bgs layout-stack-wide">
            <div class="article-pages_title">
                <div class="block-head">
                    <h1 class="primary-heading is-kerning mincho is-gry is-bold text-center">記事一覧</h3>
                </div>
            </div>
        </article>
        <div class="general-section">
            <div class="inner inner-min">
               <?php
               $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; 
               $args = array(
                    'post_type'      => 'post',      // 投稿タイプを指定
                    'posts_per_page' => 10,          // 1ページに表示する記事数
                    'orderby'        => 'date',      // 記事の並び順（日付順）
                    'order'          => 'DESC',       // 降順（新しい順）
                    'paged'          => $paged           // ページネーションのページ番号を渡す
                );

                global $wp_query;
                // WP_Queryの新しいインスタンスを作成
                $wp_query = new WP_Query($args);
                if($wp_query->have_posts()): ?>
                <?php get_template_part('include/loop', 'article'); ?>
                <?php endif; ?>
                <div class="pagination"><?php get_pagination_list($wp_query); ?></div>
                <?php wp_reset_postdata(); ?>
                
            </div>
        </div>

    </main>

<?php get_footer(); ?>