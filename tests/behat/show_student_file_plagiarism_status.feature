@plugin @plagiarism_moorsp @_file_upload
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
  Scenario: View plagiarism check information after a file submission is added to an assignment
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
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test assignment"
    When I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    And ".plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I press "Edit submission"
    And I delete "empty.txt" from "File submissions" filemanager
    And I upload "lib/tests/fixtures/upload_users.csv" file to "File submissions" filemanager
    And I press "Save changes"
    Then ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"

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
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I set the field "Title" to "Test submission 2"
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachment" filemanager
    And I press "Save changes"
    And ".plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    When I press "Edit submission"
    And I delete "empty.txt" from "Attachment" filemanager
    And I upload "lib/tests/fixtures/upload_users.csv" file to "Attachment" filemanager
    And I press "Save changes"
    Then ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"

  @javascript
  Scenario: View plagiarism check information after a file submission is added to a forum
    Given I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | Forum type | Standard forum for general use |
      | Description | Test forum for Moorsp |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
      | Group mode | No groups |
    And I add a new discussion to "Test forum" forum with:
      | Subject | Forum post 1 |
      | Message | This is the body |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I add a new discussion to "Test forum" forum with:
      | Subject | Test subject |
      | Message | Isn't this the greatest forum ever? |
      | Attachment | lib/tests/fixtures/empty.txt |
    And I follow "Test subject"
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I wait "70" seconds
    And I log in as "student2"
    And I follow "Course 1"
    And I reply "Test subject" post from "Test forum" forum with:
      | Subject | Re: Test subject |
      | Message | Nah, I've seen better. |
      | Attachment | lib/tests/fixtures/empty.txt |
    And ".plagiarised" "css_element" should exist in the "div.indent div.forumpost" "css_element"
    When I follow "Edit"
    And I delete "empty.txt" from "Attachment" filemanager
    And I upload "lib/tests/fixtures/upload_users.csv" file to "Attachment" filemanager
    And I press "Save changes"
    Then ".not-plagiarised" "css_element" should exist in the "div.indent div.forumpost" "css_element"