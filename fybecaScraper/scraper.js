const puppeteer = require('puppeteer');

let browser; // Navegador reutilizable

async function iniciarBrowser() {
  if (!browser) {
    browser = await puppeteer.launch({
      executablePath: '/usr/bin/google-chrome',
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
  }
  return browser;
}

async function buscarProductosFybeca(terminoBusqueda) {
  const browser = await iniciarBrowser();
  const page = await browser.newPage();

  // Optimiza el rendimiento bloqueando recursos innecesarios
  await page.setRequestInterception(true);
  page.on('request', (req) => {
    const resourceType = req.resourceType();
    if (['image', 'stylesheet', 'font'].includes(resourceType)) {
      req.abort();
    } else {
      req.continue();
    }
  });

  // Forzar vista de escritorio
  await page.setViewport({ width: 1366, height: 768 });
  await page.setUserAgent(
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
  );

  const resultado = { productos: [], mensaje: '' };

  try {
    await page.goto('https://www.fybeca.com', { waitUntil: 'domcontentloaded' });

    await page.waitForSelector('.search-field', { visible: true });
    await page.type('.search-field', terminoBusqueda);

    // Esperar a que el modal de sugerencias aparezca
    await page.waitForFunction(() => {
      const el = document.querySelector('.suggestions-products-list');
      return el && window.getComputedStyle(el).display !== 'none';
    }, { timeout: 5000 });

    // Extraer productos directamente cuando estén listos
    const productos = await page.$$eval('.suggestions-products-list .row', items => {
      return items.map(item => {
        const col3 = item.querySelector('.col-3');
        const col9 = item.querySelector('.col-9');

        const link = col3?.querySelector('a')?.href || null;
        const imagen = col3?.querySelector('img')?.src || null;

        const nombre = col9?.querySelectorAll('a')[0]?.innerText.trim() || null;
        const descripcion = col9?.querySelectorAll('a')[1]?.innerText.trim() || null;

        const precio = col9?.querySelector('.suggestions-final-price')?.innerText.trim() || 'No disponible';
        const precioDescuento = col9?.querySelector('.suggestions-reduced-price')?.innerText.trim() || 'No disponible';

        return {
          link,
          imagen,
          nombre,
          descripcion,
          precio,
          precioDescuento
        };
      });
    });

    resultado.productos = productos;
    resultado.mensaje = productos.length ? 'Éxito' : 'Sin resultados';
  } catch (e) {
    resultado.mensaje = `Error general: ${e.message}`;
  } finally {
    await page.close(); // Cierra solo la página, no el navegador
  }

  return resultado;
}

// Cierra el navegador manualmente al finalizar todo (opcional)
async function cerrarBrowser() {
  if (browser) {
    await browser.close();
    browser = null;
  }
}

module.exports = { buscarProductosFybeca, cerrarBrowser };
