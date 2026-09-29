<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;

final class QueueSearch
{
    private const MAX_TERMS = 5;

    private const FILLER_WORDS = ['кв', 'квартира', 'д', 'дом', 'ул', 'улица', 'заявка'];

    private const ENDINGS = ['а', 'я', 'о', 'е', 'ё', 'и', 'ы', 'у', 'ю', 'й', 'ь'];

    /** @var list<string> */
    public readonly array $terms;

    public function __construct(string $query)
    {
        $this->terms = self::parse($query);
    }

    /** @return list<string> */
    public static function parse(string $query): array
    {
        $normalized = mb_strtolower(str_replace(['№', '#'], ' ', $query));
        $tokens = preg_split('/[\s,;]+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_filter(
            array_map(fn (string $token): string => rtrim(preg_replace('/^(?:кв|д|ул)\.(?=\S)/u', '', $token) ?? $token, '.'), $tokens),
            fn (string $token): bool => $token !== '',
        ));
        $meaningful = array_values(array_filter($tokens, fn (string $token): bool => ! in_array($token, self::FILLER_WORDS, true)));

        return array_slice($meaningful !== [] ? $meaningful : $tokens, 0, self::MAX_TERMS);
    }

    public function requestNumber(): ?int
    {
        $single = $this->terms[0] ?? null;

        return count($this->terms) === 1 && $single !== null && self::isNumber($single) && strlen($single) <= 9 ? (int) $single : null;
    }

    /** @param  Builder<ServiceRequest>  $query */
    public function apply(Builder $query): void
    {
        $single = count($this->terms) === 1;
        foreach ($this->terms as $term) {
            $query->where(function (Builder $match) use ($term, $single): void {
                if (self::isNumber($term)) {
                    $this->matchNumber($match, $term, $single);
                } else {
                    $this->matchText($match, $term);
                }
            });
        }
    }

    /** @param  Builder<ServiceRequest>  $match */
    private function matchNumber(Builder $match, string $number, bool $single): void
    {
        if (strlen($number) <= 9) {
            $match->orWhere('requests.id', (int) $number);
        }
        $match->orWhere('requests.flat', $number);
        if ($single) {
            return;
        }
        if (strlen($number) <= 4) {
            $match->orWhere('requests.entrance', (int) $number);
        }
        $match->orWhereHas('house', fn (Builder $house) => $house->where('houses.address', '~', '\m'.$number.'\M'));
    }

    /** @param  Builder<ServiceRequest>  $match */
    private function matchText(Builder $match, string $term): void
    {
        $like = '%'.addcslashes(self::stem($term), '%_\\').'%';

        $match->orWhereRaw(self::ilike('requests.flat'), [addcslashes($term, '%_\\')])
            ->orWhereRaw(self::ilike('requests.description'), [$like])
            ->orWhereRaw(self::ilike('requests.responsible_name'), [$like])
            ->orWhereRaw(self::ilike('requests.redirect_name'), [$like])
            ->orWhereHas('category', fn (Builder $category) => $category->whereRaw(self::ilike('categories.name'), [$like]))
            ->orWhereHas('executor', fn (Builder $executor) => $executor->whereRaw(self::ilike('executors.name'), [$like]))
            ->orWhereHas('house', fn (Builder $house) => $house->whereRaw(self::ilike('houses.address'), [$like]));
    }

    private static function ilike(string $column): string
    {
        return $column.' ILIKE ? COLLATE "und-x-icu"';
    }

    private static function stem(string $term): string
    {
        if (mb_strlen($term) < 4 || preg_match('/^\p{L}+$/u', $term) !== 1) {
            return $term;
        }

        return in_array(mb_substr($term, -1), self::ENDINGS, true) ? mb_substr($term, 0, -1) : $term;
    }

    private static function isNumber(string $term): bool
    {
        return ctype_digit($term);
    }
}
