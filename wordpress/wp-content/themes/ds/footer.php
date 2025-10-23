    <footer>
        <ul>
            <li><a href="https://www.hoosiers.co.jp/corporation/" target="_blank" rel="noopener noreferrer">会社情報</a>　｜</li>
            <li><a href="https://www.hoosiers.co.jp/privacypolicy/" target="_blank" rel="noopener noreferrer">プライバシーポリシー</a></li>
        </ul>
        <small>Copyright © Hoosiers Holdings. All rights reserved.</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- slickのJavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="<?php _e_asset_url('js/accordion.js'); ?>"></script>
    <script src="<?php _e_asset_url('js/scroll-hint.min.js'); ?>"></script>
    <script src="<?php _e_asset_url('js/jquery-modal-video.min.js'); ?>"></script>

    <script type="text/javascript">
        // $(".over-scroll").scrollLeft(2000);
        $(".over-scroll").on("touchstart , scroll", function(){
            $(this).find(".scroll-icon").fadeOut(600);
        });


        new ScrollHint('.js-scrollable', {
            i18n: {
                scrollable: 'スクロールできます'
            }
        });

        $('.slider').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            dots: true,
            prevArrow: '<img src="<?php _e_asset_url('images/sp/arrow_l.svg'); ?>" class="slide-arrow prev-arrow">',
            nextArrow: '<img src="<?php _e_asset_url('images/sp/arrow_r.svg'); ?>" class="slide-arrow next-arrow">'
        });

        $(".js-modal-video").each(function() {
            const videoId = $(this).data("video-id");
                    $(this).modalVideo({
                    channel: "youtube",
                    youtube: {
                    autoplay: 1,
                    mute: 1,
                    loop: 1,
                    playlist: videoId // ここで個別のIDを渡す
                }
            });
        });
    </script>

    <script src="//sitest.jp/tracking/sitest_js?p=6285c07f69e46" async></script>

    <?php wp_footer(); ?>
  
</body>
</html>