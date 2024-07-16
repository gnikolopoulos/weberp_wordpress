(function( $ ) {
    'use strict';

    $(function() {
        $('.single-product .mkdf-single-product-summary').on('click', 'a.check_live_stock', function(e) {
            e.preventDefault();

            let product_id = $(this).attr('data-product-id');
            let nonce = jQuery(this).attr('data-nonce');
            let t = document.querySelector('#stock_table_row');

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: weberp.ajaxurl,
                data: {
                    action: 'get_live_stock',
                    product_id: product_id,
                    nonce: nonce
                },
                success: function(response) {
                    if(response) {
                        document.getElementById('stock_table').style.display = 'table';
                        document.getElementById('stock_table').innerHTML = '<thead><tr><th>Κατάστημα</th><th>Ποσότητα</th></tr></thead>';

                        $.each(response, function(index, item) {
                            t.content.querySelector('.warehouse').textContent = item.warehouse;
                            t.content.querySelector('.qty').textContent = item.stock;

                            let clone = document.importNode(t.content, true);
                            document.getElementById('stock_table').append(clone);
                        });
                    }
                }
            });
        });
    });

})( jQuery );
