/**
 * @file
 * Global utilities.
 *
 */
(function (Drupal) {

    'use strict';

    Drupal.behaviors.bootstrap_barrio = {
        attach: function (context, settings) {
            var position = window.scrollY;
            window.addEventListener('scroll', function () {
                if (window.scrollY > 50) {
                    document.querySelector('body').classList.add("scrolled");
                }
                else {
                    document.querySelector('body').classList.remove("scrolled");
                }
                var scroll = window.scrollY;
                if (scroll > position) {
                    document.querySelector('body').classList.add("scrolldown");
                    document.querySelector('body').classList.remove("scrollup");
                } else {
                    document.querySelector('body').classList.add("scrollup");
                    document.querySelector('body').classList.remove("scrolldown");
                }
                position = scroll;
            });
        }
    };

    Drupal.behaviors.pharmacy = {
        // eventListeners: {},
        attach: function (context, settings) {
            // window.console.log(eventListeners[context]);
            // if ()
            if (context instanceof Document || context instanceof Element) {
                // @BUG this is duplicated!
                eventListeners[context] = context.querySelector('[id^="edit-submit-product-details"]')?.addEventListener("click", function (event) {
                    event.stopPropagation
                    let searchString = event.target.closest("form").querySelector('input[type="text"]').value;
                    window.location.href = "/catalog?product_name=" + searchString;
                });
            }
        }
    };

})(Drupal);
