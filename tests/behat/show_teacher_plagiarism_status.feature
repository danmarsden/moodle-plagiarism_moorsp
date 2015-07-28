@plugin @plagiarism_moorsp @_file_upload
Feature: Show plagiarism status to teacher
  In order to check whether a student's submission has passed the plagiarism test
  As a teacher
  I need to see the plagiarism status of a student's submission

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
    And I set the field "Enable Moorsp for workshop" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: View plagiarism check information after a file submission is added to an assignment
    Given I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment |
      | Description | Test assignment for Moorsp |
      | Require students click submit button | No |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test assignment"
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test assignment"
    When I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And I log out
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test assignment"
    And I follow "View/grade all submissions"
    Then ".not-plagiarised" "css_element" should exist in the "Student 1" "table_row"
    And ".plagiarised" "css_element" should exist in the "Student 2" "table_row"

  @javascript
  Scenario: View plagiarism check information after a file submission is added to a workshop
    Given I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Test workshop |
      | Description | Test workshop for Moorsp |
      | Instructions for submission | Submit a file to be evaluated |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    And I change phase in workshop "Test workshop" to "Submission phase"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I set the field "Title" to "Test submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachment" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I set the field "Title" to "Test submission 2"
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachment" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I change phase in workshop "Test workshop" to "Assessment phase"
    When I follow "Test submission"
    Then ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I follow "Test workshop"
    And I follow "Test submission 2"
    And ".plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"



