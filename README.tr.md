# Laravel Workflow Automation

> **[English](README.md)** | Türkçe

Çok adımlı iş mantığını görsel, yapılandırılabilir graflar olarak tanımlayın — gerisini Laravel halletsin. Kod tabanınıza dağılmış if/else zincirleri, kuyruk işleri ve event listener'lar yerine, tüm akışı bir kez tanımlarsınız: tetikleyici, koşullar, aksiyonlar, döngüler, gecikmeler. Motor çalıştırmayı, yeniden denemeyi, loglama ve insan onayı beklemeyi yönetir. N8N gibi düşünün, ama sahip olduğunuz ve genişletebildiğiniz bir Laravel paketi olarak.

## Kurulum

```bash
composer require aftandilmmd/laravel-workflow-automation
php artisan vendor:publish --tag=workflow-automation-config --tag=workflow-automation-migrations
php artisan migrate
```

## Nasıl Çalışır

Bir **workflow** üç şeyden oluşan yönlü bir graftır:

- **Node** — Tek bir iş birimi: e-posta gönder, koşul kontrol et, API çağır, onay bekle.
- **Edge** — Bir node'un çıkış portunu diğer node'un girişine bağlayan bağlantı. Sırayı belirler.
- **Trigger** — İlk node. Workflow'un *ne zaman* çalışacağını belirler: manuel, model event, webhook veya cron zamanlama.

```
[Trigger] → [Koşul] → true  → [E-posta Gönder]
                     → false → [Veritabanı Güncelle]
```

