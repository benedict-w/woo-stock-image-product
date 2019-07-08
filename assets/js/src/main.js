/**
 * Stock Image Cropper
 *
 */
(function($) {

    $(function() {

        var woo_stock_image_search = function(settings) {

            // change window location on stock-image-product-select change
            $('#stock-image-product-select').on('change', function() {
                window.location.href = $(this).val();
            });

            // on product page
            if (settings.ratios) {

                // TODO php add id to featured image template
                var img = '.product-type-stock_image_product .woocommerce-product-gallery img.wp-post-image';

                // add inputs for saving crop data
                $('.variations_form')
                    .append('<input type="hidden" name="crop-max-width" value="">')
                    .append('<input type="hidden" name="crop-max-height" value="">')
                    .append('<input type="hidden" name="crop-width" value="">')
                    .append('<input type="hidden" name="crop-height" value="">')
                    .append('<input type="hidden" name="crop-x" value="0">')
                    .append('<input type="hidden" name="crop-y" value="0">');

                var update_data = function () {

                    if (croppr) {

                        var data = croppr.getValue();

                        var $img = $('.croppr-image');

                        $('input[name=crop-max-width]').val($img.width());
                        $('input[name=crop-max-height]').val($img.height());
                        $('input[name=crop-width]').val(data.width);
                        $('input[name=crop-height]').val(data.height);
                        $('input[name=crop-x]').val(data.x);
                        $('input[name=crop-y]').val(data.y);

                    }

                };

                var options = {
                    onInitialize: function () {
                        update_data();
                    },
                    onCropMove: function (data) {
                        update_data();
                    },
                    onCropEnd: function (data) {
                        update_data();
                    }
                };

                var selected_id = $('input[name=product_id]').val() || $('input[name=variation_id]').val();

                if (typeof(settings.ratios[selected_id]) !== 'undefined') {
                    options['aspectRatio'] = settings.ratios[selected_id];
                }

                var croppr = new Croppr(img, options);

                $(".variations_form").on('woocommerce_variation_has_changed', function () {

                    croppr.destroy();

                    options['aspectRatio'] = settings.ratios[$('input[name=variation_id]').val()] || null;

                    croppr = new Croppr(img, options);

                    update_data();

                });
            }

            // on cart, checkout page, etc.
            if (settings.items) {

                var canvas = document.createElement('canvas');

                var crop = function($img, crop_data) {

                    if ($img.length) {

                        var img = new Image();

                        img.onload = function() {

                            var scale_width = crop_data.max_width ? (img.naturalWidth / crop_data.max_width) : 1;
                            var scale_height = crop_data.max_height ? (img.naturalHeight / crop_data.max_height) : 1;

                            // scale width, height
                            var width = crop_data.width * scale_width;
                            var height = crop_data.height * scale_height;

                            canvas.width = width;
                            canvas.height = height;

                            // scale x, y
                            var x = crop_data.x * scale_width;
                            var y = crop_data.y * scale_height;

                            var ctx = canvas.getContext('2d');

                            ctx.drawImage(img, x, y, width, height, 0, 0, width, height);
                            $img.prop('src', canvas.toDataURL());
                            $img.addClass('cropped');
                        };

                        img.src = $img.prop('src');
                    }

                };

                for (var i in settings.items) {

                    var rows = $('table.shop_table tr.cart_item:eq(' + settings.items[i].index + ') img');

                    if (!rows.length) {
                        rows = $('table.shop_table tr.order_item:eq(' + settings.items[i].index + ') img');
                    }

                    crop(rows, settings.items[i]);
                }

                var backup = null;

                // on checkout page refresh crop after AJAX update
                $( document.body )
                    .on( 'updated_checkout', function() {
                        for (var i in settings.items) {
                            crop($('table.shop_table tr.cart_item:eq(' + settings.items[i].index + ') img'), settings.items[i]);
                        }
                    })
                    // when a cart item is removed also remove the corresponding to the setting item data.
                    .on(
                        'click',
                        '.woocommerce-cart-form .product-remove > a',
                        function() {
                            backup = settings.items;
                            var $self = $(this);
                            var index = $self.parent('table.cart').find('tr.cart_item').index($self.parent('tr.cart_item'));
                            settings.items.splice(index, 1);
                        })
                    // restore (Undo) button
                    .on(
                        'click',
                        '.woocommerce-cart .restore-item',
                        function() {
                            settings.items = backup;
                        });
            }

        } (stock_image_cropper_settings);

    });


}(jQuery));