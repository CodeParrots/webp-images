/**
 * Gruntfile.js Controls
 *
 * @author Code Parrots <support@codeparrots.com>
 * @since 1.0.0
 */
module.exports = function(grunt) {

	'use strict';

	var pkg = grunt.file.readJSON( 'package.json' );

	grunt.initConfig( {

		pkg: pkg,

		jshint: {
			assets: [ 'lib/assets/js/**/*.js', '!lib/assets/js/**/*.min.js' ],
			gruntfile: [ 'Gruntfile.js' ]
		},

		autoprefixer: {
			options: {
				browsers: [
					'Android >= 2.1',
					'Chrome >= 21',
					'Edge >= 12',
					'Explorer >= 7',
					'Firefox >= 17',
					'Opera >= 12.1',
					'Safari >= 6.0'
				],
				cascade: false
			},
			main: {
				src: [ 'lib/css/**/*.css', '!lib/css/**/*.min.cs' ]
			}
		},

		cssmin: {
			options: {
				processImport: false,
				roundingPrecision: 5,
				shorthandCompacting: false
			},
			assets: {
				expand: true,
				cwd: 'lib/assets/css/',
				src: [ '**/*.css', '!**/*.min.css' ],
				dest: 'lib/assets/css/',
				ext: '.min.css'
			}
		},

		uglify: {
			options: {
				ASCIIOnly: true
			},
			assets: {
				expand: true,
				cwd: 'lib/assets/js/',
				src: [ '**/*.js', '!**/*.min.js' ],
				dest: 'lib/assets/js/',
				ext: '.min.js'
			}
		},

		watch: {
			css: {
				files: [ 'lib/assets/css/**/*.js', '!lib/assets/css/**/*.min.js' ],
				tasks: [ 'autoprefix', 'cssmin' ]
			},
			js: {
				files: [ 'lib/assets/js/**/*.js', '!lib/assets/js/**/*.min.js' ],
				tasks: [ 'jshint', 'uglify' ]
			}
		},

		replace: {
			base_file: {
				src: [ '<%= pkg.name %>.php' ],
				overwrite: true,
				replacements: [
					{
						from: /Version: (.*)/,
						to: "Version: <%= pkg.version %>"
					},
					{
						from: /define\(\s*'WEBP_IMAGES_VERSION',\s*'(.*)'\s*\);/,
						to: "define( 'WEBP_IMAGES_VERSION', '<%= pkg.version %>' );"
					}
				]
			},
			readme_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [ {
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				} ]
			},
			readme_md: {
				src: [ 'README.md' ],
				overwrite: true,
				replacements: [ {
					from: /# (.*?) #/,
					to: "# <%= pkg.title %> v<%= pkg.version %> #"
				} ]
			}
		},

		clean: {
			pre_build: [ 'build/*' ],
		},

		copy: {
			package: {
				files: [
					{
						expand: true,
						src: [
							'*.php',
							'*.txt',
							'i18n/*.po',
							'i18n/*.mo',
							'lib/**',
						],
						dest: 'build/<%= pkg.name %>'
					}
				],
			}
		},

		makepot: {
			target: {
				options: {
					domainPath: 'i18n/',
					include: [ '.+\.php' ],
					exclude: [ 'node_modules/' ],
					potComments: 'Copyright (c) {year} Code Parrots. All Rights Reserved.',
					potHeaders: {
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = pkg.bugs.url;
						return pot;
					},
					type: 'wp-plugin',
					updatePoFiles: true
				}
			}
		},

		compress: {
			main: {
				options: {
					archive: 'build/<%= pkg.name %>-v<%= pkg.version %>.zip'
				},
				files: [
					{
						cwd: 'build/<%= pkg.name %>/',
						dest: '<%= pkg.name %>/',
						src: [ '**' ]
					}
				]
			}
		},

		devUpdate: {
			packages: {
				options: {
					packageJson: null,
					packages: {
						devDependencies: true,
						dependencies: false
					},
					reportOnlyPkgs: [],
					reportUpdated: false,
					semver: true,
					updateType: 'force'
				}
			}
		},

		wp_readme_to_markdown: {
			options: {
				post_convert: function( readme ) {
					var matches = readme.match( /\*\*Tags:\*\*(.*)\r?\n/ ),
					    tags    = matches[1].trim().split( ', ' ),
					    section = matches[0];

							for ( var i = 0; i < tags.length; i++ ) {
								section = section.replace( tags[i], '[' + tags[i] + '](https://WordPress.org/plugins/tags/' + tags[i] + '/)' );
							}

					// Banner
					if ( grunt.file.exists( 'github-assets/banner-1550x500.jpg' ) ) {

						readme = readme.replace( '**Contributors:**', "![Banner Image](github-assets/banner-1550x500.jpg)\r\n\r\n**Contributors:**" );

					}

					// Tag links
					readme = readme.replace( matches[0], section );

					// Badges
					readme = readme.replace( '## Description ##', grunt.template.process( pkg.badges.join( ' ' ) ) + "  \r\n\r\n## Description ##" );

					return readme;
				}
			},
			main: {
				files: {
					'readme.md': 'readme.txt'
				}
			}
		}

	} );

	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.registerTask( 'default', [
		'menu'
	] );

	grunt.registerTask( 'Development tasks.', [
		'autoprefixer',
		'cssmin',
		'replace',
		'jshint',
		'uglify',
		'wp_readme_to_markdown'
	] );

	grunt.registerTask( 'Build the plugin.', [
		'Development tasks.',
		'clean',
		'copy',
		'compress'
	] );

	grunt.registerTask( 'Update .pot file.',  [
		'makepot'
	] );

	grunt.registerTask( 'Check grunt plugin versions.', [
		'devUpdate'
	] );

};
