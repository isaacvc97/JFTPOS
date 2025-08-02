<?php

namespace App\Enums;

enum SaleStatus: string
{
    case PROCESADA = 'Procesada';
    case ANULADA = 'Anulada';
    case PENDIENTE = 'Pendiente';
    case CANCELADA = 'Cancelada';
}

