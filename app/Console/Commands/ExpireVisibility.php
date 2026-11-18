<?php

namespace App\Console\Commands;

use App\Actions\Visibility\VisibilityPurchases;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('visibility:expire')]
#[Description('Clôture les offres de visibilité arrivées à échéance')]
class ExpireVisibility extends Command
{
    public function handle(VisibilityPurchases $purchases): int
    {
        $this->info($purchases->expireDue().' offre(s) clôturée(s).');

        return self::SUCCESS;
    }
}
