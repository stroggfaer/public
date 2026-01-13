jQuery(document).ready(function ($) {
    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        let page = $(this).attr('href').split('paged=')[1] || 1;
        const per_page = $(this).parents('#pagination-container').data('per-page');
        console.log(per_page);
        $.ajax({
            url: ajaxpagination.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_posts',
                page: page,
                per_page
                //template: 'home' // Указываем источник (например, главная страница)
            },
            success: function (response) {
                $('#posts-container').html(response);
            }
        });
    });
});
