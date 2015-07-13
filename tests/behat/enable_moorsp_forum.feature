@plugin @plagiarism_moorsp
Feature: Enable Moorsp for forum
  In order to add plagiarism checking to an forum
  As a teacher
  I need to be able to enable Moorsp for that forum

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Moorsp" node in "Site administration>Plugins>Plagiarism"
    And I set the field "Enable Moorsp" to "1"
    And I set the field "Enable Moorsp for forum" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: Create a forum and enable Moorsp for it
    Given I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | Description | Test forum for Moorsp |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    When I follow "Test forum"
    And I navigate to "Edit settings" node in "Forum administration"
    Then the field "Enable Moorsp" matches value "Yes"
    And the field "Show plagiarism info to student" matches value "Always"