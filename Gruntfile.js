module.exports = function (grunt) {
	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		// Generates a POT file for translators.
		makepot: {
			target: {
				options: {
					type: 'wp-plugin',
					domainPath: 'languages',
					exclude: ['node_modules/.*']
				}
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'README.txt'
				}
			}
		},

		// Creates an MD version of the README file
		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'README.txt'
				}
			}
		},

		// Deploys a new version to the svn WordPress.org repo.
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'comment-popularity',
					svn_user: 'pauldewouters',
					build_dir: 'build',
					assets_dir: 'wp-assets'
				}
			}
		}

	}); // end config

};