<?php get_header(); ?>

    <main>
        <?php get_breadcrumb_html(); ?>
        <article class="general-section">
            <div class="inner inner-mid">
                <div class="single-header gothic">
                    <div class="status">
                        <time datetime="<?php the_time('Y-m-d'); ?>"><?php the_time('Y.m.d'); ?></time>
                        <ul class="categories">
                        <?php // 現在の記事に紐づくカテゴリーを取得
                        $categories = get_the_category();
                        if ( ! empty( $categories ) ) {
                            // 取得したカテゴリーをループで処理
                            foreach ( $categories as $category ) {
                                // カテゴリーへのリンクURLを取得
                                $category_link = get_category_link( $category->term_id );
                                // カテゴリー名を<li>タグで出力
                                echo '<li>' . esc_html( $category->name ) . '</li>';
                            }
                        } ?>
                        </ul>
                    </div>
                    <h1 class="title mincho"><?php the_title(); ?></h1>
                </div>
                <div class="single-detail gothic"><?php the_content(); ?></div>
            </div>
        </article>
    </main>

    <?php get_template_part('include/aside', 'contact'); ?>

<?php get_footer(); ?>