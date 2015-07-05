@plugin @plagiarism_moorsp
Feature: Enable Moorsp
  In order to enable plagiarism features
  As an Admin
  I need to be able to enable the Moorsp plugin

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email              |
      | admin    | Admin     | User     | moodle@example.com |
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"