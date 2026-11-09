<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';
    protected $primaryKey = 'faq_id';

    protected $fillable = ['question', 'answer', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** FAQs shown on the public website, in the order the admin arranged them. */
    public static function publicList()
    {
        try {
            return static::where('is_active', 1)->orderBy('sort_order')->orderBy('faq_id')->get();
        } catch (\Throwable $e) {
            // e.g. the migration has not been run yet: never break the whole page.
            report($e);
            return collect();
        }
    }
}