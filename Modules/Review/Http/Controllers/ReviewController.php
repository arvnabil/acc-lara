<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Events\ReviewStored;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Orders\Models\OrderItem;
use Modules\Review\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'max:5000'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'photos' => ['sometimes', 'array', 'max:5'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm', 'mimetypes:video/mp4,video/webm', 'max:25600'],
        ]);

        $item = OrderItem::query()->with('order')->findOrFail($validated['order_item_id']);
        abort_unless($item->order->company_id === $request->user()->company_id, 403);
        abort_unless($item->order->status->value === 'completed', 422, 'Ulasan hanya dapat dibuat untuk pesanan yang selesai.');

        if (Review::query()->where('company_id', $request->user()->company_id)->where('product_id', $item->product_id)->exists()) {
            throw ValidationException::withMessages(['order_item_id' => 'Produk ini sudah diulas oleh perusahaan Anda.']);
        }

        $paths = [];
        try {
            foreach ($request->file('photos', []) as $photo) {
                $paths[] = ['type' => 'photo', 'path' => $photo->store('reviews', 'public')];
            }
            if ($request->hasFile('video')) {
                $paths[] = ['type' => 'video', 'path' => $request->file('video')->store('reviews', 'public')];
            }

            $review = DB::transaction(function () use ($validated, $request, $item, $paths): Review {
                if (Review::query()->where('company_id', $request->user()->company_id)->where('product_id', $item->product_id)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['order_item_id' => 'Produk ini sudah diulas oleh perusahaan Anda.']);
                }

                $review = Review::create([
                    'order_id' => $item->order_id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'user_id' => $request->user()->id,
                    'company_id' => $request->user()->company_id,
                    'rating' => $validated['rating'],
                    'body' => $validated['body'],
                    'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
                    'media' => $paths ?: null,
                ]);

                event(new ReviewStored($review->id, $review->company_id, $review->user_id, $review->product_id, $review->order_id));

                return $review;
            });
        } catch (\Throwable $exception) {
            foreach ($paths as $media) {
                Storage::disk('public')->delete($media['path']);
            }

            throw $exception;
        }

        return back()->with('success', 'Ulasan Anda berhasil dikirim! Bonus +50 Poin ditambahkan ke akun Anda.');
    }
}
