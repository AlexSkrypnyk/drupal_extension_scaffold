/**
 * @file
 * Your Extension behaviors.
 */

/**
 * @param {Object} Drupal  The Drupal object.
 */
((Drupal) => {
  Drupal.behaviors.yourExtension = {
    formatLoadTime(date) {
      return `Page loaded: ${date.toLocaleString()}`;
    },
    attach(context) {
      const elements = context.querySelectorAll(
        '[data-your-extension-time]:not(.your-extension-processed)',
      );

      elements.forEach((element) => {
        element.classList.add('your-extension-processed');
        element.textContent = this.formatLoadTime(new Date());
      });
    },
  };
})(Drupal);
