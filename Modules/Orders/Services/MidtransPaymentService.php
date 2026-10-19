<?php

namespace Modules\Orders\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\PaymentTransaction;
use Modules\Email\Services\EmailNotificationService;
use RuntimeException;

class MidtransPaymentService
{
    public function createTransaction(Order $order): PaymentTransaction
    {
        $this->ensureConfigured();

        if ($order->status !== OrderStatus::PendingPayment) {
            throw new RuntimeException('Order tidak berada pada status menunggu pembayaran.');
        }

        $existing = $order->paymentTransactions()
            ->where('provider', 'midtrans')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $attempt = (int) $order->paymentTransactions()->where('provider', 'midtrans')->max('attempt') + 1;
        $providerOrderId = Str::limit($order->number.'-'.$attempt, 50, '');
        $expiresAt = now()->addMinutes((int) config('orders.payment_ttl_minutes', 15));

        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->post((string) config('services.midtrans.snap_url'), $this->payload($order, $providerOrderId, $expiresAt));

        if ($response->failed() || ! $response->json('token') || ! $response->json('redirect_url')) {
            $message = $response->json('error_messages.0')
                ?? $response->json('status_message')
                ?? 'Respons Midtrans tidak berisi token pembayaran.';

            throw new RuntimeException(sprintf(
                'Midtrans gagal membuat transaksi pembayaran (HTTP %s): %s',
                $response->status(),
                is_string($message) ? $message : 'Respons tidak valid.',
            ));
        }

        return $order->paymentTransactions()->create([
            'provider' => 'midtrans',
            'provider_order_id' => $providerOrderId,
            'attempt' => $attempt,
            'snap_token' => $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
            'status' => 'pending',
            'gross_amount' => $order->grand_total,
            'expires_at' => $expiresAt,
            'raw' => $this->safeResponsePayload($response),
        ]);
    }

    public function checkStatus(PaymentTransaction $transaction): PaymentTransaction
    {
        $this->ensureConfigured();

        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->get(rtrim((string) config('services.midtrans.status_url'), '/').'/'.$transaction->provider_order_id.'/status');

        if ($response->failed()) {
            throw new RuntimeException('Status pembayaran Midtrans tidak dapat diperiksa.');
        }

        $payload = $response->json();
        $this->validateStatusPayload($transaction, $payload);

        $status = (string) ($payload['transaction_status'] ?? 'unknown');
        $wasPaid = $transaction->status === 'paid';
        $transaction->update([
            'status' => $status,
            'payment_channel' => $payload['payment_type'] ?? $payload['channel'] ?? null,
            'last_checked_at' => now(),
            'paid_at' => in_array($status, ['settlement', 'capture'], true) ? ($transaction->paid_at ?? now()) : $transaction->paid_at,
            'raw' => $payload,
        ]);

            if (in_array($status, ['settlement', 'capture'], true) && ($status !== 'capture' || ($payload['fraud_status'] ?? 'accept') === 'accept')) {
                $result = $this->applyStatus($transaction->fresh(), $status);
                if (! $wasPaid) {
                    app(EmailNotificationService::class)->paymentReceived($result->order);
                }

                return $result;
        }

        return $transaction->fresh();
    }

