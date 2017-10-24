Feature: Roles

  Ensure that roles installed

  @api
  Scenario Outline: User with assigned role visits homepage
    Given I am logged in as a user with the "<role>" role
    And I go to the homepage
    And I save screenshot
    Examples:
      | role          |
      | administrator |
      | editor        |
      | approver      |
