<?php

namespace Modules\Content\Filament\Resources\BannerResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\BannerResource\Pages;
use Modules\Content\Models\Banner;

class BannerForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([Section::make('Banner')->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(255), Forms\Components\FileUpload::make('image_url')->required()->image()->disk('public')->directory('banners')->visibility('public'),
                Forms\Components\FileUpload::make('mobile_image_url')->image()->disk('public')->directory('banners')->visibility('public'), Forms\Components\TextInput::make('target_url')->maxLength(500),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->required(), Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\DateTimePicker::make('starts_at'), Forms\Components\DateTimePicker::make('expires_at'),
            ])->columns(2)]);
        }
}


