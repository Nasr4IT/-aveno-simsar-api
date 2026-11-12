<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\CategoryAttribute;
use Illuminate\Database\Eloquent\Builder;

// The public ad feed's filter / search / sort pipeline, shared by the
// JSON API (Api\AdController@index) and the website (Site\ExploreController)
// so the two surfaces can't drift apart — one place for "what does
// ?q=Totoya or ?sort=nearest mean". Callers validate with filterRules(),
// pass the validated filters plus the raw query array, and paginate the
// returned builder however they like.
class AdFeedQueryService
{
    // Query params that are never treated as a dynamic-attribute filter.
    private const RESERVED_PARAMS = ['category_id', 'governorate_id', 'city_id', 'min_price', 'max_price', 'q', 'page', 'sort', 'lat', 'lng'];

    public static function filterRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'q' => ['nullable', 'string', 'max:150'],
            // 'nearest' needs the caller's own position to sort by, since an
            // ad's distance is meaningless without a reference point.
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,popular,nearest'],
            'lat' => ['required_if:sort,nearest', 'numeric', 'between:-90,90'],
            'lng' => ['required_if:sort,nearest', 'numeric', 'between:-180,180'],
        ];
    }

    // $filters: the output of validating with filterRules(). $queryParams:
    // the raw request query array, scanned for per-category attribute
    // filters (?fuel_type=بنزين) — anything not in RESERVED_PARAMS.
    public function build(array $filters, array $queryParams): Builder
    {
        $query = Ad::approved()
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['governorate_id'] ?? null, fn ($q, $v) => $q->where('governorate_id', $v))
            ->when($filters['city_id'] ?? null, fn ($q, $v) => $q->where('city_id', $v))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v))
            ->tap(fn ($q) => $this->applyAttributeFilters($q, $queryParams, $filters['category_id'] ?? null));

        if ($filters['q'] ?? null) {
            $this->applySearch($query, $filters['q']);
        }

        $query->with(['images', 'category', 'city', 'governorate']);

        $this->applySort($query, $filters);

        return $query;
    }

    // Same category, price within ±30% when the ad has one, closest price
    // first. Small unpaginated set for a "similar ads" rail.
    public function similarTo(Ad $ad, int $limit = 10): Builder
    {
        $query = Ad::approved()
            ->where('id', '!=', $ad->id)
            ->where('category_id', $ad->category_id)
            ->with(['images', 'category', 'city', 'governorate']);

        if ($ad->price !== null) {
            $query->whereBetween('price', [$ad->price * 0.7, $ad->price * 1.3])
                ->orderByRaw('ABS(price - ?) asc', [$ad->price]);
        } else {
            $query->latest();
        }

        return $query->limit($limit);
    }

    // A plain substring match (fast, uses the DB) first; only if that comes
    // back empty do we fall back to a typo-tolerant pass — someone who
    // types "Totoya" instead of "Toyota" should still find it, but running
    // PHP-side fuzzy matching on every search (instead of just the ones
    // that actually need it) wouldn't scale even at this app's size.
    private function applySearch(Builder $query, string $q): void
    {
        $exactIds = (clone $query)->where(fn ($w) => $w
            ->where('title', 'like', "%{$q}%")
            ->orWhere('description', 'like', "%{$q}%"))
            ->pluck('id');

        if ($exactIds->isNotEmpty()) {
            $query->whereIn('id', $exactIds);

            return;
        }

        // Fuzzy fallback: title only (that's where a typo actually costs a
        // seller a buyer — "Totoya Corola" — descriptions are long/noisy
        // enough that fuzzy-matching them invites false positives), capped
        // to a bounded candidate pool from whatever other filters are
        // already active, never the whole table.
        $candidates = (clone $query)->select('id', 'title')->limit(500)->get();
        $fuzzyIds = $candidates->filter(fn ($ad) => $this->fuzzyMatches($ad->title, $q))->pluck('id');

        // Explicit [0] (an id that can never exist) rather than leaving the
        // query unfiltered — an empty whereIn() would silently match
        // everything instead of correctly matching nothing.
        $query->whereIn('id', $fuzzyIds->isNotEmpty() ? $fuzzyIds : [0]);
    }

    // True if any word in $title is within a typo's edit distance of any
    // word in the search query $q. Distance scales with word length so
    // short words (where one swapped letter changes meaning) aren't
    // over-matched.
    private function fuzzyMatches(string $title, string $q): bool
    {
        $titleWords = preg_split('/\s+/u', mb_strtolower(trim($title)));
        $queryWords = preg_split('/\s+/u', mb_strtolower(trim($q)));

        foreach ($queryWords as $queryWord) {
            if (mb_strlen($queryWord) < 3) {
                continue;
            }

            $maxDistance = mb_strlen($queryWord) <= 5 ? 1 : 2;

            foreach ($titleWords as $titleWord) {
                if ($this->multibyteLevenshtein($queryWord, $titleWord) <= $maxDistance) {
                    return true;
                }
            }
        }

        return false;
    }

    // PHP's own levenshtein() works byte-by-byte, which silently gives
    // wrong distances for multibyte text — and this app's titles are
    // routinely Arabic. Standard Wagner–Fischer edit distance, just run
    // over mb_str_split() characters instead of raw bytes.
    private function multibyteLevenshtein(string $a, string $b): int
    {
        $a = mb_str_split($a);
        $b = mb_str_split($b);

        $previousRow = range(0, count($b));

        foreach ($a as $i => $charA) {
            $currentRow = [$i + 1];

            foreach ($b as $j => $charB) {
                $cost = $charA === $charB ? 0 : 1;
                $currentRow[] = min(
                    $previousRow[$j + 1] + 1,
                    $currentRow[$j] + 1,
                    $previousRow[$j] + $cost
                );
            }

            $previousRow = $currentRow;
        }

        return end($previousRow);
    }

    // Default (no sort param, or 'newest') preserves the original
    // featured-first-then-newest order so existing clients/tests don't
    // change behavior. The other modes are plain, predictable orderings —
    // picking one opts out of the featured-pinning rather than layering
    // on top of it, which is what a "Sort: price low to high" control
    // actually means to a user.
    private function applySort(Builder $query, array $filters): void
    {
        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('views_count', 'desc'),
            // Flat-earth squared-distance (no sqrt/trig) — wrong unit, but
            // preserves correct nearest-first ordering at city scale and
            // runs as plain arithmetic, portable across sqlite/mysql/pgsql
            // (no reliance on each DB's math-function support).
            'nearest' => $query->whereNotNull('latitude')->whereNotNull('longitude')->orderByRaw(
                '(latitude - ?) * (latitude - ?) + (longitude - ?) * (longitude - ?) asc',
                [$filters['lat'], $filters['lat'], $filters['lng'], $filters['lng']]
            ),
            default => $query->latest('is_featured')->latest(),
        };
    }

    // Applies one whereHas(attributeValues) constraint per query param whose
    // name matches a filterable category_attribute key (e.g. ?fuel_type=بنزين,
    // per docs/API_CONTRACT.md § Ads). Scoped to $categoryId when given, since
    // the same key string can be reused with different meanings across
    // categories; without a category_id all filterable keys are eligible.
    private function applyAttributeFilters(Builder $query, array $queryParams, ?int $categoryId): void
    {
        $params = collect($queryParams)->except(self::RESERVED_PARAMS)->filter(fn ($v) => $v !== null && $v !== '');
        if ($params->isEmpty()) {
            return;
        }

        $attributes = CategoryAttribute::where('is_filterable', true)
            ->whereIn('key', $params->keys())
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->get()
            ->groupBy('key');

        // Grouped by key rather than filtered one attribute row at a time:
        // the same key (e.g. "condition") can be defined on multiple
        // categories with different category_attribute_id values, so without
        // a category_id each key may match several rows. Those must be OR'd
        // within one whereHas — a separate whereHas per row would AND them
        // together, which no single ad (belonging to exactly one category)
        // could ever satisfy.
        foreach ($attributes as $key => $attrsForKey) {
            $value = $params[$key];
            $type = $attrsForKey->first()->type;
            $attributeIds = $attrsForKey->pluck('id');

            $query->whereHas('attributeValues', function ($q) use ($attributeIds, $type, $value) {
                $q->whereIn('category_attribute_id', $attributeIds);

                if ($type === 'multiselect') {
                    $q->where('value', 'like', '%"'.$value.'"%');
                } else {
                    $q->where('value', $value);
                }
            });
        }
    }
}
