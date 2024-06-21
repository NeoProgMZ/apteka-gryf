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
        attach: function (context, settings) {

        }
    };

})(Drupal);
