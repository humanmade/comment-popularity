module.exports = function (grunt) {
	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		makepot: {
			target: {
				options: {
					type: 'wp-plugin',
					domainPath: 'languages',
					exclude: ['node_modules/.*']
				}
			}
		},

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
	});

};