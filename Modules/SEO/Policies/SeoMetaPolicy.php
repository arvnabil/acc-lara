<?php

namespace Modules\SEO\Policies;

use App\Models\User;
use Modules\SEO\Models\SeoMeta;

class SeoMetaPolicy
{
    public function viewAny(User $user): bool { return $user->can('view_any_seo_meta'); }
    public function view(User $user, SeoMeta $seoMeta): bool { return $user->can('view_seo_meta'); }
    public function update(User $user, SeoMeta $seoMeta): bool { return $user->can('update_seo_meta'); }
}
