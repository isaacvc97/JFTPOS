import { io } from 'socket.io-client';
import { API_WHATSAPP } from "./env.js";

// Reemplaza localhost con la IP o dominio del microservicio si estás en red o producción
const socket = io(`${API_WHATSAPP}`);

export default socket;
