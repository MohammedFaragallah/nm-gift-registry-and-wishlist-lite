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

    if (typeof nmgr_frontend_params === 'undefined') {
        return false;
    }

    /*
     * Account tabs
     */

    var nmgrTab = function(element) {
        this.container = $(element);
        this.tabSelectedClass = "nmgr-tab-selected";
        this.tabHoverClass = "nmgr-tab-hover";
        this.tabSelector = this.container.find('.nmgr-tab');
        this.anchorSelector = this.tabSelector.find(' > a');
        this.tabContentSelector = this.container.find('.nmgr-tab-content');
        this.tabContentVisibleClass = 'nmgr-visible';

        if (!this.container.length) {
            return;
        }

        this.showDefaultContent();

        this.tabSelector.on('mouseenter', {
            nmgrTab: this
        }, this.onTabMouseEnter);
        this.tabSelector.on('mouseleave', {
            nmgrTab: this
        }, this.onTabMouseLeave);
        this.anchorSelector.on('click', {
            nmgrTab: this
        }, this.onAnchorClick);
        $(window).on('hashchange', {
            nmgrTab: this
        }, this.onHashChange);
    };

    nmgrTab.prototype.showDefaultContent = function() {
        if (this.getHash()) {
            this.showContent(this.getHash());
        } else {
            this.showContent($(this.tabSelector).first());
        }
    };

    nmgrTab.prototype.onHashChange = function(e) {
        var hash = e.data.nmgrTab.getHash();
        e.data.nmgrTab.showContent(hash);
        nmgr.scroll_to(hash);
    };

    nmgrTab.prototype.getHash = function() {
        var hash = window.location.hash;
        return (hash.toLowerCase().indexOf('nmgr-') >= 0) ? hash : false;
    };

    nmgrTab.prototype.onTabMouseEnter = function(e) {
        $(this).addClass(e.data.nmgrTab.tabHoverClass);
    };

    nmgrTab.prototype.onTabMouseLeave = function(e) {
        $(this).removeClass(e.data.nmgrTab.tabHoverClass);
    };

    nmgrTab.prototype.onAnchorClick = function(e) {
        e.preventDefault();
        e.data.nmgrTab.showContent($(this).parent());
    };

    nmgrTab.prototype.showContent = function(tabElement) {
        if ('undefined' === $(tabElement) || !$(tabElement).is(this.tabSelector) || !$(tabElement).length) {
            tabElement = $(this.tabSelector).first();
        }

        var $link = $(tabElement).find('a').attr('href');

        $(this.tabSelector).removeClass(this.tabSelectedClass);
        $(this.tabContentSelector).removeClass(this.tabContentVisibleClass).hide();

        $(tabElement).addClass(this.tabSelectedClass);
        this.container.find($link).addClass(this.tabContentVisibleClass).show();

        /**
         * Trigger event when a tab's content is shown
         *
         * @param jQuery object|string $tab The tab element header clicked
         */
        $(document.body).trigger('nmgr_tab_shown', [tabElement]);

    };

    // Initialize tabs on page load
    nmgr.account_tabs = new nmgrTab('#nmgr-account-tabs');

    // Initialize tabs when added to modal
    $(nmgr.selectors.modal).on('set_content.bs.modal', function() {
        nmgr.account_tabs = new nmgrTab('#nmgr-account-tabs');
    });

    $('.nmgr-go-to-tab').on('click', function(e) {
        var href = $(this).attr('href');
        if (href.toLowerCase().indexOf('#nmgr-tab') >= 0) {
            e.preventDefault();
            nmgr.scroll_to(href);
            nmgr.account_tabs.showContent(href);
        }
    });
    /**
     * Handles adding a product to the wishlist
     * either normally or via ajax
     */

    jQuery(function($) {
        var selector = {
            wrapper: '.nmgr-add-to-wishlist-wrapper',
            form: '.nmgr-add-to-wishlist-form',
            add_to_wishlist_btn: '.nmgr-add-to-wishlist-button',
            dialog_submit_btn: '.nmgr-dialog-submit-button',
            dialog_form: '.nmgr-add-to-wishlist-content'
        };

        var add_to_wishlist = {
            init: function() {
                $(document.body)
                    .on('click', selector.add_to_wishlist_btn, this.prepare_to_add)
                    .on('click', selector.dialog_submit_btn, this.dialog_submit_btn_clicked)
                    .on('nmgr_removed_from_wishlist', this.removed_from_wishlist)
                    .on('nmgr_wishlist_created nmgr_profile_reloaded nmgr_shipping_reloaded', this.set_add_to_wishlist_wishlist_id)
                    .on('nmgr_profile_reloaded  nmgr_shipping_reloaded', this.enable_dialog_submit_btn);
            },

            /**
             * Events to perform after removing a product from the wishlist
             * Response object should contain
             * - wishlist_item_id
             * - wishlist_id
             * - product_id
             * - product_in_wishlist (This is an integer value, 0 or 1, representing whether the removed product is in
             * any of the user's wishlists)
             */
            removed_from_wishlist: function(e, response) {
                // Remove any 'view wishlists' button present in the page
                $(selector.wrapper).find('.nmgr-view-wishlist-button').remove();

                /**
                 * Remove the 'product-in-wishlist' icon from each add to wishlist form element of the product that has it.
                 *
                 * This doesn't work for grouped products at the moment becuase of how woocommerce handles grouped
                 * products. It is difficult to track a grouped product from its children. But it is only possible to track the
                 * children from the grouped product itself.
                 */
                var form = $('.nmgr-add-to-wishlist-form-' + response.product_id);
                if (form.length > 0) {
                    if (!response.product_in_wishlist) {
                        // remove the in-wishlist icon on the form button
                        form.removeClass('product-in-wishlist');
                    }
                }
            },

            /**
             * Show notice on the page
             *
             * @param {string} html The notice html.
             * @param {jQuery oject} form The form element containing the add to wishlist button.
             */
            show_notice: function(html, form) {
                $target = $('.woocommerce-notices-wrapper:first') || $('.nmgr-add-to-wishlist-button').closest('.woocommerce');
                if ($target.length) {
                    $target.prepend(html);
                    $('html, body').animate({
                        scrollTop: ($target.offset().top - 100)
                    }, 1000);
                } else {
                    form.closest(selector.wrapper).after(html);
                }
            },
            /**
             * Check if a product should be added to a wishlist when the add to wishlist button is clicked on the page
             *
             * @param {object} e Event
             */
            prepare_to_add: function(e) {
                e.preventDefault();

                var $thisbutton = $(this);
                var $form = $thisbutton.parent(selector.form);
                var $wishlist_input = $form.find('input[name=nmgr_wid]');

                // Deal with redirects immediately
                if ($form.is('.redirect')) {
                    $form.submit();
                    return;
                }

                // Remove any 'view wishlists' button present in the page
                $(selector.wrapper).find('.nmgr-view-wishlist-button').remove();

                // Deal with disabled status
                if ($thisbutton.is('.disabled')) {
                    // Deal with variation form
                    if ($thisbutton.is('.wc-variation-is-unavailable')) {
                        alert(nmgr_frontend_params.i18n_unavailable_text);
                    } else if ($thisbutton.is('.wc-variation-selection-needed')) {
                        alert(nmgr_frontend_params.i18n_make_a_selection_text);
                    }
                    return;
                }

                nmgr.modal.data.formdata = $form.serializeArray();

                $(nmgr.modal.data.formdata).each(function(index, obj) {
                    nmgr.modal.data[obj.name] = obj.value;
                });

                if ('auto' === $form.attr('data-create_wishlist')) {
                    // If we are supposed to create a new wishlist for the user, create it
                    add_to_wishlist.auto_create_wishlist($(this));
                    return;
                } else if ('modal' === $form.attr('data-create_wishlist')) {
                    // If the user is supposed to create a new wishlist, let him do it
                    add_to_wishlist.dialog_create_new_wishlist(e);
                    return;
                } else if ($wishlist_input.val() && 1 === parseInt($wishlist_input.attr('data-shipping-address-required'))) {
                    add_to_wishlist.dialog_set_shipping_address($wishlist_input.val());
                    return;
                } else if ($form.is('.product-type-grouped')) {
                    add_to_wishlist.dialog_add_to_wishlist();
                    return;
                } else {
                    add_to_wishlist.ajax_action($(this));
                    return;
                }

            },

            auto_create_wishlist: function($thisbutton) {
                var data = {
                    action: 'nmgr_auto_create_wishlist',
                    _wpnonce: nmgr_frontend_params.nonce,
                    get_add_to_wishlist_dialog: true,
                    nmgr_global: JSON.stringify(nmgr_global_params.global)
                };

                $(nmgr.modal.data.formdata).each(function(index, obj) {
                    data[obj.name] = obj.value;
                });

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    if (response.data.wishlist) {
                        // Remove the flag from creating a default wishlist for the user
                        $(selector.form).attr('data-create_wishlist', '');

                        // update the wishlist inputs
                        $wishlist_input = $('input[name=nmgr_wid');
                        $wishlist_input.val(response.data.wishlist.id);

                        if (nmgr_frontend_params.shipping_address_required && !response.data.wishlist.has_shipping_address) {
                            $wishlist_input.attr('data-shipping-address-required', 1);
                        } else {
                            $wishlist_input.attr('data-shipping-address-required', 0);
                        }

                        $thisbutton.trigger('click');
                    }
                });
            },

            /**
             * Create a new wishlist via the modal
             * @param {object} e Event
             */
            dialog_create_new_wishlist: function(e) {
                e.preventDefault();
                var global_params = JSON.parse(JSON.stringify(nmgr_global_params.global));
                global_params.is_modal = true;

                var data = {
                    action: 'nmgr_dialog_create_new_wishlist',
                    context: 'add_to_wishlist',
                    _wpnonce: nmgr_frontend_params.nonce,
                    nmgr_global: JSON.stringify(global_params)
                };

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    // The expected template here is the template for creating a new wishlist
                    if (response.data.template) {
                        nmgr.modal.set_content(response.data.template);
                        nmgr.modal.show_or_update();
                        nmgr.modal.data.action = 'create_new_wishlist';
                    }
                });
            },

            /**
             * Display the add to wishlist dialog
             *
             * @param {int} product_id The id of the product we want to add to the wishlist
             * @param {int} wishlist_id The id of the wishlist we want to add the product to
             */
            dialog_add_to_wishlist: function() {
                var global_params = JSON.parse(JSON.stringify(nmgr_global_params.global));
                global_params.is_modal = true;

                var data = {
                    action: 'nmgr_dialog_add_to_wishlist',
                    _wpnonce: nmgr_frontend_params.nonce,
                    nmgr_global: JSON.stringify(global_params)
                };

                if ('undefined' !== typeof(nmgr.modal.data.formdata)) {
                    $(nmgr.modal.data.formdata).each(function(index, obj) {
                        data[obj.name] = obj.value;
                    });
                }

                if ('undefined' !== typeof(nmgr.modal.data.nmgr_wid)) {
                    data.nmgr_wid = nmgr.modal.data.nmgr_wid;
                }

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    if (response.data.template) {
                        nmgr.modal.data.action = 'add_to_wishlist';
                        nmgr.modal.set_content(response.data.template);
                        nmgr.modal.show_or_update();
                    }
                });
            },

            // Set this wishlist as the wishlist the user wants to add the product to
            set_add_to_wishlist_wishlist_id: function(e, wishlist) {
                var $wishlist_input = $('input[name="nmgr_wid');

                if ('create_new_wishlist' === nmgr.modal.data.action) {
                    nmgr.modal.data.nmgr_wid = wishlist.id;

                    // Remove the flag from creating a default wishlist for the user
                    $(selector.form).attr('data-create_wishlist', '');

                    // update the wishlist inputs
                    $wishlist_input.val(wishlist.id);
                }

                var actions = ['set_shipping_address', 'create_new_wishlist'];
                if (-1 !== $.inArray(nmgr.modal.data.action, actions)) {
                    if (nmgr_frontend_params.shipping_address_required && !wishlist.has_shipping_address) {
                        $wishlist_input.attr('data-shipping-address-required', 1);
                    } else {
                        $wishlist_input.attr('data-shipping-address-required', 0);
                    }
                }
            },

            /**
             * Add a product to a wishlist via ajax
             *
             * @param {jQuery object} $thisbutton The form submit button
             */
            ajax_action: function($thisbutton) {
                var $form = $thisbutton.parent(selector.form);

                if (nmgr.is_in_modal() && 'add_to_wishlist' === nmgr.modal.data.action) {
                    nmgr.modal.hide();
                    var formdata = $(nmgr.selectors.modal).find('form').serializeArray();
                } else {
                    var formdata = $form.serializeArray();
                }

                $thisbutton.removeClass('added');
                $thisbutton.addClass('loading');

                $form.find('.nmgr-animate').removeClass('nmgr-animation-scaleshrink');

                var data = {
                    action: 'nmgr_add_to_wishlist',
                    _wpnonce: nmgr_frontend_params.nonce
                };

                $(formdata).each(function(index, obj) {
                    data[obj.name] = obj.value;
                });

                $(document.body).trigger('nmgr_adding_to_wishlist', [$thisbutton, $form, data]);

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
                    $thisbutton.removeClass('loading');

                    if (!response) {
                        return;
                    }

                    if (response.error) {
                        add_to_wishlist.show_notice(response.notice, $form);
                        return;
                    } else if (response.success) {

                        $form.addClass('product-in-wishlist');
                        $form.find('.nmgr-animate').addClass('nmgr-animation-scaleshrink');

                        if ($form.is('.single')) {
                            add_to_wishlist.show_notice(response.notice, $form);
                        }

                        if (response.notice &&
                            $(response.notice).find('.wc-forward').length &&
                            $form.is('.archive') &&
                            !$form.parent(selector.wrapper).find('.nmgr-view-wishlist-button').length) {
                            var $wishlist_link = $('.wc-forward', $(response.notice))
                                .removeClass('button')
                                .addClass('nmgr-view-wishlist-button')[0]
                                .outerHTML;
                            $form.after($wishlist_link);
                        }
                    }

                    /**
                     * Trigger event after adding to wishlist
                     *
                     * @param {object} response The response from the add to wishlist action
                     *  - This is an object containing the error, success, notice and result values
                     *  - relating to the product added to the wishlist.
                     *  -- The 'result' key in the object contains the product_id, variation_id,
                     *  -- and wishlist_id of the product added to the wishlist
                     *
                     * @param {jQuery object} $thisbutton The button that was clicked to add to wishlist
                     * @param {jQuery object} $form The form parent element of the button
                     */
                    $(document.body).trigger('nmgr_added_to_wishlist', [response, $thisbutton, $form]);
                });
            },

            // Show the wishlist shipping address form in a modal
            dialog_set_shipping_address: function(wishlist_id) {
                var global_params = JSON.parse(JSON.stringify(nmgr_global_params.global));
                global_params.is_modal = true;

                var data = {
                    action: 'nmgr_dialog_set_shipping_address',
                    _wpnonce: nmgr_frontend_params.nonce,
                    nmgr_wid: wishlist_id,
                    context: 'add_to_wishlist',
                    nmgr_global: JSON.stringify(global_params)
                };

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    nmgr.modal.set_content(response.data.template);
                    nmgr.modal.show_or_update();
                    nmgr.modal.data.action = 'set_shipping_address';
                });
            },

            // Actions to pefrom when the dialog submit button is clicked
            dialog_submit_btn_clicked: function(e) {
                e.preventDefault();

                var $thisbutton = $(this);
                var $dialog_form = $(selector.dialog_form);

                // If the button is still disabled and has a message attached to it, show the message
                actions = ['set_shipping_address', 'create_new_wishlist'];
                if (-1 !== $.inArray(nmgr.modal.data.action, actions) && $thisbutton.is('.disabled') && $thisbutton.attr('data-message')) {
                    // If the button is disabled and it has a message, show it.
                    alert($thisbutton.attr('data-message'));
                    return;
                } else if ($thisbutton.is('.disabled')) {
                    return;
                }

                if (-1 !== $.inArray(nmgr.modal.data.action, actions) && nmgr.modal.data.nmgr_pid) {
                    $('.nmgr-add-to-wishlist-form-' + nmgr.modal.data.nmgr_pid).find(selector.add_to_wishlist_btn).trigger('click');
                    nmgr.modal.hide();
                    return;
                }

                if ($thisbutton.attr('data-action') && 'add_to_wishlist' === $thisbutton.attr('data-action') &&
                    'add_to_wishlist' === nmgr.modal.data.action) {

                    // Get the wishlist select field
                    var wishlist_select = $('select[name=nmgr_wid]');

                    // Make sure a grouped product is selected, for grouped products
                    if ($dialog_form.hasClass('product-type-grouped')) {
                        var quantityInputs = $dialog_form.find('input.qty');
                        if (quantityInputs.length) {
                            var hasValue = false;
                            for (var i = 0; i < quantityInputs.length; i++) {
                                if ($(quantityInputs[i]).val() > 0) {
                                    hasValue = true;
                                }
                            }

                            if (!hasValue) {
                                alert([nmgr_frontend_params.i18n_select_quantity_text]);
                                return;
                            }
                        } else if ($dialog_form.find('input[name*=nmgr_qty]:not(".qty")').length &&
                            !$dialog_form.find('input[name*=nmgr_qty]:not(".qty"):checked').length) {
                            alert([nmgr_frontend_params.i18n_select_product_text]);
                            return;
                        }
                    }

                    // At this point we're good to go. We have a selected wishlist and we need to submit the form.
                    var $form = $('.nmgr-add-to-wishlist-form-' + nmgr.modal.data.nmgr_pid);
                    var $add_to_wishlist_button = $form.find(selector.add_to_wishlist_btn);
                    if ($form.is('.nmgr-ajax-add-to-wishlist:not(".redirect, .disabled")') &&
                        $add_to_wishlist_button.is(':not(".disabled")')) {
                        add_to_wishlist.ajax_action($add_to_wishlist_button, $form);
                        return;
                    }

                    $dialog_form.submit();
                }
            },

            /**
             * Enable the dialog submit button if it is present so that we can add the product to the wishlist.
             *
             * The dialog submit button is usually disabled if a condition is not met such as if the shipping address
             * is required but is not present.
             */
            enable_dialog_submit_btn: function(e, wishlist) {
                if (nmgr.is_in_modal() && 'create_new_wishlist' === nmgr.modal.data.action &&
                    !nmgr_frontend_params.shipping_address_required ||
                    (nmgr_frontend_params.shipping_address_required && wishlist.has_shipping_address)) {
                    $(nmgr.selectors.modal).find(selector.dialog_submit_btn).attr('disabled', false);
                }
            }

        };

        /*
         * Add variable products to the wishlist
         *
         * (Used on single product page)
         * @todo convert to class
         */
        var add_to_wishlist_variable = {
            init: function() {
                $('.variations_form')
                    .on('found_variation.wc-variation-form', this.found_variation)
                    .on('show_variation', this.show_variation)
                    .on('hide_variation', this.hide_variation);
            },
            /**
             * Get the wishlist form attached to the variation form
             *
             * @param {jQuery object} obj Variation form
             * @returns {object}
             */
            get_wishlist_form: function(obj) {
                var product_id = $(obj).data('product_id');
                return $(obj).closest('.product').find('[data-nmgr_product_id=' + product_id + ']');
            },
            found_variation: function(e, variation) {
                var variationFormValues = $(this).serialize(),
                    $form = add_to_wishlist_variable.get_wishlist_form(this);
                $form.find('input[name="nmgr_vid"]').val(variation.variation_id).change();
                $form.find('input[name="nmgr_wc_form_values"]').val(variationFormValues).change();
                $form.find('input.qty').attr('min', variation.min_qty).attr('max', variation.max_qty);
            },
            show_variation: function(e, variation, purchasable) {
                var $form = add_to_wishlist_variable.get_wishlist_form(this),
                    $add_to_wishlist_btn = $form.find(selector.add_to_wishlist_btn);
                if (purchasable) {
                    $form.removeClass('disabled');
                    $add_to_wishlist_btn.removeClass('disabled wc-variation-selection-needed');
                } else {
                    $form.addClass('disabled');
                    $add_to_wishlist_btn
                        .removeClass('wc-variation-selection-needed')
                        .addClass('disabled wc-variation-is-unavailable');
                }
            },
            hide_variation: function() {
                var $form = add_to_wishlist_variable.get_wishlist_form(this);
                $form.addClass('disabled');
                $form.find(selector.add_to_wishlist_btn).addClass('disabled wc-variation-selection-needed');
            }
        };

        add_to_wishlist.init();
        add_to_wishlist_variable.init();


    });

    /**
     * Handles wishlist cart actions
     */

    jQuery(function($) {
        var selector = {
            cart: '.nmgr-cart'
        };

        var wishlist_cart = {
            init: function() {
                $(document.body)
                    .on('nmgr_added_to_wishlist nmgr_cart_reload nmgr_items_reloaded', this.reload)
                    .on('click', '.nmgr-cart-item-remove', this.remove_wishlist_item)
                    .on('click', '.nmgr-cart-item-add-to-cart', this.add_to_wc_cart)
                    .on('click', '.nmgr-show-cart-contents', this.dialog_show_cart_contents)
                    .on('nmgr_added_to_cart nmgr_removed_from_wishlist', nmgr.refresh_cart_fragments);
            },

            reload: function() {
                var no_of_carts = $(selector.cart).length;

                if (no_of_carts > 0) {
                    $(selector.cart).each(function(index) {
                        var self = $(this);
                        nmgr.block(self);

                        var data = {
                            action: 'nmgr_load_wishlist_cart',
                            data: self.data(),
                            _wpnonce: nmgr_frontend_params.nonce,
                            nmgr_global: JSON.stringify(nmgr_global_params.global)
                        };

                        $.post(nmgr_global_params.ajax_url, data, function(response) {
                            self.replaceWith(response.data.template);
                            nmgr.unblock(self);

                            if ((no_of_carts - 1) === index) {
                                // remove notices if we are in the modal
                                if (nmgr.is_in_modal()) {
                                    nmgr.remove_notice(nmgr.selectors.modal);
                                }

                                $(document.body).trigger('nmgr_cart_reloaded');
                            }
                        });
                    });
                }
            },

            dialog_show_cart_contents: function(e) {
                if ($(this).is('.redirect')) {
                    return;
                }

                e.preventDefault();

                var data = {
                    action: 'nmgr_load_wishlist_cart',
                    data: $(this).closest(selector.cart).data(),
                    _wpnonce: nmgr_frontend_params.nonce,
                    context: 'dialog',
                    nmgr_global: JSON.stringify(nmgr_global_params.global)
                };

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    if (response.data.template) {
                        nmgr.modal.set_content(response.data.template);
                        nmgr.modal.show();
                    }
                });
            },

            remove_wishlist_item: function(e) {
                e.preventDefault();

                var data = {
                    action: 'nmgr_remove_wishlist_cart_item',
                    wishlist_id: $(this).data('wishlist-id'),
                    wishlist_item_id: $(this).data('wishlist-item-id'),
                    _wpnonce: nmgr_frontend_params.nonce,
                    nmgr_global: JSON.stringify(nmgr_global_params.global)
                };

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    // response should be array of wishlist item id removed, wishlist id and any other parameters
                    $(document.body).trigger('nmgr_removed_from_wishlist', response);
                    $(document.body).trigger('nmgr_cart_reload');
                });
            },

            add_to_wc_cart: function(e) {
                e.preventDefault();

                var data = {
                    action: 'nmgr_add_to_cart',
                    nmgr_global: JSON.stringify(nmgr_global_params.global)
                };

                $.each($(this).data(), function(index, obj) {
                    // Camelcase to dashed
                    data[index.replace(/([a-zA-Z])(?=[A-Z])/g, '$1-').toLowerCase()] = obj;
                });

                $.post(nmgr_global_params.ajax_url, data, function(response) {
                    if (!response) {
                        return;
                    }

                    $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();

                    if (response.notice) {
                        nmgr.show_notice(response.notice, true);
                    }

                    if (response.success) {
                        $(document.body).trigger('nmgr_added_to_cart');
                    }
                });
            }
        };

        wishlist_cart.init();
    });

    /**
     * Js events related to wishlist overview template
     */

    var nmgr_overview = {
        container: '#nmgr-overview',

        wishlist: {},

        /**
         * Flag to check if this template should be reloaded.
         * Used to prevent multiple reload events
         * @type Boolean
         */
        do_reload: false,

        init: function() {
            $(document.body)
                .on('click', '.nmgr-share-wishlist .nmgr-copy', this.copy_link)
                .on('nmgr_items_reloaded nmgr_profile_reloaded nmgr_shipping_reloaded', this.reload);
        },

        /**
         * Copy wishlist link to clipboard
         */
        copy_link: function(e) {
            e.preventDefault();
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val($('.nmgr-share-wishlist .link').text()).select();
            document.execCommand("copy");
            $('.nmgr-share-wishlist .nmgr-copy').text(nmgr_frontend_params.i18n_copied_text);
            $temp.remove();
        },

        reload: function(e, wishlist) {
            var this_wishlist_id = parseInt($(nmgr_overview.container).attr('data-wishlist-id')),
                wishlist_id = ('undefined' !== typeof wishlist) && wishlist.id ? wishlist.id : this_wishlist_id;

            if (nmgr_overview.do_reload || (0 !== this_wishlist_id && ('undefined' !== typeof wishlist) && wishlist.id && this_wishlist_id !== parseInt(wishlist.id))) {
                return;
            }

            nmgr_overview.do_reload = true;

            var data = {
                action: 'nmgr_load_overview',
                nmgr_global: JSON.stringify(nmgr_global_params.global),
                wishlist_id: parseInt(wishlist_id),
                _wpnonce: $(nmgr_overview.container).attr('data-nonce')
            };

            nmgr.block(nmgr_overview.container);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                $(nmgr_overview.container).replaceWith(response);
                nmgr_overview.wishlist = wishlist;
                nmgr_overview.reloaded();
            });
        },

        reloaded: function() {
            setTimeout(function() {
                nmgr_overview.do_reload = false;
            }, 500);

            nmgr.tiptip();
            $(document.body).trigger('nmgr_overview_reloaded', this.wishlist);
        }
    };

    nmgr_overview.init();

    /**
     * Js events related to wishlist profile template
     */

    nmgr.profile = {
        container: '#nmgr-profile',

        noticeTimer: '',

        wishlist: {},

        init: function() {
            nmgr.init_datepicker();

            $(nmgr.selectors.modal).on('set_content.bs.modal', nmgr.profile.dialog_init);

            $(document.body)
                .on('submit', '.nmgr-profile-form', this.submit)
                .on('nmgr_profile_reloaded', this.update_tab_title);
        },

        // Actions to perform if the profile form is in the dialog
        dialog_init: function() {
            if (nmgr.modal.has_content(nmgr.profile.container)) {
                // Initialize the datepicker
                nmgr.init_datepicker();
            }
        },

        reload: function(e, wishlist) {
            var this_wishlist_id = parseInt($(nmgr.profile.container).attr('data-wishlist-id')),
                wishlist_id = ('undefined' !== typeof wishlist) && wishlist.id ? wishlist.id : this_wishlist_id;

            if (0 !== this_wishlist_id && ('undefined' !== typeof wishlist) && wishlist.id && this_wishlist_id !== parseInt(wishlist.id)) {
                return;
            }

            var data = {
                action: 'nmgr_load_profile',
                wishlist_id: parseInt(wishlist_id),
                nmgr_global: JSON.stringify(nmgr_global_params.global),
                _wpnonce: $(nmgr.profile.container).attr('data-nonce')
            };

            nmgr.block(nmgr.profile.container);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                $(nmgr.profile.container).replaceWith(response);
                nmgr.profile.wishlist = wishlist;
                nmgr.profile.reloaded(response);
            });
        },

        reloaded: function(response) {
            nmgr.init_datepicker();
            $(document.body).trigger('nmgr_profile_reloaded', [this.wishlist, response]);
        },

        showNotice: function(element) {
            $(element).addClass('nmgr-notice').prependTo($(nmgr.profile.container));
            clearTimeout(this.noticeTimer);
            this.noticeTimer = setTimeout(function() {
                $('#nmgr-profile .nmgr-notice').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 7000);
        },

        submit: function(e) {
            e.preventDefault();

            var $form = $(this);

            var data = {
                data: $form.serialize(),
                action: 'nmgr_save_profile',
                wishlist_id: $(nmgr.profile.container).attr('data-wishlist-id'),
                _wpnonce: $(nmgr.profile.container).attr('data-nonce'),
                nmgr_global: JSON.stringify(nmgr_global_params.global)
            };

            nmgr.block($form);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                if (response.success && response.data) {
                    if (response.data.wishlist) {
                        nmgr.profile.wishlist = response.data.wishlist;
                    }

                    if (response.data.redirect) {
                        nmgr.go_to(response.data.redirect);
                    } else {
                        if (response.data.html) {
                            $(nmgr.profile.container).replaceWith($(response.data.html));
                        }

                        if (response.data.created) {
                            $(document.body).trigger('nmgr_wishlist_created', [nmgr.profile.wishlist, response]);
                        }

                        nmgr.profile.reloaded(response);
                    }
                }

                if (response.data && response.data.notice) {
                    nmgr.profile.showNotice(response.data.notice);
                    nmgr.scroll_to($('#nmgr-profile [role="alert"]'));
                }

                nmgr.unblock($form);

            });
            return false;
        },

        update_tab_title: function(e, wishlist) {
            if (wishlist.id !== 0) {
                // tab id is hardcoded for now
                var icon = $('#nmgr-tab-profile').find('.nmgr-icon');
                if (icon.length && icon.attr('data-notice') === 'require-profile' && !icon.hasClass('nmgr-hide')) {
                    icon.addClass('nmgr-hide');
                }
            }
        }
    };

    nmgr.profile.init();

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
     * Js events related to wishlist shipping template
     */

    nmgr.shipping = {
        container: '#nmgr-shipping',

        noticeTimer: '',

        wishlist: {},

        init: function() {
            this.ship_to_account_address();

            /**
             * This should always come after 'ship_to_account_address' function because that function
             * may reload the shipping template and we need to initialize select2 only after the template is reloaded.
             */
            this.init_country_state_select2();

            $(nmgr.selectors.modal).on('set_content.bs.modal', this.dialog_init);

            $(document.body)
                .on('nmgr_wishlist_created', this.reload)
                .on('change', '#nmgr_ship_to_account_address', this.ship_to_account_address)
                .on('submit', '.nmgr-shipping-form', this.submit)
                .on('nmgr_shipping_reloaded', this.update_tab_title);
        },

        reload: function(e, wishlist) {
            var this_wishlist_id = parseInt($(nmgr.shipping.container).attr('data-wishlist-id')),
                wishlist_id = ('undefined' !== typeof wishlist) && wishlist.id ? wishlist.id : this_wishlist_id;

            if (0 !== this_wishlist_id && ('undefined' !== typeof wishlist) && wishlist.id && this_wishlist_id !== parseInt(wishlist.id)) {
                return;
            }

            var data = {
                action: 'nmgr_load_shipping',
                wishlist_id: parseInt(wishlist_id),
                nmgr_global: JSON.stringify(nmgr_global_params.global),
                _wpnonce: $(nmgr.shipping.container).attr('data-nonce')
            };

            nmgr.block(nmgr.shipping.container);

            $.post(nmgr_global_params.ajax_url, data, function(response) {
                $(nmgr.shipping.container).replaceWith(response);
                nmgr.shipping.wishlist = wishlist;
                nmgr.shipping.reloaded();
            });
        },

        reloaded: function() {
            $(document.body).trigger('nmgr_shipping_reloaded', this.wishlist);
            this.ship_to_account_address();
            this.init_country_state_select2();
        },

        /**
         * initialize select2 on woocommerce shipping country and state fields
         * This is useful to maintain the right form state after ajax reload
         */
        init_country_state_select2: function() {
            $(document.body).trigger('country_to_state_changed');
            $(nmgr.shipping.container).find('#shipping_country').trigger('refresh');
        },

        ship_to_account_address: function() {
            var $container = $(nmgr.shipping.container),
                $input = $container.find('#nmgr_ship_to_account_address');

            if ($input.is(':checked')) {
                if ($input.closest('label').attr('data-save')) {
                    if (window.confirm(nmgr_frontend_params.i18n_use_account_shipping_address_text)) {
                        $('.nmgr-shipping-form').submit();
                    } else {
                        $input.prop('checked', false);
                    }
                } else {
                    $container.find('.account-shipping-address').slideDown();
                    $container.find('.wishlist-shipping-address').hide();
                }
            } else {
                $container.find('.account-shipping-address').slideUp();
                $container.find('.wishlist-shipping-address').slideDown();
            }
        },

        // Actions to perform if the shipping address form is in the dialog
        dialog_init: function() {
            if (nmgr.modal.has_content('#nmgr-shipping')) {
                // Initialize the shipping form ship to account address field to set it up properly
                nmgr.shipping.ship_to_account_address();

                /**
                 *  If we have the woocommerce shipping form, unbind the selectWoo function (wc_country_select_select2)
                 *  which prevents the modal from scrolling when a country is selected the second time.
                 *  The function is found in country-select.js. This is a bug in the woocommerce selectWoo plugin.
                 *  Because this function is not stored in a global variable, we can only unbind the event itself. This is currently
                 *  the easiest solution to the problem and we hope it doesn't affect other handlers bound to the event.
                 */
                $(document.body).off('country_to_state_changed');
            }
        },

        showNotice: function(element) {
            $(element).addClass('nmgr-notice').prependTo($(nmgr.shipping.container));
            clearTimeout(this.noticeTimer);
            this.noticeTimer = setTimeout(function() {
                $('#nmgr-shipping .nmgr-notice').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 7000);
        },

        submit: function(e) {
            e.preventDefault();

            var $form = $(this);

            var data = {
                data: $form.serialize(),
                action: 'nmgr_save_shipping',
                wishlist_id: $(nmgr.shipping.container).attr('data-wishlist-id'),
                _wpnonce: $(nmgr.shipping.container).attr('data-nonce'),
                nmgr_global: JSON.stringify(nmgr_global_params.global)
            };

            nmgr.block($form);

            $.ajax({
                url: nmgr_global_params.ajax_url,
                data: data,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    // always update the data-save attribute of the ship_to_account_address checkbox after form submission
                    $(nmgr.shipping.container).find('#nmgr_ship_to_account_address').closest('label').attr('data-save', 1);

                    if (response.data.wishlist) {
                        nmgr.shipping.wishlist = response.data.wishlist;
                    }

                    if (response.success && response.data.html) {
                        $(nmgr.shipping.container).replaceWith($(response.data.html));
                    }

                    if (response.data.notice) {
                        nmgr.shipping.showNotice(response.data.notice);
                        nmgr.scroll_to($('#nmgr-shipping [role="alert"]'));
                    }

                    // Always reload shipping template as ship to account address field is always saved.
                    nmgr.shipping.reloaded();

                    nmgr.unblock($form);
                }
            });
            return false;
        },

        update_tab_title: function(e, wishlist) {
            if (nmgr_frontend_params.shipping_address_required && $('.nmgr-tabs').length > 0) {
                // tab id is hardcoded for now
                var icon = $('#nmgr-tab-shipping').find('.nmgr-icon');

                if (!icon.length || icon.attr('data-notice') !== 'require-shipping-address') {
                    return;
                }

                if (wishlist.has_shipping_address && !icon.hasClass('nmgr-hide')) {
                    icon.addClass('nmgr-hide');
                } else if (!wishlist.has_shipping_address) {
                    icon.removeClass('nmgr-hide');
                }
            }
        }
    };

    nmgr.shipping.init();


    /**
     * Enable or disable the wishlist module for the user
     */
    function nmgr_enable_wishlist() {

        var input = document.querySelector('input[name="nmgr_enable_wishlist"]');

        if (input) {
            input.addEventListener("change", submit, false);

            function submit() {
                var isChecked = input.checked;
                if (isChecked) {
                    document.getElementById('nmgr-enable-wishlist-form').submit();
                } else {
                    if (window.confirm(nmgr_frontend_params.disable_notice)) {
                        document.getElementById('nmgr-enable-wishlist-form').submit();
                    } else {
                        input.checked = true;
                    }
                }
            }
        }
    }
    nmgr_enable_wishlist();



});