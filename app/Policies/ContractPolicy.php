<?php

namespace App\Policies;

use App\Domain\Common\Models\User;
use App\Domain\CRM\Models\Contract;

class ContractPolicy
{
    public function update(User $user, Contract $contract): bool
    {
        $tenantId = $user->tenant_id ?? null;
        $companyId = $user->default_company_id ?? $user->company_id ?? null;

        if ($tenantId && $contract->tenant_id !== $tenantId) {
            return false;
        }

        if ($companyId && $contract->company_id !== $companyId) {
            return false;
        }

        return true;
    }
}
