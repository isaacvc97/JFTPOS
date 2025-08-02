
const path = require('path');
const express = require('express');
const puppeteer = require('puppeteer');

const app = express();
const PORT = 8002;

// INVENTARIO POR VOZ Sirve archivos estáticos desde "public"
app.use(express.static(path.join(__dirname, 'public')));

app.get('/voz', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'voz.html'));
});
/* FIN INVENTARIO POR VOZ */



let browser = null;
let ultimaActividad = Date.now();
const TIEMPO_INACTIVIDAD = 5 * 60 * 1000; // 5 minutos

// Inicia el navegador si no está ya activo
async function iniciarBrowser() {
  if (!browser) {
    browser = await puppeteer.launch({
      executablePath: '/usr/bin/google-chrome',
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    console.log('🟢 Navegador iniciado');
  }
  return browser;
}

// Cierra el navegador manual o automáticamente
async function cerrarBrowser() {
  if (browser) {
    await browser.close();
    browser = null;
    console.log('🔴 Navegador cerrado');
  }
}

// Revisa la inactividad periódicamente
setInterval(async () => {
  const ahora = Date.now();
  if (browser && ahora - ultimaActividad > TIEMPO_INACTIVIDAD) {
    console.log('⏱️ Cerrando navegador por inactividad');
    await cerrarBrowser();
  }
}, 60 * 1000); // cada minuto

// Función principal de scraping
async function buscarProductosFybeca(terminoBusqueda) {
  const browser = await iniciarBrowser();
  const page = await browser.newPage();

  // Bloquear recursos que no se necesitan
  await page.setRequestInterception(true);
  page.on('request', req => {
    const type = req.resourceType();
    if (['image', 'stylesheet', 'font'].includes(type)) {
      req.abort();
    } else {
      req.continue();
    }
  });

  await page.setViewport({ width: 1366, height: 768 });
  await page.setUserAgent(
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
  );

  const resultado = { productos: [], mensaje: '' };

  try {
    await page.goto('https://www.fybeca.com', { waitUntil: 'domcontentloaded' });

    await page.waitForSelector('.search-field', { visible: true });
    await page.type('.search-field', terminoBusqueda);

    await page.waitForFunction(() => {
      const el = document.querySelector('.suggestions-products-list');
      return el && window.getComputedStyle(el).display !== 'none';
    }, { timeout: 5000 });

    const productos = await page.$$eval('.suggestions-products-list .row', items => {
      return items.map(item => {
        const col3 = item.querySelector('.col-3');
        const col9 = item.querySelector('.col-9');

        const link = col3?.querySelector('a')?.href || null;
        const imagen = col3?.querySelector('img')?.src || null;

        const name = col9?.querySelectorAll('a .suggestions-product-name')[0]?.innerText.trim() || null;
        const nombre = col9?.querySelectorAll('a')[0]?.innerText.trim() || null;
        const descripcion = col9?.querySelectorAll('a')[1]?.innerText.trim() || null;

        const precio = col9?.querySelector('.suggestions-final-price')?.innerText.trim() || 'No disponible';
        const precioDescuento = col9?.querySelector('.suggestions-reduced-price')?.innerText.trim() || 'No disponible';

        return {
          link,
          name,
          nombre,
          imagen,
          descripcion,
          precio,
          precioDescuento
        };
      });
    });

    resultado.productos = productos;
    resultado.mensaje = productos.length ? 'Finalizado' : 'Sin resultados';
  } catch (err) {
    resultado.mensaje = `Error: ${err.message}`;
  } finally {
    await page.close();
  }

  return resultado;
}

// Endpoint de búsqueda
app.get('/estructura1/search', async (req, res) => {
  const termino = req.query.query || '';
  if (!termino) {
    return res.status(400).json({ error: 'Debe proporcionar un término de búsqueda' });
  }

  try {
    ultimaActividad = Date.now(); // Actualiza actividad
    const resultado = await buscarProductosFybeca(termino);
    res.json(resultado);
  } catch (err) {
    res.status(500).json({ error: `Error del servidor: ${err.message}` });
  }
});

// Endpoint para obtener detalles del producto desde su link
app.get('/details', async (req, res) => {
  const rawLink = req.query.link;
  if (!rawLink) {
    return res.status(400).json({ error: 'Debe proporcionar el link del producto' });
  }

  const link = decodeURIComponent(rawLink);

  // Validar dominio
  if (/^https:\/\/(www\.)?fybeca\.com/.test(link)) {
    // return res.status(400).json({ error: 'Solo se permiten enlaces de fybeca.com' });
    try {
      const browser = await iniciarBrowser();
      const page = await browser.newPage();
  
      await page.setRequestInterception(true);
      page.on('request', req => {
        const type = req.resourceType();
        if (['image', 'stylesheet', 'font'].includes(type)) {
          req.abort();
        } else {
          req.continue();
        }
      });
  
      await page.setViewport({ width: 1366, height: 768 });
      await page.setUserAgent(
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
      );
  
      await page.goto(link, { waitUntil: 'domcontentloaded', timeout: 15000 });
  
      // Extraer opciones del select
      const opciones = await page.$$eval('select.product-select option', opts =>
        opts.map(opt => ({
          valor: opt.value,
          texto: opt.innerText.trim()
        }))
      ) || [];
  
      const name = await page.$eval('.product-name', el => el.innerText.trim());
  
      // Extraer los primeros 2 bloques info-content
      const { details, estructura } = await page.evaluate(() => {
        const resultado = [];
        let estructuraDetectada = null;
  
        const tab1 = document.querySelector('.tab1Content');
        const tab2 = document.querySelector('.tab2Content');
  
        // ===== ESTRUCTURA 1 =====
        if (tab1 && tab1.querySelectorAll('p strong').length) {
          estructuraDetectada = 'estructura1';
  
          // tab1Content: <p><strong>Título</strong> texto</p>
          const ps1 = Array.from(tab1.querySelectorAll('p'));
          ps1.forEach(p => {
            const strong = p.querySelector('strong');
            if (strong) {
              const titulo = strong.innerText.trim();
              const texto = p.innerText.replace(strong.innerText, '').trim();
              resultado.push({ titulo, texto });
            }
          });
  
          // tab2Content: <p><strong>Título</strong></p>
          if (tab2) {
            const ps2 = Array.from(tab2.querySelectorAll('p'));
            for (let i = 0; i < ps2.length; i++) {
              const strong = ps2[i].querySelector('strong');
              if (strong) {
                const titulo = strong.innerText.trim();
                const texto = ps2[i + 1]?.innerText.trim() || '';
                resultado.push({ titulo, texto });
              }
            }
          }
        }
  
        // ===== ESTRUCTURA 2 =====
        else if (tab1 && tab1.querySelectorAll('h4').length) {
          estructuraDetectada = 'estructura2';
  
          // tab1Content: <h4> + <ul><li>
          tab1.querySelectorAll('h4').forEach(h4 => {
            const titulo = h4.innerText.trim();
            const ul = h4.nextElementSibling;
            const items = ul && ul.tagName === 'UL'
              ? Array.from(ul.querySelectorAll('li')).map(li => li.innerText.trim())
              : [];
            const texto = items.join('; ');
            resultado.push({ titulo, texto });
          });
  
          // tab2Content: <tr><th><td>
          const filas = tab2?.querySelectorAll('table tr') || [];
          filas.forEach(fila => {
            const th = fila.querySelector('th');
            const td = fila.querySelector('td');
            if (th && td) {
              resultado.push({
                titulo: th.innerText.trim(),
                texto: td.innerText.trim()
              });
            }
          });
        }
  
        return {
          estructura: estructuraDetectada || 'desconocida',
          details: resultado
        };
      });
  
      await page.close();
      ultimaActividad = Date.now();
  
      res.json({ name, details, prices:opciones, estructura, source: 'fybeca' });
    } catch (err) {
      res.status(500).json({ error: `Error al obtener detalles: ${err.message}` });
    }
  }

  // Validar dominio https://farmaciascruzazul.ec
  if (/^https:\/\/(www\.)?farmaciascruzazul\.ec/.test(link)) {
    // return res.status(400).json({ error: 'Solo se permiten enlaces de fybeca.com' });
    console.log(`🔍 Obteniendo detalles de: ${link}`);
    try {
      const browser = await iniciarBrowser();
      const page = await browser.newPage();

      await page.setRequestInterception(true);
      page.on('request', req => {
        const type = req.resourceType();
        if (['image', 'stylesheet', 'font'].includes(type)) {
          req.abort();
        } else {
          req.continue();
        }
      });

      await page.setViewport({ width: 1366, height: 768 });
      await page.setUserAgent(
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
      );

      // 🔁 ESTA LÍNEA ESCLAVE: Cargar la URL antes de extraer
      await page.goto(link, {
        waitUntil: 'domcontentloaded',
        timeout: 20000
      });

      const data = await page.evaluate(() => {
        const result = {
          name: null,
          sku: null,
          price: null,
          brand: null,
          category: null,
          details: {}
        };

        const h1 = document.querySelector('h1.main_header');
        result.name = h1?.textContent.trim() || null;

        const sku = document.querySelector('span.sku');
        result.sku = sku?.textContent.trim().replace('Código de artículo:', '') || null;

        const price = document.querySelector('span.price[itemprop="price"]');
        result.price = price?.textContent.trim() || null;

        const brandInput = document.querySelector('#product_brand');
        result.brand = brandInput?.value.trim() || null;

        const catInput = document.querySelector('#product_masterCategory');
        result.category = catInput?.value.trim() || null;

        const slot = document.querySelector('div[data-slot-id="7"]');
        if (slot) {
          const content = {};
          const descriptionContainer = slot.querySelector('div[id^="product_longdescription"]');

          if (descriptionContainer) {
            const children = Array.from(descriptionContainer.children);

            let currentKey = null;
            for (const el of children) {
              if (el.tagName === 'STRONG') {
                currentKey = el.textContent.trim();
              } else if (el.tagName === 'P' && currentKey) {
                content[currentKey] = el.textContent.trim();
                currentKey = null;
              }
            }

            result.details = content;
          }
        }

        return result;
      });

      await page.close();
      ultimaActividad = Date.now();

      if (!data.name) {
        return res.status(404).json({ error: 'No se encontró información del producto' });
      }

      return res.json({ ...data });

    } catch (err) {
      console.error('❌ Error en detalle:', err.message);
      return res.status(500).json({ error: 'Error al obtener detalles del producto', detalle: err.message });
    }
  }

});

app.get('/2/search/:query', async (req, res) => {
  const query = req.params.query;
  console.log(`Buscando: ${query}`);

  const browser = await iniciarBrowser(); // Asumo que esta función lanza puppeteer con args necesarios
  const page = await browser.newPage();

  await page.setRequestInterception(true);
  page.on('request', req => {
    const type = req.resourceType();
    if (['image', 'stylesheet', 'font'].includes(type)) {
      req.abort();
    } else {
      req.continue();
    }
  });

  await page.setViewport({ width: 1366, height: 768 });
  await page.setUserAgent(
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
  );

  let jsonData = null;

  page.on('response', async (response) => {
    const url = response.url();
    if (
      // url.includes('/_v/public/graphql/v1') &&
      // response.request().method() === 'GET'
      true
    ) {
      console.log(`🔍 Respuesta de: ${url}`);

      try {
        const data = await response.json();
        console.log(`🔍 #: ${JSON.stringify(data)}`)
        // ✅ Verifica si tiene la estructura deseada
        if (
          data?.data?.productSuggestions?.products &&
          Array.isArray(data.data.productSuggestions.products)
        ) {
          jsonData = data;
        }
      } catch (e) {
        console.error('❌ Error parseando JSON:', e.message);
      }
    }
  });

  try {
    await page.goto('https://www.farmaciasmedicity.com/', {
      waitUntil: 'domcontentloaded',
      timeout: 15000
    });

    await page.waitForSelector('input[placeholder="Buscar"]', { timeout: 10000 });
    await page.type('input[placeholder="Buscar"]', query, { delay: 80 });

    // ⏱️ Esperar que lleguen respuestas XHR (~2s suelen bastar)
    await page.waitForTimeout(3000);

    await browser.close();

    if (jsonData) {
      res.json(jsonData);
    } else {
      res.status(404).json({ error: 'No se encontró estructura válida con productos' });
    }
  } catch (err) {
    console.error('❌ Error principal:', err.message);
    await browser.close();
    res.status(500).json({ error: 'Error al procesar la búsqueda', detalle: err.message });
  }
});

app.get('/3/search/:query', async (req, res) => {
  const query = req.params.query;
  console.log(`🔍 Buscando en Cruz Azul: ${query}`);

  const browser = await iniciarBrowser();
  const page = await browser.newPage();

  try {
    await page.setViewport({ width: 1280, height: 800 });

    await page.goto('https://farmaciascruzazul.ec/cruzazul', {
      waitUntil: 'domcontentloaded',
      timeout: 20000
    });

    // Focus y búsqueda
    await page.waitForSelector('#SimpleSearchForm_SearchTerm', { timeout: 10000 });
    await page.type('#SimpleSearchForm_SearchTerm', query, { delay: 50 });
    await page.keyboard.press('Enter');

    // Esperar los resultados
    await page.waitForSelector('.product_listing_container', { timeout: 15000 });

    // Extraer información
    const items = await page.$$eval('div.product_listing_container ul > li', (elements) =>
      elements.map((li) => {
        const container = li.querySelector('.product');
        if (!container) return null;

        const getText = (selector) => {
          const el = container.querySelector(selector);
          return el ? el.textContent.trim() : null;
        };

        const getImage = (selector) => {
          const el = container.querySelector(selector);
          return el ? el.src : null;
        };

        return {
          brand: getText('.Brand'),
          category: getText('.MasterCategory'),
          name: getText('.product_name a'),
          price: getText('.product_price'),
          image: getImage('.product_image .image > a > img'),
        };
      }).filter(item => item !== null)
    );

    await browser.close();

    res.json({ total: items.length, results: items });
  } catch (err) {
    console.error('❌ Error durante scraping:', err.message);
    await browser.close();
    res.status(500).json({ error: 'Error al buscar medicamentos', detalle: err.message });
  }
});

app.get('/estructura2/search', async (req, res) => {
  const query = req.query.query;
  if (!query) return res.status(400).json({ error: 'Debe proporcionar un término de búsqueda' });

  console.log(`🔍 Buscando en Cruz Azul: ${query}`);

  const browser = await iniciarBrowser();
  const page = await browser.newPage();

  await page.setRequestInterception(true);
  page.on('request', req => {
    const type = req.resourceType();
    if (['image', 'stylesheet', 'font'].includes(type)) {
      req.abort();
    } else {
      req.continue();
    }
  });

  await page.setViewport({ width: 1366, height: 768 });
  await page.setUserAgent(
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
  );

  try {
    await page.goto('https://farmaciascruzazul.ec/cruzazul', {
      waitUntil: 'domcontentloaded',
      timeout: 20000
    });

    // Hacer focus al buscador y escribir query
    await page.waitForSelector('#SimpleSearchForm_SearchTerm', { timeout: 10000 });
    await page.type('#SimpleSearchForm_SearchTerm', query, { delay: 60 });
    await page.keyboard.press('Enter');

    // Esperar el contenedor con resultados
    try {
      await page.waitForSelector('ul.grid_mode.grid > li', { timeout: 5000 });
    } catch (e) {
      console.warn('⏱️ Tiempo de espera agotado para resultados');
      await page.close();
      return res.status(408).json({ error: 'Timeout esperando resultados del buscador' });
    }

    const items = await page.$$('ul.grid_mode.grid > li');
    if (items.length === 0) {
      await page.close();
      return res.status(404).json({ error: 'No se encontraron productos' });
    }
    console.log(`🔍 Encontrados ${items.length} productos`);

    // Extraer resultados
    const results = await page.$$eval('ul.grid_mode.grid > li', (items) =>
      items.map((li) => {
        const getText = (selector) => {
          const el = li.querySelector(selector);
          return el ? el.textContent.trim() : null;
        };

        const getInputValue = (selector) => {
          const el = li.querySelector(selector);
          return el ? el.value.trim() : null;
        };

        const getImage = () => {
          const img = li.querySelector('.product_image img');
          return img?.getAttribute('src')?.startsWith('/')
            ? 'https://farmaciascruzazul.ec' + img.getAttribute('src')
            : img?.getAttribute('src') || null;
        };

        const getLink = () => {
          const a = li.querySelector('.product_name a');
          return a ? a.href : null;
        };

        return {
          nombre: getText('.Brand'),
          descripcion: getText('.product_name a'),
          category: getText('.MasterCategory'),
          precio: getInputValue('input[id^="ProductInfoPrice_"]'),
          precioDescuento: getText('.old_price'),
          imagen: getImage(),
          link: getLink()
        };
      })/* .filter(i => i.nombre) */
    );

    ultimaActividad = Date.now(); // Actualiza actividad
    res.json({ mensaje:results.length, total: results.length, productos: results });
  } catch (error) {
    console.error('❌ Error scraping:', error.message);
    await browser.close();
    res.status(500).json({ error: 'Error al buscar productos', detalle: error.message });
  }
});

// Endpoint para cerrar navegador manualmente
app.get('/shutdown', async (req, res) => {
  try {
    await cerrarBrowser();
    res.json({ mensaje: 'Navegador cerrado correctamente' });
  } catch (err) {
    res.status(500).json({ error: `Error al cerrar navegador: ${err.message}` });
  }
});

// Inicializar servidor
app.listen(PORT, () => {
  console.log(`🚀 Microservicio escuchando en http://0.0.0.0:${PORT}`);
});
