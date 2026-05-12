<?php

namespace App\ValueObjects;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class UploadQueueConfig implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    public const SKIP_FIRST_ROW = 'skip_first_row';

    public const SEPARATOR = 'separator';

    public const ATTRIBUTES = 'attributes';

    public const VALIDATED_ROWS = 'validated_rows';

    public const VALIDATED_AT = 'validated_at';

    public const QUICK_VALIDATION_OK = 'quick_validation_ok';

    public const QUICK_VALIDATION_AT = 'quick_validation_at';

    public const DETAILED_VALIDATION_OK = 'detailed_validation_ok';

    public const DETAILED_VALIDATION_AT = 'detailed_validation_at';

    public const UPLOADED_FILE_DELETED = 'uploaded_file_deleted';

    public const UPLOADED_FILE_DELETED_AT = 'uploaded_file_deleted_at';

    public const ADMIN_REVIEW_APPROVED = 'admin_review_approved';

    public const ADMIN_REVIEW_APPROVED_AT = 'admin_review_approved_at';

    public const ADMIN_REVIEW_REJECTED_AT = 'admin_review_rejected_at';

    public const ADMIN_REVIEW_REJECTED_REASON = 'admin_review_rejected_reason';

    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(private array $values = []) {}

    /**
     * @param  array<string, mixed>|null  $values
     */
    public static function fromArray(?array $values): self
    {
        return new self($values ?? []);
    }

    /**
     * @param  array<int, string|null>  $attributes
     */
    public static function configured(string $separator, int $skipFirstRow, array $attributes): self
    {
        return (new self)->withConfiguration($separator, $skipFirstRow, $attributes);
    }

    /**
     * @param  array<int, string|null>  $attributes
     */
    public function withConfiguration(string $separator, int $skipFirstRow, array $attributes): self
    {
        return $this->merge([
            self::SKIP_FIRST_ROW => $skipFirstRow,
            self::SEPARATOR => $separator,
            self::ATTRIBUTES => $attributes,
        ]);
    }

    public function withoutConfiguration(): self
    {
        return $this->without(
            self::VALIDATED_ROWS,
            self::VALIDATED_AT,
            self::QUICK_VALIDATION_OK,
            self::QUICK_VALIDATION_AT,
            self::DETAILED_VALIDATION_OK,
            self::DETAILED_VALIDATION_AT,
            self::ATTRIBUTES,
            self::SEPARATOR,
            self::SKIP_FIRST_ROW,
        );
    }

    public function withQuickValidation(
        bool $ok,
        ?int $validatedRows = null,
        ?string $validatedAt = null,
        ?string $quickValidationAt = null,
    ): self {
        return $this->merge(array_filter([
            self::QUICK_VALIDATION_OK => $ok,
            self::VALIDATED_ROWS => $validatedRows,
            self::VALIDATED_AT => $validatedAt,
            self::QUICK_VALIDATION_AT => $quickValidationAt,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function withDetailedValidation(
        bool $ok,
        ?int $validatedRows = null,
        ?string $validatedAt = null,
        ?string $detailedValidationAt = null,
    ): self {
        return $this->merge(array_filter([
            self::DETAILED_VALIDATION_OK => $ok,
            self::VALIDATED_ROWS => $validatedRows,
            self::VALIDATED_AT => $validatedAt,
            self::DETAILED_VALIDATION_AT => $detailedValidationAt,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function markDetailedValidationPending(): self
    {
        return $this->without(
            self::ADMIN_REVIEW_APPROVED,
            self::ADMIN_REVIEW_APPROVED_AT,
            self::ADMIN_REVIEW_REJECTED_AT,
            self::ADMIN_REVIEW_REJECTED_REASON,
        )->merge([
            self::DETAILED_VALIDATION_OK => false,
            self::DETAILED_VALIDATION_AT => null,
        ]);
    }

    public function markUploadedFileDeleted(bool $deleted, string $deletedAt): self
    {
        return $this->merge([
            self::UPLOADED_FILE_DELETED => $deleted,
            self::UPLOADED_FILE_DELETED_AT => $deletedAt,
        ]);
    }

    public function separator(string $default = ','): string
    {
        return (string) ($this->values[self::SEPARATOR] ?? $default);
    }

    public function skipFirstRow(int $default = 1): int
    {
        return (int) ($this->values[self::SKIP_FIRST_ROW] ?? $default);
    }

    /**
     * @return array<int, string|null>
     */
    public function attributes(): array
    {
        $attributes = $this->values[self::ATTRIBUTES] ?? [];

        return is_array($attributes) ? $attributes : [];
    }

    public function validatedRows(): ?int
    {
        $validatedRows = $this->values[self::VALIDATED_ROWS] ?? null;

        return is_numeric($validatedRows) ? (int) $validatedRows : null;
    }

    public function quickValidationPassed(): bool
    {
        return (bool) ($this->values[self::QUICK_VALIDATION_OK] ?? false);
    }

    public function quickValidationAt(): ?string
    {
        $validatedAt = $this->values[self::QUICK_VALIDATION_AT] ?? null;

        return is_string($validatedAt) ? $validatedAt : null;
    }

    public function detailedValidationPassed(): bool
    {
        return (bool) ($this->values[self::DETAILED_VALIDATION_OK] ?? false);
    }

    public function detailedValidationAt(): ?string
    {
        $validatedAt = $this->values[self::DETAILED_VALIDATION_AT] ?? null;

        return is_string($validatedAt) ? $validatedAt : null;
    }

    public function adminReviewApproved(): bool
    {
        return (bool) ($this->values[self::ADMIN_REVIEW_APPROVED] ?? false);
    }

    public function adminReviewApprovedAt(): ?string
    {
        $approvedAt = $this->values[self::ADMIN_REVIEW_APPROVED_AT] ?? null;

        return is_string($approvedAt) ? $approvedAt : null;
    }

    public function adminReviewRejectedAt(): ?string
    {
        $rejectedAt = $this->values[self::ADMIN_REVIEW_REJECTED_AT] ?? null;

        return is_string($rejectedAt) ? $rejectedAt : null;
    }

    public function adminReviewRejectedReason(): ?string
    {
        $reason = $this->values[self::ADMIN_REVIEW_REJECTED_REASON] ?? null;

        return is_string($reason) && trim($reason) !== '' ? $reason : null;
    }

    public function markAdminReviewApproved(string $approvedAt): self
    {
        return $this->without(
            self::ADMIN_REVIEW_REJECTED_AT,
            self::ADMIN_REVIEW_REJECTED_REASON,
        )->merge([
            self::ADMIN_REVIEW_APPROVED => true,
            self::ADMIN_REVIEW_APPROVED_AT => $approvedAt,
        ]);
    }

    public function markAdminReviewRejected(string $reason, string $rejectedAt): self
    {
        return $this->merge([
            self::ADMIN_REVIEW_APPROVED => false,
            self::ADMIN_REVIEW_REJECTED_AT => $rejectedAt,
            self::ADMIN_REVIEW_REJECTED_REASON => $reason,
        ]);
    }

    public function isConfigured(): bool
    {
        return isset($this->values[self::SKIP_FIRST_ROW], $this->values[self::SEPARATOR], $this->values[self::ATTRIBUTES]) &&
            count(array_filter($this->attributes(), fn (mixed $value): bool => $value !== null)) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontendArray(): array
    {
        return [
            self::SEPARATOR => $this->separator(),
            self::SKIP_FIRST_ROW => $this->skipFirstRow(),
            self::ATTRIBUTES => $this->attributes(),
            self::QUICK_VALIDATION_OK => $this->quickValidationPassed(),
            self::QUICK_VALIDATION_AT => $this->quickValidationAt(),
            self::DETAILED_VALIDATION_OK => $this->detailedValidationPassed(),
            self::DETAILED_VALIDATION_AT => $this->detailedValidationAt(),
            self::ADMIN_REVIEW_APPROVED => $this->adminReviewApproved(),
            self::ADMIN_REVIEW_APPROVED_AT => $this->adminReviewApprovedAt(),
            self::ADMIN_REVIEW_REJECTED_AT => $this->adminReviewRejectedAt(),
            self::ADMIN_REVIEW_REJECTED_REASON => $this->adminReviewRejectedReason(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function merge(array $values): self
    {
        return new self([...$this->values, ...$values]);
    }

    public function without(string ...$keys): self
    {
        $values = $this->values;

        foreach ($keys as $key) {
            unset($values[$key]);
        }

        return new self($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->values, $options);

        return is_string($json) ? $json : '{}';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Traversable
    {
        yield from $this->values;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->values[] = $value;

            return;
        }

        $this->values[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }
}
