<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id',
        'verb_id', 'circum_id', 'n_type'
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

    public function verb()
    {
        return $this->belongsTo(Verb::class);
    }

    public function circum()
    {
        return $this->belongsTo(Circum::class);
    }

    public function getSentenceAttribute()
    {
        $sentence = '';

        // adj1 с вероятностью 85%
        if ($this->adjective1) {
            $sentence .= $this->adjective1->word . ' ';
        }

        $sentence .= $this->adjective2->word . ' ' . $this->noun->word . ' ' .
            $this->adverbial1->word . ' ' . $this->adverbial2->word;

        // verb + circum с вероятностью 70%
        if ($this->verb && $this->circum) {
            $sentence .= ' ' . $this->verb->word . ' ' . $this->circum->word;
        }

        return trim($sentence);
    }
}
