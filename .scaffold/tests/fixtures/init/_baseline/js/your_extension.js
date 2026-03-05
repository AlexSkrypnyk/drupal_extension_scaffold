/**
 * @file
 * Force Crystal behaviors.
 */

/**
 * @param {Object} Drupal  The Drupal object.
 */
((Drupal) => {
  Drupal.behaviors.yourExtension = {
    attach(context) {
      const elements = context.querySelectorAll(
        '[data-force_crystal-time]:not(.force_crystal-processed)',
      );

      elements.forEach((element) => {
        element.classList.add('force_crystal-processed');
        element.textContent = `Page loaded: ${new Date().toLocaleString()}`;
      });
    },
  };
})(Drupal);
