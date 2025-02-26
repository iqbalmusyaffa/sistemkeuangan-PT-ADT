const mix = require('laravel-mix');

// Mengompilasi file Vue dan CSS dari folder resources/dashboard
mix.js('resources/dashboard/js/app.js', 'public/js')
   .vue() // Pastikan Anda menggunakan .vue() untuk mendukung file Vue
   .sass('resources/dashboard/css/app.scss', 'public/css') // Jika Anda menggunakan SASS
   .postCss('resources/dashboard/css/app.css', 'public/css'); // Jika Anda menggunakan CSS biasa

// Menyusun file lain jika diperlukan
mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css');
