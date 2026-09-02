# Scheduled Daily Report

> English | **[Türkçe](tr/06-zamanlanmis-raporlama.md)**

Every morning at 8 AM, fetch yesterday's sales data, filter out zero-revenue entries, aggregate by department, and email the summary. This example shows the `schedule` trigger and a linear data processing pipeline.

## Flow

```
[Schedule: 8 AM daily] → [HTTP: fetch sales] → [Filter: non-zero] → [Aggregate: by department] → [Send Mail: report]
```

## Step 1 — Define the Workflow

Create an artisan command and run it once with `php artisan workflow:setup-daily-report`.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\HttpRequestNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ScheduleTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\AggregateNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\FilterNode;
use Aftandilmmd\WorkflowAutomation\Enums\AggregateFunction;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;

// app/Console/Commands/SetupDailyReport.php

use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Illuminate\Console\Command;

class SetupDailyReport extends Command
{
    protected $signature = 'workflow:setup-daily-report';
    protected $description = 'Create the daily sales report workflow';

    public function handle(): void
    {
        $workflow = Workflow::create(['name' => 'Daily Sales Report']);

        $trigger = $workflow->addNode(
            ScheduleTriggerNode::make()
                ->title('Daily 8 AM')
                ->intervalType(ScheduleInterval::CustomCron)
                ->cron('0 8 * * *')
        );

        $fetchData = $workflow->addNode(
            HttpRequestNode::make()
                ->title('Fetch Sales')
                ->url('https://analytics.example.com/api/daily-sales?date={{ date_format(now(), "Y-m-d") }}')
                ->method(HttpMethod::Get)
        );

        $filterNonZero = $workflow->addNode(
            FilterNode::make()
                ->title('Non-Zero Revenue')
                ->condition('revenue', ConditionOperator::GreaterThan, 0)
        );

        $aggregate = $workflow->addNode(
            AggregateNode::make()
                ->title('By Department')
                ->groupBy('department')
                ->operation('revenue', AggregateFunction::Sum, 'total_revenue')
                ->operation('transactions', AggregateFunction::Sum, 'total_transactions')
        );

        $sendReport = $workflow->addNode(
            SendMailNode::make()
                ->title('Email Report')
                ->to('team@company.com')
                ->subject('Daily Sales Report — {{ date_format(now(), "M d, Y") }}')
                ->body('Daily sales report attached.')
        );

        // Edges
        $trigger->connect($fetchData);
        $fetchData->connect($filterNonZero);
        $filterNonZero->connect($aggregate);
        $aggregate->connect($sendReport);

        $workflow->activate();

        $this->info("Daily Sales Report workflow created (ID: {$workflow->id})");
    }
}
```

## Step 2 — Enable the Schedule Runner

The package provides `workflow:schedule-run`, which checks all schedule triggers every minute. Add it to your Laravel scheduler:

```php
// routes/console.php
Schedule::command('workflow:schedule-run')->everyMinute();
```

Make sure the Laravel scheduler itself is running:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

That's it. At 8:00 AM every day, the workflow runs automatically.

## Example Data Flow

**API returns:**

| department | revenue | transactions |
|------------|---------|--------------|
| Electronics | 15000 | 42 |
| Clothing | 8500 | 67 |
| Books | 0 | 0 |
| Electronics | 3200 | 15 |

**After Filter** — removes Books (zero revenue).

**After Aggregate** — grouped by department:

```json
[
    {"department": "Electronics", "total_revenue": 18200, "total_transactions": 57},
    {"department": "Clothing",    "total_revenue": 8500,  "total_transactions": 67}
]
```

## Other Schedule Options

```php
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ScheduleTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ScheduleInterval;

// Every 5 minutes
$workflow->addNode(
    ScheduleTriggerNode::make()
        ->title('Every 5 Min')
        ->intervalType(ScheduleInterval::Minutes)
        ->intervalValue(5)
);

// Weekdays at 9 AM
$workflow->addNode(
    ScheduleTriggerNode::make()
        ->title('Weekday 9 AM')
        ->intervalType(ScheduleInterval::CustomCron)
        ->cron('0 9 * * 1-5')
);

// First day of each month
$workflow->addNode(
    ScheduleTriggerNode::make()
        ->title('Monthly')
        ->intervalType(ScheduleInterval::CustomCron)
        ->cron('0 0 1 * *')
);
```

## Concepts Demonstrated

| Concept | How |
|---------|-----|
| Cron-based trigger | `schedule` with `custom_cron` runs at a specific time |
| No manual trigger | `workflow:schedule-run` dispatches automatically |
| Data filtering | `filter` removes zero-revenue entries |
| Aggregation | `aggregate` groups and sums by department |
| Built-in functions | `{{ date_format(now(), "Y-m-d") }}` in expressions |
