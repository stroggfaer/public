jQuery(document).ready(function ($) {
    console.log(jQuery);
    // Инициализация select2
    $('#selected_products').select2({
        ajax: {
            url: ajaxData.ajax_url,
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_products',
                    nonce: ajaxData.nonce,
                    term: params.term, // Текст для поиска
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function (product) {
                        return {
                            id: product.id,
                            text: product.name,
                        };
                    }),
                };
            },
            cache: true,
        },
        placeholder: 'Введите название товара',
        minimumInputLength: 2,
    });
});
