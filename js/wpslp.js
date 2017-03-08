/**
 *
 */
var cslmap;

/**
 * Send and AJAX request and process the response.
 *
 * @param action
 * @param callback
 */
slp.send_ajax = function (action, callback) {
    jQuery.post(
        slplus.ajaxurl,
        action,
        function (response) {
            try {
                response = JSON.parse(response);
            }
            catch (ex) {
            }
            callback(response);
        }
    );
};

/*
 * When the document has been loaded...
 *
 */
jQuery(document).ready( slp.run );
