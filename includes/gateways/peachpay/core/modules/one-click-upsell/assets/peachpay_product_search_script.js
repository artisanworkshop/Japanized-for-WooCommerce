/**
 * Referenced https://stackoverflow.com/questions/30973651/add-product-search-field-in-woo-commerce-product-page
 */
(function ($) { 
    $(function(){ 
        $('.pp-product-search').select2({ 
            ajax: { 
                url: ajaxurl, 
                data: function (params) { 
                    return { 
                        term : params.term, 
                        action : 'woocommerce_json_search_products_and_variations', 
                        security: $(this).attr('data-security'), 
                        exclude_type : 'variable',
                    }; 
                }, 
                processResults: function( data ) { 
                    const terms = []; 
                    if ( data ) { 
                        $.each( data, function( id, text ) { 
                            terms.push( { id: id, text: text } ); }); 
                        } 
                        return { 
                            results: terms 
                        }; 
                },
                 cache: true 
            },
            minimumInputLength: 3,
            placeholder: 'Search for a product',
            allowClear: true
        }); 
    });

    $(function(){ 
        $('.pp-pr-search').select2({ 
            ajax: { 
                url: ajaxurl, 
                data: function (params) { 
                    return { 
                        term : params.term, 
                        action : 'woocommerce_json_search_products_and_variations', 
                        security: $(this).attr('data-security'), 
                        exclude_type : 'variable',
                    }; 
                }, 
                processResults: function( data ) { 
                    const terms = []; 
                    if ( data ) { 
                        $.each( data, function( id, text ) { 
                            terms.push( { id: id, text: text } ); }); 
                        } 
                        return { 
                            results: terms 
                        }; 
                },
                cache: true 
            },
            minimumInputLength: 3,
            placeholder: 'Search for a product',
            width: '50%',
        });
    });

    $(function(){ 
        $('.pp-display-product-search').select2({ 
            ajax: { 
                url: ajaxurl, 
                data: function (params) { 
                    return { 
                        term : params.term, 
                        action : 'woocommerce_json_search_products_and_variations', 
                        security: $(this).attr('data-security'), 
                    }; 
                }, 
                processResults: function( data ) { 
                    const terms = []; 
                    if ( data ) { 
                        $.each( data, function( id, text ) { 
                            terms.push( { id: id, text: text } ); }); 
                        } 
                        return { 
                            results: terms 
                        }; 
                },
                 cache: true 
            },
            minimumInputLength: 3,
            placeholder: 'Search for a product',
        }); 
    });
})(jQuery)
