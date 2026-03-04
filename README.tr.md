# Laravel Workflow Automation

> [!WARNING]
> Bu paket aktif geliştirme aşamasındadır ve henüz production kullanımı için önerilmemektedir. API'ler, veritabanı şemaları ve özellikler değişebilir.

> **[English](README.md)** | Türkçe

Çok adımlı iş mantığını görsel, yapılandırılabilir graflar olarak tanımlayın — gerisini Laravel halletsin. Kod tabanınıza dağılmış if/else zincirleri, kuyruk işleri ve event listener'lar yerine, tüm akışı bir kez tanımlarsınız: tetikleyici, koşullar, aksiyonlar, döngüler, gecikmeler. Motor çalıştırmayı, yeniden denemeyi, loglama ve insan onayı beklemeyi yönetir. N8N gibi düşünün, ama sahip olduğunuz ve genişletebildiğiniz bir Laravel paketi olarak.

**[Detaylı Dokümantasyon](https://laravel-workflow.pilyus.com)**

## Kurulum

```bash
composer require aftandilmmd/laravel-workflow-automation
php artisan vendor:publish --tag=workflow-automation-config --tag=workflow-automation-migrations
php artisan migrate
```

## Hızlı Başlangıç

Kullanıcı kayıt olunca hoş geldin e-postası gönder:

```php
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

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
```

Her `User::create()` çağrısı artık workflow'u otomatik tetikler.

## Özellikler

- **26 Hazır Node** — Tetikleyiciler, aksiyonlar, koşullar, döngüler, gecikmeler, AI ve daha fazlası
- **Görsel Editör** — `/workflow-editor` adresinde sürükle-bırak React Flow canvas
- **İfade Motoru** — `{{ item.email }}`, aritmetik, fonksiyonlar — `eval()` yok
- **5 Tetikleyici Tipi** — Manuel, model event, Laravel event, webhook, cron zamanlama
- **İnsan Onayı** — Workflow'ları duraklatın ve dış sinyal ile devam ettirin
- **Yeniden Deneme & Tekrar Oynatma** — Hata noktasından devam veya orijinal payload ile tekrar çalıştırma
- **Özel Node'lar** — `#[AsWorkflowNode]` attribute ile tek bir PHP sınıfı
- **Plugin Sistemi** — Node'ları, middleware'leri ve listener'ları yeniden kullanılabilir paketlere dönüştürün
- **Tam REST API** — Her frontend veya AI agent için CRUD + çalıştırma endpoint'leri

## Test

```bash
composer test
```

## Lisans

MIT
