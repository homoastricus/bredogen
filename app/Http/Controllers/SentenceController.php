<?php

namespace App\Http\Controllers;

use App\Models\Adjective;
use App\Models\Adverbial;
use App\Models\Like;
use App\Models\Noun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SentenceController extends Controller
{
    public function index(Request $request)
    {
        // Проверяем наличие параметров в URL
        if ($request->has('s')) {
            $sentence = $this->getSentenceFromParams($request->s);
        } else {
            $sentence = $this->generateSentence();
        }

        $topLikes = $this->getTopLikes();

        // Передаем параметры в представление для инициализации JavaScript
        $shareParams = $request->has('s') ? $request->s : null;

        return view('generator', compact('sentence', 'topLikes', 'shareParams'));
    }

    public function generate()
    {
        $sentence = $this->generateSentence();
        $topLikes = $this->getTopLikes();

        return response()->json([
            'sentence' => $sentence,
            'topLikes' => $topLikes
        ]);
    }

    public function generateShareLink(Request $request)
    {
        $request->validate([
            'adj1_id' => 'required|exists:adjectives,id',
            'adj2_id' => 'required|exists:adjectives,id',
            'nn_id' => 'required|exists:nouns,id',
            'adv1_id' => 'required|exists:adverbials,id',
            'adv2_id' => 'required|exists:adverbials,id',
        ]);

        $params = "{$request->adj1_id}_{$request->adj2_id}_{$request->nn_id}_{$request->adv1_id}_{$request->adv2_id}";
        $shareUrl = url('/') . '/?s=' . $params;

        return response()->json(['share_url' => $shareUrl]);
    }

    public function like(Request $request)
    {
        $request->validate([
            'adj1_id' => 'required|exists:adjectives,id',
            'adj2_id' => 'required|exists:adjectives,id',
            'nn_id' => 'required|exists:nouns,id',
            'adv1_id' => 'required|exists:adverbials,id',
            'adv2_id' => 'required|exists:adverbials,id',
        ]);

        // Проверяем, не был ли уже поставлен лайк для этой комбинации
        $existingLike = Like::where('adj1_id', $request->adj1_id)
            ->where('adj2_id', $request->adj2_id)
            ->where('nn_id', $request->nn_id)
            ->where('adv1_id', $request->adv1_id)
            ->where('adv2_id', $request->adv2_id)
            ->first();

        if ($existingLike) {
            //return response()->json(['error' => 'Лайк уже поставлен'], 422);
        }

        $like = Like::create($request->all());

        return response()->json(['success' => true, 'like_id' => $like->id]);
    }

    private function generateSentence()
    {
        $adj1 = Adjective::inRandomOrder()->first();
        $adj2 = Adjective::inRandomOrder()->first();
        $noun = Noun::inRandomOrder()->first();
        $adv1 = Adverbial::inRandomOrder()->first();
        $adv2 = Adverbial::inRandomOrder()->first();

        return [
            'sentence' => "{$adj1->word} {$adj2->word} {$noun->word} {$adv1->word} {$adv2->word}",
            'words' => [
                'adj1_id' => $adj1->id,
                'adj2_id' => $adj2->id,
                'nn_id' => $noun->id,
                'adv1_id' => $adv1->id,
                'adv2_id' => $adv2->id,
            ],
            'is_random' => true
        ];
    }

    private function getSentenceFromParams($params)
    {
        $ids = explode('_', $params);

        if (count($ids) !== 5) {
            return $this->generateSentence(); // Возвращаем случайную генерацию при ошибке
        }

        try {
            $adj1 = Adjective::findOrFail($ids[0]);
            $adj2 = Adjective::findOrFail($ids[1]);
            $noun = Noun::findOrFail($ids[2]);
            $adv1 = Adverbial::findOrFail($ids[3]);
            $adv2 = Adverbial::findOrFail($ids[4]);

            return [
                'sentence' => "{$adj1->word} {$adj2->word} {$noun->word} {$adv1->word} {$adv2->word}",
                'words' => [
                    'adj1_id' => $adj1->id,
                    'adj2_id' => $adj2->id,
                    'nn_id' => $noun->id,
                    'adv1_id' => $adv1->id,
                    'adv2_id' => $adv2->id,
                ],
                'is_random' => false,
                'share_params' => $params
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->generateSentence(); // Возвращаем случайную генерацию если слова не найдены
        }
    }

    // Добавляем этот метод в класс SentenceController
    public function checkLike(Request $request)
    {
        $request->validate([
            'adj1_id' => 'required|exists:adjectives,id',
            'adj2_id' => 'required|exists:adjectives,id',
            'nn_id' => 'required|exists:nouns,id',
            'adv1_id' => 'required|exists:adverbials,id',
            'adv2_id' => 'required|exists:adverbials,id',
        ]);

        // Проверяем, был ли уже поставлен лайк для этой комбинации
        $existingLike = Like::where('adj1_id', $request->adj1_id)
            ->where('adj2_id', $request->adj2_id)
            ->where('nn_id', $request->nn_id)
            ->where('adv1_id', $request->adv1_id)
            ->where('adv2_id', $request->adv2_id)
            ->first();

        return response()->json(['liked' => !is_null($existingLike)]);
    }

    private function getTopLikes()
    {
        return Like::select('adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id')
            ->selectRaw('COUNT(*) as like_count')
            ->groupBy('adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id')
            ->orderByDesc('like_count')
            ->limit(30)
            ->with(['adjective1', 'adjective2', 'noun', 'adverbial1', 'adverbial2'])
            ->get()
            ->map(function($like) {
                $params = "{$like->adj1_id}_{$like->adj2_id}_{$like->nn_id}_{$like->adv1_id}_{$like->adv2_id}";
                return [
                    'sentence' => "{$like->adjective1->word} {$like->adjective2->word} {$like->noun->word} {$like->adverbial1->word} {$like->adverbial2->word}",
                    'like_count' => $like->like_count,
                    'share_url' => url('/') . '/?s=' . $params
                ];
            });
    }
}
