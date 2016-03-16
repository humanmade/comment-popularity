1.3.5 / 2016-03-16
==================

  * Fix a bug which updated all comment columns with karma value.
  * Now visually shows upvotes/downvotes
  * Global plugin variable

1.3.4 / 2015-02-12
==================

  * Misc fixes

1.3.3 / 2014-10-25
==================

  * Add namespace

1.3.2 / 2014-10-10
==================

  * Merge pull request #93 from humanmade/issue-93
  * Init plugin earlier
  * Merge pull request #92 from humanmade/fix-helpers-namespace
  * Merge pull request #86 from humanmade/fix-helpers-namespace

1.3.1 / 2014-09-11
==================

 * Load on init and fix namespacing issue
 * Fix function prefix
 * Adjustments to readmi
 * Fix readme markdown

1.3.0 / 2014-09-01
==================

 * Merge pull request #84 from humanmade/issue-80
 * namespaces
 * Fix insert comment hook
 * add expert status method
 * remove non visitor specific functioin
 * Merge pull request #82 from humanmade/issue-80
 * Delete option on uninstall
 * Merge pull request #80 from humanmade/issue-80
 * child class can define type
 * add filter for negative comment weight
 * only set visitor object if it doesn't exist
 * check permissions before deactivating plugin
 * Delete version DB option on uninstall
 * Use get_user_option instead of get_author_meta
 * Fix a bug in class name
 * only proceed if user is logged in
 * Guest voting works
 * Refactor: introduce visitor classes
 * Begin allowing guest voting
 * Merge pull request #71 from humanmade/issue-71
 * Merge pull request #75 from humanmade/issue-75
 * Add namespace
 * Add instructions for disabling custom sorting to the readme FAQ
 * Allow user to disable sorting by weight
 * Change tested up to version to 4.0
 * Add database prefix to meta key in user query.

1.2.1 / 2014-08-27
==================

 * Load main plugin class to fix uninstall fatal error.
 * Use the *user_option functions
 * Add some assertions
 * Rename user meta
 * Update uninstall routine
 * We're deleting a global option
 * Update the option to match current plugin version after upgrade
 * Add an upgrade routine
 * More exclude rules for build process
 * Version 1.2.0
 * Update CONTRIBUTING.md
 * Delete user voting history
 * Update user meta key
 * Prefix user meta key

1.2.0 / 2014-08-24
==================

 * Merge pull request #77 from humanmade/fix-user-can-vote
 * it should be set
 * the user_can_vote function is triggered by a vote action. therefore we cannot use it to determines if a comment can be voted on on page load. we can just check if current user has sufficient permissions. unless we persist a user/comment relationship.
 * Merge pull request #52 from humanmade/issue-52
 * Merge branch 'master' into issue-52
 * Refactor get comments by weight function
 * Use exising function instead of another comment query
 * USe the refactored function parameters
 * Use the refactored function parameters
 * Just add a newline
 * Merge pull request #73 from humanmade/issue-73
 * Merge pull request #58 from humanmade/issue-58
 * clean up
 * display gravatar
 * adds an experts widget
 * update translation files
 * Limit number of comments by user widget setting
 * Add context info for translators
 * Make more strings translatable
 * Recreate tests from WP CLI
 * Update tested up to field

1.1.5 / 2014-08-19
==================

 * Update tests to reflect refactoring
 * Change how comment weight is calculated
 * Use a multisite compatible function for user meta
 * Fix up tests config
 * Use appropriate WordPress functions
 * Remove debugging function
 * Make debugging easier

1.1.4 / 2014-08-18
==================

 * Check PHP version before evaluating any code
 * Include and register widgets from main plugin class
 * Add a tag
 * Use function instead of constant
 * Composer udpates
 * Use 5.2 compatible code here
 * Update minified script
 * Throttle clicking events
 * Add a tag
 * Use function instead of constant
 * Composer udpates
 * Use 5.2 compatible code here
 * Update CONTRIBUTING.md

1.1.3 / 2014-08-16
==================

 * Add dotorg banners
 * Bump version to 1.1.3
 * Fix PHP error
 * Switch statement order
 * Add min PHP version
 * Use html entities
 * Ignore non build files
 * Igonre contributing.md from build
 * Remove some unused code
 * Fix a PHP notice
 * Update readme and changelog

1.1.2 / 2014-08-15
==================

 * Bump version
 * Merge pull request #60 from humanmade/fix-bug-display-karma
 * Pass user ID as param instead of email
 * Add RTD config
 * fix to readme

1.1.1 / 2014-08-13
==================

 * Bump to version 1.1.1
 * Fix a PHP notice

1.1.0 / 2014-08-13
==================

 * Bump to version 1.1.0
 * Merge pull request #39 from humanmade/issue-39
 * Make the paths filterable
 * Fix how we call comments template
 * Add a comment template file
 * Move comments to own file
 * Fix a PHP notice
 * Merge pull request #56 from humanmade/edit-readme
 * Regenerate readme
 * Add link to github
 * Fix some spacing and formatting
 * Update CONTRIBUTING.md
 * Merge pull request #32 from humanmade/issue-32
 * Update CONTRIBUTING.md
 * Fix composer.json
 * Merge pull request #53 from humanmade/add-contributors
 * Regenerate readme.md
 * Fix Readme file and add contributors
 * Merge pull request #25 from humanmade/issue-25
 * Prevent users from upvoting their comments
 * No need to check if user can vote
 * Merge pull request #30 from humanmade/issue-30
 * Register widget
 * Add a most voted comments widget
 * Update min WordPress version in readme
 * Add Matt as contributor
 * Merge pull request #16 from humanmade/issue-16
 * Add sortable karma column to users view
 * Merge pull request #7 from humanmade/issue-7
 * Return just the values
 * Add task to update class plugin version
 * Update version
 * Add some actions
 * Update function desc
 * Use prefixed constant VERSION
 * Improve activation checks
 * Change required WP Version
 * Prefix filters
 * Rename constants
 * Merge pull request #49 from humanmade/issue-49
 * Add Travis badge
 * Update tests to account for downvoting a comment with no karma
 * Rename function
 * Return comment weight
 * Rename callback
 * Return as integer
 * Bump version
 * Merge pull request #47 from humanmade/contributing-guidelines
 * First version of instructions
 * Add contributing.md file

## 1.0.2 / 2014-08-08

 * Add tests for comment weight update
 * disallow negative karma
 * Fix code after refactor method
 * Return the database value
 * Fix issue where we were adding downvotes
 * Subtract karma if comment is downvoted
 * Add tests for commenter karma update
 * Update tests
 * Allow overriding vote value
 * Use the literal vote values
 * Minified CSS
 * remove build
 * Merge pull request #44 from humanmade/build-tasks
 * Add minified scripts
 * JSHint config
 * Ignore build dir
 * Merge branch 'master' into build-tasks
 * add comments
 * Changelog file
 * Add build tasks
 * Add task plugins
 * Use minified scripts and styles
 * add comments
 * Merge pull request #45 from humanmade/docs
 * Merge branch 'master' into docs
 * Update readme
 * Add readme to markdown task
 * Documentation for how to remove the built in comment template
 
## 1.0.1

* Fix contributors
* Remove unneeded files

## 1.0.0

* Initial release
