# 🚀 Fitur Baru & Pembaruan di Laravel 13

Laravel 13 resmi dirilis pada **17 Maret 2026**. Versi ini berfokus pada **AI-native workflows**, peningkatan kenyamanan developer (_developer experience_), serta pemanfaatan maksimal dari PHP 8.3+ dengan tetap mempertahankan tingkat kompatibilitas yang tinggi (_minimal breaking changes_).

Berikut adalah rangkuman lengkap mengenai fitur-fitur utama yang diperkenalkan di Laravel 13:

---

## 1. ⚡ Cache::touch()

Fitur ini memecahkan masalah efisiensi dalam pengelolaan waktu kedaluwarsa cache (_Time-to-Live_ / TTL).

- **Masalah Sebelumnya:** Untuk memperpanjang masa aktif cache, developer harus mengambil datanya terlebih dahulu (`Cache::get()`), lalu menyimpannya kembali (`Cache::put()`). Proses ini lambat karena memindahkan muatan (_payload_) data secara bolak-balik di jaringan.
- **Solusi Laravel 13:** Metode `Cache::touch()` memperbarui waktu kedaluwarsa cache secara langsung di server cache tanpa membaca atau menulis ulang datanya.

### 💻 Contoh Penggunaan

```php
use Illuminate\Support\Facades\Cache;

// Memperpanjang umur cache selama 1 jam (3600 detik)
Cache::touch('user_session', 3600);

// Memperpanjang ke waktu spesifik menggunakan Carbon
Cache::touch('report_cache', now()->addHours(6));

// Menghapus masa kedaluwarsa (menjadikannya permanen) jika didukung oleh driver
Cache::touch('forever_cache', null);
```

### ⚙️ Signature & Return Type

```php
public function touch(string $key, DateTimeInterface|DateInterval|int|null $seconds = null): bool
```

- **Return Value:** Mengembalikan `true` jika key ditemukan dan masa aktifnya berhasil diperbarui; mengembalikan `false` jika key tidak ditemukan.
- **Driver yang Mendukung:** Mendukung penuh driver `redis` (menggunakan perintah `EXPIRE`), `memcached` (menggunakan perintah `TOUCH`), serta driver `file`, `database`, `dynamodb`, dan `array`.

---

## 2. 🤖 Laravel AI SDK (Stabil/Production-Ready)

Setelah melalui fase beta, **Laravel AI SDK** kini menjadi paket resmi kelas satu (_first-party package_) yang stabil dan siap untuk produksi.

- **Fungsi:** Menyediakan API terpadu dan _provider-agnostic_ untuk berinteraksi dengan berbagai LLM populer (seperti OpenAI, Anthropic, Gemini, dan lainnya).
- **Fitur Utama:**
    - Pembuatan teks (_text generation_) & Agen pemanggil alat (_tool-calling agents_).
    - Pembuatan gambar (_image generation_) & sintesis audio (_audio synthesis_).
    - Pengelolaan _embeddings_ dan integrasi database vektor.

---

## 3. 🏷️ Dukungan PHP Attributes yang Diperluas (36 Attributes Baru)

Laravel 13 memaksimalkan fitur bawaan PHP modern dengan menambahkan **36 PHP Attributes baru** di seluruh bagian framework. Ini memungkinkan Anda mendeklarasikan konfigurasi langsung pada kelas atau metode, menggantikan penulisan properti manual atau file eksternal.

### 📌 Contoh Implementasi

#### Pada Controller & Middleware

```php
use Illuminate\Routing\Attributes\Middleware;
use Illuminate\Foundation\Auth\Access\Attributes\Authorize;

#[Middleware('auth')]
#[Authorize('viewAny', Post::class)]
class PostController extends Controller
{
    // ...
}
```

#### Pada Queue Jobs

Mengonfigurasi retries dan timeouts secara deklaratif pada Job:

```php
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Backoff;

#[Tries(5)]
#[Timeout(120)]
#[Backoff(15)]
class ProcessOrderJob implements ShouldQueue
{
    // ...
}
```

#### Pada Eloquent Models

Mendeklarasikan visibilitas kolom atau relasi secara langsung:

```php
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Appends;

#[Guarded(['id'])]
#[Appends(['full_name'])]
class User extends Model
{
    // ...
}
```

---

## 4. 🔍 Pencarian Vektor Semantik (Semantic Vector Search)

Laravel 13 menambahkan metode bawaan pada Query Builder untuk mempermudah pencarian berbasis kesamaan vektor (_similarity search_), yang sangat penting untuk aplikasi berbasis AI dan pencarian semantik.

