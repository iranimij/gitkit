const { src, dest, series, watch } = require( 'gulp' );
const zip = require( 'gulp-zip' );
const del = require( 'del' );
const run = require( 'gulp-run-command' ).default;
const sass = require( 'gulp-sass' );
const autoprefixer = require( 'gulp-autoprefixer' );
const uglify = require( 'gulp-uglify' );
const stripDebug = require( 'gulp-strip-debug' );
const rename = require( 'gulp-rename' );
const bro = require( 'gulp-bro' );
const babelify = require( 'babelify' );
const gulpLoadPlugins = require( 'gulp-load-plugins' );
const sassLint = require( 'gulp-sass-lint' );

/**
 * Automatically load and store all Gulp plugins.
 */
const $ = gulpLoadPlugins( {
	rename: {
		'gulp-clean-css': 'cleanCSS',
	},
} );

const paths = {
	styles: {
		srcFull: 'assets/src/scss/**/*.scss',
		src: 'assets/src/scss/*.scss',
		dest: 'assets/dist/css/',
	},
	scripts: {
		srcFull: 'assets/src/js/**/*.js',
		src: [
			'assets/src/js/frontend/index.js',
		],
		dest: 'assets/dist/js/',
	}
};

/*
 * Lint Sass.
 */
function lintSass() {
	return src( paths.styles.srcFull )
		.pipe( sassLint( {
			options: {
				configFile: '.sass-lint.yml',
			},
		} ) )
		.pipe( sassLint.format() )
		.pipe( sassLint.failOnError() );
}

/**
 * Task to clean.
 */
function clean() {
	return del( [
		'release',
		'*.zip',
		'assets/dist',
	] );
}

/**
 * Create Zip.
 */
function releaseZip() {
	return src( [
		'release/**',
	] )
		.pipe( zip( 'aiu.zip' ) )
		// eslint-disable-next-line no-undef
		.pipe( dest( __dirname ).on( 'end', () => {
			// Move files from release/aiu to release/
			src( 'release/aiu/**' )
				.pipe( dest( 'release' ).on( 'end', () => del( 'release/aiu' ) ) );
		} ) );
}

function release() {
	return src( [
		'**',
		'!src/**',
		'!assets/src/**',
		'!README.md',
		'!cypress/**',
		'!build/**',
		'!node_modules/**',
		'!visual-diff/**',
		'!vendor/**',
		'!wpcs/**',
		'!*.{lock,json,xml,js,yml}',
	] )
		.pipe( dest( 'release/aiu', { mode: '0755' } ) );
}

/*
 * Build persian kit styles.
 */
function buildStyles() {
	return src( paths.styles.src )
		.pipe( sass( {
			outputStyle: 'expanded',
		} ).on( 'error', sass.logError ) )
		.pipe( autoprefixer( {
			browsers: [ 'last 2 versions' ],
			cascade: false,
		} ) )
		.pipe( $.save( 'before-dest' ) )
		.pipe( dest( paths.styles.dest ) )
		.pipe( $.cleanCSS() )
		.pipe( $.rename( { suffix: '.min' } ) )
		.pipe( dest( paths.styles.dest ) )
		// RTL
		.pipe( $.save.restore( 'before-dest' ) )
		.pipe( $.rtlcss() )
		.pipe( $.rename( { suffix: '-rtl' } ) )
		.pipe( dest( paths.styles.dest ) )
		.pipe( $.cleanCSS() )
		.pipe( $.rename( { suffix: '.min' } ) )
		.pipe( dest( paths.styles.dest ) );
}

/*
 * Build persian kit scripts.
 */
function buildScripts() {
	return src( paths.scripts.src, { sourcemaps: true } )
		.pipe( bro( {
			transform: [
				babelify.configure( { presets: [ '@babel/preset-env' ] } ),
			],
		} ) )
		// eslint-disable-next-line no-console
		.on( 'error', console.log )
		.pipe( dest( paths.scripts.dest ) )
		.pipe( stripDebug() )
		.pipe( uglify() )
		.pipe( rename( {
			suffix: '.min',
		} ) )
		.pipe( dest( paths.scripts.dest ) );
}

/*
 * Watch persian kit files.
 */
module.exports.watch = () => (
	watch( paths.styles.srcFull, series( buildStyles ) ),
	watch( paths.scripts.srcFull, series( buildScripts ) )
);

module.exports.default = series(
	run( 'npm run make:pot' ),
	run( 'npm run build' ),
	run( 'npm run lint:js' ),
	lintSass,
	buildStyles,
	buildScripts,
);

module.exports.test = series(
	run( 'npm run lint:js' ),
	lintSass,
);

module.exports.release = series(
	clean,
	run( 'npm run build' ),
	run( 'npm run lint:js' ),
  run( 'npm run make:pot' ),
	lintSass,
	buildStyles,
	buildScripts,
	release,
	releaseZip
);
