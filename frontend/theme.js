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
        primary: '#1565C0',
        secondary: '#546E7A',
        accent: '#5C8AE6',
        error: '#D32F2F',
        info: '#0277BD',
        success: '#2E7D32',
        warning: '#E65100',
        // Add custom colors; use in templates as bg-mybrand, text-mybrand, etc.
        // mybrand: '#hex',
      },
    },
    dark: {
      dark: true,
      colors: {
        primary: '#5C9CE6',
        secondary: '#78909C',
        accent: '#82B1FF',
        error: '#EF5350',
        info: '#29B6F6',
        success: '#66BB6A',
        warning: '#FFA726',
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
