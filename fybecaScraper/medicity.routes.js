const express = require('express');
const puppeteer = require('puppeteer');

const router = express.Router();

router.get('/medicity/search/:query', async (req, res) => {
  const query = req.params.query;

  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();

  let jsonData = null;

  // Interceptar respuestas XHR
  page.on('response', async (response) => {
    const url = response.url();
    if (url.includes('/_v/public/graphql/v1') && response.request().method() === 'POST') {
      try {
        const data = await response.json();
        jsonData = data;
      } catch (e) {
        console.error('Error parseando JSON:', e);
      }
    }
  });

  try {
    await page.goto('https://www.farmaciasmedicity.com/', { waitUntil: 'domcontentloaded' });

    // Esperar el input y escribir
    await page.waitForSelector('input[placeholder="Buscar"]');
    await page.type('input[placeholder="Buscar"]', query, { delay: 100 });
    await page.keyboard.press('Enter');

    // Esperar por resultados (y por la respuesta interceptada)
    await page.waitForTimeout(3000);

    await browser.close();

    if (jsonData) {
      res.json(jsonData);
    } else {
      res.status(404).json({ error: 'No se encontró respuesta JSON' });
    }
  } catch (err) {
    await browser.close();
    console.error(err);
    res.status(500).json({ error: 'Error al procesar la búsqueda' });
  }
});

module.exports = router;
