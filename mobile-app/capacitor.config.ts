import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'stabimatic.support',
  appName: 'Stabimatic Support',
  webDir: 'www',
  server: {
    androidScheme: 'http',
    cleartext: true
  },
  plugins: {
    CapacitorHttp: {
      enabled: true
    },
    Camera: {
      permissions: ['camera']
    }
  }
};

export default config;
