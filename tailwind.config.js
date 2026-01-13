/** @type {import('tailwindcss').Config} */
export default {
    content: [
      "./resources/**/*.blade.php",
      "./resources/**/*.js",
      "./resources/**/*.vue",
      "./app/Http/Livewire/**/*.php",
      "./vendor/backpack/**/*.blade.php",
      "./node_modules/flowbite/**/*.js",
    ],
    plugins: [
      require('@tailwindcss/forms'),
      require('flowbite/plugin'),
    ],
  }
