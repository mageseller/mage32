require(
    ['jquery'], function ($) {
        $(document).ready(
            function () {
                $('.catalog-product-edit .admin__page-nav-link.user-defined > span:first-child').each(
                    function () {
                        if (0 === $(this).text().indexOf('Mageseller')) {
                            $(this).parents('li').addClass('Mageseller');
                        }
                    }
                );
                $('.adminhtml-system-config-edit .admin__page-nav-title > strong').each(
                    function () {
                        if (0 === $(this).text().indexOf('Mageseller')) {
                            $(this).parent().parent().addClass('Mageseller');
                        }
                    }
                );
            }
        );
    }
);