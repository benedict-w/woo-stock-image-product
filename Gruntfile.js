module.exports = function(grunt) {

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        concat: {
            options: {
                stripBanners: true,
            },
            stock_image_cropper: {
                src: [
                    'node_modules/croppr/dist/croppr.js',
                    'assets/js/src//main.js'
                ],
                dest: 'assets/js/dist/stock-image-cropper.js'
            }
        },

        uglify: {
            all: {
                files: {
                    'assets/js/dist/stock-image-cropper.min.js': ['assets/js/dist/stock-image-cropper.js']
                }
            }
        },

        sass: {
            dist: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'assets/css/styles.css': 'assets/scss/styles.scss'
                }
            }
        },

        postcss: {
            options: {
                map: true,
                processors: [
                    require('autoprefixer')({ browsers: 'last 2 versions' }),
                    require('cssnano')()
                ]
            },
            dist: {
                src: 'assets/css/*.css'
            }
        },

        copy: {
            // Copy the plugin to a versioned release directory
            main: {
                src: [
                    '**',
                    '!node_modules/**',
                    '!release/**',
                    '!.git/**',
                    '!.sass-cache/**',
                    '!css/src/**',
                    '!js/src/**',
                    '!img/src/**',
                    '!Gruntfile.js',
                    '!package.json',
                    '!.gitignore',
                    '!.gitmodules'
                ],
                dest: 'release/<%= pkg.version %>/'
            }
        },

        compress: {
            main: {
                options: {
                    mode: 'zip',
                    archive: './release/<%= pkg.name %>.<%= pkg.version %>.zip'
                },
                expand: true,
                cwd: 'release/<%= pkg.version %>/',
                src: ['**/*'],
                dest: 'woo-stock-image-product/'
            }
        },

        watch: {
            scripts: {
                files: '**/*.js',
                tasks: ['concat', 'uglify'],
                options: {
                    debounceDelay: 250,
                },
            },
            css: {
                files: '**/*.sass',
                tasks: ['sass', 'postcss'],
                options: {
                    livereload: true,
                },
            },
        },

    });

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['concat', 'uglify', 'sass', 'postcss', 'watch']);
    grunt.registerTask('build', ['concat', 'uglify', 'sass', 'postcss', 'copy', 'compress']);

};