<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id'
    ];

    public function adjective1()
    {
        return $this->belongsTo(Adjective::class, 'adj1_id');
    }

    public function adjective2()
    {
        return $this->belongsTo(Adjective::class, 'adj2_id');
    }

    public function noun()
    {
        return $this->belongsTo(Noun::class, 'nn_id');
    }

    public function adverbial1()
    {
        return $this->belongsTo(Adverbial::class, 'adv1_id');
    }

    public function adverbial2()
    {
        return $this->belongsTo(Adverbial::class, 'adv2_id');
    }

    public function getSentenceAttribute()
    {
        return sprintf('%s %s %s %s %s',
            $this->adjective1->word,
            $this->adjective2->word,
            $this->noun->word,
            $this->adverbial1->word,
            $this->adverbial2->word
        );
    }
}
