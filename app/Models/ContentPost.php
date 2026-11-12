<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['collection_id', 'platform', 'format', 'scheduled_on', 'title', 'status', 'url', 'notes'])]
final class ContentPost extends Model
{
    protected function casts(): array
    {
        return [
            'platform' => ContentPlatform::class,
            'format' => ContentFormat::class,
            'status' => ContentStatus::class,
            'scheduled_on' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
