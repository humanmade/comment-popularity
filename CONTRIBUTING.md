Contributing guidelines
=======================

Coding Standards
----------------

Please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/)

Contributions
-------------

Pull requests, reporting issues, feedback and ideas for new features and improvements are always welcome!

Releasing a new version
-----------------------

Obviously you'll need contributor access to the WordPress.org repository.

- Run `grunt bumpto:[patch|minor|major]` ( changes version number in plugin and readme )
- Stage and commit the version bump
- Run `grunt makepot` and commit if necessary
- Run `git changelog` ( requires git-extras ) and copy changelog.md to the README.txt
- Run `grunt wp_readme_to_markdown`
- Run `grunt copy:build`
- Run `git tag -a vn.n.n -m "Version n.n.n"`
- Run `git push --tags`
- Run `grunt wp_deploy`

Available Grunt tasks
---------------------

Linting: `grunt lint`
Minifying: `grunt minify`
