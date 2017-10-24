<?php

namespace Drupal\drupal_circleci_template\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test example for CircleCI template.
 *
 * @group drupal_circleci_template
 */
class DrupalCircleciTemplateWebTestCase extends WebTestBase {

  /**
   * Passing fixture assertion.
   */
  public function testTrue() {
    $this->pass('Passing fixture assertion');
  }

}
