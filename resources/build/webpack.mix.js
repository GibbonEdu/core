/*
 |--------------------------------------------------------------------------
 | Laravel Mix + Tailwind
 |--------------------------------------------------------------------------
 */

let mix = require('laravel-mix');
let tailwindcss = require('tailwindcss');

mix
    .setPublicPath('../assets')
    .scripts([
        '../assets/js/core.js',
    ], '../assets/js/core.min.js')
    .sass('../assets/css/core.scss', 'css/core.min.css')
    .sass('../assets/css/theme.scss', 'css/theme.min.css')
    .options({
        processCssUrls: false,
        postCss: [ tailwindcss('./tailwind.js') ],
    });
