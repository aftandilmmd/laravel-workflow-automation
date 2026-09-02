# Veri Hattı (ETL)

> [English](../03-data-pipeline-etl.md) | Türkçe

Dış API'den satış verilerini çek, eksik kayıtları filtrele, net geliri hesapla ve bölgeye göre toplamları al. Bu örnek `http_request`, `filter`, `code` ve `aggregate` node'larıyla ETL pipeline nasıl kurulur gösterir.

## Akış

```
[Manuel Tetikleyici] → [HTTP: satış çek] → [Filtre: tamamlananlar] → [Kod: net gelir] → [Toplama: bölgeye göre] → [HTTP: rapor gönder]
```

## Adım 1 — Workflow'u Tanımla

Bir artisan komutu oluşturup `php artisan workflow:setup-sales-pipeline` ile bir kez çalıştırın.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\HttpRequestNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ManualTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\AggregateNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\FilterNode;
use Aftandilmmd\WorkflowAutomation\Enums\AggregateFunction;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\FilterLogic;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;

// app/Console/Commands/SetupSalesPipeline.php

use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Illuminate\Console\Command;

class SetupSalesPipeline extends Command
{
    protected $signature = 'workflow:setup-sales-pipeline';
    protected $description = 'Satış veri hattı workflow\'unu oluştur';

    public function handle(): void
    {
        $workflow = Workflow::create(['name' => 'Sales Pipeline']);

        $trigger = $workflow->addNode(
            ManualTriggerNode::make()
                ->title('Start')
        );

        $fetchData = $workflow->addNode(
            HttpRequestNode::make()
                ->title('Fetch Sales')
                ->url('https://sales-api.example.com/transactions?date={{ payload.date }}')
                ->method(HttpMethod::Get)
        );

        $filterCompleted = $workflow->addNode(
            FilterNode::make()
                ->title('Completed Only')
                ->condition('status', ConditionOperator::Equals, 'completed')
                ->condition('amount', ConditionOperator::GreaterThan, 0)
                ->logic(FilterLogic::And)
        );

        $calcRevenue = $workflow->addNode(
            CodeNode::make()
                ->title('Net Revenue')
                ->mode(CodeMode::Transform)
                ->expression('{{ item.amount * (1 - item.discount / 100) }}')
        );

        $aggregate = $workflow->addNode(
            AggregateNode::make()
                ->title('By Region')
                ->groupBy('region')
                ->operation('_result', AggregateFunction::Sum, 'total_revenue')
                ->operation('_result', AggregateFunction::Count, 'transaction_count')
        );

        $pushReport = $workflow->addNode(
            HttpRequestNode::make()
                ->title('Push Report')
                ->url('https://reports.example.com/ingest')
                ->method(HttpMethod::Post)
                ->body(['report_type' => 'daily_sales', 'date' => '{{ payload.date }}'])
        );

        // Edge'ler — düz bir pipeline
        $trigger->connect($fetchData);
        $fetchData->connect($filterCompleted);
        $filterCompleted->connect($calcRevenue);
        $calcRevenue->connect($aggregate);
        $aggregate->connect($pushReport);

        $workflow->activate();

        $this->info("Sales Pipeline workflow created (ID: {$workflow->id})");
    }
}
```

## Adım 2 — Tetikle

Controller, başka bir komut veya herhangi bir yerden:

```php
$workflow = Workflow::where('name', 'Sales Pipeline')->firstOrFail();
$workflow->start([['date' => '2025-03-01']]);
```

Veya günlük çalışacak şekilde zamanlayın:

```php
// routes/console.php
Schedule::command('pipeline:sales')->dailyAt('06:00');
```

## Örnek Veri Akışı

**API 4 işlem döner:**

| id | bölge | tutar | indirim | durum |
|----|-------|-------|---------|-------|
| 1 | US | 100 | 10 | completed |
| 2 | EU | 200 | 0 | completed |
| 3 | US | 50 | 0 | refunded |
| 4 | US | 150 | 20 | completed |

**Filtre sonrası** — tx #3 (iade) kaldırılır:

3 işlem kalır.

**Kod sonrası** — net gelir hesaplanır:

| id | bölge | net gelir |
|----|-------|-----------|
| 1 | US | $90 (100 × 0.9) |
| 2 | EU | $200 (200 × 1.0) |
| 4 | US | $120 (150 × 0.8) |

**Toplama sonrası** — bölgeye göre gruplanır:

```json
[
    {"region": "US", "total_revenue": 210, "transaction_count": 2},
    {"region": "EU", "total_revenue": 200, "transaction_count": 1}
]
```

## Gösterilen Kavramlar

| Kavram | Nasıl |
|--------|-------|
| Doğrusal pipeline | Node'lar düz bir zincirde bağlı — dallanma yok |
| Filtreleme | `filter` koşullara uymayan kayıtları kaldırır |
| İfade tabanlı dönüşüm | `code` node'u PHP eval olmadan değer hesaplar |
| Toplama | `aggregate` öğeleri gruplar ve sum/count uygular |
| Payload erişimi | `{{ payload.date }}` orijinal tetikleyici verisini okur |
