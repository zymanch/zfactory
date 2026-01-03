let mix = require('laravel-mix');
const path = require('path');

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
            splitChunks: {
                cacheGroups: {
                    default: false,
                    defaultVendors: false
                }
            },
            runtimeChunk: false,
            concatenateModules: true
        },
        resolve: {
            alias: {
                'pixi.js': path.resolve(__dirname, 'node_modules/pixi.js/dist/pixi.mjs')
            }
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
        }
    });
