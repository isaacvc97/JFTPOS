const express = require('express');
const cors = require('cors');
const venom = require('venom-bot');
const http = require('http');
const { Server } = require('socket.io');
const fs = require('fs');
const path = require('path');
const axios = require("axios");
const assistantHandler = require('./assistant-handler');

const TOKEN_BASE_PATH = path.resolve(__dirname, 'tokens');
const SESSION_NAME = 'laravel-bot';
const SESSION_PATH = path.join(TOKEN_BASE_PATH, SESSION_NAME);

// Validar si el singleton.json está corrupto y eliminarlo si es necesario
function validateSessionFile() {
  const sessionFile = path.join(SESSION_PATH, 'session.json');
  if (fs.existsSync(sessionFile)) {
    try {
      const content = fs.readFileSync(sessionFile, 'utf8');
      JSON.parse(content);
      console.log('✅ Archivo session.json válido');
    } catch (err) {
      console.warn('⚠️ Archivo session.json corrupto. Se eliminará para regenerar sesión.');
      fs.rmSync(SESSION_PATH, { recursive: true, force: true });
      fs.mkdirSync(SESSION_PATH, { recursive: true });
    }
  }
}

// Asegurar que la carpeta de tokens existe con permisos correctos
if (!fs.existsSync(SESSION_PATH)) {
  fs.mkdirSync(SESSION_PATH, { recursive: true });
  console.log('✅ Carpeta de sesión creada:', SESSION_PATH);
} else {
  validateSessionFile();
}

function cleanUpSession() {
  if (fs.existsSync(SESSION_PATH)) {
    console.log('🧹 Limpiando sesión debido a error crítico...');
    try {
      fs.rmSync(SESSION_PATH, { recursive: true, force: true });
      console.log('✅ Sesión eliminada correctamente.');
    } catch (err) {
      console.error('❌ Error eliminando sesión:', err);
    }
  }
}

let client = null;
let qrCodeBase64 = null;
let isWhatsAppConnected = false;
let botNumber = null;

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: '*' },
});

async function enviarComando(texto, from, client) {
  try {
    const response = await axios.post("http://assistant:8050/procesar", {
      text: texto,
      origen: 'whatsapp',
    }, {headers: { 'Content-Type': 'application/json' }});

    const { texto: respuestaTexto, audio: respuestaAudio } = response.data;
    if (respuestaTexto) {
      await client.sendText(from, `🤖 Respuesta: "${respuestaTexto}"`);
    } else {
      await client.sendText(from, "No entendí el comando.");
    }
    if (respuestaAudio) {
      const rutaAudio = guardarAudioBase64(respuestaAudio, "respuesta");
      await client.sendVoice(from, rutaAudio);
    }
    console.log(`✅ Comando procesado: "${texto}" de ${from}`);
  } catch (err) {
    console.error("❌ Error enviando al microservicio:", err.message);
    await client.sendText(from, "Error al procesar el comando.");
  }
}
function guardarAudioBase64(base64, nombre = "respuesta") {
  const buffer = Buffer.from(base64, 'base64');
  const ruta = path.join(__dirname, `audios/${nombre}.mp3`);
  
  // Asegurar carpeta
  fs.mkdirSync(path.dirname(ruta), { recursive: true });
  fs.writeFileSync(ruta, buffer);
  return ruta;
}
app.use(cors());
app.use(express.json());

