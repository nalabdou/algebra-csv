# Installation

```bash
composer require nalabdou/algebra-csv
```

**Requirements:** PHP ≥ 8.2, `ext-mbstring`, `nalabdou/algebra-php ^1.0`

## Register adapters

algebra-csv adapters are normal classes implementing `AdapterInterface`.
Register them in with `Algebra::adapters()->register(adapter, priority)`:

```php
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

Algebra::adapters()->register(new CsvFileAdapter(new CsvOptions()), priority: 50);
Algebra::adapters()->register(new CsvStringAdapter(new CsvOptions()), priority: 40);
Algebra::adapters()->register(new CsvResourceAdapter(new CsvOptions()), priority: 30);

## First pipeline

```php
$result = Algebra::from('/data/orders.csv')
    ->where("item['status'] == 'paid'")
    ->orderBy('amount', 'desc')
    ->toArray();
```
