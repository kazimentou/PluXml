var postcss = require('gulp-postcss');
var gulp = require('gulp');
var autoprefixer = require('autoprefixer');
var cssnano = require('cssnano');
var rename = require('gulp-rename');
 
var src = './src/*.css';
var dest = '../';

gulp.task('css', function () {
    var plugins = [
        autoprefixer({browsers: ['last 1 version']}),
    ];
    return gulp.src(src)
        .pipe(postcss(plugins))
        .pipe(gulp.dest(dest));
});

gulp.task('css:mini', function () {
    var plugins = [
        autoprefixer({browsers: ['last 1 version']}),
        cssnano()
    ];
    return gulp.src(src)
        .pipe(postcss(plugins))
		.pipe(rename(function(path) {
			return {
				dirname: path.dirname,
				basename: path.basename + '.min',
				extname: path.extname
			}
		}))
        .pipe(gulp.dest(dest));
});

gulp.task('default', gulp.series('css', 'css:mini'));

