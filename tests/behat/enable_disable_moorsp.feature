@plugin @plagiarism_moorsp
Feature: Enable Moorsp
  In order to enable/disable plagiarism features
  As an Admin
  I need to be able to enable/disable the Moorsp plugin

  Background:
    Given I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Enable Moorsp
    Given I navigate to "Moorsp" node in "Site administration>Plugins>Plagiarism"
    When I set the field "Enable Moorsp" to "1"
    And I set the field "Enable Moorsp for assign" to "1"
    And I set the field "Enable Moorsp for forum" to "1"
    And I set the field "Enable Moorsp for workshop" to "1"
    And I press "Save changes"
    Then the field "Enable Moorsp" matches value "1"
    And the field "Enable Moorsp for assign" matches value "1"
    And the field "Enable Moorsp for forum" matches value "1"
    And the field "Enable Moorsp for workshop" matches value "1"

  @javascript
  Scenario: Disable Moorsp
    Given I navigate to "Moorsp" node in "Site administration>Plugins>Plagiarism"
    When I set the field "Enable Moorsp" to "0"
    And I set the field "Enable Moorsp for assign" to "0"
    And I set the field "Enable Moorsp for forum" to "0"
    And I set the field "Enable Moorsp for workshop" to "0"
    And I press "Save changes"
    Then the field "Enable Moorsp" matches value "0"
    And the field "Enable Moorsp for assign" matches value "0"
    And the field "Enable Moorsp for forum" matches value "0"
    And the field "Enable Moorsp for workshop" matches value "0"