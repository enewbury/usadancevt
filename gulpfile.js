var gulp = require('gulp');
var browserify = require('gulp-browserify');
var sass = require('gulp-sass');
var minify = require('gulp-minify-css');
var inlineCss = require('gulp-inline-css');
var gutil = require('gulp-util');
var uglify = require('gulp-uglify');

// Basic usage
gulp.task('scripts', function() {
    // Single entry point to browserify
    gulp.src('app/views/js/*.js')
        .pipe(browserify({
            insertGlobals : true,
            debug : (gutil.env.env === 'dev')
        }))
        .pipe(gutil.env.env === 'dev' ? gutil.noop() : uglify())
        .pipe(gulp.dest('public_html/js/generated'))
});

gulp.task('css', function(){
    gulp.src('app/views/sass/style.scss')
        .pipe(sass())
        .pipe(gutil.env.env === 'dev' ? gutil.noop() : minify())
        .pipe(gulp.dest('public_html'));
});

gulp.task('emailcss', function(){
   gulp.src('app/views/templates/email_templates/raw/*.twig')
       .pipe(inlineCss())
       .pipe(gulp.dest('app/views/templates/email_templates/rendered'));
});

gulp.task("compile", ['scripts','css', 'emailcss']);

gulp.task('default', ['scripts','css','emailcss'],function(){
    gulp.watch('app/views/js/**/*.js', ['scripts']);
    gulp.watch('app/views/sass/**/*.scss',['css']);
    gulp.watch('app/views/templates/email_templates/raw/**/*',['emailcss']);
});