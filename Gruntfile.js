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
					'assets/js/modules/growl/javascripts/jquery.growl.min.js': ['assets/js/modules/growl/javascripts/jquery.growl.js']
				}
			},
			plugin: {
				files: {
					'assets/js/voting.min.js': ['assets/js/voting.js']
				}
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

		shell: {
			commit: {
				command: 'git add . --all && git commit -m "Version <%= pkg.version %>"'
			},
			tag: {
				command: 'git tag -a <%= pkg.version %> -m "Version <%= pkg.version %>"'
			}
		},

		copy: {
			build: {
				files: [
					{
						expand: true,
						cwd: '.',
						src: [
							'**/*',
							'!**/.{svn,git,bowerrc,jshintrc,travis.yml,gitignore}/**',
							'!**/.DS_Store/**',
							'!**composer{.json,.lock}**',
							'!**package.json**',
							'!**Gruntfile.js**',
							'!**README.md**',
							'!**phpunit**',
							'!**/node_modules/**',
							'!**/wp-assets/**',
							'!**/tests/**',
							'!**/build/**',
							'!**/inc/lib/twig/twig/{test,doc}/**'
						],
						dest: 'build'
					}
				]
			}
		},

		clean:{
			build: {
				src: [ 'build' ]
			}
		},

		replace: {
			pluginsVersion: {
				src: [
					'comment-popularity.php'
				],
				overwrite: true,
				replacements: [ {
					from: /^Version: .*$/m,
					to: 'Version: <%= pkg.version %>'
				} ]
			},
			readmeVersion: {
				src: [
					'README.txt'
				],
				overwrite: true,
				replacements: [ {
					from: /^Stable tag: .*$/m,
					to: 'Stable tag: <%= pkg.version %>'
				} ]
			}
		},

		bump: {
			options: {
				files: [ 'package.json' ],
				updateConfigs: [ 'pkg' ],
				commit: false
			}
		},

		other: {
			changelog: 'changelog.md'
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

	});

	grunt.registerTask('minify', ['newer:uglify:modules','newer:uglify:plugin']);
	grunt.registerTask('lint', ['newer:jshint:grunt','newer:jshint:plugin']);

	// Top level function to build a new release
	grunt.registerTask( 'release', function( releaseType ) {
		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.fail.fatal( 'Please specify the release type (e.g., "grunt release:patch")' );
		} else {
			// Check to make sure the log exists
			grunt.task.run( 'log:' + releaseType );

			// Bump the version numbers
			grunt.task.run( 'bumpto:' + releaseType );

			// Create the .pot file
			grunt.task.run( 'makepot' );

			// Build the SASS and scripts
			grunt.task.run( 'default' );

			// Zip it up
			grunt.task.run( 'package' );

			// Commit and tag version update
			//grunt.task.run( 'shell:commit' );
			//grunt.task.run( 'shell:tag' );
		}
	} );

	// Default task(s).
	grunt.registerTask( 'default', [ 'minify', 'uglify' ] );

	// Bump the version to the specified value; e.g., "grunt bumpto:patch"
	grunt.registerTask( 'bumpto', function( releaseType ) {
		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.fail.fatal( 'Please specify the bump type (e.g., "grunt bumpto:patch")' );
		} else {
			grunt.task.run( 'bump-only:' + releaseType );

			// Update the version numbers
			grunt.task.run( 'replace' );
		}
	} );

	// Prompt for the changelog
	grunt.registerTask( 'log', function( releaseType ) {
		var semver = require( 'semver' ),
			changelog,
			newVersion = semver.inc( grunt.config.get( 'pkg' ).version, releaseType),
			regex = new RegExp( '^## ' + newVersion, 'gm' ); // Match the version number (e.g., "# 1.2.3")

		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.log.writeln().fail( 'Please choose a valid version type (minor, major, or patch)' );
		} else {
			// Get the new version
			changelog = grunt.file.read( grunt.config.get( 'other' ).changelog );

			if ( changelog.match( regex ) ) {
				grunt.log.ok( 'v' + newVersion + ' changlelog entry found' );
			} else {
				grunt.fail.fatal( 'Please enter a changelog entry for v' + newVersion );
			}
		}
	} );

	// Package a new release
	grunt.registerTask( 'package', [
		'copy:build',
		'compress:build',
		'clean:build'
	] );
};