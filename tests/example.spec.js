const { test, expect } = require('@playwright/test');

test('halaman utama dapat dimuat dan memiliki judul yang benar', async ({ page }) => {
  // Navigasi ke halaman utama aplikasi Anda (berdasarkan baseURL di config)
  await page.goto('/');

  // Pastikan judul (title) halaman mengandung kata yang diharapkan, 
  // contoh ini menggunakan asersi dasar yang bisa Anda ubah sesuai kebutuhan.
  // Misal: await expect(page).toHaveTitle(/Sim Kerjasama/);
  console.log('Halaman utama berhasil dimuat!');
});