let esperandoRespuesta = new Set(); // Para evitar respuestas duplicadas
let estado_gpt = {
  activo: false,
};
venom
  .create(
    SESSION_NAME,
    (base64Qr, asciiQR, attempts, urlCode) => {
      console.log('📲 QR generado');
      qrCodeBase64 = base64Qr;
    },
    undefined,
    {
      sessionPath: '/app/tokens',  // La ruta base donde se guardan sesiones, igual que SESSION_PATH
      headless: 'new',
      browserArgs: ['--no-sandbox', '--disable-dev-shm-usage'],
      executablePath: '/usr/bin/google-chrome',
      multidevice: false,
    }
  )
  .then((cli) => {
    client = cli;
    console.log('✅ WhatsApp conectado');

    client.getHostDevice().then(info => {
      console.log("📞 Número del asistente:", JSON.stringify(info));
      botNumber = info.id.user; // e.g., 593986466338 
    });

    client.onStateChange((state) => {
      console.log('📶 Estado WhatsApp:', state);
      if (state === 'CONNECTED') {
        isWhatsAppConnected = true;
      } else if (['DISCONNECTED', 'CONFLICT', 'UNLAUNCHED'].includes(state)) {
        isWhatsAppConnected = false;

        if (state === 'CONFLICT' || state === 'UNLAUNCHED') {
          cleanUpSession();
          console.log('♻️ Estado conflictivo, reiniciando proceso...');
          process.exit(1); // Salir para reiniciar limpio
        }
      }
    });

    client.onStreamChange((state) => {
      console.log('📡 Estado de red:', state);
      if (state === 'DISCONNECTED' || state === 'CONFLICT') {
        isWhatsAppConnected = false;
      }
    });

    client.onAnyMessage(async (message) => {

      if(estado_gpt.activo && message.from == "18002428478@c.us"){
         await axios.post("http://assistant:8050/procesar", {
          text: message.body,
        },{ headers: { 'Content-Type': 'application/json' } });

        return estado_gpt.activo = false;
      }
      // const botNumber = await client.getHostNumber(); // e.g., 593987654321
      const botId = `${botNumber}@c.us`;

      const isFromMe = message.fromMe;
      const from = message.from;
      const body = message.body?.toLowerCase().trim();
      
      // Debug completo
      console.log("🔍 Mensaje recibido:", {
        body: from,
        from: body,
        to: message.to,
        sender: message.sender?.id,
        fromMe: message.fromMe,
      });

      // 0 - Validar si es un COMANDO !!
      // Solo procesar si me escribo a mí mismo y con comando @asistente

      /* if (isFromMe && from === message.to && body?.startsWith("@asistente") || 
          message.mimetype === "audio/ogg; codecs=opus" &&
          !message.isGroupMsg && // solo mensajes personales
          message.to.includes(botId)
      ){

        if (esperandoRespuesta.has(from)) return;

        esperandoRespuesta.add(from);

        const comando = body.replace("@asistente", "").trim();
        await enviarComando(comando, from, client);

        esperandoRespuesta.delete(from);
      } */

      // 1. Procesar comandos por texto
      
          // await client.sendText(from, "🧠 Comando recibido, procesando...");
        
        // }

        // const comando = body.replace("@asistente", "").trim();
        // await enviarComando(comando, from, client);
    
        // 1. Detectar si es imagen
        if (message.mimetype && message.mimetype.startsWith("image/")) {
          console.log("📷 Imagen recibida de:", message.sender.name || message.sender.id);

          // Descargar imagen completa en base64
          const media = await client.downloadMedia(message);

          // media = { mimetype: 'image/jpeg', data: 'base64string' }

          // Guardar o reenviar al frontend
          const chatMessage = {
            fromMe: message.fromMe,
            type: "image",
            mimetype: media.mimetype,
            body: media.data,
            sender: message.sender.pushname || message.sender.id,
            timestamp: message.timestamp,
            messageId: message.id
          };

          // Aquí podrías emitirlo por WebSocket, guardarlo en una DB, etc.
          console.log("Imagen descargada:", chatMessage);
          await client.sendText(message.from, chatMessage);
        }

      // 2. Procesar audios
      /* if (
        message.mimetype === "audio/ogg; codecs=opus" &&
        !message.isGroupMsg && // solo mensajes personales
        message.to.includes(botId)// === client.info.wid._serialized // solo si el audio va al asistente
      ) {
        try {
          await client.sendText(message.from, "🎤 Recibí un audio, procesando...");
          const buffer = await client.decryptFile(message);
          const formData = new FormData();
          formData.append("file", new Blob([buffer]), "audio.ogg");

          const response = await axios.post("http://assistant:8050/transcribir", formData, {
            headers: formData.getHeaders?.() || {
              "Content-Type": "multipart/form-data"
            }
          });

          const { texto, audio } = response.data;
          if (texto) {
            await client.sendText(message.from, `🗣 Dices: "${texto}"`);
            await enviarComando(texto, message.from, client);
          } else {
            await client.sendText(message.from, "No entendí el audio.");
          }

          if (audio) {
            const ruta = guardarAudioBase64(audio);
            await client.sendVoice(message.from, ruta);
          }
        } catch (e) {
          console.error("❌ Error al procesar audio:", e.message);
          await client.sendText(message.from, "Error al procesar el audio.");
        }
      } */
      /* if (message.isMedia && message.type === 'audio' || message.mimetype === "audio/ogg; codecs=opus") {
        const buffer = await client.decryptFile(message)
        const fs = require('fs')
        const FormData = require('form-data')
        const axios = require('axios')

        const tempPath = `./audio-${Date.now()}.ogg`
        fs.writeFileSync(tempPath, buffer)

        const form = new FormData()
        form.append('audio', fs.createReadStream(tempPath))

        const res = await axios.post('http://stt-tts-service:8010/asistente', form, {
          headers: form.getHeaders(),
          responseType: 'stream'
        })

        // Guardar y enviar el audio
        const replyPath = `./respuesta-${Date.now()}.mp3`
        const out = fs.createWriteStream(replyPath)
        await new Promise((resolve) => res.data.pipe(out).on('finish', resolve))

        await client.sendFile(message.from, replyPath, 'respuesta.mp3', '')

        fs.unlinkSync(tempPath)
        fs.unlinkSync(replyPath)
      } else {
        const respuesta = await axios.post("http://asistente:8010/texto", {
          text: mensaje,
          session_id: numeroTelefono,
        });
        const texto = respuesta.headers["x-texto"];
        const audioBase64 = respuesta.data; // si manejas binario
        client.sendText(numeroTelefono, texto);

        if (texto.includes("¿Deseas guardar este cambio?")) {
          // Marca que hay una confirmación pendiente
        }
      } */
    
      io.emit("whatsapp-message", message);
    });

    io.on('connection', (socket) => {
      socket.on('send-message', (data) => {
        if (client) client.sendText(data.to, data.body);
      });
    });
  })
  .catch((error) => {
    console.error('❌ Error conectando a WhatsApp:', error);
    const errStr = String(error).toLowerCase();
    if (errStr.includes('singletonlock') || errStr.includes('error no open browser')) {
      cleanUpSession();
      process.exit(1);
    }
  });

