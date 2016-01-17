module.exports = function( grunt ) {

	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),
		wp_readme_to_markdown: {
			your_target: {
				files: {
					'readme.md' : 'readme.txt'
				},
			},
		},
		makepot: {
			target: {
				options: {
					type: 'wp-plugin'
				}
			}
		}
	});

	// Load plugins.
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	// Configure tasks.
	grunt.registerTask( 'build', ['wp_readme_to_markdown', 'makepot'] );
};