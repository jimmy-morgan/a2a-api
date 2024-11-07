<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';
}
