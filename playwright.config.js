// @ts-check
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  /* Jalankan test secara paralel */
  fullyParallel: true,
  /* Gagal build pada CI jika terdapat test yang '.only' */
  forbidOnly: !!process.env.CI,
  /* Retry pada CI saja */
  retries: process.env.CI ? 2 : 0,
  /* Opt-out parallel tests pada CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter yang digunakan. Lihat https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  /* Konfigurasi umum untuk semua project */
  use: {
    /* URL dasar agar Anda tidak perlu mengetik URL penuh (misal: 'http://sim-kerjasama.test') */
    baseURL: 'http://127.0.0.1:8000',

    /* Mengumpulkan trace ketika test gagal. Lihat https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },

  /* Konfigurasi untuk target browser */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
});
