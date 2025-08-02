<?php

namespace App\Enums;
enum SalePaymentType: string
{
    case EFECTIVO = 'Efectivo';
    case TARJETA = 'Tarjeta';
    case TRANSFERENCIA = 'Transferencia';
}