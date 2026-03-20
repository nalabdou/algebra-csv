# Using with algebra-symfony

Register any of the CSV adapters via `#[AsAlgebraAdapter]`:

```php
// src/Adapter/CsvAdapter.php
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;

#[AsAlgebraAdapter(priority: 50)]
final class CsvAdapter extends CsvFileAdapter
{
    public function __construct()
    {
        parent::__construct(new CsvOptions(
            coerceTypes: true,
            skipEmpty:   true,
        ));
    }
}
```

That's it — no `services.yaml` entry needed.

```php
// In any service or controller

public function __construct(
) {}

public function report(): array
{
    return Algebra::from('/data/orders.csv')
        ->where("item['status'] == 'paid'")
        ->groupBy('region')
        ->aggregate(['revenue' => 'sum(amount)'])
        ->toArray();
}
```

## Register all three adapters

```php
// src/Adapter/CsvStringAdapterTagged.php
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;

#[AsAlgebraAdapter(priority: 49)]
final class CsvStringAdapterTagged extends CsvStringAdapter {}

// src/Adapter/CsvResourceAdapterTagged.php
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;

#[AsAlgebraAdapter(priority: 48)]
final class CsvResourceAdapterTagged extends CsvResourceAdapter {}
```

## Priority guidelines

| Priority | Adapter |
|---|---|
| 100 | `DoctrineQueryBuilderAdapter` (algebra-symfony) |
| 90 | `DoctrineCollectionAdapter` (algebra-symfony) |
| 50 | `CsvFileAdapter` |
| 49 | `CsvStringAdapter` |
| 48 | `CsvResourceAdapter` |
