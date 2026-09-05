/**
 * @file
 * VVJB compat shim.
 *
 * The carousel's behavior is fully encapsulated in the
 * `<vvjb-carousel>` custom element (see `vvjb-carousel-element.js`).
 * This file exists for two backwards-compat reasons:
 *
 *   1. v1 sites referenced `Drupal.behaviors.vvjbCarousel` from custom
 *      code. Registering a no-op behavior under that key keeps any
 *      such code from crashing.
 *
 *   2. v1 sites used the public API `Drupal.vvjb.goToSlide(id, n)`,
 *      `nextSlide`, `prevSlide`, `pause`, `resume`, `getCurrentSlide`,
 *      `getTotalSlides`, `getInstance`. Those methods now delegate to
 *      the matching methods on the custom element instance.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal, drupalSettings, once) => {
  Drupal.behaviors.vvjbCarousel = {
    attach(context) {
      once('vvjb-carousel-marker', 'vvjb-carousel', context);
    },
    detach() {},
  };

  /**
   * Resolve a deeplink-id, CSS selector, or DOM node to the matching
   * `<vvjb-carousel>` custom element.
   *
   * @param {string|Element} identifier
   *   Either a deeplink-id (e.g. `'products'`), a CSS selector, or an
   *   existing element.
   * @return {Element|null}
   *   The matched `<vvjb-carousel>` element, or null.
   */
  function getElement(identifier) {
    if (identifier instanceof Element) {
      return identifier.closest('vvjb-carousel');
    }
    if (typeof identifier !== 'string') {
      return null;
    }
    let el = document.querySelector(
      `vvjb-carousel[data-deeplink-id="${identifier}"]`,
    );
    if (el) {
      return el;
    }
    el = document.querySelector(
      `vvjb-carousel .vvjb-items[data-deeplink-id="${identifier}"]`,
    );
    if (el) {
      return el.closest('vvjb-carousel');
    }
    el = document.querySelector(identifier);
    if (el) {
      return el.closest('vvjb-carousel') || el;
    }
    return null;
  }

  Drupal.vvjb = Drupal.vvjb || {};

  Drupal.vvjb.goToSlide = (identifier, slideNumber) => {
    const el = getElement(identifier);
    if (!el || typeof el.goToSlide !== 'function') {
      console.warn(
        `VVJB: Carousel "${identifier}" not found or not yet hydrated`,
      );
      return false;
    }
    return el.goToSlide(slideNumber);
  };

  Drupal.vvjb.getCurrentSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getCurrentSlide !== 'function') {
      return null;
    }
    return el.getCurrentSlide();
  };

  Drupal.vvjb.getTotalSlides = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getTotalSlides !== 'function') {
      return null;
    }
    return el.getTotalSlides();
  };

  Drupal.vvjb.nextSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.nextSlide !== 'function') {
      return false;
    }
    return el.nextSlide();
  };

  Drupal.vvjb.prevSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.prevSlide !== 'function') {
      return false;
    }
    return el.prevSlide();
  };

  Drupal.vvjb.pause = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.pause !== 'function') {
      return false;
    }
    return el.pause();
  };

  Drupal.vvjb.resume = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.resume !== 'function') {
      return false;
    }
    return el.resume();
  };

  Drupal.vvjb.getInstance = (containerOrSelector) =>
    getElement(containerOrSelector);
})(Drupal, drupalSettings, once);
