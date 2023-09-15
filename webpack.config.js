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


    // allow sass/scss files to be processed
    .enableSassLoader()

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
