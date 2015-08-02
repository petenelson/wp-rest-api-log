module.exports = function( grunt ) {

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		concat: {
			options: {
				stripBanners: true,
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			main: {
				src: [
					'admin/js/src/*.js',
				],
				dest: 'admin/js/wp-rest-api-log-admin.js'
			},
		},

		jshint: {
			all: [
				'Gruntfile.js',
				'admin/js/src/*.js',
			]
		},

		uglify: {
			all: {
				files: {
					'admin/js/wp-rest-api-log-admin.min.js': ['admin/js/wp-rest-api-log-admin.js'],
				},
				options: {
					banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
						' * <%= pkg.homepage %>\n' +
						' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
						' * Licensed GPLv2+' +
						' */\n',
					mangle: {
						except: ['jQuery']
					}
				}
			}
		},

		sass: {
			all: {
				options: {
					precision: 2,
					sourceMap: false
				},
				files: {
					'admin/css/wp-rest-api-log-admin.css': 'admin/css/sass/wp-rest-api-log-admin.scss'
				}
			}
		},

		postcss: {
			dist: {
				options: {
					map: false,
					processors: [
						require('autoprefixer-core')({
							browsers: ['last 2 versions']
						}),
						require('postcss-clearfix'),
						require('postcss-pseudo-class-enter'),
						require('cssnano')()
					]
				},
				files: {
					'admin/css/wp-rest-api-log-admin.css': [ 'admin/css/wp-rest-api-log-admin.css' ]
				}
			}
		},

		cssmin: {
			options: {
				sourceMap: true
			},
			minify: {
				expand: true,

				cwd: 'admin/css/',
				src: ['wp-rest-api-log-admin.css'],

				dest: 'admin/css/',
				ext: '.min.css'
			}
		},

		watch: {
			styles: {
				files: ['admin/css/sass/*.scss'],
				tasks: ['sass', 'postcss', 'cssmin'],
				options: {
					debounceDelay: 500
				}
			},
			scripts: {
				files: [ 'adnin/js/src/*.js' ],
				tasks: [ 'jshint', 'concat', 'uglify' ],
				options: {
					debounceDelay: 500
				}
			}
		},

		clean: {
			main: ['release/<%= pkg.version %>']
		},

		copy: {
			// Copy the theme to a versioned release directory
			main: {
				src:  [
					'**',
					'!**/.*',
					'!**/readme.md',
					'!node_modules/**',
					'!vendor/**',
					'!tests/**',
					'!release/**',
					'!assets/css/sass/**',
					'!assets/css/src/**',
					'!assetsjs/src/**',
					'!images/src/**',
					'!bootstrap.php',
					'!bower.json',
					'!composer.json',
					'!composer.lock',
					'!Gruntfile.js',
					'!package.json',
					'!phpunit.xml',
					'!phpunit.xml.dist'
				],
				dest: 'release/<%= pkg.version %>/'
			}
		},

		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './release/health.<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>/',
				src: ['**/*'],
				dest: 'health/'
			}
		},

		phpunit: {
			classes: {
				dir: 'tests/phpunit/'
			},
			options: {
				bin: 'vendor/bin/phpunit',
				bootstrap: 'bootstrap.php',
				colors: true
			}
		},

		qunit: {
			all: ['tests/qunit/**/*.html']
		},

		phantomcss: {
			options: {
				mismatchTolerance: 0.05,
				screenshots: 'test/phantomcss/baselines',
				results: 'test/phantomcss/results',
				viewportSize: [1280, 800]
			},
			src: [
				'test/phantomcss/start.js',
				'test/phantomcss/*-test.js'
			]
		},

		hologram: {
			generate: {
				options: {
					config: './hologram_config.yml'
				}
			}
		}
	} );

	// Load tasks
	require('load-grunt-tasks')(grunt);

	// Register tasks
	grunt.registerTask( 'default', ['jshint', 'concat', 'uglify', 'sass', 'postcss', 'cssmin'] );

	grunt.registerTask( 'build', ['default', 'clean', 'copy', 'compress'] );

	grunt.registerTask( 'test', ['phpunit', 'qunit', 'phantomcss'] );

	grunt.util.linefeed = '\n';
};
