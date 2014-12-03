module.exports = function(grunt) {
  grunt.initConfig({
    jshint: {
      build: {
        files: {
          src: ['jquery.sessionTimeout.js']
        }
      }
    },
    uglify: {
      build: {
        src  : 'jquery.sessionTimeout.js',
        dest : 'jquery.sessionTimeout.min.js'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.registerTask('default', ['jshint', 'uglify']);
  grunt.registerTask('test', ['jshint']);
};