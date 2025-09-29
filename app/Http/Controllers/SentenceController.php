<?php

namespace App\Http\Controllers;

use App\Models\Adjective;
use App\Models\Adverbial;
use App\Models\Circum;
use App\Models\Like;
use App\Models\Noun;
use App\Models\Verb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SentenceController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Проверяем наличие параметров в URL
            if ($request->has('s')) {
                $sentence = $this->getSentenceFromParams($request->s);
            } else {
                $sentence = $this->generateSentence();
            }

            $topLikes = $this->getTopLikes();

            return view('generator', compact('sentence', 'topLikes'));

        } catch (\Exception $e) {
            // Если произошла ошибка, показываем 404
            abort(404);
        }
    }

    public function generate()
    {
        try {
            $sentence = $this->generateSentence();
            $topLikes = $this->getTopLikes();

            return response()->json([
                'sentence' => $sentence,
                'topLikes' => $topLikes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка генерации'], 500);
        }
    }

    public function generateShareLink(Request $request)
    {
        try {
            $request->validate([
                'adj1_id' => 'nullable|exists:adjectives,id',
                'adj2_id' => 'required|exists:adjectives,id',
                'nn_id' => 'required|exists:nouns,id',
                'adv1_id' => 'required|exists:adverbials,id',
                'adv2_id' => 'required|exists:adverbials,id',
                'verb_id' => 'nullable|exists:verbs,id',
                'circum_id' => 'nullable|exists:circums,id',
                'n_type' => 'nullable|integer|min:1|max:5',
            ]);

            $params = [];
            $params[] = $request->adj1_id ?? '0';
            $params[] = $request->adj2_id;
            $params[] = $request->nn_id;
            $params[] = $request->adv1_id;
            $params[] = $request->adv2_id;
            $params[] = $request->verb_id ?? '0';
            $params[] = $request->circum_id ?? '0';
            $params[] = $request->n_type ?? '0';

            $paramString = implode('_', $params);
            $shareUrl = url('/') . '/?s=' . $paramString;

            return response()->json(['share_url' => $shareUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка создания ссылки'], 500);
        }
    }

    public function like(Request $request)
    {
        try {
            $request->validate([
                'adj1_id' => 'nullable|exists:adjectives,id',
                'adj2_id' => 'required|exists:adjectives,id',
                'nn_id' => 'required|exists:nouns,id',
                'adv1_id' => 'required|exists:adverbials,id',
                'adv2_id' => 'required|exists:adverbials,id',
                'verb_id' => 'nullable|exists:verbs,id',
                'circum_id' => 'nullable|exists:circums,id',
                'n_type' => 'nullable|integer|min:1|max:5',
            ]);

            // Проверяем, не был ли уже поставлен лайк для этой комбинации
            $existingLike = Like::where('adj1_id', $request->adj1_id)
                ->where('adj2_id', $request->adj2_id)
                ->where('nn_id', $request->nn_id)
                ->where('adv1_id', $request->adv1_id)
                ->where('adv2_id', $request->adv2_id)
                ->where('verb_id', $request->verb_id)
                ->where('circum_id', $request->circum_id)
                ->where('n_type', $request->n_type)
                ->first();

            if ($existingLike) {
                return response()->json(['error' => 'Лайк уже поставлен'], 422);
            }

            $like = Like::create($request->all());

            return response()->json(['success' => true, 'like_id' => $like->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка при постановке лайка'], 500);
        }
    }

    public function checkLike(Request $request)
    {
        try {
            $request->validate([
                'adj1_id' => 'nullable|exists:adjectives,id',
                'adj2_id' => 'required|exists:adjectives,id',
                'nn_id' => 'required|exists:nouns,id',
                'adv1_id' => 'required|exists:adverbials,id',
                'adv2_id' => 'required|exists:adverbials,id',
                'verb_id' => 'nullable|exists:verbs,id',
                'circum_id' => 'nullable|exists:circums,id',
                'n_type' => 'nullable|integer|min:1|max:5',
            ]);

            $existingLike = Like::where('adj1_id', $request->adj1_id)
                ->where('adj2_id', $request->adj2_id)
                ->where('nn_id', $request->nn_id)
                ->where('adv1_id', $request->adv1_id)
                ->where('adv2_id', $request->adv2_id)
                ->where('verb_id', $request->verb_id)
                ->where('circum_id', $request->circum_id)
                ->where('n_type', $request->n_type)
                ->first();

            return response()->json(['liked' => !is_null($existingLike)]);
        } catch (\Exception $e) {
            return response()->json(['liked' => false]);
        }
    }

    private function generateSentence()
    {
        // Генерируем adj1 с вероятностью 85%
        $adj1 = null;
        if (mt_rand(1, 100) <= 60) {
            $adj1 = Adjective::inRandomOrder()->first();
        }

        $adj2 = Adjective::inRandomOrder()->first();
        $noun = Noun::inRandomOrder()->first();
        $adv1 = Adverbial::inRandomOrder()->first();
        $adv2 = Adverbial::inRandomOrder()->first();

        // Генерируем verb и circum с вероятностью 70%
        $verb = null;
        $circum = null;
        $nType = null;

        if (mt_rand(1, 100) <= 95) {
            $nType = $this->getRandomNType();
            $verb = Verb::where('type', 'v' . $nType)->inRandomOrder()->first();
            $circum = Circum::where('type', 'v' . $nType)->inRandomOrder()->first();
        }

        $sentence = '';
        if ($adj1) {
            $sentence .= $adj1->word . ' ';
        }
        $sentence .= $adj2->word . ' ' . $noun->word . ' ' . $adv1->word . ' ' . $adv2->word;

        if ($verb && $circum) {
            $sentence .= ' ' . $verb->word . ' ' . $circum->word;
        }

        return [
            'sentence' => $sentence,
            'words' => [
                'adj1_id' => $adj1->id ?? null,
                'adj2_id' => $adj2->id,
                'nn_id' => $noun->id,
                'adv1_id' => $adv1->id,
                'adv2_id' => $adv2->id,
                'verb_id' => $verb->id ?? null,
                'circum_id' => $circum->id ?? null,
                'n_type' => $nType,
            ],
            'is_random' => true
        ];
    }

    private function getRandomNType()
    {
        $rand = mt_rand(1, 100);

        if ($rand <= 31) return 1;      // 31%
        if ($rand <= 49) return 2;      // 18%
        if ($rand <= 59) return 3;      // 10%
        if ($rand <= 77) return 4;      // 18%
        return 5;                       // 23%
    }

    private function getSentenceFromParams($params)
    {
        $ids = explode('_', $params);

        // Ожидаем 8 параметров: adj1, adj2, noun, adv1, adv2, verb, circum, n_type
        if (count($ids) !== 8) {
            throw new \Exception('Invalid parameters count');
        }

        try {
            $adj1 = $ids[0] != '0' ? Adjective::findOrFail($ids[0]) : null;
            $adj2 = Adjective::findOrFail($ids[1]);
            $noun = Noun::findOrFail($ids[2]);
            $adv1 = Adverbial::findOrFail($ids[3]);
            $adv2 = Adverbial::findOrFail($ids[4]);
            $verb = $ids[5] != '0' ? Verb::findOrFail($ids[5]) : null;
            $circum = $ids[6] != '0' ? Circum::findOrFail($ids[6]) : null;
            $nType = $ids[7] != '0' ? $ids[7] : null;

            $sentence = '';
            if ($adj1) {
                $sentence .= $adj1->word . ' ';
            }
            $sentence .= $adj2->word . ' ' . $noun->word . ' ' . $adv1->word . ' ' . $adv2->word;

            if ($verb && $circum) {
                $sentence .= ' ' . $verb->word . ' ' . $circum->word;
            }

            return [
                'sentence' => $sentence,
                'words' => [
                    'adj1_id' => $adj1->id ?? null,
                    'adj2_id' => $adj2->id,
                    'nn_id' => $noun->id,
                    'adv1_id' => $adv1->id,
                    'adv2_id' => $adv2->id,
                    'verb_id' => $verb->id ?? null,
                    'circum_id' => $circum->id ?? null,
                    'n_type' => $nType,
                ],
                'is_random' => false,
                'share_params' => $params
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \Exception('Words not found');
        }
    }

    private function getTopLikes()
    {
        return Like::select('adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id', 'verb_id', 'circum_id', 'n_type')
            ->selectRaw('COUNT(*) as like_count')
            ->groupBy('adj1_id', 'adj2_id', 'nn_id', 'adv1_id', 'adv2_id', 'verb_id', 'circum_id', 'n_type')
            ->orderByDesc('like_count')
            ->limit(30)
            ->with(['adjective1', 'adjective2', 'noun', 'adverbial1', 'adverbial2', 'verb', 'circum'])
            ->get()
            ->map(function($like) {
                $params = [
                    $like->adj1_id ?? '0',
                    $like->adj2_id,
                    $like->nn_id,
                    $like->adv1_id,
                    $like->adv2_id,
                    $like->verb_id ?? '0',
                    $like->circum_id ?? '0',
                    $like->n_type ?? '0'
                ];
                $paramString = implode('_', $params);

                return [
                    'sentence' => $like->sentence,
                    'like_count' => $like->like_count,
                    'share_url' => url('/') . '/?s=' . $paramString
                ];
            });
    }
}
