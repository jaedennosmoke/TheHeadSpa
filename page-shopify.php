<?php
/**
 * Template Name: Shopify Page
 */

get_header();
?>

<main id="content" class="site-main" role="main">
    <div class="shopify-page-content">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>
</main>

<?php
get_footer();