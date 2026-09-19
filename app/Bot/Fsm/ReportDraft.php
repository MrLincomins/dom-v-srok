<?php

declare(strict_types=1);

namespace App\Bot\Fsm;

use App\Domain\Requests\Dto\PhotoDraft;

/** черновик заявки, лежит в bot_sessions.payload */
final class ReportDraft
{
    private const PHOTO_LIMIT = 5;

    public ?int $houseId = null;

    public ?int $rootId = null;

    public ?int $categoryId = null;

    public string $description = '';

    public ?int $entrance = null;

    public ?string $flat = null;

    public ?int $repeatOfId = null;

    /** @var list<array{token:string|null,url:string|null}> */
    public array $photos = [];

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $draft = new self;
        $draft->houseId = self::int($data['house_id'] ?? null);
        $draft->rootId = self::int($data['root_id'] ?? null);
        $draft->categoryId = self::int($data['category_id'] ?? null);
        $draft->description = (string) ($data['description'] ?? '');
        $draft->entrance = self::int($data['entrance'] ?? null);
        $draft->flat = isset($data['flat']) ? (string) $data['flat'] : null;
        $draft->repeatOfId = self::int($data['repeat_of_id'] ?? null);
        foreach ((array) ($data['photos'] ?? []) as $photo) {
            if (is_array($photo)) {
                $draft->photos[] = [
                    'token' => isset($photo['token']) ? (string) $photo['token'] : null,
                    'url' => isset($photo['url']) ? (string) $photo['url'] : null,
                ];
            }
        }

        return $draft;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'house_id' => $this->houseId,
            'root_id' => $this->rootId,
            'category_id' => $this->categoryId,
            'description' => $this->description,
            'entrance' => $this->entrance,
            'flat' => $this->flat,
            'repeat_of_id' => $this->repeatOfId,
            'photos' => $this->photos,
        ];
    }

    /** @param list<array{token:string|null,url:string|null}> $photos */
    public function addPhotos(array $photos): void
    {
        $this->photos = array_values(array_slice([...$this->photos, ...$photos], 0, self::PHOTO_LIMIT));
    }

    /** @return list<PhotoDraft> */
    public function photoDrafts(): array
    {
        return array_map(fn (array $photo) => new PhotoDraft($photo['token'], $photo['url']), $this->photos);
    }

    public function isReady(): bool
    {
        return $this->houseId !== null && $this->categoryId !== null;
    }

    private static function int(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
