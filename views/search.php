<form role="search" method="get" class="woocommerce-product-search stock-image-search" action="/">
    <label class="screen-reader-text" for="woocommerce-stock-image-search"><?php esc_html(__("Search for images:", \WooStockImageProduct\PLUGIN_NAME)); ?></label>
    <input type="search" id="woocommerce-stock-image-search" class="search-field" placeholder="Search images…" value="<?php the_search_query(); ?>" name="s">
    <button type="submit" value="Search"><?php echo esc_html(__("Search", \WooStockImageProduct\PLUGIN_NAME)); ?></button>
    <input type="hidden" name="post_type" value="product">
    <input type="hidden" name="product_type" value="stock_image_product">
</form>