/**
 * @file
 * Pattern Swatch: the arrows lean towards the pointer (magnetic arrows).
 *
 * Fine pointers only, never under reduced motion. Writes `--vvjb-mx/-my` on
 * the button; the Pattern CSS reads them.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal, once) => {
  /** @type {WeakMap<HTMLElement, AbortController>} */
  const controllers = new WeakMap();
  const fine = window.matchMedia('(hover: hover) and (pointer: fine)');

  /**
   * Wires the arrows of one Swatch instance.
   *
   * @param {HTMLElement} root
   *   The vvjb-carousel element carrying the Pattern class.
   */
  function wire(root) {
    const controller = new AbortController();
    controllers.set(root, controller);
    const { signal } = controller;
    root.querySelectorAll('.vvjb-button').forEach((button) => {
      const reset = () => {
        button.style.setProperty('--vvjb-mx', '0px');
        button.style.setProperty('--vvjb-my', '0px');
      };
      button.addEventListener(
        'pointermove',
        (event) => {
          if (Drupal.Vvj.reducedMotion() || !fine.matches) {
            return;
          }
          const box = button.getBoundingClientRect();
          const x = (event.clientX - box.left - box.width / 2) / (box.width / 2);
          const y = (event.clientY - box.top - box.height / 2) / (box.height / 2);
          button.style.setProperty('--vvjb-mx', `${(x * 8).toFixed(1)}px`);
          button.style.setProperty('--vvjb-my', `${(y * 8).toFixed(1)}px`);
        },
        { signal },
      );
      button.addEventListener('pointerleave', reset, { signal });
    });
  }

  Drupal.behaviors.vvjPatternVvjbSwatch = {
    attach(context) {
      once('vvj-pattern-vvjb-swatch', '.vvj-pattern--vvjb-swatch', context).forEach(wire);
    },
    detach(context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      once
        .remove('vvj-pattern-vvjb-swatch', '.vvj-pattern--vvjb-swatch', context)
        .forEach((root) => {
          controllers.get(root)?.abort();
          controllers.delete(root);
        });
    },
  };
})(Drupal, once);
