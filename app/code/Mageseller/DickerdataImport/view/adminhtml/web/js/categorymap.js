define([
    "jquery",
    'Magento_Ui/js/modal/alert',
    "jquery/ui",
], function ($, alert) {
    'use strict';
    $.widget('mage.categorymap', {
        options: {
            confirmMsg: ('divElement is removed.')
        },
        _create: function () {
            var self = this;
            self._init();
            self._bind();
        },
        _init: function () {
            var self = this;
        },
        _bind: function () {
            var self = this;
            var saveCategoryUrl = self.options.save_category_url;
            var useAsAttribute_url = self.options.use_as_attribute_url;
            var deleteCategoryUrl = self.options.delete_category_url;
            var supplierCategoryUrl = self.options.supplier_category_url;
            var alreadyMappedArray = self.options.already_mapped_array;

            $('.supplier-category').find("li a").live('click', function () {
                $("#supplier_input").val($(this).html().trim().replace("&amp;", "&"));
                $("#supplier_input_id").val($(this).data("id"));
            });

            $('.shop-category-body').find("li .shop-category-li").live('click', function () {
                $("#shop_input").val($(this).html().trim().replace("&amp;", "&"));
                $("#shop_input_id").val($(this).data("id"));
            });
            $('#use_as_attribute').live('click', function () {
                var supplierId = $("#supplier_input_id").val().trim();
                var attribute_id = $("#attribute_id").val().trim();
                if(supplierId && attribute_id){
                    $.ajax({
                        url: useAsAttribute_url,
                        data: {
                            "category_id": supplierId,
                            "attribute_id": attribute_id,
                            "form_key": window.FORM_KEY
                        },
                        type: "POST",
                        showLoader: true,
                        success: function (response) {
                            if(response){
                                $('.supplier-category-body').html(response);
                                $("#shop_input").val("");
                                $("#shop_input_id").val("");
                                $("#supplier_input").val("");
                                $("#supplier_input_id").val("");
                            }
                        }
                    });
                }
            });
            $('#submit').live('click', function () {
                var supplier = $("#supplier_input").val().trim();
                var shop = $("#shop_input").val().trim();
                var shopId = $("#shop_input_id").val().trim();
                var supplierId = $("#supplier_input_id").val().trim();

                var flag = 1;

                for (var i = 0; i < alreadyMappedArray.length; i++) {
                    if (alreadyMappedArray[i] == supplier) {
                        flag = 2;
                    }
                }


                if (flag == 2) {
                    $("#shop_input").val("");
                    $("#shop_input_id").val("");
                    $("#supplier_input").val("");
                    $("#supplier_input_id").val("");
                    alert(supplier + " is already mapped");

                } else {
                    if (supplier && shop) {
                        $.ajax({
                            url: saveCategoryUrl,
                            data: {
                                "supplier": supplier,
                                "shop": shop,
                                "sortorder": 0,
                                "shopId": shopId,
                                "supplierId": supplierId,
                                "form_key": window.FORM_KEY
                            },
                            type: "POST",
                            showLoader: true,
                            success: function (response) {
                                $('.shop-category-body').html(response);
                                $.ajax({
                                    url: supplierCategoryUrl,
                                    data: {"supplier": supplier, "shop": shop, "sortorder": 0, "shopId": shopId},
                                    type: "POST",
                                    showLoader: true,
                                    success: function (response) {
                                        $('.supplier-category-body').html(response);
                                        $("#shop_input").val("");
                                        $("#shop_input_id").val("");
                                        $("#supplier_input").val("");
                                        $("#supplier_input_id").val("");
                                        //window.location.reload();
                                    }
                                });
                            }
                        });
                    }
                }
            });
            $('.remove-shop-category').live('click', function () {
                var url = deleteCategoryUrl;
                var shopId = $(this).data("id");
                var supplierId = $(this).data("supplier-id");
                if (shopId) {
                    $.ajax({
                        url: url,
                        data: {"shopId": shopId,"supplierId" : supplierId},
                        type: "POST",
                        showLoader: true,
                        success: function (response) {
                            $('.shop-category-body').html(response);
                            //window.location.reload();
                            $.ajax({
                                url: supplierCategoryUrl,
                                data: {"shopId": shopId},
                                type: "POST",
                                showLoader: true,
                                success: function (response) {
                                    $('.supplier-category-body').html(response);
                                    //window.location.reload();
                                }
                            });
                        }
                    });

                }
            });
        }

    });
    return $.mage.categorymap;
});