// Manejo de señales para cierre limpio
async function closeClientAndExit() {
  console.log('🛑 Proceso detenido. Cerrando sesión...');
  try {
    if (client) await client.close();
  } catch (err) {
    console.error('❌ Error cerrando cliente:', err);
  }
  process.exit(0);
}

process.on('SIGINT', closeClientAndExit);
process.on('SIGTERM', closeClientAndExit);

// Rutas HTTP
app.get('/get-qr', (req, res) => {
  if (qrCodeBase64) res.json({ qr: qrCodeBase64 });
  else res.status(404).json({ error: 'QR aún no generado' });
});

app.get('/status', async (req, res) => {
  try {
    const info = await client.getHostDevice();
    res.json({ connected: !!info?.id });
  } catch {
    res.json({ connected: false });
  }
});

app.get('/inbox', async (req, res) => {
  if (!client) return res.status(500).json({ error: 'Cliente no iniciado' });
  try {
    const chats = await client.getAllChats();
    res.json(chats);
  } catch (err) {
    res.status(500).json({ error: 'Error al obtener chats' });
  }
});

app.get('/chat/:id', async (req, res) => {
  try {
    const messages = await client.getAllMessagesInChat(req.params.id, true);
    res.json(messages.map(msg => ({
      id: msg.id,
      fromMe: msg.fromMe,
      body: msg.body,
      timestamp: msg.timestamp,
    })));
  } catch {
    res.status(500).json({ error: 'Error al obtener conversación' });
  }
});

app.post('/send-message', async (req, res) => {
  if (!client) return res.status(500).json({ error: 'Cliente no inicializado' });
  try {
    if (!req.body.to || !req.body.message)  return res.status(400).json({ error: 'Faltan datos: "para" y "message" son requeridos' });    
    
    if (req.body.to == "18002428478@c.us") estado_gpt.activo = true;

    console.log('Enviando mensaje:', req.body, "a:", req.body.to);

    await client.sendText(req.body.to, req.body.message);
    res.json({ status: 'success' });
  } catch (error) {
    res.status(500).json({ error: 'Error enviando mensaje', details: error });
  }
});

// /contacts?nombre=mama
app.get('/contacts', async (req, res) => {
  const nombreBuscado = (req.query.nombre || '').toLowerCase();
  const contacts = await client.getAllContacts();

  return contacts.length === 0
    ? res.status(404).json({ error: 'No hay contactos disponibles' })
    : res.json(contacts.map(c => ({
        id: c.id._serialized,
        nombre: c.pushname || c.name || c.id.user
      })));

  // Filtrado difuso
  const contacto = contacts.find((c) => {
    const nombre = (c.name || c.pushname || c.id.user || '').toLowerCase();
    return nombre.includes(nombreBuscado);
  });

  if (!contacto) {
    return res.status(404).json({ error: 'Contacto no encontrado' });
  }

  res.json({
    id: contacto.id._serialized,
    nombre: contacto.pushname || contacto.name || contacto.id.user
  });
});


// Ruta para enviar mensaje
app.use(express.json());
app.post('/send-message', async (req, res) => {
  const { id, mensaje } = req.body;
  if (!id || !mensaje) {
    return res.status(400).json({ error: 'Faltan datos' });
  }

  try {
    await client.sendText(id, mensaje);
    res.json({ ok: true, enviado: true });
  } catch (err) {
    res.status(500).json({ error: 'No se pudo enviar el mensaje', detalle: err });
  }
});


const PORT = 8001;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`🚀 Microservicio WhatsApp + WebSocket en http://0.0.0.0:${PORT}`);
});
