@plugin @plagiarism_moorsp @test
Feature: Show plagiarism status to student
  In order to check whether my submission has passed the plagiarism test
  As a student
  I need to see the plagiarism status of my submission

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Moorsp" node in "Site administration>Plugins>Plagiarism"
    And I set the field "Enable Moorsp" to "1"
    And I set the field "Enable Moorsp for assign" to "1"
    And I set the field "Enable Moorsp for forum" to "1"
    And I set the field "Enable Moorsp for workshop" to "1"
    And I set the field "Student Disclosure" to "Test student disclosure"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: View plagiarism check information after a submission is added
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment |
      | Description | Test assignment for Moorsp |
      | Require students click submit button | Yes |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test assignment"
    When I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then "This file contains original content" "img" should exist in the ".plagiarismreport" "css_element"