@plugin @plagiarism_moorsp
Feature: Show plagiarism status to student
  In order to check whether my file submission has passed the plagiarism test
  As a student
  I need to see the plagiarism status of my file submission

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Moorsp" node in "Site administration>Plugins>Plagiarism"
    And I set the field "Enable Moorsp" to "1"
    And I set the field "Enable Moorsp for assign" to "1"
    And I set the field "Enable Moorsp for forum" to "1"
    And I set the field "Enable Moorsp for workshop" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: View plagiarism check information after a text submission is added to an assignment
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment |
      | Description | Test assignment for Moorsp |
      | Require students click submit button | Yes |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test assignment"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | Test online text submission |
    And I press "Save changes"
