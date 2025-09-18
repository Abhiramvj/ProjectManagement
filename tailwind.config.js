// tailwind.config.js
export default {
  darkMode: 'class',

  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
      colors: {
        primary: {
          light: '#6366F1',
          DEFAULT: '#4F46E5',
          dark: '#3730A3',
        },
        accent: {
          light: '#34D399',
          dark: '#059669',
        },
      },
    },
  },


};