- **Metode Baru:** `whereVectorSimilarTo()`
- **Integrasi Database:** Berjalan mulus di database yang mendukung pencarian vektor, seperti PostgreSQL dengan ekstensi `pgvector`.

```php
$similarArticles = Article::query()
    ->whereVectorSimilarTo('embedding', $userSearchQueryEmbedding)
    ->limit(5)
    ->get();
```

---

## 5. 🔑 Passkey (WebAuthn) Authentication secara Bawaan

Untuk mendukung era tanpa sandi (_passwordless_), Laravel 13 menyematkan dukungan **Passkey (WebAuthn)** bawaan di dalam **Laravel Fortify** dan paket starter kit resmi (Breeze/Jetstream).

- **Kelebihan:** Pengguna dapat masuk menggunakan biometrik perangkat (seperti FaceID, sensor sidik jari, atau kunci keamanan perangkat keras) tanpa perlu mengetikkan kata sandi, yang jauh lebih aman dari serangan phishing.

---

## 6. 📻 Reverb Database Driver

**Laravel Reverb** (layanan real-time/WebSocket bawaan Laravel) kini memiliki opsi **Database Driver**.

- **Sebelumnya:** Reverb membutuhkan Redis jika Anda ingin mengaktifkan penskalaan horizontal secara multi-server.
- **Di Laravel 13:** Anda dapat melakukan penskalaan horizontal langsung menggunakan database relasional yang sudah ada (seperti MySQL atau PostgreSQL), sehingga memangkas biaya infrastruktur tanpa memerlukan server Redis terpisah.

---

## 7. 🔌 JSON:API Resources Resmi

Laravel 13 kini menyertakan dukungan kelas satu (*first-party support*) bawaan untuk spesifikasi standar **JSON:API**. Fitur ini sangat menyederhanakan proses serialisasi data, pemformatan respons, hubungan antar-relasi (*nested relationships*), serta penanganan tautan (*links*) dan informasi meta tanpa memerlukan pustaka (*library*) pihak ketiga.

### 🛠️ Cara Membuat JSON:API Resource
Anda dapat membuat resource baru yang mematuhi standar JSON:API menggunakan perintah Artisan `make:resource` dengan opsi baru `--json-api`:
```bash
php artisan make:resource PostResource --json-api
```

### 💻 Contoh Implementasi
Resource baru ini mewarisi kelas `JsonApiResource` (yang memperluas `JsonResource` bawaan Laravel) dan otomatis menghasilkan struktur data yang terstandardisasi:

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonApiResource;

class PostResource extends JsonApiResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => 'posts',
            'attributes' => [
                'title' => $this->title,
                'slug' => $this->slug,
                'content' => $this->content,
                'created_at' => $this->created_at->toIso8601String(),
            ],
            'relationships' => [
                'author' => [
                    'links' => [
                        'self' => route('posts.relationships.author', $this->id),
                        'related' => route('posts.author', $this->id),
                    ],
                    'data' => new AuthorIdentifierResource($this->whenLoaded('author')),
                ],
            ],
            'links' => [
                'self' => route('posts.show', $this->id),
            ],
        ];
    }
}
```

### 🌟 Kelebihan Utama:
* **Format Terstandardisasi:** Otomatis membungkus respons Anda dengan spesifikasi JSON:API yang baku (mengandung elemen `data`, `attributes`, `relationships`, `links`, dan `meta`).
* **Content-Type Otomatis:** Laravel otomatis menetapkan header respons HTTP `Content-Type` ke `application/vnd.api+json`.
* **Kemudahan Integrasi:** Sangat kompatibel dengan package query parsing populer seperti *Spatie Laravel Query Builder* untuk mempermudah pemfilteran (*filtering*), pengurutan (*sorting*), dan pemuatan relasi dinamis.

---

## 💻 Persyaratan Sistem & Dukungan

- **PHP Minimum:** Membutuhkan **PHP 8.3 atau lebih tinggi** (dukungan untuk PHP 8.2 telah dihentikan guna merampingkan framework dan mempercepat performa).
- **Masa Dukungan (Support Lifecycle):**
    - **Perbaikan Bug (Bug Fixes):** Hingga Kuartal 3 (Q3) 2027.
    - **Pembaruan Keamanan (Security Fixes):** Hingga 17 Maret 2028.
- **Kemudahan Migrasi:** Laravel 13 dirancang agar ramah migrasi (_minimal breaking changes_), sehingga transisi dari Laravel 12 dapat dilakukan dengan sangat cepat menggunakan alat otomatis seperti **Laravel Shift**.
