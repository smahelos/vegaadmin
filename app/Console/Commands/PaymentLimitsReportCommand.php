<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Payment\ValueObjects\PaymentAmountLimits;

/**
 * Outputs a structured report of payment amount limits and supported currencies.
 * Can optionally write a JSON artifact for CI / documentation sync.
 */
class PaymentLimitsReportCommand extends Command
{
	/** @var string */
	protected $signature = 'payment:limits {--json : Output JSON} {--save : Persist JSON to storage/app/metrics/payment_amount_limits.json}';
	/** @var string */
	protected $description = 'Report supported payment currencies and min/max limits.';

	public function handle(): int
	{
		$currencies = PaymentAmountLimits::supportedCurrencies();
		sort($currencies);
		$min = PaymentAmountLimits::minAmount();
		$rows = [];
		foreach ($currencies as $c) {
			$rows[] = [
				'currency' => $c,
				'min' => number_format($min, 2, '.', ''),
				'max' => number_format(PaymentAmountLimits::maxFor($c), 2, '.', ''),
			];
		}

		$payload = [
			'generated_at' => now()->toIso8601String(),
			'min' => $min,
			'currencies' => $rows,
		];

		if ($this->option('save')) {
			$path = storage_path('app/metrics');
			if (!is_dir($path)) {
				@mkdir($path, 0777, true);
			}
			file_put_contents($path.'/payment_amount_limits.json', json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		}

		if ($this->option('json')) {
			$this->line(json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		} else {
			$this->table(['Currency','Min','Max'], array_map(fn($r)=>[$r['currency'],$r['min'],$r['max']], $rows));
			$this->line('Min (global): '.number_format($min, 2, '.', ''));
		}
		return 0;
	}
}

