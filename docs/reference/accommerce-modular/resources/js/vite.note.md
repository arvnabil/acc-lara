vite.config.js: tambahkan 'Modules/**/resources/js/**' ke laravel({ input: ['resources/js/app.jsx'], refresh: ['Modules/**'] }).
Tailwind v4: tambahkan `@source '../../Modules/**/*.jsx';` di resources/css/app.css agar class di halaman modul ikut ter-scan.
