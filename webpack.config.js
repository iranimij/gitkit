const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = {
	entry: {
		'assets/dist/admin/admin': path.resolve(
			process.cwd(),
			'includes/admin/src/index.js'
		),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd() ),
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				},
			},
			{
				test: /\.s[ac]ss$/i,
				use: [
					{ loader: MiniCssExtractPlugin.loader },
					{ loader: 'css-loader' },
					{
						loader: 'sass-loader',
						options: {
							prependData: "@import './includes/admin/src/variables';",
						},
					},
				],
			},
			{
				test: /\.css$/,
				use: [ 'style-loader', 'css-loader' ],
			},
		],
	},
	resolve: {
		extensions: [ '.js', '.jsx' ],
	},
	externals: {
		jquery: 'jQuery',
		lodash: 'lodash', // Necessary for wp.media script.
		'@wordpress/i18n': 'wp.i18n',
		'@gitkit': 'gitkit',
	},
	plugins: [
		new MiniCssExtractPlugin( {
			// Options similar to the same options in webpackOptions.output
			// both options are optional
			filename: '[name].css',
			chunkFilename: '[id].css',
		} ),
	],
};
