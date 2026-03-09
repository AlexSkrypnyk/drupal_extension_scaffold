/**
 * @file
 * Tests for Force Crystal behaviors.
 */

const fs = require('fs');
const path = require('path');

describe('Drupal.behaviors.yourExtension', () => {
  beforeEach(() => {
    global.Drupal = { behaviors: {} };

    const filePath = path.resolve(__dirname, 'force_crystal.js');
    const code = fs.readFileSync(filePath, 'utf8');
    eval(code);
  });

  afterEach(() => {
    delete global.Drupal;
  });

  describe('formatLoadTime', () => {
    it('should format a date into a load time string', () => {
      const date = new Date('2025-01-15T10:30:00');
      const result = Drupal.behaviors.yourExtension.formatLoadTime(date);
      expect(result).toMatch(/^Page loaded: /);
      expect(result).toContain('2025');
    });
  });

  describe('attach', () => {
    it('should set text content using formatLoadTime', () => {
      const mockDate = new Date('2025-06-15T12:00:00');
      jest.spyOn(global, 'Date').mockImplementation(() => mockDate);

      document.body.innerHTML = '<div data-force_crystal-time></div>';
      Drupal.behaviors.yourExtension.attach(document);

      const el = document.querySelector('[data-force_crystal-time]');
      expect(el.classList.contains('force_crystal-processed')).toBe(true);
      expect(el.textContent).toBe(
        Drupal.behaviors.yourExtension.formatLoadTime(mockDate),
      );

      jest.restoreAllMocks();
    });

    it('should not re-process already processed elements', () => {
      document.body.innerHTML =
        '<div data-force_crystal-time class="force_crystal-processed">original</div>';
      Drupal.behaviors.yourExtension.attach(document);

      const el = document.querySelector('[data-force_crystal-time]');
      expect(el.textContent).toBe('original');
    });
  });
});
