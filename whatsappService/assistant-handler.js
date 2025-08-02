const axios = require('axios')
const fs = require('fs')
const path = require('path')

module.exports = async function handleAssistant(client, message) {
  const number = message.from
  const sessionId = number.replace(/[^0-9]/g, '') // simple ID

  // 🧠 Función para procesar texto por el asistente
  const procesarTexto = async (texto) => {
    const res = await axios.post('http://stt-tts-service:8010/texto', {
      text: texto,
      session_id: sessionId
    }, { responseType: 'stream' })

    const textoRespuesta = res.headers['x-texto']
    let audioPath = path.join(__dirname, `res_${sessionId}.mp3`)

    const writer = fs.createWriteStream(audioPath)
    res.data.pipe(writer)

    writer.on('finish', async () => {
      await client.sendText(number, textoRespuesta)
      await client.sendVoice(number, audioPath)
      fs.unlinkSync(audioPath)
    })
  }

  // 🎙️ Procesar audio si es de tipo voice
  if (message.isMedia && message.mimetype.includes('audio')) {
    const buffer = await client.decryptFile(message)
    const tempPath = path.join(__dirname, `${sessionId}.ogg`)
    fs.writeFileSync(tempPath, buffer)

    const form = new FormData()
    form.append('file', fs.createReadStream(tempPath), {
      filename: 'audio.ogg'
    })
    form.append('session_id', sessionId)

    const res = await axios.post('http://stt-tts-service:8010/audio', form, {
      headers: form.getHeaders()
    })

    fs.unlinkSync(tempPath)
    const textoDetectado = res.data.text

    if (textoDetectado) {
      await client.sendText(number, `Texto detectado: *${textoDetectado}*`)
      await procesarTexto(textoDetectado)
    } else {
      await client.sendText(number, `No se pudo convertir el audio a texto.`)
    }

    return
  }

  // 📩 Texto directo
  if (message.body) {
    await procesarTexto(message.body)
  }
}
