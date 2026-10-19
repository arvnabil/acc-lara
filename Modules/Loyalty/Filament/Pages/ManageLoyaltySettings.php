<?php

namespace Modules\Loyalty\Filament\Pages;

use App\LoyaltySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageLoyaltySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Loyalty Settings';
    protected static ?string $title = 'Loyalty Settings';
    public ?array $data = [];

    public function mount(): void
    {
        $settings = LoyaltySetting::getInstance()->settings ?? config('loyalty');
        $tiers = data_get($settings, 'tiers', config('loyalty.tiers', []));
        $tierRows = collect($tiers)
            ->map(fn (array $tier, string $id): array => array_merge(['id' => $id], $tier))
            ->values()
            ->all();

        $this->form->fill([
            'point_value_idr' => data_get($settings, 'point_value_idr', 100),
            'spend_per_point_idr' => data_get($settings, 'spend_per_point_idr', 10000),
            'referral_enabled' => data_get($settings, 'referral.enabled', true),
            'referral_percent' => data_get($settings, 'referral.percent', 0.5),
            'referral_max_idr' => data_get($settings, 'referral.max_idr', 500000),
            'referral_registration_points' => data_get($settings, 'referral.registration_points', 0),
            'maturity_days' => data_get($settings, 'redemption.maturity_days', 7),
            'expiry_months' => data_get($settings, 'redemption.expiry_months', 24),
            'return_days' => data_get($settings, 'redemption.return_days', 7),
            'ewallet_monthly_limit' => data_get($settings, 'redemption.ewallet_monthly_limit', 5000),
            'ewallet_points' => data_get($settings, 'redemption.ewallet_points', [500, 1000, 2500]),
            'voucher_points' => data_get($settings, 'redemption.voucher_points', [750, 1500, 3000]),
            'voucher_validity_days' => data_get($settings, 'redemption.voucher_validity_days', 30),
            'voucher_min_order_per_point_idr' => data_get($settings, 'redemption.voucher_min_order_per_point_idr', 1000),
            'tiers' => $tierRows,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Point Rules')->schema([
                TextInput::make('point_value_idr')->label('Nilai 1 Poin (Rp)')->hintIcon('heroicon-m-information-circle', tooltip: 'Menentukan nilai rupiah dari 1 poin. Contoh: 100 berarti 1.500 poin bernilai Rp150.000.')->numeric()->integer()->minValue(1)->required(),
                TextInput::make('spend_per_point_idr')->label('Belanja untuk 1 Poin (Rp)')->numeric()->integer()->minValue(1)->required(),
                TextInput::make('maturity_days')->label('Masa Tunggu Klaim (Hari)')->numeric()->integer()->minValue(0)->required(),
                TextInput::make('expiry_months')->label('Masa Berlaku Poin (Bulan)')->numeric()->integer()->minValue(1)->required(),
                TextInput::make('return_days')->label('Batas Retur (Hari)')->numeric()->integer()->minValue(0)->required(),
            ])->columns(2),
            Section::make('Referral Rules')->schema([
                Toggle::make('referral_enabled')->label('Aktifkan Referral')->default(true)->columnSpanFull(),
                TextInput::make('referral_percent')->label('Bonus Referral dari Transaksi (%)')->hintIcon('heroicon-m-information-circle', tooltip: 'Persentase bonus yang diterima pemilik kode referral dari nilai transaksi user yang menggunakan kodenya.')->numeric()->minValue(0)->maxValue(100)->step(0.01)->required(),
                TextInput::make('referral_max_idr')->label('Maksimal Bonus Referral (Rp)')->hintIcon('heroicon-m-information-circle', tooltip: 'Batas nilai transaksi yang digunakan untuk menghitung bonus referral.')->numeric()->integer()->minValue(0)->required(),
                TextInput::make('referral_registration_points')->label('Poin Referral Saat Registrasi')->hintIcon('heroicon-m-information-circle', tooltip: 'Poin yang diberikan kepada pemilik kode referral ketika user baru mendaftar menggunakan kode tersebut. Isi 0 untuk menonaktifkan.')->numeric()->integer()->minValue(0)->required(),
            ])->columns(2),
            Section::make('E-Wallet Redemption')->schema([
                TextInput::make('ewallet_monthly_limit')->label('Batas E-Wallet / Bulan')->hintIcon('heroicon-m-information-circle', tooltip: 'Batas maksimal total poin yang dapat ditukar ke e-wallet oleh satu akun dalam satu bulan.')->numeric()->integer()->minValue(0)->required(),
                TagsInput::make('ewallet_points')->label('Nominal Poin E-Wallet')->hintIcon('heroicon-m-information-circle', tooltip: 'Daftar pilihan nominal poin yang dapat ditukar menjadi saldo e-wallet. Pisahkan setiap nominal dengan Enter.')->separator(','),
            ])->columns(2),
            Section::make('Voucher Redemption')->schema([
                TagsInput::make('voucher_points')->label('Nominal Poin Voucher')->hintIcon('heroicon-m-information-circle', tooltip: 'Daftar pilihan poin untuk membuat voucher. Contoh 1.500 poin akan menghasilkan voucher berdasarkan nilai 1 poin.')->separator(','),
                TextInput::make('voucher_validity_days')->label('Masa Berlaku Voucher (Hari)')->hintIcon('heroicon-m-information-circle', tooltip: 'Jumlah hari voucher dapat digunakan sejak voucher diterbitkan.')->numeric()->integer()->minValue(1)->required(),
                TextInput::make('voucher_min_order_per_point_idr')->label('Minimum Belanja per Poin Voucher (Rp)')->hintIcon('heroicon-m-information-circle', tooltip: 'Pengali minimum belanja. Contoh 1.000 berarti voucher 1.500 poin membutuhkan minimum belanja Rp1.500.000.')->numeric()->integer()->minValue(0)->required(),
            ])->columns(2),
            Section::make('Tier Rules')->schema([
                Repeater::make('tiers')
                    ->label('Aturan Membership Tier')
                    ->schema([
                        TextInput::make('id')->label('Kode Tier')->required()->alphaDash(),
                        TextInput::make('label')->label('Nama Tier')->required(),
                        TextInput::make('min')->label('Poin Minimum')->numeric()->integer()->minValue(0)->required(),
                        TextInput::make('max')->label('Poin Maksimum')->numeric()->integer()->minValue(0)->nullable()->helperText('Kosongkan untuk tier tertinggi.'),
                        TextInput::make('spend_min')->label('Belanja Minimum (Rp)')->numeric()->integer()->minValue(0)->required(),
                        TextInput::make('spend_max')->label('Belanja Maksimum (Rp)')->numeric()->integer()->minValue(0)->nullable()->helperText('Kosongkan untuk tier tertinggi.'),
                        TextInput::make('bonus_percent')->label('Bonus Poin (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Repeater::make('perks')
                            ->label('Benefit Tier')
                            ->simple(TextInput::make('benefit')->required())
                            ->reorderable()
                            ->addActionLabel('Tambah Benefit')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['id'] ?? null)
                    ->addActionLabel('Tambah Tier')
                    ->reorderable(false)
                    ->required(),
            ]),
        ])->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment($this->getFormActionsAlignment())
                        ->fullWidth($this->hasFullWidthFormActions())
                        ->sticky($this->areFormActionsSticky())
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Simpan Pengaturan')->submit('save')];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = LoyaltySetting::getInstance()->settings ?? config('loyalty');
        $settings['point_value_idr'] = (int) $data['point_value_idr'];
        $settings['spend_per_point_idr'] = (int) $data['spend_per_point_idr'];
        $settings['referral'] = [
            'enabled' => (bool) $data['referral_enabled'],
            'percent' => (float) $data['referral_percent'],
            'max_idr' => (int) $data['referral_max_idr'],
            'registration_points' => (int) $data['referral_registration_points'],
        ];
        $settings['redemption'] = array_merge($settings['redemption'] ?? [], [
            'maturity_days' => (int) $data['maturity_days'],
            'expiry_months' => (int) $data['expiry_months'],
            'return_days' => (int) $data['return_days'],
            'ewallet_monthly_limit' => (int) $data['ewallet_monthly_limit'],
            'ewallet_points' => self::normalizePointOptions($data['ewallet_points'] ?? []),
            'voucher_points' => self::normalizePointOptions($data['voucher_points'] ?? []),
            'voucher_validity_days' => (int) $data['voucher_validity_days'],
            'voucher_min_order_per_point_idr' => (int) $data['voucher_min_order_per_point_idr'],
        ]);
        $tiers = [];
        foreach (($data['tiers'] ?? []) as $tier) {
            $id = trim((string) ($tier['id'] ?? ''));
            if ($id === '' || isset($tiers[$id])) {
                Notification::make()->title('Kode tier wajib diisi dan harus unik')->danger()->send();

                return;
            }

            $tiers[$id] = [
                'label' => (string) ($tier['label'] ?? $id),
                'min' => (int) ($tier['min'] ?? 0),
                'max' => ($tier['max'] ?? '') === '' ? null : (int) $tier['max'],
                'spend_min' => (int) ($tier['spend_min'] ?? 0),
                'spend_max' => ($tier['spend_max'] ?? '') === '' ? null : (int) $tier['spend_max'],
                'bonus_percent' => (int) ($tier['bonus_percent'] ?? 0),
                'perks' => is_array($tier['perks'] ?? null) ? array_values(array_filter($tier['perks'])) : [],
            ];
        }

        if ($tiers === []) {
            Notification::make()->title('Minimal harus ada satu tier')->danger()->send();

            return;
        }
        $settings['tiers'] = $tiers;
        LoyaltySetting::getInstance()->update(['settings' => $settings]);
        Notification::make()->title('Loyalty settings saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('manage_loyalty') || auth()->user()?->can('adjust_loyalty'));
    }

    /**
     * @return array<int, int>
     */
    private static function normalizePointOptions(array|string|null $value): array
    {
        $values = is_array($value) ? $value : preg_split('/[,\s]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);

        return collect($values ?: [])
            ->map(fn (mixed $point): int => (int) $point)
            ->filter(fn (int $point): bool => $point > 0)
            ->values()
            ->all();
    }
}
