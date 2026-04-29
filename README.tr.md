<div align="center">

# Laravel Workflow Automation

**Laravel uygulamanızın içinde yaşayan, n8n tarzı görsel workflow motoru.**

Otomasyon akışlarını canvas üzerine node'ları sürükleyerek kurun — ya da düz İngilizce/Türkçe ile anlatın, AI sizin için kursun. Yeni altyapı yok, harici servis yok. Sadece `composer require` ve `/workflow-editor`.

[![Laravel Compatibility](https://badge.laravel.cloud/badge/aftandilmmd/laravel-workflow-automation)](https://packagist.org/packages/aftandilmmd/laravel-workflow-automation)
[![Documentation](https://img.shields.io/badge/docs-laravel--workflow.pilyus.com-blue)](https://laravel-workflow.pilyus.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**[English](README.md)** | Türkçe

</div>

> [!WARNING]
> Bu paket aktif geliştirme aşamasındadır ve henüz production kullanımı için önerilmemektedir. API'ler, veritabanı şemaları ve özellikler değişebilir.

![Workflow Editor](docs/screenshots/workflow-editor.png)

---

## Neden bu paket

Her Laravel uygulaması zamanla bir otomasyon yumağına dönüşür — fraud kontrolleri, drip e-postalar, onay akışları, webhook yönlendiricileri, cron job'ları. Bir controller'da başlar, listener'lara yayılır, job'lara sızar ve kimsenin dokunmaya cesaret edemediği bir `if` mezarlığına dönüşür.

Bu paket o mantığı kodunuzdan alıp **veritabanında saklanan görsel bir grafa** taşır:

- **Controller'larınız temiz kalır** — otomasyon model'lerde değil, workflow'larda yaşar.
- **Geliştirici olmayanlar da kural yayınlayabilir** — ürün, operasyon ve destek ekibi akışları tarayıcıdan düzenler.
- **AI ajanları birinci sınıf vatandaş olur** — akışı sohbette anlatın, ajan REST API veya MCP server üzerinden kursun.
- **Her run gözlemlenebilir** — node bazında input, output, süre ve hata bilgisi, replay desteği ile.

n8n'i düşünün — ama sahip olduğunuz, genişlettiğiniz ve kendiniz host ettiğiniz bir Laravel paketi olarak.

## Öne çıkanlar

### Görsel editör — `/workflow-editor`

Pakete tam bir React + React Flow editörü dahildir. Ek kurulum yok, ayrı servis yok. Paletten node'ları sürükleyin, port'ları bağlayın, dinamik form'lar üzerinden konfigüre edin, **Run**'a basın ve grafın canlı çalıştığını izleyin.

- Sürükle-bırak canvas — zoom, pan, çoklu seçim
- Her node'un şemasından otomatik üretilen config form'ları (18+ alan tipi: code editör, JSON, key-value, model picker, slider, color, koşullu `show_when`, …)
- Tekrarlanabilir test için node çıktılarını **pinleyin** — geliştirme sırasında pahalı HTTP/AI çağrılarını atlayın
- Run bazında zaman çizelgesi — durum, süre, açılabilir I/O, replay, iptal
- Karanlık/aydınlık tema, klasörler, etiketler, arama

### AI Builder — anlatın, ajan kursun

Bir workflow açın → **AI**'a tıklayın → şunu yazın: *"Bir kullanıcı kayıt olduğunda 3 gün bekle, kullanım kontrol et, onboarding ya da hatırlatma e-postası gönder."* Ajan planını stream eder ve paketin MCP araçları üzerinden node'ları ve kenarları canlı olarak canvas'a kurar.

OpenAI, Anthropic, Gemini, Groq, Mistral, DeepSeek, Ollama, xAI ve Cohere kutudan çıktığı gibi desteklenir.

### 26 hazır node

| Kategori | Node'lar |
|---|---|
| **Tetikleyiciler** | Manual · Model Event · Laravel Event · Schedule · Webhook · Sub-workflow |
| **Aksiyonlar** | Send Mail · HTTP Request · Update Model · Dispatch Job · Send Notification · Run Command · AI |
| **Mantık** | If · Switch · Loop · Merge · Filter · Aggregate |
| **Akış Kontrolü** | Delay · Wait/Resume · Sub-workflow · Error Handler |
| **Veri** | Set Fields · Parse Data · Code (yalnızca expression) |

### Bir sınıf = bir custom node

```php
#[AsWorkflowNode(key: 'notify_crm', name: 'Notify CRM', type: NodeType::Action)]
class NotifyCrmNode extends BaseNode
{
    public function execute(WorkflowNodeRun $nodeRun, array $input): array
    {
        return ['response' => Http::post($this->config('url'), $input)->json()];
    }
}
```

Otomatik keşfedilir ve görsel editörde, REST API'de ve AI builder'da anında kullanılabilir.

### Güvenli expression motoru

Özel recursive-descent parser — **`eval()` yok, closure yok, keyfi PHP yok**. Herhangi bir config alanında `{{ item.email }}`, aritmetik, ternary ve 30+ helper (`upper`, `lower`, `now`, `json`, `count`, …) kullanın.

### Human-in-the-loop, retry, gözlemlenebilirlik

- **Wait/Resume** node'u harici onay beklerken çalışmayı duraklatır — REST veya PHP üzerinden istenilen payload ile devam ettirin.
- Herhangi bir run'ı orijinal veya değiştirilmiş input ile **replay** edin. Tek tek başarısız node'ları yeniden deneyin.
- Her run; node bazında durum, süre, tam input/output JSON ve hata bilgisi kaydeder.

### AI ajanları ve harici araçlar için tasarlandı

- Tam CRUD, execution, registry, run, folder ve tag için **REST API**.
- LLM'in doğrudan çağırabildiği birinci sınıf araçlar barındıran **MCP server**.
- Input ve output'ların her node'un kontratıyla eşleşmesini garanti eden **schema validation** middleware'i.

## Kurulum

```bash
composer require aftandilmmd/laravel-workflow-automation
php artisan vendor:publish --tag=workflow-automation-config --tag=workflow-automation-migrations
php artisan migrate
```

`http://your-app.test/workflow-editor` adresini açın, hazırsınız.

## Hızlı tat

Tam bir hoş geldin e-postası workflow'u, fluent şekilde:

```php
$workflow = Workflow::create(['name' => 'Welcome Email']);

$workflow
    ->addNode('User Created', 'model_event', ['model' => User::class, 'events' => ['created']])
    ->connect($workflow->addNode('Send Welcome', 'send_mail', [
        'to'      => '{{ item.email }}',
        'subject' => 'Welcome, {{ item.name }}!',
        'body'    => 'Thanks for signing up.',
    ]));

$workflow->activate();
```

…ya da aynısını editörde, 20 saniyede, tek satır PHP yazmadan kurun.

## Dokümantasyon

Detaylı rehberler, node referansları, örnekler ve tarifler:

**[laravel-workflow.pilyus.com](https://laravel-workflow.pilyus.com)**

- [Neden bunu kullanmalı?](https://laravel-workflow.pilyus.com/getting-started/why-use-this) — tam tanıtım
- [Görsel editör](https://laravel-workflow.pilyus.com/ui-editor) — her panel, alan tipi ve kısayol
- [AI builder](https://laravel-workflow.pilyus.com/ai-builder) — provider kurulumu ve MCP araçları
- [Custom node'lar](https://laravel-workflow.pilyus.com/advanced/custom-nodes) ve [Plugin'ler](https://laravel-workflow.pilyus.com/advanced/plugins)
- [Örnekler](https://laravel-workflow.pilyus.com/examples/user-onboarding) — onboarding, Stripe webhook'ları, drip kampanyaları, onaylar, zamanlanmış raporlar

## Gereksinimler

- PHP 8.3+
- Laravel 10, 11, 12 veya 13
- `illuminate/*` dışında bağımlılık yok

## Test

```bash
composer test
```

## Lisans

MIT © [aftandilmmd](https://github.com/aftandilmmd)
