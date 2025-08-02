export interface Cashregister {
  id: number;
  user_id: number;
  initial_amount: number;
  closing_amount: number;
  system_amount: number;
  sales_amount: number;
  purchases_amount: number;
  income_amount: number;
  expenses_amount: number;
  status: Status;
  note: string;
  opened_at: Date;
  updated_at: Date;
  created_at: Date;
}

enum Status {
  open = 'open',
  closed = 'closed'
}

export function emptyCashregister(){
  return {
    id: 0,
    user_id: 0,
    initial_amount: 0,
    closing_amount: 0,
    system_amount: 0,
    sales_amount: 0,
    purchases_amount: 0,
    income_amount: 0,
    expenses_amount: 0,
    status: Status.open,
    note: '',
    opened_at: new Date(),
    updated_at: new Date(),
    created_at: new Date(),
  }
}

// | id               | bigint unsigned       | NO   | PRI | NULL    | auto_increment |
// | user_id          | bigint unsigned       | NO   | MUL | NULL    |                |
// | initial_amount   | decimal(10,2)         | YES  |     | NULL    |                |
// | closing_amount   | decimal(10,2)         | YES  |     | NULL    |                |
// | system_amount    | decimal(10,2)         | YES  |     | NULL    |                |
// | sales_amount     | decimal(10,2)         | YES  |     | NULL    |                |
// | purchases_amount | decimal(10,2)         | YES  |     | NULL    |                |
// | income_amount    | decimal(10,2)         | YES  |     | NULL    |                |
// | expenses_amount  | decimal(10,2)         | YES  |     | NULL    |                |
// | status           | enum('open','closed') | NO   |     | NULL    |                |
// | note             | varchar(255)          | YES  |     | NULL    |                |
// | opened_at_at     | timestamp             | YES  |     | NULL    |                |
// | closed_at        | timestamp             | YES  |     | NULL    |                |
// | created_at       | timestamp             | YES  |     | NULL    |                |
// | updated_at       | timestamp             | YES  |     | NULL    |                |

interface Cash_movements {
  id: number;
  cash_register_id: number;
  user_id: number;
  type: Type;
  reason: string;
  amount: number;
  related_id: number;
  related_type: number;
  created_at: Date;
  updated_at: Date;
}

// | id               | bigint unsigned          | NO   | PRI | NULL    | auto_increment |
// | cash_register_id | tinyint                  | NO   |     | NULL    |                |
// | user_id          | tinyint                  | NO   |     | NULL    |                |
// | type             | enum('income','expense') | NO   |     | NULL    |                |
// | reason           | varchar(255)             | YES  |     | NULL    |                |
// | amount           | decimal(10,2)            | YES  |     | NULL    |                |
// | related_id       | tinyint                  | YES  |     | NULL    |                |
// | related_type     | tinyint                  | YES  |     | NULL    |                |
// | created_at       | timestamp                | YES  |     | NULL    |                |
// | updated_at       | timestamp                | YES  |     | NULL    |                |
