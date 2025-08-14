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

  plugins: [
    require('@tailwindcss/forms'),
    require('daisyui'),
  ],

  // ✅ DaisyUI theme tuned for the app brand
  daisyui: {
    themes: [
      {
        worksphere: {
          primary: '#4F46E5',
          'primary-content': '#ffffff',
          secondary: '#22c55e',
          accent: '#f59e0b',
          neutral: '#1f2937',
          'base-100': '#f8fafc',
          info: '#0ea5e9',
          success: '#10b981',
          warning: '#f59e0b',
          error: '#ef4444',
        },
      },
      'light',
    ],
  },
};
