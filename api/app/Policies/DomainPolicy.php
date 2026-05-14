<?php

namespace App\Policies;

use App\Models\Domain;
use Illuminate\Http\Request;

class DomainPolicy
{
    /**
     * Determine whether domains can be listed.
     */
    public function viewAny(Request $request): bool
    {
        return true;
    }

    /**
     * Determine whether content can be created on the given domain.
     */
    public function createContent(Request $request, Domain $domain): bool
    {
        return true;
    }
}
