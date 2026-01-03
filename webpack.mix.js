let mix = require('laravel-mix');

mix
    .js('resources/js/game.js', 'public/js')
    .js('resources/js/application.js', 'public/js')
    .js('resources/js/regions.js', 'public/js')
    .js('resources/js/admin.js', 'public/js')
    .js('resources/js/admin-map-editor.js', 'public/js')
    .sass('resources/css/style.scss', 'public/css')
    .sass('resources/css/game.scss', 'public/css')
    .sass('resources/css/regions.scss', 'public/css')
    .sass('resources/css/admin.scss', 'public/css')
    .sass('resources/css/admin-map-editor.scss', 'public/css')
    .options({processCssUrls: false})
    .disableNotifications()
    .webpackConfig({
        target: ['web', 'es5'],
        optimization: {
            splitChunks: false,
            runtimeChunk: false,
            concatenateModules: true
        },
        module: {
            rules: [
                {
                    test: /\.m?js$/,
                    resolve: {
                        fullySpecified: false
                    }
                }
            ]
        },
        plugins: [
            new (require('webpack').optimize.LimitChunkCountPlugin)({
                maxChunks: 1
            })
        ]
    });
