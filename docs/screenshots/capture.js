// Mengambil ulang screenshot aplikasi ke folder ini.
//
// Persiapan:
//   1. Impor ulang database.sql (data contoh) dan jalankan aplikasi, misalnya: php -S localhost:8000
//   2. npm install playwright (sekali saja, di folder mana pun)
//   3. node docs/screenshots/capture.js
//
// Variabel lingkungan opsional:
//   BASE_URL     alamat aplikasi (default http://localhost:8000/index.php)
//   ASSETS_DIR   folder berisi bootstrap/ (= folder dist paket npm bootstrap) dan bootstrap-icons/
//                (= folder font paket npm bootstrap-icons) untuk menggantikan CDN
//                jika komputer tidak bisa mengakses cdn.jsdelivr.net
//
// Skrip ini mengubah data (mengonfirmasi reservasi dan membuat satu pesanan),
// jadi impor ulang database.sql setelah selesai.

const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000/index.php';
const ASSETS_DIR = process.env.ASSETS_DIR || '';
const OUT = __dirname;
const ROOT = path.resolve(__dirname, '..', '..');

function tomorrow() {
    const d = new Date(Date.now() + 7 * 3600 * 1000 + 24 * 3600 * 1000); // Asia/Jakarta
    return d.toISOString().slice(0, 10);
}

async function useLocalAssets(page) {
    if (!ASSETS_DIR) {
        return;
    }
    await page.route('https://cdn.jsdelivr.net/**', (route) => {
        const url = new URL(route.request().url());
        const file = url.pathname.includes('bootstrap-icons')
            ? path.join(ASSETS_DIR, 'bootstrap-icons', url.pathname.split('/font/')[1])
            : path.join(ASSETS_DIR, 'bootstrap', url.pathname.split('/dist/')[1]);
        return route.fulfill({ path: file });
    });
}

async function shot(page, name, options = {}) {
    await page.evaluate(() => document.activeElement && document.activeElement.blur()); // tanpa outline fokus
    await page.mouse.move(2, 2);
    await page.waitForTimeout(400); // tunggu animasi/hover selesai
    await page.screenshot({ path: path.join(OUT, name), fullPage: true, ...options });
    console.log('ok', name);
}

async function go(page, query) {
    await page.goto(BASE_URL + '?page=' + query);
}

(async () => {
    // Format tanggal & jam di input mengikuti Indonesia (dd/mm/yyyy, 19.00)
    const browser = await chromium.launch({ args: ['--lang=id-ID'], env: { ...process.env, LANG: 'id_ID.UTF-8', LANGUAGE: 'id' } });
    const context = await browser.newContext({ locale: 'id-ID', timezoneId: 'Asia/Jakarta', viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();
    await useLocalAssets(page);
    page.on('dialog', (dialog) => dialog.accept());

    await go(page, 'dashboard');
    await shot(page, '01-dashboard.png');
    await go(page, 'customers');
    await shot(page, '02-pelanggan.png');
    await go(page, 'tables');
    await shot(page, '03-meja.png');
    await go(page, 'menu');
    await shot(page, '04-menu.png');
    await go(page, 'reservations&scope=all');
    await shot(page, '05-reservasi-daftar.png');

    // Form reservasi: pelanggan baru + 5 tamu (meja kecil otomatis tidak bisa dipilih)
    await go(page, 'reservations&action=create');
    await page.click('label[for="customer_mode_new"]');
    await page.fill('#new_customer_name', 'Rina Kurnia');
    await page.fill('#new_customer_phone', '081299990000');
    await page.fill('#guest_count', '5');
    await page.dispatchEvent('#guest_count', 'input');
    await shot(page, '06-reservasi-form-pelanggan-baru.png');

    // Error tumpang tindih: M01 besok 19:00, padahal sudah ada reservasi 18:30
    await go(page, 'reservations&action=create');
    await page.selectOption('#customer_id', '2');
    await page.fill('#guest_count', '2');
    await page.dispatchEvent('#guest_count', 'input');
    await page.selectOption('#table_id', '1');
    await page.fill('#reservation_date', tomorrow());
    await page.fill('#reservation_time', '19:00');
    await Promise.all([page.waitForNavigation(), page.click('button:has-text("Simpan Reservasi")')]);
    await shot(page, '07-reservasi-error-tumpang-tindih.png');

    // Konfirmasi reservasi Andi (#3), lalu buat pesanan dari reservasi tersebut
    await go(page, 'reservations&scope=today');
    await Promise.all([page.waitForNavigation(), page.click('form:has(input[name="id"][value="3"]) button:has-text("Konfirmasi")')]);
    await Promise.all([page.waitForNavigation(), page.click('a[href*="reservation_id=3"]')]);
    await page.click('tr.menu-row:has-text("Ayam Bakar") .qty-plus');
    await page.click('tr.menu-row:has-text("Ayam Bakar") .qty-plus');
    await page.fill('tr.menu-row:has-text("Es Teh Manis") .qty-input', '3');
    await page.dispatchEvent('tr.menu-row:has-text("Es Teh Manis") .qty-input', 'input');
    await shot(page, '08-pesanan-dari-reservasi.png');

    await Promise.all([page.waitForNavigation(), page.click('button:has-text("Simpan Pesanan")')]);
    const detailUrl = page.url();
    await shot(page, '10-pesanan-detail.png');

    await go(page, 'orders&action=create');
    await page.click('label[for="order_type_walkin"]');
    await shot(page, '09-pesanan-walkin.png');

    await page.goto(detailUrl);
    await Promise.all([page.waitForNavigation(), page.click('button:has-text("Tandai Selesai")')]);
    await shot(page, '11-pesanan-selesai-reservasi-otomatis.png');

    await go(page, 'reservations&scope=today');
    await shot(page, '12-reservasi-selesai-otomatis.png');

    await go(page, 'reports');
    await shot(page, '13-laporan.png');
    await page.emulateMedia({ media: 'print' });
    await go(page, 'reports');
    await shot(page, '14-laporan-cetak.png');
    await page.emulateMedia({ media: 'screen' });

    const mobile = await context.newPage();
    await mobile.setViewportSize({ width: 390, height: 844 });
    await useLocalAssets(mobile);
    await mobile.goto(BASE_URL + '?page=reservations&scope=all');
    await shot(mobile, '15-ponsel-reservasi.png');

    // Output unit test sebagai gambar terminal
    let output;
    try {
        output = execFileSync('php', [path.join(ROOT, 'tests', 'test_functions.php')], { encoding: 'utf8' });
    } catch (error) {
        output = error.stdout; // tetap tampilkan output walaupun ada test yang gagal
    }
    const escaped = output.replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/\[PASS\]/g, '<span class="pass">[PASS]</span>')
        .replace(/\[FAIL\]/g, '<span class="fail">[FAIL]</span>');
    const terminal = await context.newPage();
    await terminal.setViewportSize({ width: 900, height: 600 });
    await terminal.setContent(`<html><body style="margin:0;background:#1e1e1e">
        <div style="background:#333;color:#ccc;font:13px sans-serif;padding:6px 12px">Terminal — php tests/test_functions.php</div>
        <pre style="margin:0;padding:12px 16px;color:#ddd;font:13px/1.45 Consolas,'DejaVu Sans Mono',monospace">$ php tests/test_functions.php\n${escaped}</pre>
        <style>.pass{color:#4ec97b}.fail{color:#f14c4c}</style></body></html>`);
    await shot(terminal, '16-unit-test.png');

    await browser.close();
})();