Grafı bir kez tanımlarsınız (genellikle bir artisan komutu veya setup controller'ında). Sonra tetiklersiniz — motor grafı gezir, her node'u çalıştırır ve her adımı loglar.

> Hem fluent (`$workflow->addNode(...)`) hem de Facade (`Workflow::addNode(...)`) API'leri desteklenir. Örnekler fluent stili kullanır.

## Hızlı Başlangıç

En basit gerçek senaryo: kullanıcı kayıt olunca hoş geldin e-postası gönder.

**Adım 1 — Workflow'u tanımla** (bir kez çalıştır: `php artisan workflow:setup-welcome`):

```php
// app/Console/Commands/SetupWelcomeWorkflow.php

use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Illuminate\Console\Command;

class SetupWelcomeWorkflow extends Command
{
    protected $signature = 'workflow:setup-welcome';
    protected $description = 'Hoş geldin e-postası workflow\'unu oluştur';

    public function handle(): void
    {
        $workflow = Workflow::create(['name' => 'Welcome Email']);

        $trigger = $workflow->addNode('User Created', 'model_event', [
            'model'  => 'App\\Models\\User',
            'events' => ['created'],
        ]);

        $email = $workflow->addNode('Send Welcome', 'send_mail', [
            'to'      => '{{ item.email }}',
            'subject' => 'Welcome, {{ item.name }}!',
            'body'    => 'Thanks for signing up.',
        ]);

        $trigger->connect($email);
        $workflow->activate();

        $this->info("Welcome Email workflow created (ID: {$workflow->id})");
    }
}
```

**Adım 2 — Model listener'ı kaydet** (tek satır, bir kez):

```php
// app/Providers/AppServiceProvider.php

use Aftandilmmd\WorkflowAutomation\Listeners\ModelEventListener;

public function boot(): void
{
    ModelEventListener::register();
}
```

**Bu kadar.** Her `User::create()` çağrısı artık workflow'u otomatik tetikler. Manuel tetikleme gerekmez.

## Mantık Ekleme

Veriye göre dallanma yapmak için `if_condition` node'u ekleyin. Bu workflow, 100$'ın üzerindeki siparişler için VIP bildirimi gönderir, altındakileri işlenmiş olarak işaretler:

```php
$trigger   = $workflow->addNode('New Order', 'manual');
$condition = $workflow->addNode('High Value?', 'if_condition', [
    'field'    => 'amount',
    'operator' => 'greater_than',
    'value'    => 100,
]);
$notify    = $workflow->addNode('Notify VIP Team', 'send_mail', [
    'to'      => 'vip-team@company.com',
    'subject' => 'High value order: ${{ item.amount }}',
    'body'    => 'Order #{{ item.id }} needs review.',
]);
$markDone  = $workflow->addNode('Mark Processed', 'set_fields', [
    'fields' => ['status' => 'processed'],
]);

$trigger->connect($condition);
$condition->connect($notify, sourcePort: 'true');
$condition->connect($markDone, sourcePort: 'false');
```

`sourcePort` dallanmanın çalışma şeklidir. Koşul node'ları isimli portlara çıkış verir (`true`/`false`). Switch node'ları `case_*` portlarına çıkış verir. Edge'leri istediğiniz porta bağlarsınız.

Sonra istediğiniz yerden tetikleyin:

```php
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

$workflow = Workflow::where('name', 'Order Processing')->first();
$run = $workflow->start([['id' => 42, 'amount' => 250, 'email' => 'customer@test.com']]);
// $run->status === 'completed'
```

## Tetikleyiciler

Bir workflow'u başlatmanın 4 yolu var:

**Manuel** — Kodunuzdan veya API'den `$workflow->start()` çağırın.

```php
$workflow->addNode('Start', 'manual');
// Tetikleme: $workflow->start([['key' => 'value']]);
```

**Model Event** — Bir Eloquent modeli oluşturulduğunda, güncellendiğinde veya silindiğinde tetiklenir.

```php
$workflow->addNode('Order Created', 'model_event', [
    'model'  => 'App\\Models\\Order',
    'events' => ['created'],
]);
// Tetikleme: otomatik — Order::create([...]) workflow'u başlatır
// Gereksinim: AppServiceProvider'da ModelEventListener::register()
```

**Webhook** — POST isteklerini kabul eden benzersiz bir URL oluşturur.

```php
$node = $workflow->addNode('Stripe Hook', 'webhook', [
    'method'    => 'POST',
    'auth_type' => 'bearer',
]);
// URL: POST /workflow-webhook/{uuid} (uuid: $node->config['path'])
// Tetikleme: dış servis HTTP isteği gönderir
```

**Zamanlama** — Cron zamanlamasına göre çalışır.

```php
$workflow->addNode('Morning Report', 'schedule', [
    'interval_type' => 'custom_cron',
    'cron'          => '0 8 * * *', // Her gün saat 8'de
]);
// Tetikleme: otomatik — Schedule::command('workflow:schedule-run')->everyMinute() gerektirir
```

## Node Tipleri

### Tetikleyiciler

| Anahtar | Açıklama |
|---------|----------|
| `manual` | Kod veya API ile tetiklenir |
| `model_event` | Eloquent model eventleri (created, updated, deleted) |
| `schedule` | Cron tabanlı zamanlama |
| `webhook` | Kimlik doğrulama destekli HTTP endpoint |

### Aksiyonlar

| Anahtar | Açıklama |
|---------|----------|
| `send_mail` | Laravel Mail ile e-posta gönder |
| `http_request` | Dış API'lere HTTP isteği gönder |
| `update_model` | Eloquent modelleri bul ve güncelle |
| `dispatch_job` | Laravel kuyruk işi gönder |
| `send_notification` | Laravel bildirimi gönder |

### Koşullar

| Anahtar | Açıklama | Çıkış Portları |
|---------|----------|----------------|
| `if_condition` | İkili dallanma | `true`, `false` |
| `switch` | Çok yönlü dallanma | `case_*`, `default` |

### Dönüştürücüler

| Anahtar | Açıklama |
|---------|----------|
| `set_fields` | Öğelerin alanlarını ayarla veya üzerine yaz |
| `parse_data` | JSON, CSV veya key-value stringleri parse et |

### Kontroller

| Anahtar | Açıklama |
|---------|----------|
| `loop` | Bir dizi alanı üzerinde iterasyon (çıkışlar: `loop_item`, `loop_done`) |
| `merge` | Birden fazla dalı bekle, sonra birleştir |
| `delay` | Kuyruk tabanlı bekleme (non-blocking, Laravel jobs kullanır) |
| `sub_workflow` | Başka bir workflow çalıştır |
| `error_handler` | Hataları farklı stratejilere yönlendir |
| `wait_resume` | Dış sinyal için duraklat (çıkışlar: `resume`, `timeout`) |

### Yardımcılar

| Anahtar | Açıklama |
|---------|----------|
| `filter` | Koşullara uyan öğeleri tut |
| `aggregate` | Grupla ve topla (sum, count, avg, min, max) |
| `code` | Güvenli ifade tabanlı dönüşümler |

## İfadeler (Expressions)

Herhangi bir config değerinde `{{ }}` kullanın. Motor her node çalışmadan önce bunları çözümler.

```
{{ item.email }}                          Mevcut öğenin alanına eriş
{{ item.price * item.qty }}               Aritmetik
{{ item.status == 'active' }}             Karşılaştırma (bool döner)
{{ item.age > 18 ? 'adult' : 'minor' }}  Ternary
{{ upper(item.name) }}                    Fonksiyon çağrısı
{{ payload.date }}                        Orijinal tetikleyici verisi
{{ nodes.Fetch_Data.main.0.total }}       Başka bir node'un çıktısı (isimle)
```

Kullanılabilir fonksiyonlar: `upper`, `lower`, `trim`, `length`, `substr`, `replace`, `contains`, `starts_with`, `ends_with`, `split`, `join`, `round`, `ceil`, `floor`, `abs`, `min`, `max`, `sum`, `avg`, `count`, `first`, `last`, `pluck`, `flatten`, `unique`, `sort`, `now`, `date_format`, `date_diff`, `int`, `float`, `string`, `bool`, `json_encode`, `json_decode`

> `eval()` yok — motor özel bir recursive descent parser kullanır.

## İleri Düzey Kalıplar

### Öğeler üzerinde döngü

Bir diziyi tekil öğelere genişlet, her birini işle:

```php
$loop = $workflow->addNode('Each Item', 'loop', [
    'source_field' => 'order_items',
]);

$updateStock = $workflow->addNode('Update Stock', 'http_request', [
    'url'    => 'https://inventory.api/stock',
    'method' => 'POST',
    'body'   => ['sku' => '{{ item._loop_item.sku }}', 'qty' => '{{ item._loop_item.qty }}'],
]);

$loop->connect($updateStock, sourcePort: 'loop_item');
```

### İnsan onayı bekleme

Biri onaylayana veya reddetene kadar workflow'u duraklat:

```php
$wait = $workflow->addNode('Await Approval', 'wait_resume', [
    'timeout_seconds' => 259200, // 3 gün
]);

$approved = $workflow->addNode('Approved?', 'if_condition', [
    'field' => 'approved', 'operator' => 'equals', 'value' => true,
]);

$wait->connect($approved, sourcePort: 'resume');
```

Workflow duraklar (`$run->status === 'waiting'`). Sonra devam ettirin:

```php
use Aftandilmmd\WorkflowAutomation\Facades\Workflow;

Workflow::resume($runId, $resumeToken, ['approved' => true]);
```

Veya API ile: `POST /workflow-engine/runs/{id}/resume` — `{"resume_token": "...", "payload": {"approved": true}}`.

### Hatalı çalıştırmaları yeniden dene ve tekrar oynat

```php
use Aftandilmmd\WorkflowAutomation\Facades\Workflow;

// Replay: tamamlanmış veya hatalı workflow'u orijinal payload ile yeniden çalıştır
Workflow::replay($runId);

// Retry: hata noktasından devam et (bağlamı geri yükler)
Workflow::retryFromFailure($runId);

// Belirli bir node'u yeniden dene
Workflow::retryNode($runId, $nodeId);
```

Veya API ile:

```bash
POST /workflow-engine/runs/{id}/replay
POST /workflow-engine/runs/{id}/retry
POST /workflow-engine/runs/{id}/retry-node   {"node_id": 42}
```

## Özel Node'lar

Bir sınıf oluşturun, attribute ekleyin, bitti:

```php
// app/Workflow/Nodes/SendSmsAction.php

use Aftandilmmd\WorkflowAutomation\Attributes\AsWorkflowNode;
use Aftandilmmd\WorkflowAutomation\Contracts\NodeInterface;
use Aftandilmmd\WorkflowAutomation\DTOs\{NodeInput, NodeOutput};
use Aftandilmmd\WorkflowAutomation\Enums\NodeType;

#[AsWorkflowNode(key: 'send_sms', type: NodeType::Action, label: 'Send SMS')]
class SendSmsAction implements NodeInterface
{
    public function inputPorts(): array  { return ['main']; }
    public function outputPorts(): array { return ['main', 'error']; }

    public static function configSchema(): array
    {
        return [
            ['key' => 'to', 'type' => 'string', 'label' => 'Phone', 'required' => true],
            ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true],
        ];
    }

    public function execute(NodeInput $input, array $config): NodeOutput
    {
        $results = [];
        foreach ($input->items as $item) {
            // SMS gönderme mantığınız
            $results[] = array_merge($item, ['sms_sent' => true]);
        }
        return NodeOutput::main($results);
    }
}
```

Pakete nerede bulacağını söyleyin:

```php
// config/workflow-automation.php
'node_discovery' => [
    'app_paths' => [app_path('Workflow/Nodes')],
],
```

## Yapılandırma

```php
// config/workflow-automation.php
return [
    'tables'     => [...],              // Özel tablo isimleri
    'models'     => [...],              // Özel model sınıfları (varsayılanları extend edin)
    'async'      => true,               // Varsayılan olarak kuyruk tabanlı çalıştırma
    'queue'      => 'default',          // Kuyruk adı
    'prefix'     => 'workflow-engine',  // API route prefix'i
    'middleware'  => ['api'],            // API middleware'i
    'routes'     => true,               // Paket route'larını devre dışı bırakmak için false
    'webhook_prefix'         => 'workflow-webhook',
    'max_execution_time'     => 300,
    'default_retry_count'    => 0,
    'default_retry_delay_ms' => 1000,
    'retry_backoff'          => 'exponential',
    'expression_mode'        => 'safe',  // 'strict' fonksiyonları devre dışı bırakır
    'node_discovery'         => ['app_paths' => []],
    'log_retention_days'     => 30,
];
```

## API Referansı

Tüm endpoint'ler yapılandırılabilir prefix altındadır (varsayılan: `/workflow-engine`).

### Workflow'lar

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| GET | `/workflows` | Liste (sayfalı) |
| POST | `/workflows` | Oluştur |
| GET | `/workflows/{id}` | Node ve edge'lerle göster |
| PUT | `/workflows/{id}` | Güncelle |
| DELETE | `/workflows/{id}` | Soft delete |
| POST | `/workflows/{id}/activate` | Aktifleştir |
| POST | `/workflows/{id}/deactivate` | Deaktifleştir |
| POST | `/workflows/{id}/run` | Manuel tetikle |
| POST | `/workflows/{id}/duplicate` | Kopyala |
| POST | `/workflows/{id}/validate` | Grafı doğrula |

### Node'lar ve Edge'ler

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| POST | `/workflows/{id}/nodes` | Node ekle |
| PUT | `/workflows/{id}/nodes/{nodeId}` | Node config güncelle |
| DELETE | `/workflows/{id}/nodes/{nodeId}` | Node sil |
| PATCH | `/workflows/{id}/nodes/{nodeId}/position` | Canvas pozisyonu güncelle |
| POST | `/workflows/{id}/edges` | Edge ekle |
| DELETE | `/workflows/{id}/edges/{edgeId}` | Edge sil |

### Çalıştırmalar

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| GET | `/workflows/{id}/runs` | Çalıştırmaları listele (statüye göre filtrelenebilir) |
| GET | `/runs/{id}` | Tüm node çalıştırmalarıyla detay |
| POST | `/runs/{id}/cancel` | Çalışan/bekleyen workflow'u iptal et |
| POST | `/runs/{id}/resume` | Bekleyen workflow'u devam ettir |
| POST | `/runs/{id}/replay` | Orijinal payload ile yeniden çalıştır |
| POST | `/runs/{id}/retry` | Hata noktasından yeniden çalıştır |
| POST | `/runs/{id}/retry-node` | Belirli bir hatalı node'u yeniden dene |

### Kayıt Defteri

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| GET | `/registry/nodes` | Tüm kullanılabilir node tipleri |
| GET | `/registry/nodes/{key}` | Node tipi detayları + config şeması |

## Eventler

| Event | Payload |
|-------|---------|
| `WorkflowStarted` | `WorkflowRun $run` |
| `WorkflowCompleted` | `WorkflowRun $run` |
| `WorkflowFailed` | `WorkflowRun $run`, `Throwable $exception` |
| `WorkflowResumed` | `WorkflowRun $run`, `array $payload` |
| `NodeExecuted` | `WorkflowNodeRun $nodeRun` |
| `NodeFailed` | `WorkflowNodeRun $nodeRun`, `Throwable $exception` |

## Artisan Komutları

```bash
php artisan workflow:schedule-run          # Zamanı gelen workflow'ları kontrol et ve çalıştır
php artisan workflow:clean-runs            # Eski çalıştırmaları sil (varsayılan: 30 gün)
php artisan workflow:clean-runs --days=7   # Özel saklama süresi
php artisan workflow:validate {id}         # Workflow grafını doğrula
```

Zamanlama çalıştırıcısını scheduler'ınıza ekleyin:

```php
// routes/console.php
Schedule::command('workflow:schedule-run')->everyMinute();
```

## Test

```bash
composer test
```

## Lisans

MIT
