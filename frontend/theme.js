/**
 * Vuetify 3 theme configuration – customize colors here.
 * Used in createVuetify({ theme: appTheme }) in each admin app's main.js.
 * @see https://v3.vuetifyjs.com/en/features/theme/
 */
export const appTheme = {
  defaultTheme: 'light',
  themes: {
    light: {
      dark: false,
      colors: {
        primary: '#1976d2',
        secondary: '#424242',
        accent: '#82B1FF',
        error: '#FF5252',
        info: '#2196F3',
        success: '#4CAF50',
        warning: '#FFC107',
        // Add custom colors; use in templates as bg-mybrand, text-mybrand, etc.
        // mybrand: '#hex',
      },
    },
    dark: {
      dark: true,
      colors: {
        primary: '#2196F3',
        secondary: '#424242',
        accent: '#82B1FF',
        error: '#FF5252',
        info: '#2196F3',
        success: '#4CAF50',
        warning: '#FFC107',
      },
    },
  },
  // Generate lighten/darken variants for primary, secondary, etc.
  variations: {
    colors: ['primary', 'secondary'],
    lighten: 2,
    darken: 2,
  },
};
