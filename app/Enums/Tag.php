<?php

declare(strict_types=1);

namespace App\Enums;

enum Tag: string
{
    case Flower = 'flower';
    case Bird = 'bird';
    case Fungus = 'fungus';
    case Insect = 'insect';
    case View = 'view';
    case Other = 'other';
}
