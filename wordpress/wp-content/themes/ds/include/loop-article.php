                <article class="article-lists gothic">
                    <?php while($wp_query->have_posts()): $wp_query->the_post(); ?>
                    <a href="<?php the_permalink(); ?>" class="item">
                        <div class="thumbnail"><?php echo get_thumbnail_with_fallback('thumbnail'); ?></div>
                        <div class="info">
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
                                        
                                        // カテゴリー名とリンクを<li>タグで出力
                                        echo '<li>' . esc_html( $category->name ) . '</li>';
                                    }
                                } ?>
                                </ul>
                            </div>
                            <h1 class="title mincho"><?php the_title(); ?></h1>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </article>