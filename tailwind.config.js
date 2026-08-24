/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./*.html", 
    "./*.py", // Added just in case, based on your current file extension
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}