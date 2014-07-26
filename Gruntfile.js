module.exports = function (grunt) {
	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		makepot: {
			target: {
				options: {
					type: 'wp-plugin'
				}
			}
		}
	});

	//grunt.registerTask('default', []);


};