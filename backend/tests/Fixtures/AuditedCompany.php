<?php

namespace Tests\Fixtures;

use App\Audit\Auditable;
use App\Models\Company;

/** Azienda con il registro attivo, per provare Auditor prima di applicare il trait ai modelli */
class AuditedCompany extends Company
{
    use Auditable;

    public function auditType(): string
    {
        return 'company';
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}
