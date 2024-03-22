<?php

namespace App\Enums;

enum QuestionTypeEnum: string
{
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
}