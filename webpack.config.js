const path = require("path");

module.exports = {
	mode: "none", // "production" | "development" | "none"  // Chosen mode tells webpack to use its built-in optimizations accordingly.
	entry: ["./worker/src/index.js"], // string | object | array  // defaults to ./src
	// Here the application starts executing
	// and webpack starts bundling
	output: {
		// options related to how webpack emits results
		path: path.resolve(__dirname, "worker/dist"), // string
		// the target directory for all output files
		// must be an absolute path (use the Node.js path module)
		filename: "serviceworker.js", // string    // the filename template for entry chunks
		//	publicPath: "/assets/", // string    // the url to the output directory resolved relative to the HTML page
		//	library: "SW", // string,
		// the name of the exported library
		libraryTarget: "umd" // universal module definition    // the type of the exported library
		/* Advanced output configuration (click to show) */
	},
	module: {
		// configuration regarding modules
		rules: [
			// rules for modules (configure loaders, parser options, etc.)
			{
				test: /\.jsx?$/,
				include: [path.resolve(__dirname, "app")],
				exclude: [path.resolve(__dirname, "app/demo-files")],
				// these are matching conditions, each accepting a regular expression or string
				// test and include have the same behavior, both must be matched
				// exclude must not be matched (takes preferrence over test and include)
				// Best practices:
				// - Use RegExp only in test and for filename matching
				// - Use arrays of absolute paths in include and exclude
				// - Try to avoid exclude and prefer include
				//	issuer: {test, include, exclude},
				// conditions for the issuer (the origin of the import)
				enforce: "pre",
				enforce: "post",
				// flags to apply these rules, even if they are overridden (advanced option)
				loader: "babel-loader",
				// the loader which should be applied, it'll be resolved relative to the context
				// -loader suffix is no longer optional in webpack2 for clarity reasons
				// see webpack 1 upgrade guide
				options: {presets: ["es2017"]}
				// options for the loader
			}
		]
		/* Advanced module configuration (click to show) */
	},
	resolve: {
		// options for resolving module requests
		// (does not apply to resolving to loaders)
		modules: ["node_modules", path.resolve(__dirname, "app")],
		// directories where to look for modules
		extensions: [".js", ".json", ".jsx", ".css"],
		// extensions that are used
		alias: {
			// a list of module name aliases
			module: "new-module",
			// alias "module" -> "new-module" and "module/path/file" -> "new-module/path/file"
			"only-module$": "new-module",
			// alias "only-module" -> "new-module", but not "only-module/path/file" -> "new-module/path/file"
			module: path.resolve(__dirname, "app/third/module.js")
			// alias "module" -> "./app/third/module.js" and "module/file" results in error
			// modules aliases are imported relative to the current context
		}
		/* alternative alias syntax (click to show) */
		/* Advanced resolve configuration (click to show) */
	},
	performance: {
		hints: "warning", // enum    maxAssetSize: 200000, // int (in bytes),
		maxEntrypointSize: 400000, // int (in bytes)
		assetFilter: function(assetFilename) {
			// Function predicate that provides asset filenames
			return (
				assetFilename.endsWith(".css") || assetFilename.endsWith(".js")
			);
		}
	},
	//	devtool: "source-map", // enum  // enhance debugging by adding meta info for the browser devtools
	// source-map most detailed at the expense of build speed.
	context: __dirname, // string (absolute path!)
	// the home directory for webpack
	// the entry and module.rules.loader option
	//   is resolved relative to this directory
	target: "webworker", // enum  // the environment in which the bundle should run
	// changes chunk loading behavior and available modules
	externals: ["react", /^@angular\//], // Don't follow/bundle these modules, but request them at runtime from the environment
	serve: {
		//object
		port: 1337,
		content: "./worker/dist"
	},
	// lets you provide options for webpack-serve
	stats: "normal", // lets you precisely control what bundle information gets displayed
	plugins: [
		// ...
	],
	// list of additional plugins
	/* Advanced configuration (click to show) */
	resolveLoader: {
		/* same as resolve */
	},
	// separate resolve options for loaders
	parallelism: 1, // number
	// limit the number of parallel processed modules
	profile: true, // boolean
	// capture timing information
	bail: true, //boolean
	// fail out on the first error instead of tolerating it.
	cache: false, // boolean
	// disable/enable caching
	watch: false, // boolean
	// enables watching
	watchOptions: {
		aggregateTimeout: 1000, // in ms
		// aggregates multiple changes to a single rebuild
		poll: true,
		poll: 500 // intervall in ms
		// enables polling mode for watching
		// must be used on filesystems that doesn't notify on change
		// i. e. nfs shares
	},
	node: {
		// Polyfills and mocks to run Node.js-
		// environment code in non-Node environments.
		console: false, // boolean | "mock"
		global: true, // boolean | "mock"
		process: true, // boolean
		__filename: "mock", // boolean | "mock"
		__dirname: "mock", // boolean | "mock"
		Buffer: true, // boolean | "mock"
		setImmediate: true // boolean | "mock" | "empty"
	},
	recordsPath: path.resolve(__dirname, "worker/build/records.json"),
	recordsInputPath: path.resolve(__dirname, "worker/build/records.json"),
	recordsOutputPath: path.resolve(__dirname, "worker/build/records.json")
	// TODO
};
