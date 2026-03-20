<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Integration;

use Nalabdou\Algebra\Adapter\AdapterRegistry;
use Nalabdou\Algebra\Aggregate\AggregateRegistry;
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Collection\CollectionFactory;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;
use Nalabdou\Algebra\Expression\ExpressionCache;
use Nalabdou\Algebra\Expression\ExpressionEvaluator;
use Nalabdou\Algebra\Expression\PropertyAccessor;
use Nalabdou\Algebra\Planner\QueryPlanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvFileAdapter::class)]
#[CoversClass(CsvStringAdapter::class)]
#[CoversClass(CsvResourceAdapter::class)]
final class CsvPipelineTest extends TestCase
{
    private CollectionFactory $factory;
    private string $fixtures;

    protected function setUp(): void
    {
        Algebra::reset();

        $accessor = new PropertyAccessor();
        $cache = new ExpressionCache();
        $evaluator = new ExpressionEvaluator($accessor, $cache);
        $aggregates = new AggregateRegistry();
        $planner = new QueryPlanner($evaluator);

        $adapterRegistry = new AdapterRegistry();
        $adapterRegistry->register(new CsvFileAdapter(), priority: 50);
        $adapterRegistry->register(new CsvStringAdapter(), priority: 49);
        $adapterRegistry->register(new CsvResourceAdapter(), priority: 48);

        $this->factory = new CollectionFactory(
            $planner,
            $evaluator,
            $accessor,
            $aggregates,
            adapterRegistry: $adapterRegistry,
        );

        $this->fixtures = \dirname(__DIR__).'/Fixtures';
    }

    public function testFileWherePipeline(): void
    {
        $result = $this->factory->create("{$this->fixtures}/orders.csv")
            ->where("item['status'] == 'paid'")
            ->toArray();

        self::assertCount(4, $result);
        foreach ($result as $row) {
            self::assertSame('paid', $row['status']);
        }
    }

    public function testFileGroupAggregatePipeline(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(coerceTypes: true));
        $factory = $this->factoryWith($adapter);

        $result = $factory->create("{$this->fixtures}/orders.csv")
            ->where("item['status'] == 'paid'")
            ->groupBy('region')
            ->aggregate(['total' => 'sum(amount)', 'count' => 'count(*)'])
            ->orderBy('total', 'desc')
            ->toArray();

        self::assertCount(3, $result);
        self::assertSame(800, $result[0]['total']);
    }

    public function testFileOrderByAndLimit(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(coerceTypes: true));
        $factory = $this->factoryWith($adapter);

        $result = $factory->create("{$this->fixtures}/orders.csv")
            ->orderBy('amount', 'desc')
            ->limit(3)
            ->toArray();

        self::assertCount(3, $result);
        self::assertSame(800, $result[0]['amount']);
        self::assertSame(500, $result[1]['amount']);
    }

    public function testStringAdapterPipeline(): void
    {
        $csv = new CsvString(
            "id,status,amount\n1,paid,100\n2,pending,200\n3,paid,300\n",
            new CsvOptions(coerceTypes: true)
        );

        $result = $this->factory->create($csv)
            ->where("item['status'] == 'paid'")
            ->aggregate(['total' => 'sum(amount)', 'count' => 'count(*)'])
            ->toArray();

        self::assertSame(400, $result[0]['total']);
        self::assertSame(2, $result[0]['count']);
    }

    public function testResourceAdapterPipeline(): void
    {
        $h = \fopen("{$this->fixtures}/orders.csv", 'r');

        $result = $this->factory->create($h)
            ->tally('status')
            ->toArray();

        \fclose($h);

        self::assertArrayHasKey('paid', $result);
        self::assertArrayHasKey('pending', $result);
        self::assertArrayHasKey('cancelled', $result);
        self::assertSame(4, $result['paid']);
    }

    public function testWindowFunctionOnCsv(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(coerceTypes: true));
        $factory = $this->factoryWith($adapter);

        $result = $factory->create("{$this->fixtures}/orders.csv")
            ->where("item['status'] == 'paid'")
            ->orderBy('amount', 'asc')
            ->window('running_sum', field: 'amount', as: 'cumulative')
            ->toArray();

        self::assertCount(4, $result);
        self::assertGreaterThan(0.0, $result[0]['cumulative']);
        self::assertGreaterThan($result[0]['cumulative'], $result[3]['cumulative']);
    }

    public function testPivotOnCsv(): void
    {
        $csv = new CsvString(
            "month,region,revenue\n".
                "Jan,Nord,1000\nJan,Sud,800\n".
                "Feb,Nord,1200\nFeb,Sud,600\n",
            new CsvOptions(coerceTypes: true)
        );

        $result = $this->factory->create($csv)
            ->pivot(rows: 'month', cols: 'region', value: 'revenue', aggregateFn: 'sum')
            ->toArray();

        self::assertCount(2, $result);
        $jan = \array_values(\array_filter($result, static fn ($r) => 'Jan' === $r['_row']))[0];
        self::assertSame(1000, $jan['Nord']);
        self::assertSame(800, $jan['Sud']);
    }

    public function testSemicolonFileAutoDetected(): void
    {
        $result = $this->factory->create("{$this->fixtures}/semicolon.csv")
            ->where("item['name'] != 'Bob'")
            ->toArray();

        self::assertCount(2, $result);
    }

    public function testParallelPipelinesOnCsv(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(coerceTypes: true));
        $factory = $this->factoryWith($adapter);
        $path = "{$this->fixtures}/orders.csv";

        $results = Algebra::parallel([
            'paid' => $factory->create($path)->where("item['status'] == 'paid'"),
            'pending' => $factory->create($path)->where("item['status'] == 'pending'"),
        ]);

        self::assertCount(4, $results['paid']);
        self::assertCount(2, $results['pending']);
    }

    private function factoryWith(CsvFileAdapter $adapter): CollectionFactory
    {
        $accessor = new PropertyAccessor();
        $cache = new ExpressionCache();
        $evaluator = new ExpressionEvaluator($accessor, $cache);
        $aggregates = new AggregateRegistry();
        $planner = new QueryPlanner($evaluator);

        $reg = new AdapterRegistry();
        $reg->register($adapter, priority: 50);
        $reg->register(new CsvStringAdapter(), priority: 49);
        $reg->register(new CsvResourceAdapter(), priority: 48);

        return new CollectionFactory(
            $planner,
            $evaluator,
            $accessor,
            $aggregates,
            $reg,
        );
    }
}
