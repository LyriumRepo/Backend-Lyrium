<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

final class MigratePaymentMethodsToTokens extends Command
{
    protected $signature = 'payment-methods:migrate-cards
        {--pretend : Show what would be changed without writing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Migrate existing plaintext card PANs to tokenization model. Sets token_status=needs_update on all cards.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                'This will IRREVERSIBLY hash existing card PANs. Make sure you have a DB backup. Continue?',
                false
            );
            if (! $confirmed) {
                $this->warn('Command aborted.');
                return Command::FAILURE;
            }
        }

        $cards = PaymentMethod::where('tipo_metodo', 'tarjeta')->get();

        if ($cards->isEmpty()) {
            $this->info('No card PaymentMethods to migrate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$cards->count()} card(s) to migrate.");
        $bar = $this->output->createProgressBar($cards->count());

        $migrated = 0;
        $errors   = 0;

        foreach ($cards as $card) {
            $this->migrateCard($card, $bar, $migrated, $errors);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$migrated} migrated, {$errors} errors.");
        Log::info("MigratePaymentMethodsToTokens completed", [
            'total'    => $cards->count(),
            'migrated' => $migrated,
            'errors'   => $errors,
        ]);

        return Command::SUCCESS;
    }

    private function migrateCard(PaymentMethod $card, $bar, int &$migrated, int &$errors): void
    {
        try {
            if ($this->option('pretend')) {
                $this->line(" [PRETEND] Card #{$card->id} would be marked needs_update");
                $bar->advance();
                $migrated++;
                return;
            }

            $existingPan = $card->documento;

            $card->update([
                'card_last4'   => $this->extractLast4($existingPan, $card->detalle_extra),
                'card_brand'   => $this->guessBrand($existingPan, $card->detalle_extra),
                'documento'    => null,
                'detalle_extra'=> null,
                'token_status' => 'needs_update',
            ]);

            $bar->advance();
            $migrated++;
        } catch (\Throwable $e) {
            $this->error("Error on card #{$card->id}: {$e->getMessage()}");
            Log::error('MigratePaymentMethodsToTokens: card error', [
                'card_id' => $card->id,
                'error'   => $e->getMessage(),
            ]);
            $errors++;
        }
    }

    private function extractLast4(?string $pan, ?string $detalleExtra): ?string
    {
        $candidates = [];

        if ($pan && preg_match('/(\d{4})$/', $pan, $m)) {
            $candidates[] = $m[1];
        }

        if ($detalleExtra && preg_match('/(\d{4})/', $detalleExtra, $m)) {
            $candidates[] = $m[1];
        }

        if (! empty($candidates)) {
            return end($candidates);
        }

        return null;
    }

    private function guessBrand(?string $pan, ?string $detalleExtra): string
    {
        $haystack = ($pan ?? '') . ' ' . ($detalleExtra ?? '');

        $brands = [
            'visa'       => 'Visa',
            'mastercard' => 'Mastercard',
            'amex'       => 'American Express',
            'dinners'    => 'Diners Club',
            'discover'   => 'Discover',
        ];

        foreach ($brands as $key => $label) {
            if (mb_stripos($haystack, $key) !== false) {
                return $label;
            }
        }

        return 'Desconocida';
    }
}
