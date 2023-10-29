<?php

declare(strict_types=1);

namespace App\Validator\Feedback;

use Symfony\Component\Validator\Constraint;

class SearchTermTransferConstraint extends Constraint
{
    public string $textNotBlankMessage = 'text.not_blank';

    public string $textSingleLineMessage = 'text.single_line';

    public string $textAllowedChars = ',;()*^$#!~';
    public string $textAllowedCharsMessage = 'text.allowed_chars';

    public int $textMinLength = 2;
    public string $textMinLengthMessage = 'text.min_length';

    public int $textMaxLength = 256;
    public string $textMaxLengthMessage = 'text.max_length';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'app.feedback_search_term_validator';
    }
}