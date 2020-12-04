/**
 * Common js code
 */

var nmgr = window.nmgr || {};

jQuery(function($) {

    nmgr.selectors = {
        modal: '#nmgr-modal'
    };

    /**
     * Define a common blockUI block setup for plugin
     *
     * @param {string | jQuery object} selector The element to be blocked
     */
    nmgr.block = function(selector) {
        if (!nmgr.is_blocked(selector)) {
            $(selector).addClass('is-blocked').block({
                message: null,
                overlayCSS: {
                    backgroundColor: '#fff'
                },
                css: {
                    backgroundColor: 'transparent',
                    border: 'none'
                }
            });
        }
    };

    /**
     * Define a common blockUI unblock setup for plugin
     *
     * @param {string | jQuery object} selector The element to be unblocked
     */
    nmgr.unblock = function(selector) {
        $(selector).removeClass('is-blocked').unblock();
    };

    /**
     * Check whether an element is currently blocked with blockUI
     *
     * @param {string | jQuery object} selector The element to be checked
     * @returns {jQuery.length|Boolean} True if the element is blocked, false if not
     */
    nmgr.is_blocked = function(selector) {
        return $(selector).is('is-blocked') || $(selector).parents('is-blocked').length;
    };

    /**
     * Scroll to an element
     *
     * @param {string | jQuery object} selector The element to scroll to
     */
    nmgr.scroll_to = function(selector) {
        if ($(selector).length) {
            if (nmgr.is_in_modal(selector)) {
                $('#nmgr-modal').animate({
                    scrollTop: 0
                });
            } else {
                $('html, body').animate({
                    scrollTop: ($(selector).offset().top - 100)
                }, 1000);
            }
        }
    };

    /**
     * Go to a url
     * @param {string} url
     */
    nmgr.go_to = function(url) {
        if (-1 === url.indexOf('https://') || -1 === url.indexOf('http://')) {
            window.location = url;
        } else {
            window.location = decodeURI(url);
        }
    };

    /**
     * Show a woocommerce notice
     *
     * @param {string} html The woocommerce notice template e.g. error, success html template
     * @param {boolean} show_in_modal whether to show the notice in the modal if the modal is open
     */
    nmgr.show_notice = function(html, show_in_modal) {
        $target = $('.woocommerce-notices-wrapper:first') || $('.nmgr-cart-item-add-to-cart').closest('.woocommerce');

        if (true === show_in_modal && nmgr.is_in_modal()) {
            $target = $(nmgr.selectors.modal).find('.modal-body');
        }

        if ($target.length) {
            nmgr.remove_notice();
            $(html).addClass('nmgr-notice').prependTo($target);
            nmgr.scroll_to($target);
        }
    };

    /**
     * Remove woocommerce notices from the page
     * @returns {undefined}
     */
    nmgr.remove_notice = function(selector) {
        var notice_selectors = '.woocommerce-error, .woocommerce-message, .woocommerce-info';

        if ('undefined' !== typeof(selector)) {
            $(selector).find(notice_selectors).remove();
        } else {
            $(notice_selectors).remove();
        }
    };

    nmgr.refresh_cart_fragments = function() {
        var data = {
            action: 'nmgr_get_cart_fragments'
        };

        $.post(nmgr_global_params.ajax_url, data, function(response) {
            if (response && response.fragments) {
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
            }
        });
    };

    /**
     * Make a table responsive when it's width is above a certain size.
     *
     * The responsiveness in this case is for desktop display rather than mobile.
     * By default, the table should already be mobile responsive.
     *
     * @param {string} selector The table element to make responsive
     * @param {int} width Minimum width at which table would be responsive. Default 700px
     */
    nmgr.make_table_responsive = function(selector, width) {
        width = width || 600;
        selector = selector || '.nmgr-table';

        var table = $(selector);
        if (table.width() > width) {
            table.addClass('responsive');
        } else {
            table.removeClass('responsive');
        }
    };

    nmgr.tiptip = function() {
        $('.nmgr-tip').tipTip();

        $('svg.nmgr-tip').each(function() {
            var $element = $(this),
                title = $element.find('title');

            if (!title.length) {
                return;
            }

            $element.tipTip({
                content: function() {
                    if (title.length) {
                        if (title.attr('data-title')) {
                            title.text('');
                            return title.attr('data-title');
                        }
                    }
                }
            });
        });
    };

    nmgr.tiptip();


    nmgr.modal = {};

    // Initialize bootstrap native modal
    if (document.querySelector(nmgr.selectors.modal)) {
        nmgr.modal = new BSN.Modal(nmgr.selectors.modal);
    }

    nmgr.modal.data = {};

    // Check if we are in a modal window
    nmgr.is_in_modal = function(element) {
        if (element) {
            return $(nmgr.selectors.modal).hasClass('show') && $(element).closest(nmgr.selectors.modal).length > 0;
        } else {
            return $(nmgr.selectors.modal).hasClass('show');
        }
    };

    // Check if the modal has an element
    nmgr.modal.has_content = function(selector) {
        return $(nmgr.selectors.modal).find(selector).length > 0;
    };

    /**
     * Show or update the modal
     *
     * We need to do this to preserve the event bindings on the modal.
     * If we show the modal when it is already being shown, all the elements such as the close button
     * and the backdrop would lose their event bindings. So in this case we need to update the modal
     * instead of showing it.
     * As a rule, always update the modal if it is already being shown. And only show the modal if it is currently hidden.
     */
    nmgr.modal.show_or_update = function() {
        if (nmgr.is_in_modal()) {
            /**
             * The 'update.bs.modal' event is a custom event created by us. It doesn'e exist
             * in the BSN.Modal class.
             */
            $(nmgr.selectors.modal).first().trigger('update.bs.modal');
            nmgr.modal.update();
        } else {
            /**
             * Although the modal selector '#nmgr-modal' has the 'show.bs.modal' event,
             * we are triggering this event also on the jquery object of the selector to provide the same
             * api consistency with the 'update.bs.modal' event on the same selector created by us.
             */
            $(nmgr.selectors.modal).first().trigger('show.bs.modal');
            nmgr.modal.show();
        }
    };

    nmgr.modal.set_content = function(content) {
        nmgr.modal.setContent(content);
        $(nmgr.selectors.modal).first().trigger('set_content.bs.modal');
    };

    nmgr.init_datepicker = function(idselector) {
        idselector = idselector || '#nmgr_event_date';
        var datepickerClass = 'nmgr-datepicker';
        var datepickerOptions = {
            dateFormat: nmgr_global_params.date_format
        };

        if (nmgr_global_params.style_datepicker) {
            datepickerOptions = Object.assign(datepickerOptions, {
                beforeShow: function(input, inst) {
                    if (inst.id === idselector.replace(/^#/, '')) {
                        inst.dpDiv.addClass(datepickerClass);
                    }
                },
                onClose: function(dateText, inst) {
                    if (inst.id === idselector.replace(/^#/, '')) {
                        inst.dpDiv.removeClass(datepickerClass);
                    }
                }
            });
        }

        $(idselector).datepicker(datepickerOptions);
    };


    nmgr.stupidtable = function(selector) {
        $(selector).stupidtable();
        $(selector).on('aftertablesort', this.add_arrow);
        $(selector).find('th').on('mouseenter', this.show_default_arrow);
        $(selector).find('th').on('mouseleave', this.remove_default_arrow);
    };

    nmgr.stupidtable.prototype.add_arrow = function(event, data) {
        var th = $(this).find('th');
        var arrow = data.direction === 'asc' ? '&uarr;' : '&darr;';
        var index = data.column;
        th.find('.nmgr-arrow').remove();
        th.eq(index).append('<span class="nmgr-arrow">' + arrow + '</span>');
    };

    nmgr.stupidtable.prototype.show_default_arrow = function() {
        if ($(this).hasClass('sortable') && $(this).find('.nmgr-arrow').length === 0) {
            $(this).append('<span class="nmgr-arrow hover">&uarr;</span>');
        }
    };

    nmgr.stupidtable.prototype.remove_default_arrow = function() {
        $(this).find('.nmgr-arrow.hover').remove();
    };




});


jQuery(function($) {

    if (typeof nmgr_admin_params === 'undefined') {
        return false;
    }

    /**
     * Js events related to wishlist items template
     */


    var nmgr_items = {
        container: '#nmgr-items',

        wishlist: {},

        init: function() {
            new nmgr.stupidtable('.nmgr-items-table');
            nmgr.make_table_responsive('.nmgr-items-table');
            this.checkValidation();

            $(window).on('resize', function() {
                nmgr.make_table_responsive('.nmgr-items-table');
            });

            $(document.body)
                .on('change', '#nmgr-items td.quantity input.quantity', this.quantity_changed)
                .on('click', '#nmgr-items a.edit-wishlist-item', this.edit_item)
                .on('click', '#nmgr-items a.delete-wishlist-item', this.delete_item)
                .on('click', '#nmgr-items button.save-action', this.save_items)
                .on('click', '#nmgr-items .nmgr-add-items-action', this.add_items_action)
                .on('change', '.nmgr-product-search', this.new_add_items_row)
                .on('click', '.nmgr-add-items .nmgr-add', this.add_items)
                .on('nmgr_wishlist_created nmgr_shipping_reloaded', this.reload)
                .on('removed_from_cart', nmgr.remove_notice)
                .on('click', '.nmgr_add_to_cart_button', this.add_to_cart)
                .on('nmgr_added_to_cart nmgr_removed_from_wishlist', nmgr.refresh_cart_fragments)
                .on('nmgr_tab_shown', this.maybe_make_table_responsive);
        },

        reload: function(e, wishlist) {
            var this_wishlist_id = parseInt($(nmgr_items.container).attr('data-wishlist-id')),
                wishlist_id = ('undefined' !== typeof wishlist) && wishlist.id ? wishlist.id : this_wishlist_id;

            if (0 !== this_wishlist_id && ('undefined' !== typeof wishlist) && wishlist.id && this_wishlist_id !== parseInt(wishlist.id)) {
                return;
            }

            var data = {
                action: 'nmgr_load_items',
                wishlist_id: parseInt(wishlist_id),
                nmgr_global: JSON.stringify(nmgr_global_params.global),
                _wpnonce: $(nmgr_items.container).attr('data-nonce')
            };

            nmgr.block(nmgr_items.container);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                $(nmgr_items.container).replaceWith(response);
                nmgr_items.wishlist = wishlist;
                nmgr_items.reloaded();
            });
        },

        reloaded: function() {
            $(document.body).trigger('nmgr_items_reloaded', this.wishlist);
            new nmgr.stupidtable('.nmgr-items-table');
            this.checkValidation();
            nmgr.make_table_responsive('.nmgr-items-table');
        },

        checkValidation: function() {
            $('#nmgr-items input').on('invalid', function() {
                $(this).closest('tr').find('.edit').show();
                $(this).closest('tr').find('.view').hide();
                $('#nmgr-items button.save-action').attr('data-reload', true);
            });
        },

        quantity_changed: function() {
            var $row = $(this).closest('tr.item');
            var qty = $(this).val();
            var item_total = $('input.item_total', $row);
            var item_cost = $('.cost', $row).attr('data-sort-value');
            item_total.val(item_cost * qty);
        },

        edit_item: function() {
            $(this).closest('tr').find('.view').toggle();
            $(this).closest('tr').find('.edit').toggle();
            $('#nmgr-items button.save-action').attr('data-reload', true);
            return false;
        },

        delete_item: function() {
            var answer = window.confirm(nmgr_global_params.i18n_delete_item_text);
            if (answer) {

                nmgr.block(nmgr_items.container);

                var $item = $(this).closest('tr.item'),
                    data = {
                        wishlist_item_ids: $item.attr('data-wishlist_item_id'),
                        action: 'nmgr_delete_item',
                        wishlist_id: $(nmgr_items.container).attr('data-wishlist-id'),
                        _wpnonce: $(nmgr_items.container).attr('data-nonce'),
                        nmgr_global: JSON.stringify(nmgr_global_params.global)
                    };

                // Check if items have changed, if so pass them through so we can save them before adding a new item.
                if ('true' === $('#nmgr-items button.save-action').attr('data-reload')) {
                    data.items = $('.nmgr-items-table :input[name]').serialize();
                }

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    if (response.success) {
                        nmgr_items.wishlist = response.data.wishlist;
                        $(nmgr_items.container).replaceWith($(response.data.html));
                        nmgr_items.reloaded();
                        $(document.body).trigger('nmgr_removed_from_wishlist');
                    } else if (response.error) {
                        window.alert(response.data.notice);
                    }
                });
            }
            return false;
        },

        save_items: function() {
            var data = {
                items: $('.nmgr-items-table :input[name]').serialize(),
                action: 'nmgr_save_items',
                wishlist_id: $(nmgr_items.container).attr('data-wishlist-id'),
                _wpnonce: $(nmgr_items.container).attr('data-nonce'),
                nmgr_global: JSON.stringify(nmgr_global_params.global)
            };

            nmgr.block(nmgr_items.container);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                if (response.success) {
                    nmgr_items.wishlist = response.data.wishlist;
                    $(nmgr_items.container).replaceWith($(response.data.html));
                    nmgr_items.reloaded();
                }
                nmgr.unblock(nmgr_items.container);
            });
            return false;
        },

        add_items_action: function() {
            if ($(this).attr('data-url')) {
                nmgr.go_to($(this).attr('data-url'));
            } else if ($(this).attr('data-target')) {
                // Initialize bootstrap native modal
                new BSN.Modal($(this).attr('data-target')).show();

                // reset the modal content form
                nmgr_items.reset_add_items_form($(this).attr('data-target'));

                // initialize select2 on the select field in the modal
                $(document.body).trigger('nmgr-select2-init');
            }
        },

        reset_add_items_form: function(element) {
            if ('undefined' !== typeof element) {
                $(element).find('tr.added-row').remove();
                $(element).find('form').trigger('reset').find(':input').each(function() {
                    if ($(this).is('.nmgr-product-search')) {
                        $(this).val('');
                    }
                });
            }
        },

        new_add_items_row: function() {
            if (!$(this).closest('tr').is(':last-child')) {
                return;
            }

            var item_table = $(this).closest('table'),
                item_table_body = item_table.find('tbody'),
                index = item_table_body.find('tr').length,
                row = item_table_body.data('row').replace(/\[0\]/g, '[' + index + ']');

            item_table_body.append('<tr class="added-row">' + row + '</tr>');

            $(document.body).trigger('nmgr-select2-init');
        },

        add_items: function() {
            var table_body = $(this).closest('.nmgr-add-items').find('table tbody'),
                rows = table_body.find('tr'),
                add_items = [];

            $(rows).each(function() {
                var item_id = $(this).find(':input[name="item_id"]').val(),
                    item_qty = $(this).find(':input[name="item_qty"]').val(),
                    item_fav = $(this).find(':input[name="item_fav"]').val();

                add_items.push({
                    'id': item_id,
                    'qty': item_qty ? item_qty : 1,
                    'fav': item_fav
                });
            });

            var modal_id = '#' + $(this).closest('.modal').attr('id');
            // Initializing the modal if it is already open hides it. We don't need to explicitly call hide()
            new BSN.Modal(modal_id);

            nmgr.block(nmgr_items.container);

            var data = {
                action: 'nmgr_add_item',
                wishlist_id: $(nmgr_items.container).attr('data-wishlist-id'),
                nmgr_global: JSON.stringify(nmgr_global_params.global),
                data: add_items
            };

            // Check if items have changed, if so pass them through so we can save them before adding a new item.
            if ('true' === $('#nmgr-items button.save-action').attr('data-reload')) {
                data.items = $('.nmgr-items-table :input[name]').serialize();
            }

            $.ajax({
                type: 'POST',
                url: nmgr_global_params.ajax_url,
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(nmgr_items.container).replaceWith(response.data.html);
                        nmgr_items.reloaded();
                    } else {
                        nmgr.unblock(nmgr_items.container);
                        window.alert(response.data.error);
                    }
                }
            });
        },

        add_to_cart: function(e) {
            var $thisbutton = $(this),
                $form = $thisbutton.closest('.nmgr-add-to-cart-form');
            /**
             *  If we are not adding to cart via ajax, we are supposed to return here
             *  but we will only return if adding the wishlist item to the cart is not restricted.
             *  Otherwise we will continue into the ajax code below in order to retrieve the
             *  restricted notice set during the add-to-cart validation
             */
            if (!$thisbutton.is('.nmgr_ajax_add_to_cart')) {
                return true;
            }

            e.preventDefault();

            var data = {
                action: 'nmgr_add_to_cart'
            };

            var formdata = $form.serializeArray();

            $(formdata).each(function(index, obj) {
                data[obj.name] = obj.value;
            });

            $thisbutton.removeClass('added');
            $thisbutton.addClass('loading');

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                if (!response) {
                    return;
                }

                $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
                $thisbutton.removeClass('loading');

                if (response.notice) {
                    nmgr.show_notice(response.notice);
                }

                if (response.success) {
                    $(document.body).trigger('nmgr_added_to_cart', [$form]);
                }
            });
        },

        maybe_make_table_responsive: function(e, tab) {
            if ($(tab).is('#nmgr-tab-items')) {
                nmgr.make_table_responsive('.nmgr-items-table');
            }
        }
    };
    nmgr_items.init();

    /**
     * Initializes all select2 elements used by plugin and retrieves their data
     */

    var nmgr_select2 = {
        init: function() {
            try {
                $(document.body)
                    .on('nmgr-select2-init', this.activate_select2)
                    .trigger('nmgr-select2-init');
            } catch (e) {
                window.console.log(e);
            }
        },

        activate_select2: function() {
            nmgr_select2.select2_user_search();
            nmgr_select2.select2_product_search();
        },

        select2_product_search: function() {
            $(':input.nmgr-product-search').each(function() {
                var select2_args = {
                    allowClear: $(this).data('allow_clear') ? true : false,
                    placeholder: $(this).data('placeholder'),
                    minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : '3',
                    escapeMarkup: function(m) {
                        return m;
                    },
                    ajax: {
                        url: nmgr_global_params.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                action: 'nmgr_json_search_products',
                                security: nmgr_admin_params.search_products_nonce,
                                exclude: $(this).data('exclude'),
                                exclude_type: $(this).data('exclude_type'),
                                include: $(this).data('include'),
                                limit: $(this).data('limit'),
                                display_stock: $(this).data('display_stock'),
                            };
                        },
                        processResults: function(data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function(id, text) {
                                    terms.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    }
                };

                $(this).select2(select2_args);

            });
        },

        select2_user_search: function() {
            $(':input.nmgr-user-search').each(function() {
                var select2_args = {
                    allowClear: $(this).data('allow_clear') ? true : false,
                    placeholder: $(this).data('placeholder'),
                    minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : '1',
                    escapeMarkup: function(m) {
                        return m;
                    },
                    ajax: {
                        url: nmgr_global_params.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                action: 'nmgr_json_search_users',
                                security: nmgr_admin_params.search_users_nonce,
                                exclude: $(this).data('exclude')
                            };
                        },
                        processResults: function(data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function(id, text) {
                                    terms.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    }
                };

                $(this).select2(select2_args);

            });
        }
    };

    nmgr_select2.init();
    var ship_to_account_address = {

        selector: {
            ship_to: '#nmgr_ship_to_account_address',
            user_id: '#nmgr_user_id'
        },

        init: function() {
            if (typeof nmgr_admin_params === 'undefined') {
                return false;
            }

            this.maybe_hide_ship_to_account_address_field();
            this.toggle_fieldsets();

            $(this.selector.user_id).on('change', this.maybe_hide_ship_to_account_address_field);
            $(this.selector.ship_to).on('change', this.maybe_toggle_fieldsets);
        },

        maybe_hide_ship_to_account_address_field: function() {
            if (isNaN($(ship_to_account_address.selector.user_id).val())) {
                $(ship_to_account_address.selector.ship_to).prop('disabled', true);
            } else {
                $(ship_to_account_address.selector.ship_to).prop('disabled', false);
            }
        },

        maybe_toggle_fieldsets: function() {
            if ($(this).is(':checked')) {
                if (!$(ship_to_account_address.selector.user_id).val()) {
                    alert(nmgr_admin_params.i18n_select_user);
                    $(this).prop('checked', false);
                    return false;
                } else {
                    if (!window.confirm(nmgr_admin_params.i18n_use_account_shipping_address_text)) {
                        $(this).prop('checked', false);
                        return false;
                    }
                }
            }
            ship_to_account_address.toggle_fieldsets();
        },

        toggle_fieldsets: function() {
            if ($(this.selector.ship_to).is(':checked')) {
                $('.account-shipping-address').slideDown();
                $('.wishlist-shipping-address').hide();
            } else {
                $('.account-shipping-address').slideUp();
                $('.wishlist-shipping-address').slideDown();
            }
        }
    };

    ship_to_account_address.init();

    nmgr.init_datepicker();

    /**
     * Use selectWoo to select the shipping country and state the woocommerce way in admin
     * This code is simply a modified version of the same code woocommerce uses to select shipping
     * country and state in the order screen. Modified from meta-boxes-order.js in woocommerce plugin.
     */
    var shipping_country_state = {
        states: null,

        init: function() {
            if (!(typeof nmgr_admin_params === 'undefined' || typeof nmgr_admin_params.countries === 'undefined')) {
                this.states = $.parseJSON(nmgr_admin_params.countries.replace(/&quot;/g, '"'));
            }

            $('.js_field-country').selectWoo().change(this.change_country);
            $('.js_field-country').trigger('change', [true]);
            $(document.body).on('change', 'select.js_field-state', this.change_state);
        },

        change_country: function(e, stickValue) {
            // Check for stickValue before using it
            if (typeof stickValue === 'undefined') {
                stickValue = false;
            }

            // Prevent if we don't have the metabox data
            if (shipping_country_state.states === null) {
                return;
            }

            var $this = $(this),
                country = $this.val(),
                $state = $this.parents('div.nmgr-shipping-fields').find(':input.js_field-state'),
                $parent = $state.parent(),
                input_name = $state.attr('name'),
                input_id = $state.attr('id'),
                value = $this.data('woocommerce.stickState-' + country) ? $this.data('woocommerce.stickState-' + country) : $state.val(),
                placeholder = $state.attr('placeholder'),
                $newstate;

            if (stickValue) {
                $this.data('woocommerce.stickState-' + country, value);
            }

            // Remove the previous DOM element
            $parent.show().find('.select2-container').remove();

            if (!$.isEmptyObject(shipping_country_state.states[country])) {
                var state = shipping_country_state.states[country],
                    $defaultOption = $('<option value=""></option>')
                    .text(nmgr_admin_params.i18n_select_state_text);

                $newstate = $('<select></select>')
                    .prop('id', input_id)
                    .prop('name', input_name)
                    .prop('placeholder', placeholder)
                    .addClass('js_field-state select short')
                    .append($defaultOption);

                $.each(state, function(index) {
                    var $option = $('<option></option>')
                        .prop('value', index)
                        .text(state[index]);
                    $newstate.append($option);
                });

                $newstate.val(value);

                $state.replaceWith($newstate);

                $newstate.show().selectWoo().hide().change();
            } else {
                $newstate = $('<input type="text" />')
                    .prop('id', input_id)
                    .prop('name', input_name)
                    .prop('placeholder', placeholder)
                    .addClass('js_field-state')
                    .val('');
                $state.replaceWith($newstate);
            }
        },

        change_state: function() {
            // Here we will find if state value on a select has changed and stick it to the country data
            var $this = $(this),
                state = $this.val(),
                $country = $this.parents('div.nmgr-shipping-fields').find(':input.js_field-country'),
                country = $country.val();

            $country.data('woocommerce.stickState-' + country, state);
        }
    };

    shipping_country_state.init();

    /**
     * If the post author is a guest, set it in the author column of the admin list table
     */
    function set_post_author_guest() {
        var cols = document.querySelectorAll('tr.type-nm_gift_registry td.column-author');
        if (cols.length) {
            for (var i = 0; i < cols.length; i++) {
                if (!cols[i].innerText) {
                    cols[i].innerHTML = '<div class="nmgr-post-author">' + nmgr_admin_params.i18n_guest_text + '</div>';
                }
            }
        }
    }

    set_post_author_guest();

});