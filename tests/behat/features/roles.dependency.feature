Feature: Roles

  Ensure that roles from specified dependency were installed

  Note, that in real project, this test would be committed to the dependency
  repository and would not run as a part of the test suit for this module.
  We are running this specific test here to check that dependency resolution
  worked correctly during the automated build.

  @api @testapi
  Scenario Outline: User with assigned role visits homepage
    Given I am logged in as a user with the "<role>" role
    And I go to the homepage
    And I save screenshot
    Examples:
      | role          |
      | editor        |
