/* jshint node:true */

module.exports = function (grunt) {
	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		pkg: grunt.file.readJSON( 'package.json' ),

		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: [
					'Gruntfile.js'
				]
			},
			plugin: {
				src: [
					'assets/js/voting.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some'
			},
			modules: {
				files: {
					'js/jquery.growl.min.js': ['node_modules/jquery.growl/javascripts/jquery.growl.js']
				}
			},
			plugin: {
				files: {
					'js/voting.min.js': ['js/voting.js']
				}
			}
		},

		cssmin: {
			files:{
				expand:true,
				cwd:'node_modules/jquery.growl/stylesheets',
				src:['jquery.growl.css'],
				dest:'css/',
				ext:'.growl.min.css'
			}
		},

		// Generates a POT file for translators.
		makepot: {
			target: {
				options: {
					type: 'wp-plugin',
					domainPath: 'languages',
					exclude: ['node_modules/.*'],
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = 'support@humanmade.co.uk';
						pot.headers['last-translator'] = 'Human Made Limited';
						pot.headers['language-team'] = 'Human Made Limited';
						return pot;
					}
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
	});

	grunt.registerTask('minify', ['newer:uglify:modules','newer:uglify:plugin']);
	grunt.registerTask('lint', ['newer:jshint:grunt','newer:jshint:plugin']);

	// Default task(s).
	grunt.registerTask( 'default', [ 'minify', 'uglify', 'cssmin' ] );
};