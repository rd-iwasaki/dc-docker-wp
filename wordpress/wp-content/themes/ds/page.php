<?php get_header(); ?>

    <main>
        <article class="general-section bgs layout-stack-wide">
            <div class="article-pages_title">
                <div class="block-head">
                    <h1 class="primary-heading is-kerning mincho is-gry is-bold text-center"><?php the_title(); ?></h3>
                </div>
            </div>
        </article>
        <article class="general-section">
            <div class="inner inner-mid">
                <div class="single-detail gothic"><?php the_content(); ?></div>
            </div>
        </article>
    </main>

<?php get_footer(); ?>