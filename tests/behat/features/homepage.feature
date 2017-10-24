Feature: Homepage

  Ensure that homepage is displayed as expected

  @api
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    And I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    Then I save screenshot
