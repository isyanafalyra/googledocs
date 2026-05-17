<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseVersion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'expense_id',
        'title',
        'amount',
        'category',
        'updated_by',
        'created_at',
    ];

    /**
     * Get the expense that this version belongs to.
     */
    public function expense(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the user who updated this version.
     */
    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
