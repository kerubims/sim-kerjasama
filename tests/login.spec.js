import { test, expect } from '@playwright/test';

test.describe('Fungsi Login untuk Berbagai Role', () => {

  test('Super Admin dapat login dengan sukses', async ({ page }) => {
    // Navigasi ke halaman login
    await page.goto('/login');

    // Mengisi form login
    await page.fill('input[name="email"]', 'admin@univ.ac.id');
    await page.fill('input[name="password"]', 'password');

    // Klik tombol submit (biasanya menggunakan tag button untuk submit form)
    await page.click('button[type="submit"]');

    // Verifikasi bahwa login berhasil dan dialihkan ke dashboard/halaman utama
    // URL biasanya mengandung '/dashboard' setelah login berhasil
    await expect(page).toHaveURL(/.*dashboard/);

    // Anda bisa tambahkan asersi lain, misal melihat nama role atau nama user di halaman
    await expect(page.getByRole('heading', { name: /Selamat Datang, Super Admin/i })).toBeVisible();
  });

  test('Unit Pengusul dapat login dengan sukses', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="email"]', 'unit_ti@univ.ac.id');
    await page.fill('input[name="password"]', 'password');

    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/.*dashboard/);
    await expect(page.getByRole('heading', { name: /Selamat Datang, Unit TI/i })).toBeVisible();
  });

  test('Client / Mitra dapat login dengan sukses', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="email"]', 'pt_tech@mitra.com');
    await page.fill('input[name="password"]', 'password');

    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/.*dashboard/);
    await expect(page.getByRole('heading', { name: /Selamat Datang, PT Teknologi/i })).toBeVisible();
  });

  test('Gagal login dengan kredensial yang salah', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="email"]', 'admin@univ.ac.id');
    await page.fill('input[name="password"]', 'password_salah');

    await page.click('button[type="submit"]');

    // Harus tetap di halaman login
    await expect(page).toHaveURL(/.*login/);
    
    // Harus ada pesan error (Breeze biasanya menggunakan 'These credentials do not match our records.')
    // Ini asersi opsional, sesuaikan dengan teks error di aplikasi Anda
    // await expect(page.locator('text=These credentials do not match')).toBeVisible();
  });

});
