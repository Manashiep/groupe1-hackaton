<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case EnAttente = 'en_attente';
    case Membre = 'membre';
    case Refusee = 'refusee';
}
