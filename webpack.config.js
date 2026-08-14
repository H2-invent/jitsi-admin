// webpack.config.js
var Encore = require('@symfony/webpack-encore');
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}
const CopyWebpackPlugin = require('copy-webpack-plugin');
Encore


    // directory where all compiled assets will be stored
    .setOutputPath('public/build')

    // what's the public path to this directory (relative to your project's document root dir)
    .setPublicPath('/build')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()
    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    //.enableSingleRuntimeChunk()
    .disableSingleRuntimeChunk()
    .addEntry('app', './assets/js/app.js')
    .addEntry('join', './assets/js/join.js')
    .addEntry('joinBlack', './assets/js/joinBlack.js')
    .addEntry('black', './assets/js/black.js')
    .addEntry('frontend', './assets/js/frontend.js')
    .addEntry('public', './assets/js/public.js')
    .addEntry('startpage', './assets/js/startpage.js')
    .addEntry('lobbyModerator', './assets/js/lobbyModerator.js')
    .addEntry('lobbyParticipant', './assets/js/lobbyParticipant.js')
    .addEntry('onlyConference', './assets/js/onlyConference.js')
    .addEntry('onlyConferenceLivekit', './assets/js/onlyConferenceLivekit.js')
    .addEntry('onlyClosablePage', './assets/js/onlyClosablePage.js')
    .addLoader({
        test: /\.mp3$/,
        loader: 'file-loader',
        options: {
            name: 'static/media/[name].[hash:8].[ext]'
        }
    })

    .addPlugin(
        new CopyWebpackPlugin({
            patterns: [
                {
                    from: './node_modules/css-star-rating/images/star-rating.icons.svg',
                    to: '.'
                },
            ]
        })
    )
    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()


    // will output as web/build/app.js
    //.addEntry('app', ['./src/public/app.js','./src/public/main.scss'])

    // .createSharedEntry('vendor', './src/public/app.js')


    // allow sass/scss files to be processed.
    // The callback switches sass-loader to the modern JS API (instead of the
    // deprecated "legacy" render() API) and bridges the two things the legacy
    // API used to provide for free but the modern API does not:
    //   - resolving `~package` imports to node_modules, and
    //   - resolving bare node_modules packages without a relative path.
    .enableSassLoader((options) => {
        const path = require('path');
        const fs = require('fs');

        // sass-loader 13's built-in modern importer is only a stub (its
        // `canonicalize` always returns null), so we provide our own importer
        // that maps a `~package/subpath` request onto a real file inside
        // node_modules, trying the same filename candidates Sass itself would
        // (extension, `_partial`, and index-file variants).
        const resolveTilde = (url) => {
            if (!url.startsWith('~')) {
                return null;
            }
            const modulePath = url.slice(1);
            const base = path.join(process.cwd(), 'node_modules', modulePath);
            // If the request already carries an extension, only an exact match
            // is valid (e.g. `~pkg/dist/foo.css`).
            if (path.extname(base)) {
                return fs.existsSync(base) ? base : null;
            }
            const candidates = [
                `${base}.scss`,
                `${base}.sass`,
                `${base}.css`,
                path.join(path.dirname(base), `_${path.basename(base)}.scss`),
                path.join(path.dirname(base), `_${path.basename(base)}.sass`),
                path.join(path.dirname(base), `_${path.basename(base)}.css`),
                path.join(base, 'index.scss'),
                path.join(base, 'index.sass'),
                path.join(base, 'index.css'),
            ];
            for (const candidate of candidates) {
                if (fs.existsSync(candidate)) {
                    return candidate;
                }
            }
            return null;
        };

        options.api = 'modern';
        options.sassOptions = Object.assign({}, options.sassOptions, {
            // `@import` is deprecated (removed in Dart Sass 3.0), but the app
            // depends on its global scoping: `mbd.scss` chains dozens of
            // mdb-ui-kit/bootstrap partials that share variables/mixins
            // globally, and layout partials share `$main-color` etc. Converting
            // to `@use`/`@forward` would require namespacing every shared value
            // across all files, so we silence the warning instead.
            silenceDeprecations: ['import'],
            // Silence deprecation warnings originating from third-party
            // dependency files (e.g. node-snackbar's own darken() usage) that
            // we cannot fix ourselves.
            quietDeps: true,
            importers: [
                {
                    // Resolve `~` URLs to an absolute file:// URL via the
                    // candidate search above; return null for everything else
                    // so Sass falls through to its default resolution.
                    canonicalize(url) {
                        const resolved = resolveTilde(url);
                        return resolved ? new URL('file://' + resolved) : null;
                    },
                    // Read the resolved file and tell Sass which syntax to
                    // parse: indented for `.sass`, plain CSS for `.css`, and
                    // scss otherwise.
                    load(canonicalUrl) {
                        const filePath = canonicalUrl.pathname;
                        const ext = path.extname(filePath);
                        const syntax = ext === '.sass' ? 'indented' : ext === '.css' ? 'css' : 'scss';
                        return {
                            contents: fs.readFileSync(filePath, 'utf8'),
                            syntax,
                            sourceMapUrl: canonicalUrl,
                        };
                    },
                },
            ],
        });
    })

    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .configureBabel(function(babelConfig) {
        babelConfig.plugins.push("@babel/plugin-proposal-class-properties");
    })
// create hashed filenames (e.g. app.abc123.css)
//.enableVersioning()

;

// export the final configuration
module.exports = Encore.getWebpackConfig();
