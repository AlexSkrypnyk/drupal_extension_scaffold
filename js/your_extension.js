/**
 * @file
 * Your Extension behaviors.
 */

/**
 * @param {Object} Drupal  The Drupal object.
 */
((Drupal) => {
  Drupal.behaviors.yourExtension = {
    attach(context) {
      const elements = context.querySelectorAll(
        '[data-your-extension-time]:not(.your-extension-processed)',
      );

      elements.forEach((element) => {
        element.classList.add('your-extension-processed');
        element.textContent = `Page loaded: ${new Date().toLocaleString()}`;
      });
    },
  };
})(Drupal);
