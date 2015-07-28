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
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test assignment"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | Test online text submission |
    And I press "Save changes"
    And ".plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    When I press "Edit submission"
    And I set the following fields to these values:
      | Online text | Test online text submission 2 |
    And I press "Save changes"
    Then ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"

  @javascript
  Scenario: View plagiarism check information after a text submission is added to a workshop
    Given I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Test workshop |
      | Description | Test workshop for Moorsp |
      | Instructions for submission | Provide a text submission |
      | Maximum number of submission attachments | 0 |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
    And I change phase in workshop "Test workshop" to "Submission phase"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I set the following fields to these values:
      | Title | Test submission |
      | Submission content | Test submission content |
    And I press "Save changes"
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I set the following fields to these values:
      | Title | Test submission |
      | Submission content | Test submission content |
    And I press "Save changes"
    And ".plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    When I press "Edit submission"
    And I set the following fields to these values:
      | Title | Test submission |
      | Submission content | Test submission content 2 |
    And I press "Save changes"
    Then ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"

  @javascript
  Scenario: View plagiarism check information after a text submission is added to a forum
    Given I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum |
      | Forum type | Standard forum for general use |
      | Description | Test forum for Moorsp |
      | Maximum number of attachments | 0 |
      | Enable Moorsp | Yes |
      | Show plagiarism info to student | Always |
      | Group mode | No groups |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I add a new discussion to "Test forum" forum with:
      | Subject | Test subject |
      | Message | Test forum post |
    And I follow "Test subject"
    And ".not-plagiarised" "css_element" should exist in the ".plagiarismreport" "css_element"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I reply "Test subject" post from "Test forum" forum with:
      | Subject | Re: Test subject |
      | Message | Test forum post |
    And ".plagiarised" "css_element" should exist in the "div.indent div.forumpost" "css_element"
    When I follow "Edit"
    And I set the field "Message" to "Test forum post 2"
    And I press "Save changes"
    And I follow "Continue"
    Then ".not-plagiarised" "css_element" should exist in the "div.indent div.forumpost" "css_element"