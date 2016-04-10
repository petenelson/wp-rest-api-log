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

		makepot: {
			target: {
				options: {
					type:        'wp-plugin',
					mainFile:    'wp-rest-api-log.php'
				}
			}
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
				tasks: ['sass', 'cssmin'],
				options: {
					debounceDelay: 250
				}
			},
			scripts: {
				files: [ 'admin/js/src/*.js' ],
				tasks: [ 'jshint', 'concat', 'uglify' ],
				options: {
					debounceDelay: 250
				}
			}
		},


		clean: {
			main: [ 'release/' ]
		},


		copy:   {
			// create release for WordPress repository
			wp: {
				files: [

					// directories
					{ expand: true, src: [ 
						'admin/css/wp-rest-api-log-admin.css',
						'admin/css/wp-rest-api-log-admin.min.css',
						'admin/js/wp-rest-api-log-admin.js',
						'admin/js/wp-rest-api-log-admin.min.js',
						'admin/*.php',
						'admin/partials/*.php',
						], 
						dest: 'release/' },

					{ expand: true, src: ['includes/**'], dest: 'release/' },
					{ expand: true, src: ['languages/**'], dest: 'release/' },

					// root dir files
					{
						expand: true,
						src: [
							'*.php',
							'readme.txt',
							],
						dest: 'release/'
					}

				]
			} // wp
		},


		phplint: {
			options: {
				limit: 10,
				stdout: true,
				stderr: true
			},
			files: [
				'admin/*.php',
				'admin/**/*.php',
				'includes/*.php',
				'includes/**/*.php',
				'*.php'
			]
		},

		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
		},

	} );

	// Load tasks
	require('load-grunt-tasks')(grunt);
	require('phplint').gruntPlugin(grunt);

	// Register tasks
	grunt.registerTask( 'default', [ 'jshint', 'concat', 'uglify', 'sass', 'cssmin' ] );

	grunt.registerTask( 'build', [ 'default', 'test', 'clean', 'copy' ] );

	grunt.registerTask( 'test', [ 'phplint', 'phpunit' ] );

	grunt.util.linefeed = '\n';
};
