

export interface Cart {
  id: number;
  items: CartItem[];
  payment_type: string;
  sale_type: string;
  note?: string;
  created_at: string;
  client: Client;
}


// | id         | bigint unsigned | NO   | PRI | NULL    | auto_increment |
// | user_id    | bigint unsigned | NO   | MUL | NULL    |                |
// | session_id | varchar(255)    | YES  |     | NULL    |                |
// | finished   | tinyint(1)      | NO   |     | 0       |                |
// | created_at | timestamp       | YES  |     | NULL    |                |
// | updated_at | timestamp       | YES  |     | NULL    |                |
// | client_id  | int

export interface CartItem {
  id: number;
  medicine_id: number;
  name: string;
  generic_name: string;
  concentration: string;
  form: string;
  presentation_id: number;
  presentations: CartItemPresentation[];
  unit_type: string;
  quantity?: number;
  cost?: number;
  price: number;
  stock?: number; 
  iva?: boolean;
  discount?: number;
  subtotal?: number;
}

export interface CartItemPresentation {
  id: number;
  label: string;
  value: number;
  unit_type: string;
  quantity: number;
  price: number;
  stock: number;
}

export interface Client {
  id: number;
  name: string;
  identification: string;
  phone?: string;
  email?: string;
  address?: string;
  created_at?: Date;
}

export function emptyClient(): Client{
  return {
    id: 0,
    name: '',
    identification: '',
    phone: undefined,
    email: undefined,
    address: undefined,
    created_at: undefined
  }
}

// 'id' => $item->id,
// 'medicine_id' => $medicine->id,
// 'name' => $medicine->name,
// 'generic_name' => $medicine->generic_name,
// 'dosage_id' => $dosage->id,
// 'concentration' => $dosage->concentration,
// 'form_id' => $dosage->form->id,
// 'form_name' => $dosage->form->name,
// 'presentation_id' => $item->medicine_presentation_id,
// 'presentations' => $dosage->presentations->map(fn($p) => [
//     'id' => $p->id,
//     'label' => "{$p->unit_type} x {$p->quantity} - \${$p->price}",
//     'value' => $p->id,
//     'unit_type' => $p->unit_type,
//     'quantity' => $p->quantity,
//     'price' => $p->price,
//     'stock' => $p->stock,
//     'cost' => $p->cost,
//     'iva' => $p->iva,
//     'data' => []
// ]),
// 'unit_type' => $presentation->unit_type,
// 'quantity' => $item->cantidad,
// 'price' => (float) $presentation->price,
// 'stock' => $presentation->stock,
// 'cost' => $presentation->cost,
// 'cantidad' => $item->cantidad,
// 'discount' => $item->descuento,