    public function applyStatus(PaymentTransaction $transaction, string $gatewayStatus): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $gatewayStatus): PaymentTransaction {
            $lockedTransaction = PaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($lockedTransaction->order_id)->lockForUpdate()->firstOrFail();

            if ($lockedTransaction->status === 'paid' && $order->status === OrderStatus::Paid) {
                return $lockedTransaction->fresh();
            }

            $lockedTransaction->update([
                'status' => 'paid',
                'paid_at' => $lockedTransaction->paid_at ?? now(),
            ]);

            if ($order->status === OrderStatus::PendingPayment) {
                app(OrderStateMachine::class)->systemTransition($order, OrderStatus::Paid, [
                    'paid_at' => $lockedTransaction->paid_at,
                    'invoice_paid_at' => $lockedTransaction->paid_at,
                ], 'midtrans:'.$gatewayStatus);
            } elseif ($order->status === OrderStatus::Cancelled) {
                $order->update(['admin_notes' => trim(($order->admin_notes ?? '')."\nMidtrans paid after cancellation; manual review required.")]);
            }

            return $lockedTransaction->fresh();
        });
    }

    /** @param array<string, mixed> $payload */
    public function handleNotification(array $payload): PaymentTransaction
    {
        $transaction = PaymentTransaction::query()
            ->where('provider', 'midtrans')
            ->where('provider_order_id', $payload['order_id'] ?? null)
            ->firstOrFail();

        $this->validateStatusPayload($transaction, $payload);
        $status = (string) ($payload['transaction_status'] ?? 'unknown');
        $wasPaid = $transaction->status === 'paid';

        $transaction->update([
            'status' => $status,
            'payment_channel' => $payload['payment_type'] ?? $payload['channel'] ?? null,
            'last_checked_at' => now(),
            'paid_at' => in_array($status, ['settlement', 'capture'], true) ? ($transaction->paid_at ?? now()) : $transaction->paid_at,
            'raw' => $payload,
        ]);

        if (in_array($status, ['settlement', 'capture'], true) && ($status !== 'capture' || ($payload['fraud_status'] ?? 'accept') === 'accept')) {
            $result = $this->applyStatus($transaction->fresh(), $status);
            if (! $wasPaid) {
                app(EmailNotificationService::class)->paymentReceived($result->order);
            }

            return $result;
        }

        return $transaction->fresh();
    }

    /** @return array<string, mixed> */
    private function payload(Order $order, string $providerOrderId, \DateTimeInterface $expiresAt): array
    {
        $shipping = $order->shipping_address ?? [];
        $items = $order->items
            ->filter(fn ($item): bool => (int) $item->quantity > 0)
            ->map(fn ($item): array => [
                'id' => (string) ($item->sku ?: $item->product_id),
                'price' => (int) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'name' => Str::substr(trim((string) $item->product_name), 0, 50),
            ])->values()->all();

        $itemsTotal = collect($items)->sum(fn (array $item): int => $item['price'] * $item['quantity']);
        $adjustment = (int) $order->grand_total - $itemsTotal;
        if ($adjustment !== 0) {
            $items[] = [
                'id' => 'adjustment',
                'price' => $adjustment,
                'quantity' => 1,
                'name' => 'Penyesuaian order',
            ];
        }

        return [
            'transaction_details' => [
                'order_id' => $providerOrderId,
                'gross_amount' => (int) $order->grand_total,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $order->user?->name ?? $shipping['recipient'] ?? 'Customer',
                'email' => $order->user?->email,
                'phone' => $shipping['phone'] ?? $order->user?->phone,
                'billing_address' => ['address' => $shipping['address'] ?? null, 'city' => $shipping['city'] ?? null],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => (int) max(1, now()->diffInMinutes($expiresAt)),
            ],
            'callbacks' => ['finish' => route('payment', $order->number)],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function validateStatusPayload(PaymentTransaction $transaction, array $payload): void
    {
        abort_unless(($payload['order_id'] ?? null) === $transaction->provider_order_id, 422, 'Order ID Midtrans tidak cocok.');
        abort_unless((int) ($payload['gross_amount'] ?? 0) === (int) $transaction->gross_amount, 422, 'Nominal pembayaran Midtrans tidak cocok.');

        if (! empty($payload['signature_key'])) {
            $expected = hash('sha512', $transaction->provider_order_id.($payload['status_code'] ?? '').$payload['gross_amount'].config('services.midtrans.server_key'));
            abort_unless(hash_equals($expected, (string) $payload['signature_key']), 422, 'Signature pembayaran Midtrans tidak valid.');
        }
    }

    private function ensureConfigured(): void
    {
        if (! config('services.midtrans.server_key')) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }
    }

    /** @return array<string, mixed> */
    private function safeResponsePayload(Response $response): array
    {
        return collect($response->json())->except(['token', 'redirect_url'])->all();
    }
}
