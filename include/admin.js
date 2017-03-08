/**
 * SLP administrative JavaScript stuff.
 *
 * @package StoreLocatorPlus\JavaScript\SLP_ADMIN
 * @author Lance Cleveland <lance@storelocatorplus.com>
 * @copyright 2016 Charleston Software Associates, LLC
 */


var SLP_ADMIN = SLP_ADMIN || {};

/**
 * Option Manager Class
 */
var slp_options_manager = function () {

    this.change_option = function( input_object , input_id , input_value ) {
        if ( typeof( input_object !== null ) ) {
            if (jQuery(input_object).is(':checkbox')) {
                if (typeof input_object.checked !== 'undefined') {
                    input_object.value = input_object.checked ? '1' : '0';
                }
            }
        }

        var post_data = new Object();
        post_data['action']     = 'slp_change_option';
        post_data['formdata']   = {
            'option_name' : ( typeof( input_id ) === 'undefined' ) ? jQuery( input_object ).attr('id') : input_id ,
            'option_value' : ( typeof( input_value ) === 'undefined' ) ? jQuery( input_object ).attr('value') : input_value
        };
        jQuery.post( ajaxurl, post_data , this.process_change_option_response );
    };

    /**
     * Handle the change option response.
     *
     * @param response
     */
    this.process_change_option_response = function( response ) {
        var json_response = JSON.parse( response );
        if ( json_response.status !== 'ok' ) {
            alert('Option not saved.');
        }
    }

};

/**
 * Nofications Manager Class
 */
var slp_notifications = function() {

    /**
     * For all notifications - attach the close on click and auto-close after 10 seconds
     */
    this.initialize = function() {
        jQuery( 'div.slp-notification' ).click( function() {
            this.remove();
        });
        setTimeout( this.remove_all , 5000 );
    }

    /**
     * Remove all notifications
     */
    this.remove_all = function() {
        jQuery( '.slp-notification' ).each( function(i) {
            var elm = jQuery(this);
            setTimeout( function() {
                elm.remove();
            } , 3000 + i*300 );
        } );
    }

};

/**
 * AdminUI Class
 */
(function($) {

    AdminUI = {
        /**
         * Confirm a message then redirect the user.
         */
        confirmClick: function(message, href) {
            if (confirm(message)) {
                location.href = href;
            }
            else {
                return false;
            }
        },

        // Fires on dismissing the admin notice.
        //
        dismiss_persistent_notice: function() {
            SLP_ADMIN.options.change_option( null, 'options_nojs[admin_notice_dismissed]' , '1' );
        }   ,

        /**
         * Perform an action on the specified form.
         */
        doAction: function(theAction, thePrompt, formID, fieldID) {
            formID = typeof formID !== 'undefined' ? formID : 'locationForm';

            if (jQuery('#' + formID).length && jQuery('#' + formID).is('form')) {
                targetForm = '#' + formID;
            } else {
                targetForm = '#' + formID + ' form';
            }

            fieldID = typeof fieldID !== 'undefined' ? fieldID : 'act';
            if ( ( typeof thePrompt == 'undefined' ) || (thePrompt === '') || confirm(thePrompt)) {
                jQuery(targetForm + ' [name="' + fieldID + '"]').attr('value', theAction);
                jQuery(targetForm).submit();
            } else {
                return false;
            }
        },

        /**
         * toggle_nav_tabs()
         *
         */
        toggle_nav_tabs: function() {
            var flip = 0;

            $('#expand_options').click(function() {
                if (flip == 0) {
                    flip = 1;
                    $('#wpcsl_container #wpcsl-nav').hide();
                    $('#wpcsl_container #content').width(785);
                    $('#wpcsl_container .group').add('#wpcsl_container .group h1').show();

                    $(this).text('[-]');

                } else {
                    flip = 0;
                    $('#wpcsl_container #wpcsl-nav').show();
                    $('#wpcsl_container #content').width(595);
                    $('#wpcsl_container .group').add('#wpcsl_container .group h1').hide();
                    $('#wpcsl_container .group:first').show();
                    $('#wpcsl_container #wpcsl-nav li').removeClass('current');
                    $('#wpcsl_container #wpcsl-nav li:first').addClass('current');

                    $(this).text('[+]');

                }

            });
        }, // End toggle_nav_tabs()

        /**
         * load_first_tab()
         */
        load_first_tab: function() {
            $('.group').hide();
            var selectedNav = $('#selected_nav_element').val();
            if ((typeof selectedNav === 'undefined') || (selectedNav == '')) {
                $('.group:has(".section"):first').show();
            } else {
                $(selectedNav).show();
            }
        }, // End load_first_tab()

        /**
         * open_first_menu()
         */
        open_first_menu: function() {
            $('#wpcsl-nav li.current.has-children:first ul.sub-menu').hide().addClass('open').children('li:first').addClass('active').parents('li.has-children').addClass('open');
        }, // End open_first_menu()

        /**
         * toggle_nav_menus()
         */
        toggle_nav_menus: function() {
            $('#wpcsl-nav li.has-children > a').click(function(e) {
                if ($(this).parent().hasClass('open')) {
                    return false;
                }

                $('#wpcsl-nav li.top-level').removeClass('open').removeClass('current');
                $('#wpcsl-nav li.active').removeClass('active');
                if ($(this).parents('.top-level').hasClass('open')) {
                } else {
                    $('#wpcsl-nav .sub-menu.open').removeClass('open').hide().parent().removeClass('current');
                    $(this).parent().addClass('open').addClass('current').find('.sub-menu').hide().addClass('open').children('li:first').addClass('active');
                }

                // Find the first child with sections and display it.
                var clickedGroup = $(this).parent().find('.sub-menu li a:first').attr('href');
                if (clickedGroup != '') {
                    $('.group').hide();
                    $(clickedGroup).show();
                }
                return false;
            });
        }, // End toggle_nav_menus()

        /**
         * setup_nav_highlights()
         */
        setup_nav_highlights: function() {
            // Highlight the first item by default.
            var selectedNav = $('#selected_nav_element').val();
            if (selectedNav == '') {
                $('#wpcsl-nav li.top-level:first').addClass('current').addClass('open');
            } else {
                $('#wpcsl-nav li.top-level:has(a[href="' + selectedNav + '"])').addClass('current').addClass('open');
            }

            // Default single-level logic.
            $('#wpcsl-nav li.top-level').not('.has-children').find('a').click(function(e) {
                var thisObj = $(this);
                var clickedGroup = thisObj.attr('href');

                if (clickedGroup != '') {
                    $('#selected_nav_element').val(clickedGroup);
                    $('#wpcsl-nav .open').removeClass('open');
                    $('.sub-menu').hide();
                    $('#wpcsl-nav .active').removeClass('active');
                    $('#wpcsl-nav li.current').removeClass('current');
                    thisObj.parent().addClass('current');

                    $('.group').hide();
                    $(clickedGroup).show();
                    $(clickedGroup).trigger('is_shown');

                    return false;
                }
            });

            $('#wpcsl-nav li:not(".has-children") > a:first').click(function(evt) {
                var thisObj = $(this);

                var clickedGroup = thisObj.attr('href');

                if ($(this).parents('.top-level').hasClass('open')) {
                } else {
                    $('#wpcsl-nav li.top-level').removeClass('current').removeClass('open');
                    $('#wpcsl-nav .sub-menu').removeClass('open').hide();
                    $(this).parents('li.top-level').addClass('current');
                }

                $('.group').hide();
                $(clickedGroup).show();

                evt.preventDefault();
                return false;
            });

            // Sub-menu link click logic.
            $('.sub-menu a').click(function(e) {
                var thisObj = $(this);
                var parentMenu = $(this).parents('li.top-level');
                var clickedGroup = thisObj.attr('href');

                if ($('.sub-menu li a[href="' + clickedGroup + '"]').hasClass('active')) {
                    return false;
                }

                if (clickedGroup != '') {
                    parentMenu.addClass('open');
                    $('.sub-menu li, .flyout-menu li').removeClass('active');
                    $(this).parent().addClass('active');
                    $('.group').hide();
                    $(clickedGroup).show();
                }

                return false;
            });
        }, // End setup_nav_highlights()

        /**
         * unhide_hidden()
         */
        unhide_hidden: function(obj) {
            obj = $('#' + obj); // Get the jQuery object.

            if (obj.attr('checked')) {
                obj.parent().parent().parent().nextAll().hide().removeClass('hidden').addClass('visible');
            } else {
                obj.parent().parent().parent().nextAll().each(function() {
                    if ($(this).filter('.last').length) {
                        $(this).hide().addClass('hidden');
                        return false;
                    }
                    $(this).hide().addClass('hidden');
                });
            }
        } // End unhide_hidden()

    }; // End AdminUI Object


})(jQuery);

// If callbacks are supported load the pubsub module.
//
if ( typeof( jQuery.Callbacks ) !== 'undefined' ) {
    SLP_ADMIN.filters = {}
    var slp_AdminFilter = function( id ) {
        var callbacks, method,
            filter = id && SLP_ADMIN.filters[ id ];

        if ( !filter ) {
            callbacks = jQuery.Callbacks();
            filter = {
                publish: callbacks.fire,
                subscribe: callbacks.add,
                unsubscribe: callbacks.remove
            };

            if ( id ) {
                SLP_ADMIN.filters[ id ] = filter;
            }
        }
        return filter;
    };
    SLP_ADMIN.has_pubsub = true;

// No callbacks.
//
} else {
    SLP_ADMIN.log( 'jQuery callbacks not supported.' );
    SLP_ADMIN.log( 'Something is forcing jQuery version ' + jQuery.fn.jquery + ' .');
    SLP_ADMIN.has_pubsub = false;
}

/**
 * Log a message if the console window is active.
 *
 * @param message
 */
SLP_ADMIN.log = function( message ) {
    if ( window.console ) {
        console.log(message);
    }
};

// Document Ready
//
jQuery(document).ready(function() {

    // Handle SLP Notifications
    //
    // if ( jQuery( '.slp-notification' ) ) {
    AdminUI.notifications = new slp_notifications();
    AdminUI.notifications.initialize();
    //}

    // Real Time Options Manager
    //
    SLP_ADMIN.options = new slp_options_manager();

    // Setup Panel Navigation Elements
    //
    AdminUI.toggle_nav_tabs();
    AdminUI.load_first_tab();
    AdminUI.setup_nav_highlights();
    AdminUI.toggle_nav_menus();
    AdminUI.open_first_menu();

    // Settigns group expand/collapse
    // Defunct(?)
    jQuery('div.settings-group').children('h3').click(function() {
        var p = jQuery(this).parent('.settings-group');
        p.toggleClass('closed');
    });

    // Dismiss persistent admin notices
    //
    jQuery( '#slp_persistent_notice .notice-dismiss' ).click(
        AdminUI.dismiss_persistent_notice
    );

});
